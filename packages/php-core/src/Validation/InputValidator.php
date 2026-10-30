
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Validation;

use InvalidArgumentException;

final class InputValidator
{
    /**
     * Errores acumulados por campo.
     *
     * @var array<string, list<string>>
     */
    private array $errors = [];

    private readonly EmailValidator $emailValidator;

    public function __construct(
        ?EmailValidator $emailValidator = null
    ) {
        $this->emailValidator = $emailValidator ?? new EmailValidator();
    }

    /**
     * Comprueba que un campo exista y no esté vacío.
     */
    public function required(
        array $data,
        string $field,
        ?string $message = null
    ): self {
        $this->validateFieldName($field);

        if (!array_key_exists($field, $data)) {
            $this->addError(
                $field,
                $message ?? 'Este campo es obligatorio.'
            );

            return $this;
        }

        $value = $data[$field];

        if (
            $value === null
            || (is_string($value) && trim($value) === '')
        ) {
            $this->addError(
                $field,
                $message ?? 'Este campo no puede estar vacío.'
            );
        }

        return $this;
    }

    /**
     * Comprueba que un valor sea una cadena dentro de los límites.
     *
     * @param int $minLength Longitud mínima en caracteres.
     * @param int $maxLength Longitud máxima en caracteres.
     */
    public function validateString(
        array $data,
        string $field,
        int $minLength = 0,
        int $maxLength = 255
    ): self {
        $this->validateFieldName($field);

        if ($minLength < 0 || $maxLength < $minLength) {
            throw new InvalidArgumentException(
                'Los límites de longitud de la cadena no son válidos.'
            );
        }

        // Los campos ausentes pueden ser opcionales.
        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return $this;
        }

        $value = $data[$field];

        if (!is_string($value)) {
            $this->addError(
                $field,
                'El valor debe ser una cadena de texto.'
            );

            return $this;
        }

        $length = preg_match_all('/./us', $value);

        if ($length === false) {
            $this->addError(
                $field,
                'El texto contiene caracteres UTF-8 no válidos.'
            );

            return $this;
        }

        if ($length < $minLength) {
            $this->addError(
                $field,
                sprintf(
                    'Debe contener al menos %d caracteres.',
                    $minLength
                )
            );
        }

        if ($length > $maxLength) {
            $this->addError(
                $field,
                sprintf(
                    'No puede superar los %d caracteres.',
                    $maxLength
                )
            );
        }

