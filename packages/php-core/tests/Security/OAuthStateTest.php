
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Security;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Contracts\ClockInterface;
use ShieldPHP\Core\Contracts\OAuthStateStoreInterface;
use ShieldPHP\Core\OAuth\OAuthStateManager;

/**
 * Pruebas de seguridad para OAuthStateManager.
 */
final class OAuthStateTest extends TestCase
{
    private OAuthStateTestMemoryStore $store;

    private OAuthStateTestFrozenClock $clock;

    private OAuthStateManager $stateManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new OAuthStateTestMemoryStore();

        $this->clock = new OAuthStateTestFrozenClock(
            new DateTimeImmutable('2026-01-01 12:00:00 UTC')
        );

        $this->stateManager = new OAuthStateManager(
            $this->store,
            600,
            $this->clock
        );
    }

    /**
     * Comprueba que se genere un state no vacío.
     */
    public function testGeneratesNonEmptyState(): void
    {
        $state = $this->stateManager->generateAndStore();

        $this->assertIsString($state);
        $this->assertNotEmpty($state);
    }

    /**
     * Comprueba que el state generado tenga formato URL-safe.
     */
    public function testGeneratesUrlSafeState(): void
    {
        $state = $this->stateManager->generateAndStore();

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+$/',
            $state
        );

        $this->assertGreaterThanOrEqual(
            32,
            strlen($state)
        );
    }

    /**
     * Comprueba que cada state generado sea diferente.
     */
    public function testGeneratesUniqueStates(): void
    {
        $firstState = $this->stateManager->generateAndStore();
        $secondState = $this->stateManager->generateAndStore();

        $this->assertNotSame($firstState, $secondState);
    }

    /**
     * Comprueba que el state se almacene mediante SHA-256.
     */
    public function testStoresStateAsSha256Hash(): void
    {
        $state = $this->stateManager->generateAndStore();

        $stateHash = hash('sha256', $state);

        $this->assertArrayHasKey(
            $stateHash,
            $this->store->getStoredStates()
        );

        $this->assertArrayNotHasKey(
            $state,
            $this->store->getStoredStates()
        );
    }

    /**
     * Comprueba que un state válido pueda consumirse.
     */
    public function testConsumesValidState(): void
    {
        $state = $this->stateManager->generateAndStore();

        $result = $this->stateManager->consume($state);

        $this->assertTrue($result);
    }

    /**
     * Comprueba que un state solo pueda utilizarse una vez.
     *
     * Evita ataques de repetición (replay attacks).
     */
    public function testPreventsStateReplay(): void
    {
        $state = $this->stateManager->generateAndStore();

        $firstAttempt = $this->stateManager->consume($state);
        $secondAttempt = $this->stateManager->consume($state);

        $this->assertTrue($firstAttempt);
        $this->assertFalse($secondAttempt);
    }

    /**
     * Comprueba que un state inexistente sea rechazado.
     */
    public function testRejectsUnknownState(): void
    {
        $result = $this->stateManager->consume(
            'unknown-oauth-state-value'
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que un state modificado sea rechazado.
     */
    public function testRejectsModifiedState(): void
    {
        $state = $this->stateManager->generateAndStore();

        $modifiedState = $state . 'tampered';

        $result = $this->stateManager->consume($modifiedState);

        $this->assertFalse($result);

        // El state original debe seguir siendo válido.
        $this->assertTrue(
            $this->stateManager->consume($state)
        );
    }

    /**
     * Comprueba que un state expire después de su TTL.
     */
    public function testRejectsExpiredState(): void
    {
        $state = $this->stateManager->generateAndStore();

        // TTL configurado: 600 segundos.
        $this->clock->advance('+601 seconds');

        $result = $this->stateManager->consume($state);

        $this->assertFalse($result);
    }

    /**
     * Comprueba que un state todavía válido pueda consumirse.
     */
    public function testAcceptsStateBeforeExpiration(): void
    {
        $state = $this->stateManager->generateAndStore();

        $this->clock->advance('+599 seconds');

        $result = $this->stateManager->consume($state);

        $this->assertTrue($result);
    }

    /**
     * Comprueba que el state expire exactamente al alcanzar su TTL.
     */
    public function testRejectsStateAtExactExpirationTime(): void
    {
        $state = $this->stateManager->generateAndStore();

        $this->clock->advance('+600 seconds');

        $result = $this->stateManager->consume($state);

        $this->assertFalse($result);
    }

    /**
     * Comprueba que la expiración de un state no afecte a otros.
     */
    public function testExpirationDoesNotAffectOtherStates(): void
    {
        $firstState = $this->stateManager->generateAndStore();

        $this->clock->advance('+300 seconds');

        $secondState = $this->stateManager->generateAndStore();

        $this->clock->advance('+301 seconds');

        $this->assertFalse(
            $this->stateManager->consume($firstState)
        );

        $this->assertTrue(
            $this->stateManager->consume($secondState)
        );
    }

    /**
     * Comprueba que cada state tenga su propia fecha de expiración.
     */
    public function testStoresExpirationForEachState(): void
    {
        $state = $this->stateManager->generateAndStore();

        $stateHash = hash('sha256', $state);

        $storedStates = $this->store->getStoredStates();

        $this->assertArrayHasKey($stateHash, $storedStates);

        $this->assertEquals(
            new DateTimeImmutable('2026-01-01 12:10:00 UTC'),
            $storedStates[$stateHash]
        );
    }

    /**
     * Comprueba que consumir un state lo elimine del almacenamiento.
     */
    public function testRemovesStateAfterConsumption(): void
    {
        $state = $this->stateManager->generateAndStore();

        $stateHash = hash('sha256', $state);

        $this->assertArrayHasKey(
            $stateHash,
            $this->store->getStoredStates()
        );

        $this->stateManager->consume($state);

        $this->assertArrayNotHasKey(
            $stateHash,
            $this->store->getStoredStates()
        );
    }
}

/**
 * Almacenamiento en memoria utilizado exclusivamente para pruebas.
 */
final class OAuthStateTestMemoryStore implements OAuthStateStoreInterface
{
    /**
     * @var array<string, DateTimeImmutable>
     */
    private array $states = [];

    public function store(
        string $stateHash,
        DateTimeImmutable $expiresAt
    ): void {
        $this->states[$stateHash] = $expiresAt;
    }

    public function consume(
        string $stateHash,
        DateTimeImmutable $now
    ): bool {
        if (!isset($this->states[$stateHash])) {
            return false;
        }

        $expiresAt = $this->states[$stateHash];

        // Eliminar antes de devolver el resultado.
        // Así se garantiza el consumo de un solo uso.
        unset($this->states[$stateHash]);

        return $expiresAt > $now;
    }

    public function removeExpired(DateTimeImmutable $now): int
    {
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
     * Permite inspeccionar el almacenamiento desde las pruebas.
     *
     * @return array<string, DateTimeImmutable>
     */
    public function getStoredStates(): array
    {
        return $this->states;
    }
}

/**
 * Reloj controlable para evitar depender del tiempo real.
 */
final class OAuthStateTestFrozenClock implements ClockInterface
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
