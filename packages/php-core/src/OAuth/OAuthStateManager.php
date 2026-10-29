
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth;

use DateTimeImmutable;
use Random\RandomException;
use ShieldPHP\Core\Contracts\ClockInterface;
use ShieldPHP\Core\Contracts\OAuthStateStoreInterface;
use ShieldPHP\Core\Exceptions\ShieldException;

final class OAuthStateManager
{
    /**
     * 32 bytes aleatorios producen 43 caracteres Base64 URL-safe
     * cuando se elimina el padding.
     */
    private const STATE_BYTES = 32;

    private const STATE_PATTERN = '/\A[A-Za-z0-9_-]{43}\z/';

    public function __construct(
        private readonly OAuthStateStoreInterface $store,
        private readonly int $ttlSeconds = 600,
        private readonly ?ClockInterface $clock = null
    ) {
        if ($this->ttlSeconds < 60 || $this->ttlSeconds > 1800) {
            throw new \InvalidArgumentException(
                'La duración de OAuth state debe estar entre 60 y 1800 segundos.'
            );
        }
    }

    /**
     * Genera un estado criptográficamente aleatorio y lo almacena.
     *
     * Solo se almacena el hash SHA-256 del estado original.
     */
    public function generateAndStore(): string
    {
        try {
            $state = rtrim(
                strtr(
                    base64_encode(random_bytes(self::STATE_BYTES)),
                    '+/',
                    '-_'
                ),
                '='
            );
        } catch (RandomException $exception) {
            throw new ShieldException(
                'No fue posible generar un estado OAuth seguro.',
                0,
                $exception
            );
        }

        $stateHash = hash('sha256', $state);
        $expiresAt = $this->now()->modify(
            sprintf('+%d seconds', $this->ttlSeconds)
        );

        if ($expiresAt === false) {
            throw new ShieldException(
                'No fue posible calcular la expiración del estado OAuth.'
            );
        }

        // El almacenamiento debe persistir el hash y su expiración.
        $this->store->store($stateHash, $expiresAt);

        return $state;
    }

    /**
     * Valida y consume el estado recibido en el callback.
     *
     * Devuelve false si el formato no es válido, no existe,
     * ha expirado o ya fue utilizado.
     *
     * El almacenamiento debe hacer la comprobación y eliminación
     * de manera atómica para impedir la reutilización concurrente.
     */
    public function consume(string $state): bool
    {
        if (preg_match(self::STATE_PATTERN, $state) !== 1) {
            return false;
        }

        $stateHash = hash('sha256', $state);

        return $this->store->consume(
            $stateHash,
            $this->now()
        );
    }

    /**
     * Obtiene la hora actual.
     */
    private function now(): DateTimeImmutable
    {
        return $this->clock?->now() ?? new DateTimeImmutable();
    }
}
