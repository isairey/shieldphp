<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth;

use DateTimeImmutable;
use InvalidArgumentException;
use Random\RandomException;
use ShieldPHP\Core\Contracts\ClockInterface;
use ShieldPHP\Core\Contracts\PKCEStoreInterface;
use ShieldPHP\Core\Exceptions\ShieldException;

final class PKCEManager
{
    /**
     * 32 bytes codificados en Base64 URL-safe producen
     * un verifier de 43 caracteres, sin padding.
     */
    private const VERIFIER_BYTES = 32;

    private const STATE_PATTERN = '/\A[A-Za-z0-9_-]{43}\z/';

    public function __construct(
        private readonly PKCEStoreInterface $store,
        private readonly int $ttlSeconds = 600,
        private readonly ?ClockInterface $clock = null
    ) {
        if ($this->ttlSeconds < 60 || $this->ttlSeconds > 1800) {
            throw new InvalidArgumentException(
                'La duración de PKCE debe estar entre 60 y 1800 segundos.'
            );
        }
    }

    /**
     * Genera un code_verifier y devuelve su challenge S256.
     *
     * El verifier se almacena en el backend, asociado al hash
     * del state, para recuperarlo durante el callback OAuth.
     */
    public function createChallenge(string $state): string
    {
        $this->validateState($state);

        try {
            $verifier = self::base64UrlEncode(
                random_bytes(self::VERIFIER_BYTES)
            );
        } catch (RandomException $exception) {
            throw new ShieldException(
                'No fue posible generar el código verificador PKCE.',
                0,
                $exception
            );
        }

        $expiresAt = $this->now()->modify(
            sprintf('+%d seconds', $this->ttlSeconds)
        );

        if ($expiresAt === false) {
            throw new ShieldException(
                'No fue posible calcular la expiración de PKCE.'
            );
        }

        // No guardamos el state original como clave de almacenamiento.
        $stateHash = hash('sha256', $state);

        $this->store->store(
            $stateHash,
            $verifier,
            $expiresAt
        );

        // S256 = BASE64URL(SHA256(ASCII(code_verifier))).
        return self::base64UrlEncode(
            hash('sha256', $verifier, true)
        );
    }

    /**
     * Recupera y consume el verifier asociado al state.
     *
     * Devuelve null cuando no existe, ha expirado o ya fue utilizado.
     */
    public function consumeVerifier(string $state): ?string
    {
        if (preg_match(self::STATE_PATTERN, $state) !== 1) {
            return null;
        }

        $stateHash = hash('sha256', $state);

        $verifier = $this->store->consume(
            $stateHash,
            $this->now()
        );

        if ($verifier === null) {
            return null;
        }

        // Verificar el formato del dato recuperado.
        if (
            preg_match('/\A[A-Za-z0-9._~-]{43,128}\z/', $verifier) !== 1
        ) {
            throw new ShieldException(
                'El almacenamiento devolvió un verifier PKCE inválido.'
            );
        }

        return $verifier;
    }

    /**
     * Elimina los verifiers cuyo plazo ya venció.
     */
    public function removeExpired(): int
    {
        return $this->store->removeExpired($this->now());
    }

    /**
     * Comprueba el formato del state generado por OAuthStateManager.
     */
    private function validateState(string $state): void
    {
        if (preg_match(self::STATE_PATTERN, $state) !== 1) {
            throw new InvalidArgumentException(
                'El state debe tener 43 caracteres Base64 URL-safe.'
            );
        }
    }

    /**
     * Obtiene la hora actual para permitir pruebas controladas.
     */
    private function now(): DateTimeImmutable
    {
        return $this->clock?->now() ?? new DateTimeImmutable();
    }

    /**
     * Codifica datos en Base64 URL-safe sin padding.
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }
}