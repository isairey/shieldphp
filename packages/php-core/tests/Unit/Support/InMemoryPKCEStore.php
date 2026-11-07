
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Support;

use DateTimeImmutable;
use ShieldPHP\Core\OAuth\PKCEStoreInterface;

/**
 * Almacenamiento PKCE en memoria para pruebas.
 *
 * Guarda los code_verifier asociados al hash del estado OAuth.
 *
 * Permite:
 * - Almacenar verificadores.
 * - Recuperarlos una sola vez.
 * - Comprobar expiraciones.
 * - Eliminar verificadores expirados.
 *
 * No debe utilizarse en producción.
 */
final class InMemoryPKCEStore implements PKCEStoreInterface
{
    /**
     * Verificadores almacenados.
     *
     * Estructura:
     *
     * [
     *     'sha256-state-hash' => [
     *         'verifier' => 'code-verifier',
     *         'expires_at' => DateTimeImmutable,
     *     ],
     * ]
     *
     * @var array<string, array{
     *     verifier: string,
     *     expires_at: DateTimeImmutable
     * }>
     */
    private array $verifiers = [];

    /**
     * Almacena un code_verifier asociado al hash del estado.
     *
     * @param string $stateHash Hash SHA-256 del estado OAuth.
     * @param string $verifier Code verifier PKCE.
     * @param DateTimeImmutable $expiresAt Fecha de expiración.
     */
    public function store(
        string $stateHash,
        string $verifier,
        DateTimeImmutable $expiresAt
    ): void {
        $this->verifiers[$stateHash] = [
            'verifier' => $verifier,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Consume un code_verifier.
     *
     * Si existe y no ha expirado, devuelve el verifier y lo elimina.
     * Si no existe o está expirado, devuelve null.
     *
     * @param string $stateHash Hash SHA-256 del estado OAuth.
     * @param DateTimeImmutable $now Fecha y hora actuales.
     *
     * @return string|null Code verifier o null si no es válido.
     */
    public function consume(
        string $stateHash,
        DateTimeImmutable $now
    ): ?string {
        if (!isset($this->verifiers[$stateHash])) {
            return null;
        }

        $stored = $this->verifiers[$stateHash];

        // Un verifier expirado no puede utilizarse.
        if ($stored['expires_at'] <= $now) {
            unset($this->verifiers[$stateHash]);

            return null;
        }

        // Eliminar antes de devolverlo impide su reutilización.
        unset($this->verifiers[$stateHash]);

        return $stored['verifier'];
    }

    /**
     * Elimina todos los verificadores expirados.
     *
     * @param DateTimeImmutable $now Fecha y hora actuales.
     *
     * @return int Número de verificadores eliminados.
     */
    public function removeExpired(
        DateTimeImmutable $now
    ): int {
        $removed = 0;

        foreach ($this->verifiers as $stateHash => $stored) {
            if ($stored['expires_at'] <= $now) {
                unset($this->verifiers[$stateHash]);

                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Comprueba si existe un verifier para un estado.
     *
     * Método auxiliar para pruebas.
     */
    public function has(string $stateHash): bool
    {
        return isset($this->verifiers[$stateHash]);
    }

    /**
     * Devuelve la cantidad de verificadores almacenados.
     *
     * Método auxiliar para pruebas.
     */
    public function count(): int
    {
        return count($this->verifiers);
    }

    /**
     * Devuelve la fecha de expiración de un verifier.
     *
     * Método auxiliar para pruebas.
     */
    public function getExpiration(
        string $stateHash
    ): ?DateTimeImmutable {
        return $this->verifiers[$stateHash]['expires_at'] ?? null;
    }

    /**
     * Devuelve un verifier sin consumirlo.
     *
     * Método exclusivo para inspección durante pruebas.
     */
    public function peek(string $stateHash): ?string
    {
        return $this->verifiers[$stateHash]['verifier'] ?? null;
    }

    /**
     * Elimina todos los verificadores almacenados.
     *
     * Método auxiliar para reiniciar el almacenamiento.
     */
    public function clear(): void
    {
        $this->verifiers = [];
    }
}
