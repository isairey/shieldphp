
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Validation;

use InvalidArgumentException;

final class EmailValidator
{
    private const MAX_EMAIL_LENGTH = 254;
    private const MAX_LOCAL_LENGTH = 64;

    /**
     * Comprueba si una dirección de correo es válida.
     */
    public function isValid(string $email): bool
    {
        $email = trim($email);

        if (
            $email === ''
            || strlen($email) > self::MAX_EMAIL_LENGTH
            || preg_match('/[\x00-\x20\x7F]/', $email) === 1
        ) {
            return false;
        }

        // Obtener la posición de la última arroba.
        $atPosition = strrpos($email, '@');

        if ($atPosition === false) {
            return false;
        }

        $localPart = substr($email, 0, $atPosition);
        $domain = substr($email, $atPosition + 1);

        // Validar la parte local.
        if (
            $localPart === ''
            || strlen($localPart) > self::MAX_LOCAL_LENGTH
        ) {
            return false;
        }

        // Validar el dominio.
        if ($domain === '' || strlen($domain) > 253) {
            return false;
        }

        // Convertir dominios internacionales a ASCII cuando
        // la extensión Intl esté disponible.
        if (preg_match('/[^\x00-\x7F]/', $domain) === 1) {
            if (!function_exists('idn_to_ascii')) {
                return false;
            }

            $asciiDomain = idn_to_ascii(
                $domain,
                IDNA_DEFAULT,
                INTL_IDNA_VARIANT_UTS46
            );

            if ($asciiDomain === false) {
                return false;
            }

            $domain = $asciiDomain;
        }

        // Validar el correo completo.
        $normalizedEmail = $localPart . '@' . $domain;

        return filter_var(
            $normalizedEmail,
            FILTER_VALIDATE_EMAIL
        ) !== false;
    }

    /**
     * Valida un correo y devuelve el resultado.
     *
     * Método alternativo para mantener una API expresiva.
     */
    public function validate(string $email): bool
    {
        return $this->isValid($email);
    }

    /**
     * Comprueba el correo y lanza una excepción si no es válido.
     */
    public function assertValid(string $email): void
    {
        if (!$this->isValid($email)) {
            throw new InvalidArgumentException(
                'La dirección de correo electrónico no es válida.'
            );
        }
    }
}
