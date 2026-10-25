<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Auth;

use InvalidArgumentException;
use RuntimeException;

final class PasswordHasher
{
    private const MAX_PASSWORD_LENGTH = 4096;

    public function __construct(
        private readonly string|int|null $algorithm = PASSWORD_DEFAULT,
        private readonly array $options = []
    ) {
    }

    /**
     * Genera un hash seguro de una contraseña.
     *
     * El hash generado se almacena en la base de datos,
     * nunca la contraseña original.
     */
    public function hash(string $password): string
    {
        $this->validatePasswordForHashing($password);

        $hash = password_hash(
            $password,
            $this->algorithm,
            $this->options
        );

        if ($hash === false) {
            throw new RuntimeException(
                'ShieldPHP no pudo generar el hash de la contraseña.'
            );
        }

        return $hash;
    }

    /**
     * Comprueba una contraseña contra un hash almacenado.
     */
    public function verify(string $password, string $hash): bool
    {
        if (
            strlen($password) > self::MAX_PASSWORD_LENGTH
            || str_contains($password, "\0")
            || $hash === ''
        ) {
            return false;
        }

        return password_verify($password, $hash);
    }

    /**
     * Comprueba si el hash debe regenerarse con los parámetros actuales.
     */
    public function needsRehash(string $hash): bool
    {
        if ($hash === '') {
            return true;
        }

        return password_needs_rehash(
            $hash,
            $this->algorithm,
            $this->options
        );
    }

    /**
     * Valida las entradas antes de generar un hash.
     */
    private function validatePasswordForHashing(
        string $password
    ): void {
        if ($password === '') {
            throw new InvalidArgumentException(
                'La contraseña no puede estar vacía.'
            );
        }

        if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(
                'La contraseña supera la longitud máxima permitida.'
            );
        }

        if (str_contains($password, "\0")) {
            throw new InvalidArgumentException(
                'La contraseña contiene un carácter no permitido.'
            );
        }
    }
}