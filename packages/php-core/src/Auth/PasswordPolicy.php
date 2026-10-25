<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Auth;

use InvalidArgumentException;

final class PasswordPolicy
{
    private const MAX_PASSWORD_BYTES = 4096;

    public function __construct(
        private readonly int $minLength = 12,
        private readonly int $maxLength = 1024
    ) {
        if ($this->minLength < 1) {
            throw new InvalidArgumentException(
                'La longitud mínima debe ser mayor que cero.'
            );
        }

        if ($this->maxLength < $this->minLength) {
            throw new InvalidArgumentException(
                'La longitud máxima no puede ser menor que la mínima.'
            );
        }

        if ($this->maxLength > self::MAX_PASSWORD_BYTES) {
            throw new InvalidArgumentException(
                'La longitud máxima no puede superar 4096 caracteres.'
            );
        }
    }

    /**
     * Valida una contraseña y devuelve los errores encontrados.
     *
     * @return list<string>
     */
    public function validate(string $password): array
    {
        $errors = [];

        if ($password === '') {
            return ['La contraseña es obligatoria.'];
        }

        if (strlen($password) > self::MAX_PASSWORD_BYTES) {
            return [
                'La contraseña supera el límite de 4096 bytes.'
            ];
        }

        // Se requiere UTF-8 válido para medir caracteres correctamente.
        $length = preg_match_all('/./us', $password);

        if ($length === false) {
            return [
                'La contraseña contiene una secuencia de texto inválida.'
            ];
        }

        if (str_contains($password, "\0")) {
            $errors[] = 'La contraseña contiene un carácter no permitido.';
        }

        if ($length < $this->minLength) {
            $errors[] = sprintf(
                'La contraseña debe tener al menos %d caracteres.',
                $this->minLength
            );
        }

        if ($length > $this->maxLength) {
            $errors[] = sprintf(
                'La contraseña no puede superar %d caracteres.',
                $this->maxLength
            );
        }

        return $errors;
    }

    /**
     * Indica si la contraseña cumple todas las reglas.
     */
    public function isValid(string $password): bool
    {
        return $this->validate($password) === [];
    }

    /**
     * Valida la contraseña o lanza una excepción.
     */
    public function assertValid(string $password): void
    {
        $errors = $this->validate($password);

        if ($errors !== []) {
            throw new InvalidArgumentException(
                implode(' ', $errors)
            );
        }
    }

    public function getMinLength(): int
    {
        return $this->minLength;
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }
}