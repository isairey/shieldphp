<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Contracts;

use DateTimeImmutable;

interface SessionStoreInterface
{
    /**
     * Recupera los datos de una sesión.
     *
     * Debe devolver null cuando la sesión no exista o haya expirado.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $sessionId): ?array;

    /**
     * Guarda o actualiza una sesión con su fecha de expiración.
     *
     * @param array<string, mixed> $data Datos de la sesión.
     */
    public function save(
        string $sessionId,
        array $data,
        DateTimeImmutable $expiresAt
    ): void;

    /**
     * Elimina una sesión.
     *
     * La operación debe ser segura aunque la sesión no exista.
     */
    public function delete(string $sessionId): void;

    /**
     * Elimina las sesiones que hayan expirado.
     *
     * @return int Número de sesiones eliminadas.
     */
    public function deleteExpired(
        DateTimeImmutable $now
    ): int;
}