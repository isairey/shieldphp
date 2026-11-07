
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Support;

use DateTimeImmutable;
use ShieldPHP\Core\OAuth\OAuthStateStoreInterface;

/**
 * Almacenamiento OAuth State en memoria para pruebas.
 *
 * Permite almacenar estados OAuth con su fecha de expiración,
 * consumirlos una sola vez y eliminar los estados expirados.
 *
 * No debe utilizarse como almacenamiento de producción.
 */
final class InMemoryOAuthStateStore implements OAuthStateStoreInterface
{
    /**
     * Estados OAuth almacenados.
     *
     * Estructura:
     *
     * [
     *     'sha256-state-hash' => DateTimeImmutable,
     * ]
     *
     * @var array<string, DateTimeImmutable>
     */
    private array $states = [];

    /**
     * Almacena un estado OAuth utilizando su hash.
     *
     * @param string $stateHash Hash SHA-256 del estado original.
     * @param DateTimeImmutable $expiresAt Fecha de expiración.
     */
    public function store(
        string $stateHash,
        DateTimeImmutable $expiresAt
    ): void {
        $this->states[$stateHash] = $expiresAt;
    }

    /**
     * Consume un estado OAuth.
     *
     * Un estado válido solamente puede consumirse una vez.
     * Los estados expirados también se eliminan al intentar consumirlos.
     *
     * @param string $stateHash Hash SHA-256 del estado original.
     * @param DateTimeImmutable $now Fecha y hora actuales.
     *
     * @return bool True si el estado existe y todavía es válido.
     */
    public function consume(
        string $stateHash,
        DateTimeImmutable $now
    ): bool {
        if (!isset($this->states[$stateHash])) {
            return false;
        }

        $expiresAt = $this->states[$stateHash];

        // La fecha de expiración es exclusiva:
        // al llegar a ella, el estado deja de ser válido.
        if ($expiresAt <= $now) {
            unset($this->states[$stateHash]);

            return false;
        }

        // Eliminar antes de devolver true garantiza el uso único.
        unset($this->states[$stateHash]);

        return true;
    }

    /**
     * Elimina todos los estados expirados.
     *
     * @param DateTimeImmutable $now Fecha y hora actuales.
     *
     * @return int Cantidad de estados eliminados.
     */
    public function removeExpired(
        DateTimeImmutable $now
    ): int {
        $removed = 0;

        foreach ($this->states as $stateHash => $expiresAt) {
            if ($expiresAt <= $now) {
                unset($this->states[$stateHash]);

                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Comprueba si un estado está almacenado.
     *
     * Método auxiliar exclusivo para pruebas.
     */
    public function has(string $stateHash): bool
    {
        return isset($this->states[$stateHash]);
    }

    /**
     * Devuelve la cantidad de estados almacenados.
     *
     * Método auxiliar exclusivo para pruebas.
     */
    public function count(): int
    {
        return count($this->states);
    }

    /**
     * Elimina todos los estados almacenados.
     *
     * Método auxiliar para reiniciar el almacenamiento entre pruebas.
     */
    public function clear(): void
    {
        $this->states = [];
    }

    /**
     * Devuelve la fecha de expiración de un estado.
     *
     * Método auxiliar exclusivo para pruebas.
     */
    public function getExpiration(
        string $stateHash
    ): ?DateTimeImmutable {
        return $this->states[$stateHash] ?? null;
    }
}
