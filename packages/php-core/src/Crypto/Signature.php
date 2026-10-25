<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Crypto;

use InvalidArgumentException;
use LogicException;
use SodiumException;
use ShieldPHP\Core\Exceptions\ShieldException;

final class Signature
{
    private readonly ?string $secretKey;
    private readonly string $publicKey;

    /**
     * Las claves se reciben codificadas en Base64.
     *
     * Para firmar se necesita la clave privada.
     * Para verificar basta con la clave pública.
     */
    public function __construct(
        ?string $secretKeyBase64 = null,
        ?string $publicKeyBase64 = null
    ) {
        if (!extension_loaded('sodium')) {
            throw new ShieldException(
                'La extensión Sodium de PHP no está disponible.'
            );
        }

        if ($secretKeyBase64 === null && $publicKeyBase64 === null) {
            throw new InvalidArgumentException(
                'Debes proporcionar una clave privada o una clave pública.'
            );
        }

        $secretKey = $secretKeyBase64 !== null
            ? self::decodeKey(
                $secretKeyBase64,
                SODIUM_CRYPTO_SIGN_SECRETKEYBYTES,
                'privada'
            )
            : null;

        $publicKey = $publicKeyBase64 !== null
            ? self::decodeKey(
                $publicKeyBase64,
                SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES,
                'pública'
            )
            : null;

        if ($secretKey !== null) {
            try {
                $derivedPublicKey =
                    sodium_crypto_sign_publickey_from_secretkey($secretKey);
            } catch (SodiumException $exception) {
                throw new ShieldException(
                    'No fue posible obtener la clave pública.',
                    0,
                    $exception
                );
            }

            if (
                $publicKey !== null
                && !hash_equals($derivedPublicKey, $publicKey)
            ) {
                throw new InvalidArgumentException(
                    'La clave pública no corresponde a la clave privada.'
                );
            }

            $publicKey ??= $derivedPublicKey;
        }

        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
    }

    /**
     * Genera un par de claves Ed25519.
     *
     * Devuelve las claves codificadas en Base64.
     *
     * @return array{public_key: string, secret_key: string}
     */
    public static function generateKeyPair(): array
    {
        if (!extension_loaded('sodium')) {
            throw new ShieldException(
                'La extensión Sodium de PHP no está disponible.'
            );
        }

        try {
            $keyPair = sodium_crypto_sign_keypair();

            return [
                'public_key' => base64_encode(
                    sodium_crypto_sign_publickey($keyPair)
                ),
                'secret_key' => base64_encode(
                    sodium_crypto_sign_secretkey($keyPair)
                ),
            ];
        } catch (SodiumException $exception) {
            throw new ShieldException(
                'No fue posible generar el par de claves.',
                0,
                $exception
            );
        }
    }

    /**
     * Firma un mensaje y devuelve la firma en Base64 URL-safe.
     */
    public function sign(string $message): string
    {
        if ($this->secretKey === null) {
            throw new LogicException(
                'No se puede firmar sin una clave privada.'
            );
        }

        try {
            $signature = sodium_crypto_sign_detached(
                $message,
                $this->secretKey
            );

            return self::encodeBase64Url($signature);
        } catch (SodiumException $exception) {
            throw new ShieldException(
                'No fue posible firmar el mensaje.',
                0,
                $exception
            );
        }
    }

    /**
     * Verifica una firma digital.
     *
     * Devuelve false si la firma tiene un formato incorrecto,
     * pertenece a otro mensaje o no es válida.
     */
    public function verify(
        string $message,
        string $signature
    ): bool {
        $decodedSignature = self::decodeBase64UrlSignature($signature);

        if ($decodedSignature === null) {
            return false;
        }

        try {
            return sodium_crypto_sign_verify_detached(
                $decodedSignature,
                $message,
                $this->publicKey
            );
        } catch (SodiumException) {
            return false;
        }
    }

    /**
     * Valida y decodifica una clave Base64.
     */
    private static function decodeKey(
        string $encodedKey,
        int $expectedLength,
        string $keyType
    ): string {
        $decodedKey = base64_decode($encodedKey, true);

        if (
            $decodedKey === false
            || strlen($decodedKey) !== $expectedLength
            || base64_encode($decodedKey) !== $encodedKey
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'La clave %s no tiene un formato Base64 válido '
                    . 'o una longitud correcta.',
                    $keyType
                )
            );
        }

        return $decodedKey;
    }

    /**
     * Codifica una firma usando Base64 URL-safe sin padding.
     */
    private static function encodeBase64Url(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }

    /**
     * Decodifica una firma Base64 URL-safe y valida su formato.
     */
    private static function decodeBase64UrlSignature(
        string $signature
    ): ?string {
        if (
            preg_match('/\A[A-Za-z0-9_-]{86}\z/', $signature) !== 1
        ) {
            return null;
        }

        $base64 = strtr($signature, '-_', '+/');

        $decoded = base64_decode($base64 . '==', true);

        if (
            $decoded === false
            || strlen($decoded) !== SODIUM_CRYPTO_SIGN_BYTES
            || self::encodeBase64Url($decoded) !== $signature
        ) {
            return null;
        }

        return $decoded;
    }
}