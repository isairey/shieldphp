<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Crypto;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class KeyDerivation
{
    /**
     * Longitud de la clave generada para Encryption.php.
     */
    private const KEY_LENGTH = SODIUM_CRYPTO_SECRETBOX_KEYBYTES;

    /**
     * Longitud de la sal de Argon2id.
     */
    private const SALT_LENGTH = SODIUM_CRYPTO_PWHASH_SALTBYTES;

    /**
     * Genera una sal criptográficamente segura.
     *
     * La sal no es secreta y debe almacenarse junto con los
     * datos necesarios para volver a derivar la misma clave.
     */
    public function generateSalt(): string
    {
        return random_bytes(self::SALT_LENGTH);
    }

    /**
     * Deriva una clave de 32 bytes a partir de una contraseña.
     *
     * La misma contraseña y la misma sal producirán la misma
     * clave si se utilizan los mismos parámetros de derivación.
     */
    public function deriveKey(
        string $password,
        string $salt,
        ?int $opslimit = null,
        ?int $memlimit = null
    ): string {
        if ($password === '') {
            throw new InvalidArgumentException(
                'La contraseña no puede estar vacía.'
            );
        }

        if (strlen($password) > 4096) {
            throw new InvalidArgumentException(
                'La contraseña supera el límite de 4096 bytes.'
            );
        }

        if (strlen($salt) !== self::SALT_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'La sal debe tener exactamente %d bytes.',
                    self::SALT_LENGTH
                )
            );
        }

        $opslimit ??= SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE;
        $memlimit ??= SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE;

        if (
            $opslimit < SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE
            || $memlimit < SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        ) {
            throw new InvalidArgumentException(
                'Los parámetros de derivación son inferiores '
                . 'a los valores interactivos recomendados por Sodium.'
            );
        }

        try {
            return sodium_crypto_pwhash(
                self::KEY_LENGTH,
                $password,
                $salt,
                $opslimit,
                $memlimit,
                SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'ShieldPHP no pudo derivar la clave criptográfica.',
                0,
                $exception
            );
        }
    }
}