        return $this;
    }

    /**
     * Valida una dirección de correo electrónico.
     */
    public function validateEmail(
        array $data,
        string $field
    ): self {
        $this->validateFieldName($field);

        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return $this;
        }

        $email = $data[$field];

        if (!is_string($email)) {
            $this->addError(
                $field,
                'El correo electrónico debe ser una cadena.'
            );

            return $this;
        }

        if (!$this->emailValidator->isValid($email)) {
            $this->addError(
                $field,
                'La dirección de correo electrónico no es válida.'
            );
        }

        return $this;
    }

    /**
     * Valida un número entero y sus límites.
     */
    public function validateInteger(
        array $data,
        string $field,
        ?int $min = null,
        ?int $max = null
    ): self {
        $this->validateFieldName($field);

        if ($min !== null && $max !== null && $min > $max) {
            throw new InvalidArgumentException(
                'El límite mínimo no puede superar al máximo.'
            );
        }

        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return $this;
        }

        $value = $data[$field];

        if (is_int($value)) {
            $integer = $value;
        } elseif (
            is_string($value)
            && preg_match('/\A(?:0|-?[1-9][0-9]*)\z/', $value) === 1
        ) {
            $integer = filter_var(
                $value,
                FILTER_VALIDATE_INT
            );

            if ($integer === false) {
                $this->addError(
                    $field,
                    'El número entero está fuera del rango permitido.'
                );

                return $this;
            }
        } else {
            $this->addError(
                $field,
                'El valor debe ser un número entero.'
            );

            return $this;
        }

        if ($min !== null && $integer < $min) {
            $this->addError(
                $field,
                sprintf('El valor mínimo permitido es %d.', $min)
            );
        }

        if ($max !== null && $integer > $max) {
            $this->addError(
                $field,
                sprintf('El valor máximo permitido es %d.', $max)
            );
        }

        return $this;
    }

    /**
     * Valida valores booleanos.
     *
     * Admite booleanos reales y los valores 0, 1, "0" y "1".
     */
    public function validateBoolean(
        array $data,
        string $field
    ): self {
        $this->validateFieldName($field);

        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return $this;
        }

        if (!in_array($data[$field], [true, false, 0, 1, '0', '1'], true)) {
            $this->addError(
                $field,
                'El valor debe ser verdadero o falso.'
            );
        }

        return $this;
    }

    /**
     * Valida una URL HTTP o HTTPS.
     *
     * Por defecto, exige HTTPS.
     */
    public function validateUrl(
        array $data,
        string $field,
        bool $httpsOnly = true
    ): self {
        $this->validateFieldName($field);

        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return $this;
        }

        $value = $data[$field];

        if (
            !is_string($value)
            || strlen($value) > 2048
            || filter_var($value, FILTER_VALIDATE_URL) === false
        ) {
            $this->addError(
                $field,
                'La URL proporcionada no es válida.'
            );

            return $this;
        }

        $parts = parse_url($value);

        if (
            $parts === false
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            $this->addError(
                $field,
                'La URL no tiene una estructura permitida.'
            );

            return $this;
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        $allowedSchemes = $httpsOnly
            ? ['https']
            : ['https', 'http'];

        if (!in_array($scheme, $allowedSchemes, true)) {
            $this->addError(
                $field,
                $httpsOnly
                    ? 'La URL debe utilizar HTTPS.'
                    : 'La URL debe utilizar HTTP o HTTPS.'
            );
        }

        return $this;
    }

    /**
     * Comprueba que el valor pertenezca a una lista permitida.
     *
     * @param list<mixed> $allowedValues
     */
    public function validateOneOf(
        array $data,
        string $field,
        array $allowedValues
    ): self {
        $this->validateFieldName($field);

        if ($allowedValues === [] || !array_is_list($allowedValues)) {
            throw new InvalidArgumentException(
                'La lista de valores permitidos no puede estar vacía.'
            );
        }

        if (!array_key_exists($field, $data) || $data[$field] === null) {
            return $this;
        }

        if (!in_array($data[$field], $allowedValues, true)) {
            $this->addError(
                $field,
                'El valor proporcionado no está permitido.'
            );
        }

        return $this;
    }

    /**
     * Indica si todos los campos validados son correctos.
     */
    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * Obtiene todos los errores agrupados por campo.
     *
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtiene el primer error encontrado.
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (isset($fieldErrors[0])) {
                return $fieldErrors[0];
            }
        }

        return null;
    }

    /**
     * Obtiene los errores de un campo específico.
     *
     * @return list<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Lanza una excepción si existen errores.
     */
    public function assertValid(): void
    {
        if (!$this->isValid()) {
            throw new InvalidArgumentException(
                'Los datos proporcionados no son válidos.'
            );
        }
    }

    /**
     * Limpia los errores para reutilizar el validador.
     */
    public function reset(): self
    {
        $this->errors = [];

        return $this;
    }

    /**
     * Registra un error de validación.
     */
    private function addError(
        string $field,
        string $message
    ): void {
        $this->errors[$field] ??= [];

        $this->errors[$field][] = $message;
    }

    /**
     * Comprueba que el nombre de un campo sea válido.
     */
    private function validateFieldName(string $field): void
    {
        if (
            $field === ''
            || strlen($field) > 128
            || preg_match('/[\x00-\x1F\x7F]/', $field) === 1
        ) {
            throw new InvalidArgumentException(
                'El nombre del campo no es válido.'
            );
        }
    }
}
