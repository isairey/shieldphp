
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\JWT;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Contracts\ClockInterface;
use ShieldPHP\Core\JWT\TokenBlacklist;
use ShieldPHP\Core\JWT\TokenRevocationStoreInterface;

final class TokenBlacklistTest extends TestCase
{
    private TokenBlacklistTestFrozenClock $clock;

    private TokenBlacklist $tokenBlacklist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = new TokenBlacklistTestFrozenClock(
            new DateTimeImmutable('2026-11-04 12:00:00 UTC')
        );

        $this->tokenBlacklist = new TokenBlacklist(
            $this->clock
        );
    }

    /**
     * Obtiene una fecha de expiración futura.
     */
    private function futureExpiration(
        int $seconds = 3600
    ): DateTimeImmutable {
        return $this->clock->now()->modify(
            sprintf('+%d seconds', $seconds)
        );
    }

    /**
     * Comprueba que se pueda crear TokenBlacklist.
     */
    public function testCreatesTokenBlacklistInstance(): void
    {
        $blacklist = new TokenBlacklist();

        $this->assertInstanceOf(
            TokenBlacklist::class,
            $blacklist
        );
    }

    /**
     * Comprueba que TokenBlacklist implemente
     * TokenRevocationStoreInterface.
     */
    public function testImplementsTokenRevocationStoreInterface(): void
    {
        $this->assertInstanceOf(
            TokenRevocationStoreInterface::class,
            $this->tokenBlacklist
        );
    }

    /**
     * Comprueba que la lista comience vacía.
     */
    public function testStartsWithEmptyBlacklist(): void
    {
        $this->assertSame(
            0,
            $this->tokenBlacklist->count()
        );
    }

    /**
     * Comprueba que se pueda revocar un token.
     */
    public function testRevokesToken(): void
    {
        $this->tokenBlacklist->revoke(
            'token-123',
            $this->futureExpiration()
        );

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('token-123')
        );
    }

    /**
     * Comprueba que un token inexistente
     * no aparezca como revocado.
     */
    public function testUnknownTokenIsNotRevoked(): void
    {
        $this->assertFalse(
            $this->tokenBlacklist->isRevoked('unknown-token')
        );
    }

    /**
     * Comprueba que un token revocado
     * permanezca registrado mientras no expire.
     */
    public function testRevokedTokenRemainsRegisteredBeforeExpiration(): void
    {
        $this->tokenBlacklist->revoke(
            'token-456',
            $this->futureExpiration(600)
        );

        $this->clock->advance('+300 seconds');

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('token-456')
        );
    }

    /**
     * Comprueba que removeExpired() elimine
     * los tokens cuya expiración ya pasó.
     */
    public function testRemoveExpiredDeletesExpiredTokens(): void
    {
        $this->tokenBlacklist->revoke(
            'expired-token',
            $this->futureExpiration(60)
        );

        $this->clock->advance('+120 seconds');

        $removed = $this->tokenBlacklist->removeExpired(
            $this->clock->now()
        );

        $this->assertSame(1, $removed);

        $this->assertFalse(
            $this->tokenBlacklist->isRevoked('expired-token')
        );
    }

    /**
     * Comprueba que removeExpired() conserve
     * los tokens que todavía no han expirado.
     */
    public function testRemoveExpiredPreservesValidRevocations(): void
    {
        $this->tokenBlacklist->revoke(
            'valid-token',
            $this->futureExpiration(3600)
        );

        $removed = $this->tokenBlacklist->removeExpired(
            $this->clock->now()
        );

        $this->assertSame(0, $removed);

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('valid-token')
        );
    }

    /**
     * Comprueba que se eliminen los tokens
     * que expiran exactamente en el momento indicado.
     */
    public function testRemoveExpiredHandlesExactExpirationTime(): void
    {
        $expiration = $this->futureExpiration(300);

        $this->tokenBlacklist->revoke(
            'boundary-token',
            $expiration
        );

        $removed = $this->tokenBlacklist->removeExpired(
            $expiration
        );

        $this->assertSame(1, $removed);

        $this->assertFalse(
            $this->tokenBlacklist->isRevoked('boundary-token')
        );
    }

    /**
     * Comprueba que removeExpired() devuelva cero
     * cuando no existen tokens expirados.
     */
    public function testRemoveExpiredReturnsZeroWhenNothingExpires(): void
    {
        $this->tokenBlacklist->revoke(
            'active-token',
            $this->futureExpiration()
        );

        $removed = $this->tokenBlacklist->removeExpired(
            $this->clock->now()
        );

        $this->assertSame(0, $removed);
    }

    /**
     * Comprueba que removeExpired() devuelva
     * la cantidad exacta de tokens eliminados.
     */
    public function testRemoveExpiredReturnsCorrectCount(): void
    {
        $now = $this->clock->now();

        $this->tokenBlacklist->revoke(
            'expired-1',
            $now->modify('-300 seconds')
        );

        $this->tokenBlacklist->revoke(
            'expired-2',
            $now->modify('-100 seconds')
        );

        $this->tokenBlacklist->revoke(
            'active-1',
            $now->modify('+3600 seconds')
        );

        $removed = $this->tokenBlacklist->removeExpired($now);

        $this->assertSame(2, $removed);
    }

    /**
     * Comprueba que varios tokens puedan revocarse
     * de manera independiente.
     */
    public function testRevokesMultipleTokens(): void
    {
        $this->tokenBlacklist->revoke(
            'token-001',
            $this->futureExpiration()
        );

        $this->tokenBlacklist->revoke(
            'token-002',
            $this->futureExpiration()
        );

        $this->tokenBlacklist->revoke(
            'token-003',
            $this->futureExpiration()
        );

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('token-001')
        );

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('token-002')
        );

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('token-003')
        );
    }

    /**
     * Comprueba que count() refleje el número
     * de registros de revocación.
     */
    public function testCountReturnsCorrectNumberOfRevokedTokens(): void
    {
        $this->assertSame(
            0,
            $this->tokenBlacklist->count()
        );

        $this->tokenBlacklist->revoke(
            'token-001',
            $this->futureExpiration()
        );

        $this->assertSame(
            1,
            $this->tokenBlacklist->count()
        );

        $this->tokenBlacklist->revoke(
            'token-002',
            $this->futureExpiration()
        );

        $this->assertSame(
            2,
            $this->tokenBlacklist->count()
        );
    }

    /**
     * Comprueba que count() se actualice después
     * de eliminar tokens expirados.
     */
    public function testCountUpdatesAfterExpiredTokenRemoval(): void
    {
        $this->tokenBlacklist->revoke(
            'expired-token',
            $this->futureExpiration(60)
        );

        $this->tokenBlacklist->revoke(
            'active-token',
            $this->futureExpiration(3600)
        );

        $this->assertSame(
            2,
            $this->tokenBlacklist->count()
        );

        $this->clock->advance('+120 seconds');

        $this->tokenBlacklist->removeExpired(
            $this->clock->now()
        );

        $this->assertSame(
            1,
            $this->tokenBlacklist->count()
        );
    }

    /**
     * Comprueba que limpiar tokens expirados
     * no elimine otros registros.
     */
    public function testRemovingExpiredTokensPreservesOtherTokens(): void
    {
        $now = $this->clock->now();

        $this->tokenBlacklist->revoke(
            'expired-token',
            $now->modify('-60 seconds')
        );

        $this->tokenBlacklist->revoke(
            'active-token',
            $now->modify('+3600 seconds')
        );

        $this->tokenBlacklist->removeExpired($now);

        $this->assertFalse(
            $this->tokenBlacklist->isRevoked('expired-token')
        );

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('active-token')
        );
    }

    /**
     * Comprueba que revocar el mismo identificador
     * no genere registros duplicados.
     */
    public function testRevokingSameTokenDoesNotCreateDuplicates(): void
    {
        $expiration = $this->futureExpiration();

        $this->tokenBlacklist->revoke(
            'token-duplicate',
            $expiration
        );

        $this->tokenBlacklist->revoke(
            'token-duplicate',
            $expiration
        );

        $this->assertSame(
            1,
            $this->tokenBlacklist->count()
        );

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('token-duplicate')
        );
    }

    /**
     * Comprueba que diferentes instancias
     * mantengan sus listas independientes.
     */
    public function testBlacklistInstancesRemainIndependent(): void
    {
        $firstBlacklist = new TokenBlacklist($this->clock);
        $secondBlacklist = new TokenBlacklist($this->clock);

        $firstBlacklist->revoke(
            'token-first',
            $this->futureExpiration()
        );

        $secondBlacklist->revoke(
            'token-second',
            $this->futureExpiration()
        );

        $this->assertTrue(
            $firstBlacklist->isRevoked('token-first')
        );

        $this->assertFalse(
            $firstBlacklist->isRevoked('token-second')
        );

        $this->assertTrue(
            $secondBlacklist->isRevoked('token-second')
        );

        $this->assertFalse(
            $secondBlacklist->isRevoked('token-first')
        );
    }

    /**
     * Comprueba que se pueda utilizar TokenBlacklist
     * sin inyectar un reloj personalizado.
     */
    public function testWorksWithoutCustomClock(): void
    {
        $blacklist = new TokenBlacklist();

        $blacklist->revoke(
            'token-no-clock',
            new DateTimeImmutable('+1 hour')
        );

        $this->assertTrue(
            $blacklist->isRevoked('token-no-clock')
        );
    }

    /**
     * Comprueba que varias operaciones de revocación
     * y limpieza permanezcan independientes.
     */
    public function testMultipleOperationsRemainIndependent(): void
    {
        $now = $this->clock->now();

        $this->tokenBlacklist->revoke(
            'token-001',
            $now->modify('+600 seconds')
        );

        $this->tokenBlacklist->revoke(
            'token-002',
            $now->modify('+1200 seconds')
        );

        $this->clock->advance('+900 seconds');

        $removed = $this->tokenBlacklist->removeExpired(
            $this->clock->now()
        );

        $this->assertSame(1, $removed);

        $this->assertFalse(
            $this->tokenBlacklist->isRevoked('token-001')
        );

        $this->assertTrue(
            $this->tokenBlacklist->isRevoked('token-002')
        );

        $this->assertSame(
            1,
            $this->tokenBlacklist->count()
        );
    }
}

/**
 * Reloj controlable para las pruebas de expiración.
 */
final class TokenBlacklistTestFrozenClock implements ClockInterface
{
    public function __construct(
        private DateTimeImmutable $currentTime
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return $this->currentTime;
    }

    public function advance(string $interval): void
    {
        $newTime = $this->currentTime->modify($interval);

        if ($newTime === false) {
            throw new \InvalidArgumentException(
                'Invalid time interval: ' . $interval
            );
        }

        $this->currentTime = $newTime;
    }
}
