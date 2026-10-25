<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Crypto;

use InvalidArgumentException;
use Random\RandomException;
use ShieldPHP\Core\Exceptions\ShieldException;

final class RandomGenerator
{
    /**
     * Límite de bytes por operación para evitar asignaciones excesivas.
     */
    private const MAX_BYTES = 1_048_576;

    /**
     * Tamaño predeterminado de los tokens, en bytes.
     */
    private const DEFAULT_TOKEN_BYTES = 32;

    /**
     * Tamaño mínimo recomendado para los tokens generados.
     */
    private const MIN_TOKEN_BYTES = 16;

    /**
     * Genera bytes criptográficamente seguros.
     */
    public function generateBytes(int $length = 32): string
    {
        if ($length < 1 || $length > self::MAX_BYTES) {
            throw new InvalidArgumentException(
                sprintf(
                    'La longitud debe estar entre 1 y %d bytes.',
                    self::MAX_BYTES
                )
            );
        }

        try {
            return random_bytes($length);
        } catch (RandomException $exception) {
            throw new ShieldException(
                'No fue posible generar bytes aleatorios seguros.',
                0,
                $exception
            );
        }
    }

    /**
     * Genera un token aleatorio codificado en hexadecimal.
     *
     * El resultado tiene el doble de caracteres que bytes solicitados.
     */
    public function generateHex(int $length = 32): string
    {
        return bin2hex($this->generateBytes($length));
    }

    /**
     * Genera un token aleatorio apto para utilizar en URL.
     *
     * El resultado utiliza Base64 URL-safe sin padding.
     */
    public function generateToken(
        int $length = self::DEFAULT_TOKEN_BYTES
    ): string {
        if (
            $length < self::MIN_TOKEN_BYTES
            || $length > self::MAX_BYTES
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'La longitud del token debe estar entre %d y %d bytes.',
                    self::MIN_TOKEN_BYTES,
                    self::MAX_BYTES
                )
            );
        }

        return $this->generateBase64Url($length);
    }

    /**
     * Genera datos aleatorios codificados en Base64 URL-safe.
     *
     * Los caracteres + y / se sustituyen por - y _,
     * y se eliminan los caracteres de padding (=).
     */
    public function generateBase64Url(int $length = 32): string
    {
        $bytes = $this->generateBytes($length);

        return rtrim(
            strtr(base64_encode($bytes), '+/', '-_'),
            '='
        );
    }

    /**
     * Genera un entero aleatorio criptográficamente seguro.
     *
     * Los límites son inclusivos.
     */
    public function generateInteger(
        int $min,
        int $max
    ): int {
        if ($min > $max) {
            throw new InvalidArgumentException(
                'El límite mínimo no puede superar al máximo.'
            );
        }

        try {
            return random_int($min, $max);
        } catch (RandomException $exception) {
            throw new ShieldException(
                'No fue posible generar un entero aleatorio seguro.',
                0,
                $exception
            );
        }
    }

    /**
     * Genera un UUID versión 4.
     */
    public function generateUuidV4(): string
    {
        $bytes = $this->generateBytes(16);

        // Establecer la versión 4 del UUID.
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);

        // Establecer la variante RFC 4122.
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}