
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\JWT;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\JWT\JWTClaims;

final class JWTClaimsTest extends TestCase
{
    private const ISSUER = 'https://shieldphp.test';

    private const SUBJECT = 'user-123';

    private const AUDIENCE = 'shieldphp-api';

    private JWTClaims $claims;

    protected function setUp(): void
    {
        parent::setUp();

        $this->claims = $this->createClaims();
    }

    /**
     * Crea un conjunto de claims válido para las pruebas.
     *
     * @param string|list<string> $audience
     * @param array<string, mixed> $customClaims
     */
    private function createClaims(
        string|array $audience = self::AUDIENCE,
        ?int $notBefore = null,
        ?string $jwtId = null,
        array $customClaims = [],
        ?int $issuedAt = null,
        ?int $expiresAt = null
    ): JWTClaims {
        $now = time();

        return new JWTClaims(
            self::ISSUER,
            self::SUBJECT,
            $audience,
            $issuedAt ?? $now,
            $expiresAt ?? ($now + 3600),
            $notBefore,
            $jwtId,
            $customClaims
        );
    }

    /**
     * Comprueba que se pueda crear JWTClaims.
     */
    public function testCreatesJWTClaimsInstance(): void
    {
        $claims = $this->createClaims();

        $this->assertInstanceOf(
            JWTClaims::class,
            $claims
        );
    }

    /**
     * Comprueba que toArray() devuelva un array.
     */
    public function testToArrayReturnsArray(): void
    {
        $result = $this->claims->toArray();

        $this->assertIsArray($result);
    }

    /**
     * Comprueba que se serialicen correctamente
     * los claims registrados principales.
     */
    public function testSerializesRegisteredClaims(): void
    {
        $now = time();

        $claims = $this->createClaims(
            issuedAt: $now,
            expiresAt: $now + 3600
        );

        $result = $claims->toArray();

        $this->assertSame(
            self::ISSUER,
            $result['iss']
        );

        $this->assertSame(
            self::SUBJECT,
            $result['sub']
        );

        $this->assertSame(
            self::AUDIENCE,
            $result['aud']
        );

        $this->assertSame(
            $now,
            $result['iat']
        );

        $this->assertSame(
            $now + 3600,
            $result['exp']
        );
    }

    /**
     * Comprueba que se acepte una audiencia representada
     * mediante una cadena.
     */
    public function testSupportsStringAudience(): void
    {
        $claims = $this->createClaims(
            audience: 'shieldphp-api'
        );

        $result = $claims->toArray();

        $this->assertSame(
            'shieldphp-api',
            $result['aud']
        );
    }

    /**
     * Comprueba que se acepte una lista de audiencias.
     */
    public function testSupportsMultipleAudiences(): void
    {
        $audiences = [
            'shieldphp-api',
            'shieldphp-mobile',
            'shieldphp-admin',
        ];

        $claims = $this->createClaims(
            audience: $audiences
        );

        $result = $claims->toArray();

        $this->assertSame(
            $audiences,
            $result['aud']
        );
    }

    /**
     * Comprueba que se incluya nbf cuando se proporciona.
     */
    public function testIncludesNotBeforeClaim(): void
    {
        $now = time();

        $claims = $this->createClaims(
            notBefore: $now + 300
        );

        $result = $claims->toArray();

        $this->assertArrayHasKey('nbf', $result);

        $this->assertSame(
            $now + 300,
            $result['nbf']
        );
    }

    /**
     * Comprueba que se incluya jti cuando se proporciona.
     */
    public function testIncludesJwtIdClaim(): void
    {
        $jwtId = 'token-unique-123';

        $claims = $this->createClaims(
            jwtId: $jwtId
        );

        $result = $claims->toArray();

        $this->assertArrayHasKey('jti', $result);

        $this->assertSame(
            $jwtId,
            $result['jti']
        );
    }

    /**
     * Comprueba que los claims opcionales nbf y jti
     * se omitan cuando no se proporcionan.
     */
    public function testOmitsOptionalClaimsWhenNotProvided(): void
    {
        $claims = $this->createClaims();

        $result = $claims->toArray();

        $this->assertArrayNotHasKey('nbf', $result);
        $this->assertArrayNotHasKey('jti', $result);
    }

    /**
     * Comprueba que se incluyan los claims personalizados.
     */
    public function testIncludesCustomClaims(): void
    {
        $customClaims = [
            'role' => 'admin',
            'permissions' => [
                'users.read',
                'users.create',
            ],
        ];

        $claims = $this->createClaims(
            customClaims: $customClaims
        );

        $result = $claims->toArray();

        $this->assertSame(
            'admin',
            $result['role']
        );

        $this->assertSame(
            [
                'users.read',
                'users.create',
            ],
            $result['permissions']
        );
    }

