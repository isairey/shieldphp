<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Crypto;

use ShieldPHP\Core\Exceptions\EncryptionException;

final class KeyManager
{
    /**
     * Identificador de clave: letras, números, guiones y guiones bajos.
     */
    private const KEY_ID_PATTERN = '/\A[a-zA-Z0-9_-]{1,64}\z/';

    /**
     * Claves registradas, indexadas por su identificador.
     *
     * @var array<string, string>
     */
    private readonly array $keys;

    /**
     * Identificador de la clave utilizada para nuevos cifrados.
     */
    private readonly string $activeKeyId;

    /**
     * @param array<string, string> $keys
     *        Mapa de identificadores a claves binarias de 32 bytes.
     * @param string $activeKeyId Identificador de la clave activa.
     */
    public function __construct(
        array $keys,
        string $activeKeyId
    ) {
        $this->ensureSodiumAvailable();

        if ($keys === []) {
            throw new EncryptionException(
                'Debe registrarse al menos una clave criptográfica.'
            );
        }

        foreach ($keys as $keyId => $key) {
            if (
                !is_string($keyId)
                || preg_match(self::KEY_ID_PATTERN, $keyId) !== 1
            ) {
                throw new EncryptionException(
                    'El identificador de una clave no es válido.'
                );
            }

            if (
                !is_string($key)
                || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES
            ) {
                throw new EncryptionException(
                    sprintf(
                        'La clave "%s" debe tener exactamente 32 bytes.',
                        $keyId
                    )
                );
            }
        }

        if (!isset($keys[$activeKeyId])) {
            throw new EncryptionException(
                'La clave activa no está registrada.'
            );
        }

        $this->keys = $keys;
        $this->activeKeyId = $activeKeyId;
    }

    /**
     * Crea un administrador a partir de una clave Base64
     * almacenada en una variable de entorno.
     */
    public static function fromEnvironment(
        string $variable = 'SHIELDPHP_ENCRYPTION_KEY',
        string $keyId = 'v1'
    ): self {
        $encodedKey = getenv($variable);

        if ($encodedKey === false || $encodedKey === '') {
            throw new EncryptionException(
                sprintf(
                    'No se ha configurado la variable de entorno "%s".',
                    $variable
                )
            );
        }

        $key = self::decodeBase64Key($encodedKey);

        return new self(
            [$keyId => $key],
            $keyId
        );
    }

    /**
     * Genera una nueva clave criptográfica de 32 bytes.
     *
     * La clave resultante debe almacenarse de forma segura.
     */
    public static function generateKey(): string
    {
        if (!extension_loaded('sodium')) {
            throw new EncryptionException(
                'La extensión Sodium de PHP no está disponible.'
            );
        }

        return sodium_crypto_secretbox_keygen();
    }

    /**
     * Decodifica y valida una clave codificada en Base64.
     */
    public static function decodeBase64Key(string $encodedKey): string
    {
        if (!extension_loaded('sodium')) {
            throw new EncryptionException(
                'La extensión Sodium de PHP no está disponible.'
            );
        }

        $key = base64_decode($encodedKey, true);

        if (
            $key === false
            || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES
            || base64_encode($key) !== $encodedKey
        ) {
            throw new EncryptionException(
                'La clave debe ser una codificación Base64 válida de 32 bytes.'
            );
        }

        return $key;
    }

    /**
     * Devuelve la clave activa para nuevos cifrados.
     */
    public function getActiveKey(): string
    {
        return $this->keys[$this->activeKeyId];
    }

    /**
     * Devuelve el identificador de la clave activa.
     */
    public function getActiveKeyId(): string
    {
        return $this->activeKeyId;
    }

    /**
     * Obtiene una clave registrada por su identificador.
     */
    public function getKey(string $keyId): string
    {
        if (!isset($this->keys[$keyId])) {
            throw new EncryptionException(
                sprintf('La clave "%s" no está registrada.', $keyId)
            );
        }

        return $this->keys[$keyId];
    }

    /**
     * Comprueba si una clave está registrada.
     */
    public function hasKey(string $keyId): bool
    {
        return isset($this->keys[$keyId]);
    }

    /**
     * Devuelve los identificadores registrados, nunca las claves.
     *
     * @return list<string>
     */
    public function getKeyIds(): array
    {
        return array_keys($this->keys);
    }

    private function ensureSodiumAvailable(): void
    {
        if (!extension_loaded('sodium')) {
            throw new EncryptionException(
                'La extensión Sodium de PHP no está disponible.'
            );
        }
    }
}