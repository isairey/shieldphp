<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Crypto;

use ShieldPHP\Core\Contracts\EncryptionInterface;
use ShieldPHP\Core\Exceptions\EncryptionException;

final class Encryption implements EncryptionInterface
{
    /**
     * Identificador de la versión del formato de cifrado.
     */
    private const FORMAT_PREFIX = 'shp:v1:';

    /**
     * Clave secreta utilizada para cifrar y descifrar.
     */
    private readonly string $key;

    /**
     * Inicializa el cifrado con una clave secreta de 32 bytes.
     *
     * La clave debe ser binaria, no una cadena codificada en Base64.
     */
    public function __construct(string $key)
    {
        if (!extension_loaded('sodium')) {
            throw new EncryptionException(
                'La extensión Sodium de PHP no está disponible.'
            );
        }

        if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new EncryptionException(
                'La clave de cifrado debe tener exactamente 32 bytes.'
            );
        }

        $this->key = $key;
    }

    /**
     * Cifra un texto mediante XSalsa20-Poly1305.
     *
     * El resultado incluye la versión del formato y el nonce,
     * codificados junto con el texto cifrado en Base64.
     */
    public function encrypt(string $plaintext): string
    {
        try {
            // Cada operación utiliza un nonce aleatorio nuevo.
            $nonce = random_bytes(
                SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
            );

            $encrypted = sodium_crypto_secretbox(
                $plaintext,
                $nonce,
                $this->key
            );

            // El nonce no es secreto y debe guardarse con el cifrado.
            $payload = $nonce . $encrypted;

            return self::FORMAT_PREFIX . base64_encode($payload);
        } catch (\Throwable $exception) {
            throw new EncryptionException(
                'No fue posible cifrar el contenido.',
                0,
                $exception
            );
        }
    }

    /**
     * Descifra un texto generado por encrypt().
     *
     * Si la clave es incorrecta o el contenido fue alterado,
     * el descifrado falla y se lanza EncryptionException.
     */
    public function decrypt(string $ciphertext): string
    {
        if (!str_starts_with($ciphertext, self::FORMAT_PREFIX)) {
            throw new EncryptionException(
                'El formato del contenido cifrado no es compatible.'
            );
        }

        $encodedPayload = substr(
            $ciphertext,
            strlen(self::FORMAT_PREFIX)
        );

        $payload = base64_decode($encodedPayload, true);

        if ($payload === false) {
            throw new EncryptionException(
                'El contenido cifrado no tiene un formato válido.'
            );
        }

        $minimumLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
            + SODIUM_CRYPTO_SECRETBOX_MACBYTES;

        if (strlen($payload) < $minimumLength) {
            throw new EncryptionException(
                'El contenido cifrado está incompleto.'
            );
        }

        $nonce = substr(
            $payload,
            0,
            SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
        );

        $encrypted = substr(
            $payload,
            SODIUM_CRYPTO_SECRETBOX_NONCEBYTES
        );

        try {
            $plaintext = sodium_crypto_secretbox_open(
                $encrypted,
                $nonce,
                $this->key
            );
        } catch (\Throwable $exception) {
            throw new EncryptionException(
                'No fue posible descifrar el contenido.',
                0,
                $exception
            );
        }

        if ($plaintext === false) {
            throw new EncryptionException(
                'El contenido no es auténtico o la clave es incorrecta.'
            );
        }

        return $plaintext;
    }
}