    /**
     * Comprueba que se conserven estructuras anidadas
     * dentro de los claims personalizados.
     */
    public function testPreservesNestedCustomClaims(): void
    {
        $customClaims = [
            'user' => [
                'id' => 'user-123',
                'profile' => [
                    'department' => 'development',
                    'level' => 3,
                ],
            ],
        ];

        $claims = $this->createClaims(
            customClaims: $customClaims
        );

        $result = $claims->toArray();

        $this->assertSame(
            $customClaims['user'],
            $result['user']
        );
    }

    /**
     * Comprueba que no se puedan sobrescribir
     * los claims reservados mediante datos personalizados.
     */
    public function testRejectsReservedClaimOverride(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createClaims(
            customClaims: [
                'sub' => 'attacker-999',
            ]
        );
    }

    /**
     * Comprueba que un token cuya expiración
     * se encuentra en el futuro no esté expirado.
     */
    public function testIsExpiredReturnsFalseForValidClaims(): void
    {
        $claims = $this->createClaims(
            expiresAt: time() + 3600
        );

        $this->assertFalse(
            $claims->isExpired()
        );
    }

    /**
     * Comprueba que un token cuya expiración
     * se encuentra en el pasado esté expirado.
     */
    public function testIsExpiredReturnsTrueForExpiredClaims(): void
    {
        $claims = $this->createClaims(
            expiresAt: time() - 60
        );

        $this->assertTrue(
            $claims->isExpired()
        );
    }

    /**
     * Comprueba que un token sin nbf no esté
     * pendiente de activación.
     */
    public function testIsNotYetValidReturnsFalseWithoutNotBefore(): void
    {
        $claims = $this->createClaims(
            notBefore: null
        );

        $this->assertFalse(
            $claims->isNotYetValid()
        );
    }

    /**
     * Comprueba que un token con nbf futuro
     * todavía no sea válido.
     */
    public function testIsNotYetValidReturnsTrueForFutureNotBefore(): void
    {
        $claims = $this->createClaims(
            notBefore: time() + 3600
        );

        $this->assertTrue(
            $claims->isNotYetValid()
        );
    }

    /**
     * Comprueba que un token cuyo nbf está en el pasado
     * ya pueda considerarse activado.
     */
    public function testIsNotYetValidReturnsFalseForPastNotBefore(): void
    {
        $claims = $this->createClaims(
            notBefore: time() - 60
        );

        $this->assertFalse(
            $claims->isNotYetValid()
        );
    }

    /**
     * Comprueba que los claims con issuedAt anterior
     * conserven correctamente su timestamp.
     */
    public function testSupportsPastIssuedAt(): void
    {
        $issuedAt = time() - 3600;

        $claims = $this->createClaims(
            issuedAt: $issuedAt,
            expiresAt: time() + 3600
        );

        $result = $claims->toArray();

        $this->assertSame(
            $issuedAt,
            $result['iat']
        );
    }

    /**
     * Comprueba que el claim jti conserve su valor
     * durante la serialización.
     */
    public function testPreservesJwtIdDuringSerialization(): void
    {
        $jwtId = 'jwt-session-987654';

        $claims = $this->createClaims(
            jwtId: $jwtId
        );

        $firstResult = $claims->toArray();
        $secondResult = $claims->toArray();

        $this->assertSame(
            $firstResult['jti'],
            $secondResult['jti']
        );

        $this->assertSame(
            $jwtId,
            $secondResult['jti']
        );
    }

    /**
     * Comprueba que dos instancias mantengan
     * sus respectivos claims independientes.
     */
    public function testClaimsInstancesRemainIndependent(): void
    {
        $firstClaims = $this->createClaims(
            customClaims: [
                'role' => 'admin',
            ]
        );

        $secondClaims = $this->createClaims(
            customClaims: [
                'role' => 'user',
            ]
        );

        $this->assertSame(
            'admin',
            $firstClaims->toArray()['role']
        );

        $this->assertSame(
            'user',
            $secondClaims->toArray()['role']
        );
    }

    /**
     * Comprueba que serializar los claims a JSON
     * produzca una estructura válida.
     */
    public function testClaimsCanBeSerializedToJson(): void
    {
        $claims = $this->createClaims(
            jwtId: 'jwt-123',
            customClaims: [
                'role' => 'admin',
            ]
        );

        $json = json_encode(
            $claims->toArray(),
            JSON_THROW_ON_ERROR
        );

        $this->assertIsString($json);
        $this->assertNotEmpty($json);

        $decoded = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame(
            self::ISSUER,
            $decoded['iss']
        );

        $this->assertSame(
            self::SUBJECT,
            $decoded['sub']
        );

        $this->assertSame(
            'admin',
            $decoded['role']
        );
    }

    /**
     * Comprueba que consultar y serializar los claims
     * repetidamente no altere su información.
     */
    public function testSerializationDoesNotChangeClaims(): void
    {
        $claims = $this->createClaims(
            jwtId: 'jwt-stable-123',
            customClaims: [
                'role' => 'admin',
                'verified' => true,
            ]
        );

        $original = $claims->toArray();

        $claims->isExpired();
        $claims->isNotYetValid();

        $claims->toArray();
        $claims->toArray();

        $this->assertSame(
            $original,
            $claims->toArray()
        );
    }
}
