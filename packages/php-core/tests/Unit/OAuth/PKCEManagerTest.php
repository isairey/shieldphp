
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\OAuth;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\OAuth\PKCEManager;
use ShieldPHP\Core\OAuth\PKCEStoreInterface;

final class PKCEManagerTest extends TestCase
{
    private const DEFAULT_TTL = 300;

    private const MIN_TTL = 60;

    private const MAX_TTL = 1800;

    private PKCEStoreInterface&MockObject $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = $this->createMock(PKCEStoreInterface::class);
    }

    private function createManager(
        int $ttl = self::DEFAULT_TTL
    ): PKCEManager {
        return new PKCEManager(
            $this->store,
            $ttl
        );
    }

    /**
     * Codifica un valor utilizando Base64 URL-safe.
     */
    private function base64UrlEncode(string $value): string
    {
        return rtrim(
            strtr(base64_encode($value), '+/', '-_'),
            '='
        );
    }

    /**
     * Comprueba que se pueda generar un challenge PKCE.
     */
    public function testCreatesChallenge(): void
    {
        $this->store
            ->expects($this->once())
            ->method('store');

        $manager = $this->createManager();

        $challenge = $manager->createChallenge(
            'oauth-state-123456789'
        );

        $this->assertIsString($challenge);
        $this->assertNotEmpty($challenge);
    }

    /**
     * El challenge debe utilizar caracteres seguros para URL.
     */
    public function testChallengeIsUrlSafe(): void
    {
        $this->store
            ->method('store');

        $manager = $this->createManager();

        $challenge = $manager->createChallenge(
            'oauth-state-123456789'
        );

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+$/',
            $challenge
        );
    }

    /**
     * El challenge S256 debe tener 43 caracteres.
     */
    public function testChallengeHasCorrectLength(): void
    {
        $this->store
            ->method('store');

        $manager = $this->createManager();

        $challenge = $manager->createChallenge(
            'oauth-state-123456789'
        );

        $this->assertSame(43, strlen($challenge));
    }

    /**
     * Comprueba que el challenge se genere utilizando SHA-256
     * y codificación Base64 URL-safe.
     */
    public function testGeneratesS256Challenge(): void
    {
        $verifier = null;

        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->anything(),
                $this->callback(
                    function (string $storedVerifier) use (&$verifier): bool {
                        $verifier = $storedVerifier;

                        return true;
                    }
                ),
                $this->anything()
            );

        $manager = $this->createManager();

        $challenge = $manager->createChallenge(
            'oauth-state-s256'
        );

        $this->assertNotNull($verifier);

        $expectedChallenge = $this->base64UrlEncode(
            hash('sha256', $verifier, true)
        );

        $this->assertSame(
            $expectedChallenge,
            $challenge
        );
    }

    /**
     * El code verifier debe cumplir con la longitud RFC 7636.
     */
    public function testVerifierHasValidLength(): void
    {
        $verifier = null;

        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->anything(),
                $this->callback(
                    function (string $storedVerifier) use (&$verifier): bool {
                        $verifier = $storedVerifier;

                        return true;
                    }
                ),
                $this->anything()
            );

        $manager = $this->createManager();

        $manager->createChallenge('oauth-state-verifier');

        $this->assertNotNull($verifier);

        $this->assertGreaterThanOrEqual(
            43,
            strlen($verifier)
        );

        $this->assertLessThanOrEqual(
            128,
            strlen($verifier)
        );
    }

    /**
     * El verifier debe utilizar caracteres permitidos por RFC 7636.
     */
    public function testVerifierUsesValidCharacters(): void
    {
        $verifier = null;

        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->anything(),
                $this->callback(
                    function (string $storedVerifier) use (&$verifier): bool {
                        $verifier = $storedVerifier;

                        return true;
                    }
                ),
                $this->anything()
            );

        $manager = $this->createManager();

        $manager->createChallenge('oauth-state-characters');

        $this->assertNotNull($verifier);

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9\-._~]{43,128}$/',
            $verifier
        );
    }

    /**
     * El verifier original no debe ser el challenge.
     */
    public function testVerifierIsDifferentFromChallenge(): void
    {
        $verifier = null;

        $this->store
            ->method('store')
            ->willReturnCallback(
                function (
                    string $stateHash,
                    string $storedVerifier,
                    DateTimeImmutable $expiresAt
                ) use (&$verifier): void {
                    $verifier = $storedVerifier;
                }
            );

        $manager = $this->createManager();

        $challenge = $manager->createChallenge(
            'oauth-state-different-values'
        );

        $this->assertNotNull($verifier);
        $this->assertNotSame($verifier, $challenge);
    }

    /**
     * El verifier debe guardarse asociado al hash del estado OAuth.
     */
    public function testStoresVerifierUsingHashedState(): void
    {
        $state = 'oauth-state-hash-123456789';

        $expectedHash = hash('sha256', $state);

        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->callback(
                    fn (string $stateHash): bool =>
                        hash_equals($expectedHash, $stateHash)
                ),
                $this->isType('string'),
                $this->isInstanceOf(DateTimeImmutable::class)
            );

        $manager = $this->createManager();

        $manager->createChallenge($state);
    }

    /**
     * El estado original no debe almacenarse directamente.
     */
    public function testDoesNotStorePlainState(): void
    {
        $state = 'oauth-state-private-123456789';

        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->callback(
                    fn (string $stateHash): bool =>
                        $stateHash !== $state
                        && hash_equals(
                            hash('sha256', $state),
                            $stateHash
                        )
                ),
                $this->anything(),
                $this->anything()
            );

        $manager = $this->createManager();

        $manager->createChallenge($state);
    }

    /**
     * Cada llamada debe generar un verifier diferente.
     */
    public function testGeneratesUniqueVerifiers(): void
    {
        $verifiers = [];

        $this->store
            ->expects($this->exactly(2))
            ->method('store')
            ->willReturnCallback(
                function (
                    string $stateHash,
                    string $verifier,
                    DateTimeImmutable $expiresAt
                ) use (&$verifiers): void {
                    $verifiers[] = $verifier;
                }
            );

        $manager = $this->createManager();

        $manager->createChallenge('oauth-state-one');
        $manager->createChallenge('oauth-state-two');

        $this->assertCount(2, $verifiers);
        $this->assertNotSame($verifiers[0], $verifiers[1]);
    }

    /**
     * Cada estado debe producir un challenge diferente.
     */
    public function testGeneratesUniqueChallenges(): void
    {
        $this->store
            ->expects($this->exactly(2))
            ->method('store');

        $manager = $this->createManager();

        $firstChallenge = $manager->createChallenge(
            'oauth-state-first'
        );

        $secondChallenge = $manager->createChallenge(
            'oauth-state-second'
        );

        $this->assertNotSame(
            $firstChallenge,
            $secondChallenge
        );
    }

    /**
     * Comprueba que se almacene una fecha de expiración.
     */
    public function testStoresExpirationTime(): void
    {
        $expiration = null;

        $before = new DateTimeImmutable();

        $this->store
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(
                    function (
                        DateTimeImmutable $expiresAt
                    ) use (&$expiration): bool {
                        $expiration = $expiresAt;

                        return true;
                    }
                )
            );

        $manager = $this->createManager(300);

        $manager->createChallenge('oauth-state-expiration');

        $after = new DateTimeImmutable();

        $this->assertNotNull($expiration);

        $this->assertGreaterThanOrEqual(
            $before->getTimestamp() + 300,
            $expiration->getTimestamp()
        );

        $this->assertLessThanOrEqual(
            $after->getTimestamp() + 300,
            $expiration->getTimestamp()
        );
    }

    /**
     * Comprueba que se pueda recuperar un verifier.
     */
    public function testConsumesVerifier(): void
    {
        $state = 'oauth-state-consume-123456789';

        $expectedVerifier = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNO';

        $this->store
            ->expects($this->once())
            ->method('consume')
            ->with(
                hash('sha256', $state),
                $this->isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn($expectedVerifier);

        $manager = $this->createManager();

        $result = $manager->consumeVerifier($state);

        $this->assertSame($expectedVerifier, $result);
    }

    /**
     * Si no existe el verifier, debe devolver null.
     */
    public function testReturnsNullForUnknownVerifier(): void
    {
        $state = 'oauth-state-unknown-verifier';

        $this->store
            ->expects($this->once())
            ->method('consume')
            ->with(
                hash('sha256', $state),
                $this->isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn(null);

        $manager = $this->createManager();

        $this->assertNull(
            $manager->consumeVerifier($state)
        );
    }

    /**
     * El verifier debe recuperarse utilizando el hash del estado.
     */
    public function testConsumesVerifierUsingHashedState(): void
    {
        $state = 'oauth-state-consume-hash';

        $expectedHash = hash('sha256', $state);

        $this->store
            ->expects($this->once())
            ->method('consume')
            ->with(
                $this->callback(
                    fn (string $stateHash): bool =>
                        hash_equals($expectedHash, $stateHash)
                ),
                $this->isInstanceOf(DateTimeImmutable::class)
            )
            ->willReturn('valid-code-verifier');

        $manager = $this->createManager();

        $result = $manager->consumeVerifier($state);

        $this->assertSame(
            'valid-code-verifier',
            $result
        );
    }

    /**
     * Un verifier consumido no debe poder recuperarse nuevamente.
     */
    public function testVerifierCanOnlyBeConsumedOnce(): void
    {
        $state = 'oauth-state-single-use';

        $verifier = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNO';

        $this->store
            ->expects($this->exactly(2))
            ->method('consume')
            ->willReturnOnConsecutiveCalls(
                $verifier,
                null
            );

        $manager = $this->createManager();

        $firstResult = $manager->consumeVerifier($state);
        $secondResult = $manager->consumeVerifier($state);

        $this->assertSame($verifier, $firstResult);
        $this->assertNull($secondResult);
    }

    /**
     * Dos estados diferentes deben mantener sus verifiers independientes.
     */
    public function testDifferentStatesHaveIndependentVerifiers(): void
    {
        $firstState = 'oauth-state-first';
        $secondState = 'oauth-state-second';

        $firstVerifier = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNO';
        $secondVerifier = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklm';

        $this->store
            ->expects($this->exactly(2))
            ->method('consume')
            ->willReturnMap([
                [
                    hash('sha256', $firstState),
                    $this->anything(),
                    $firstVerifier,
                ],
                [
                    hash('sha256', $secondState),
                    $this->anything(),
                    $secondVerifier,
                ],
            ]);

        $manager = $this->createManager();

        $this->assertSame(
            $firstVerifier,
            $manager->consumeVerifier($firstState)
        );

        $this->assertSame(
            $secondVerifier,
            $manager->consumeVerifier($secondState)
        );
    }

    /**
     * Comprueba que se acepte el TTL mínimo.
     */
    public function testAcceptsMinimumTtl(): void
    {
        $this->store
            ->expects($this->once())
            ->method('store');

        $manager = $this->createManager(self::MIN_TTL);

        $challenge = $manager->createChallenge(
            'oauth-state-min-ttl'
        );

        $this->assertNotEmpty($challenge);
    }

    /**
     * Comprueba que se acepte el TTL máximo.
     */
    public function testAcceptsMaximumTtl(): void
    {
        $this->store
            ->expects($this->once())
            ->method('store');

        $manager = $this->createManager(self::MAX_TTL);

        $challenge = $manager->createChallenge(
            'oauth-state-max-ttl'
        );

        $this->assertNotEmpty($challenge);
    }

    /**
     * Rechaza un TTL inferior al mínimo permitido.
     */
    public function testRejectsTtlBelowMinimum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManager(self::MIN_TTL - 1);
    }

    /**
     * Rechaza un TTL superior al máximo permitido.
     */
    public function testRejectsTtlAboveMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createManager(self::MAX_TTL + 1);
    }

    /**
     * No debe guardar un verifier cuando la configuración es inválida.
     */
    public function testInvalidTtlDoesNotStoreVerifier(): void
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

        $this->fail(
            'Se esperaba una excepción por TTL inválido.'
        );
    }
}
