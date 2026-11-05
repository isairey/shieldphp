
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\OAuth;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Contracts\ClockInterface;
use ShieldPHP\Core\OAuth\OAuthStateManager;
use ShieldPHP\Core\OAuth\OAuthStateStoreInterface;

final class OAuthStateManagerTest extends TestCase
{
    private const DEFAULT_TTL = 600;

    private const MIN_TTL = 60;

    private const MAX_TTL = 1800;

    private DateTimeImmutable $now;

    private OAuthStateStoreInterface&MockObject $store;

    private ClockInterface&MockObject $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2026-01-15 12:00:00 UTC');

        $this->store = $this->createMock(OAuthStateStoreInterface::class);

        $this->clock = $this->createMock(ClockInterface::class);

        $this->clock
            ->method('now')
            ->willReturn($this->now);
    }

    private function createManager(
        int $ttl = self::DEFAULT_TTL
    ): OAuthStateManager {
        return new OAuthStateManager(
            $this->store,
            $ttl,
            $this->clock
        );
    }

    /**
     * Verifica que se pueda generar un estado OAuth.
     */
    public function testGeneratesState(): void
    {
        $this->store
            ->expects($this->once())
            ->method('store');

        $manager = $this->createManager();

        $state = $manager->generateAndStore();

        $this->assertIsString($state);
        $this->assertNotEmpty($state);
    }

    /**
     * El estado generado debe ser suficientemente largo.
     */
    public function testGeneratedStateHasSufficientLength(): void
    {
        $this->store
            ->method('store');

        $manager = $this->createManager();

        $state = $manager->generateAndStore();

        $this->assertGreaterThanOrEqual(32, strlen($state));
    }

    /**
     * El estado debe utilizar caracteres seguros para URL.
     */
    public function testGeneratedStateIsUrlSafe(): void
    {
        $this->store
            ->method('store');

        $manager = $this->createManager();

        $state = $manager->generateAndStore();

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+$/',
            $state
        );
    }

    /**
     * Cada estado generado debe ser diferente.
     */
    public function testGeneratesUniqueStates(): void
    {
        $this->store
            ->expects($this->exactly(2))
            ->method('store');

        $manager = $this->createManager();

        $firstState = $manager->generateAndStore();
        $secondState = $manager->generateAndStore();

        $this->assertNotSame($firstState, $secondState);
    }

    /**
     * No debe almacenar el estado original, sino su hash SHA-256.
     */
    public function testStoresHashedStateInsteadOfPlainState(): void
    {
        $manager = $this->createManager();

        $expectedStateHash = null;

        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->callback(
                    function (string $stateHash) use (&$expectedStateHash): bool {
                        $expectedStateHash = $stateHash;

                        return preg_match(
                            '/^[a-f0-9]{64}$/',
                            $stateHash
                        ) === 1;
                    }
                ),
                $this->anything()
            );

        $state = $manager->generateAndStore();

        $this->assertNotSame($state, $expectedStateHash);
        $this->assertSame(
            hash('sha256', $state),
            $expectedStateHash
        );
    }

    /**
     * El estado debe almacenarse con la fecha de expiración correcta.
     */
    public function testStoresExpirationTime(): void
    {
        $manager = $this->createManager(300);

        $expectedExpiration = $this->now->modify('+300 seconds');

        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->isType('string'),
                $this->callback(
                    fn (DateTimeImmutable $expiresAt): bool =>
                        $expiresAt->getTimestamp() ===
                        $expectedExpiration->getTimestamp()
                )
            );

        $manager->generateAndStore();
    }

    /**
     * Debe utilizar el reloj inyectado.
     */
    public function testUsesInjectedClock(): void
    {
        $customTime = new DateTimeImmutable('2027-05-20 10:30:00 UTC');

        $clock = $this->createMock(ClockInterface::class);

        $clock
            ->expects($this->once())
            ->method('now')
            ->willReturn($customTime);

        $store = $this->createMock(OAuthStateStoreInterface::class);

        $store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->isType('string'),
                $this->callback(
                    fn (DateTimeImmutable $expiresAt): bool =>
                        $expiresAt->getTimestamp() ===
                        $customTime->modify('+600 seconds')->getTimestamp()
                )
            );

        $manager = new OAuthStateManager(
            $store,
            self::DEFAULT_TTL,
            $clock
        );

        $manager->generateAndStore();
    }

    /**
     * Un estado válido debe poder consumirse.
     */
    public function testConsumesValidState(): void
    {
        $state = 'valid-oauth-state-example-123456789';

        $this->store
            ->expects($this->once())
            ->method('consume')
            ->with(
                hash('sha256', $state),
                $this->now
            )
            ->willReturn(true);

        $manager = $this->createManager();

        $this->assertTrue($manager->consume($state));
    }

    /**
     * Un estado inexistente no debe aceptarse.
     */
    public function testRejectsUnknownState(): void
    {
        $state = 'unknown-oauth-state-example-123456789';

        $this->store
            ->expects($this->once())
            ->method('consume')
            ->with(
                hash('sha256', $state),
                $this->now
            )
            ->willReturn(false);

        $manager = $this->createManager();

        $this->assertFalse($manager->consume($state));
    }

    /**
     * El estado original no debe enviarse al almacenamiento al consumirlo.
     */
    public function testConsumesStateUsingItsHash(): void
    {
        $state = 'oauth-state-to-hash-123456789012345';

        $expectedHash = hash('sha256', $state);

        $this->store
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->callback(
                    fn (string $hash): bool =>
                        hash_equals($expectedHash, $hash)
                ),
                $this->now
            )
            ->willReturn(true);

        $manager = $this->createManager();

        $this->assertTrue($manager->consume($state));
    }

    /**
     * Dos consumos del mismo estado no deben resultar exitosos
     * cuando el almacenamiento aplica consumo de un solo uso.
     */
    public function testStateCannotBeConsumedTwice(): void
    {
        $state = 'single-use-oauth-state-123456789012';

        $this->store
            ->expects($this->exactly(2))
            ->method('consume')
            ->with(
                hash('sha256', $state),
                $this->now
            )
            ->willReturnOnConsecutiveCalls(true, false);

        $manager = $this->createManager();

        $this->assertTrue($manager->consume($state));
        $this->assertFalse($manager->consume($state));
    }

    /**
     * Dos estados diferentes deben tratarse de forma independiente.
     */
    public function testDifferentStatesAreIndependent(): void
    {
        $firstState = 'first-oauth-state-123456789012345678';
        $secondState = 'second-oauth-state-12345678901234567';

        $this->store
            ->expects($this->exactly(2))
            ->method('consume')
            ->willReturnMap([
                [
                    hash('sha256', $firstState),
                    $this->now,
                    true,
                ],
                [
                    hash('sha256', $secondState),
                    $this->now,
                    false,
                ],
            ]);

        $manager = $this->createManager();

        $this->assertTrue($manager->consume($firstState));
        $this->assertFalse($manager->consume($secondState));
    }

    /**
     * Verifica que se acepte el TTL mínimo.
     */
    public function testAcceptsMinimumTtl(): void
    {
        $this->store
            ->expects($this->once())
            ->method('store');

        $manager = $this->createManager(self::MIN_TTL);

        $state = $manager->generateAndStore();

        $this->assertNotEmpty($state);
    }

    /**
     * Verifica que se acepte el TTL máximo.
     */
    public function testAcceptsMaximumTtl(): void
    {
        $this->store
            ->expects($this->once())
            ->method('store');

        $manager = $this->createManager(self::MAX_TTL);

        $state = $manager->generateAndStore();

        $this->assertNotEmpty($state);
    }

    /**
     * No debe aceptar un TTL inferior al mínimo.
     */
    public function testRejectsTtlBelowMinimum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManager(self::MIN_TTL - 1);
    }

    /**
     * No debe aceptar un TTL superior al máximo.
     */
    public function testRejectsTtlAboveMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManager(self::MAX_TTL + 1);
    }

    /**
     * Verifica que el almacenamiento no se invoque
     * cuando la construcción del manager falla.
     */
    public function testInvalidTtlDoesNotStoreState(): void
    {
        $this->store
            ->expects($this->never())
            ->method('store');

        try {
            $this->createManager(10);
        } catch (InvalidArgumentException) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('Se esperaba una excepción por TTL inválido.');
    }

    /**
     * Verifica el TTL predeterminado del manager.
     */
    public function testUsesDefaultTtl(): void
    {
        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->isType('string'),
                $this->callback(
                    fn (DateTimeImmutable $expiresAt): bool =>
                        $expiresAt->getTimestamp() ===
                        $this->now->modify(
                            '+' . self::DEFAULT_TTL . ' seconds'
                        )->getTimestamp()
                )
            );

        $manager = new OAuthStateManager(
            $this->store,
            self::DEFAULT_TTL,
            $this->clock
        );

        $manager->generateAndStore();
    }
}
