
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\JWT;

use DateTimeImmutable;
use InvalidArgumentException;
use ShieldPHP\Core\Contracts\ClockInterface;

final class TokenBlacklist implements TokenRevocationStoreInterface
{
    /**
     * Identificadores de tokens revocados y sus expiraciones Unix.
     *
     * @var array<string, int>
     */
    private array $revokedTokens = [];

    public function __construct(
        private readonly ?ClockInterface $clock = null
    ) {
    }

    /**
     * Registra un JWT como revocado hasta su expiración.
     */
    public function revoke(
        string $tokenId,
        DateTimeImmutable $expiresAt
    ): void {
        $tokenId = $this->normalizeTokenId($tokenId);

        // No necesitamos guardar tokens que ya han expirado.
        if ($expiresAt->getTimestamp() <= $this->now()->getTimestamp()) {
            unset($this->revokedTokens[$tokenId]);

            return;
        }

        $this->revokedTokens[$tokenId] = $expiresAt->getTimestamp();
    }

    /**
     * Comprueba si un JWT continúa revocado.
     */
    public function isRevoked(string $tokenId): bool
    {
        $tokenId = $this->normalizeTokenId($tokenId);

        if (!isset($this->revokedTokens[$tokenId])) {
            return false;
        }

        // Las revocaciones dejan de ser necesarias cuando expira el JWT.
        if (
            $this->revokedTokens[$tokenId]
            <= $this->now()->getTimestamp()
        ) {
            unset($this->revokedTokens[$tokenId]);

            return false;
        }

        return true;
    }

    /**
     * Elimina las revocaciones que ya expiraron.
     *
     * @return int Número de registros eliminados.
     */
    public function removeExpired(DateTimeImmutable $now): int
    {
        $removed = 0;
        $timestamp = $now->getTimestamp();

        foreach ($this->revokedTokens as $tokenId => $expiresAt) {
            if ($expiresAt <= $timestamp) {
                unset($this->revokedTokens[$tokenId]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Devuelve el número de revocaciones almacenadas.
     */
    public function count(): int
    {
        return count($this->revokedTokens);
    }

    /**
     * Obtiene la hora actual.
     */
    private function now(): DateTimeImmutable
    {
        return $this->clock?->now() ?? new DateTimeImmutable();
    }

    /**
     * Valida el identificador único del JWT (jti).
     */
    private function normalizeTokenId(string $tokenId): string
    {
        $tokenId = trim($tokenId);

        if ($tokenId === '' || strlen($tokenId) > 255) {
            throw new InvalidArgumentException(
                'El identificador del JWT no es válido.'
            );
        }

        return $tokenId;
    }
}
