
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\JWT;

use DateTimeImmutable;

interface TokenRevocationStoreInterface
{
    /**
     * Registra un token como revocado hasta su fecha de expiración.
     *
     * @param string $tokenId Identificador único del JWT (claim jti).
     * @param DateTimeImmutable $expiresAt Fecha de expiración del token.
     */
    public function revoke(
        string $tokenId,
        DateTimeImmutable $expiresAt
    ): void;

    /**
     * Comprueba si un token continúa revocado.
     *
     * Debe devolver false cuando no exista una revocación vigente.
     */
    public function isRevoked(string $tokenId): bool;

    /**
     * Elimina los registros de revocación que ya hayan expirado.
     *
     * @param DateTimeImmutable $now Hora utilizada para determinar
     *                               qué registros han expirado.
     *
     * @return int Número de registros eliminados.
     */
    public function removeExpired(DateTimeImmutable $now): int;
}
