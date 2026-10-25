<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Contracts;

use DateTimeImmutable;

interface TokenStoreInterface
{
    /**
     * Guarda un token asociado a un usuario.
     *
     * @param string $tokenId Identificador único del token.
     * @param string $subject Identificador del usuario propietario.
     * @param string $tokenHash Hash del token, nunca el token original.
     * @param DateTimeImmutable $expiresAt Fecha de expiración.
     * @param array<string, mixed> $metadata Datos adicionales no sensibles.
     */
    public function save(
        string $tokenId,
        string $subject,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        array $metadata = []
    ): void;

    /**
     * Busca un token por su identificador.
     *
     * Devuelve null si no existe o ya expiró.
     *
     * @return array{
     *     token_id: string,
     *     subject: string,
     *     token_hash: string,
     *     expires_at: DateTimeImmutable,
     *     metadata: array<string, mixed>
     * }|null
     */
    public function find(string $tokenId): ?array;

    /**
     * Comprueba si existe un token válido.
     */
    public function exists(string $tokenId): bool;

    /**
     * Elimina un token por su identificador.
     *
     * La operación debe ser segura aunque el token no exista.
     */
    public function delete(string $tokenId): void;

    /**
     * Elimina todos los tokens de un usuario.
     *
     * Útil para cerrar todas las sesiones del usuario.
     *
     * @return int Número de tokens eliminados.
     */
    public function deleteAllForSubject(string $subject): int;

    /**
     * Elimina los tokens expirados.
     *
     * @return int Número de tokens eliminados.
     */
    public function deleteExpired(
        DateTimeImmutable $now
    ): int;
}