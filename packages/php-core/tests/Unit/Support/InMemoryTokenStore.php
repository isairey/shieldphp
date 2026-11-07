
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Support;

use DateTimeImmutable;
use ShieldPHP\Core\JWT\TokenRevocationStoreInterface;

/**
 * Almacenamiento de revocación de tokens en memoria.
 *
 * Implementación para pruebas de TokenRevocationStoreInterface.
 *
 * Permite:
 * - Revocar tokens.
 * - Comprobar si un token está revocado.
 * - Registrar la fecha de expiración.
 * - Eliminar tokens expirados.
 * - Consultar los registros almacenados.
 *
 * No debe utilizarse en producción.
 */
final class InMemoryTokenStore implements TokenRevocationStoreInterface
{
    /**
     * Tokens revocados.
     *
     * Estructura:
     *
     * [
     *     'token-id' => DateTimeImmutable,
     * ]
     *
     * @var array<string, DateTimeImmutable>
     */
    private array $revokedTokens = [];

    /**
     * Registra un token como revocado.
     *
     * La clave debe ser el identificador único del token (jti),
     * no el JWT completo.
     *
     * @param string $tokenId Identificador único del token.
     * @param DateTimeImmutable $expiresAt Fecha de expiración del token.
     */
    public function revoke(
        string $tokenId,
        DateTimeImmutable $expiresAt
    ): void {
        $this->revokedTokens[$tokenId] = $expiresAt;
    }

    /**
     * Comprueba si un token está revocado y todavía vigente.
     *
     * Un registro expirado no se considera una revocación vigente.
     * El registro permanece almacenado hasta ejecutar removeExpired().
     *
     * @param string $tokenId Identificador único del token.
     */
    public function isRevoked(string $tokenId): bool
    {
        if (!isset($this->revokedTokens[$tokenId])) {
            return false;
        }

        $now = new DateTimeImmutable('now');

        return $this->revokedTokens[$tokenId] > $now;
    }

    /**
     * Elimina los registros de tokens cuya expiración
     * sea anterior o igual a la fecha proporcionada.
     *
     * @param DateTimeImmutable $now Fecha y hora de referencia.
     *
     * @return int Cantidad de registros eliminados.
     */
    public function removeExpired(DateTimeImmutable $now): int
    {
        $removed = 0;

        foreach ($this->revokedTokens as $tokenId => $expiresAt) {
            if ($expiresAt <= $now) {
                unset($this->revokedTokens[$tokenId]);

                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Comprueba si un identificador está registrado,
     * independientemente de su fecha de expiración.
     *
     * Método auxiliar exclusivo para pruebas.
     */
    public function has(string $tokenId): bool
    {
        return isset($this->revokedTokens[$tokenId]);
    }

    /**
     * Devuelve la cantidad de registros almacenados.
     *
     * Incluye registros expirados que todavía no se han limpiado.
     */
    public function count(): int
    {
        return count($this->revokedTokens);
    }

    /**
     * Obtiene la fecha de expiración de un token.
     *
     * Método auxiliar exclusivo para pruebas.
     */
    public function getExpiration(
        string $tokenId
    ): ?DateTimeImmutable {
        return $this->revokedTokens[$tokenId] ?? null;
    }

    /**
     * Devuelve todos los identificadores almacenados.
     *
     * Método auxiliar exclusivo para pruebas.
     *
     * @return string[]
     */
    public function getTokenIds(): array
    {
        return array_keys($this->revokedTokens);
    }

    /**
     * Elimina todos los registros almacenados.
     *
     * Método auxiliar para reiniciar las pruebas.
     */
    public function clear(): void
    {
        $this->revokedTokens = [];
    }
}
