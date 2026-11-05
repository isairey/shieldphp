
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\JWT;

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Exceptions\ExpiredTokenException;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\JWT\JWTClaims;
use ShieldPHP\Core\JWT\JWTConfig;
use ShieldPHP\Core\JWT\JWTManager;
use stdClass;

final class JWTManagerTest extends TestCase
{
    private const SIGNING_KEY =
        'shieldphp-test-signing-key-minimum-32-bytes';

    private const ISSUER = 'https://shieldphp.test';

    private const AUDIENCE = 'shieldphp-api';

    private JWTManager $jwtManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtManager = $this->createManager();
    }

    /**
     * Crea un JWTManager con una configuración personalizada.
     */
    private function createManager(
        ?string $key = null,
        string $issuer = self::ISSUER,
        string $audience = self::AUDIENCE,
        string $algorithm = 'HS256',
        int $leeway = 0
    ): JWTManager {
        return new JWTManager(
            new JWTConfig(
                $key ?? self::SIGNING_KEY,
                $issuer,
                $audience,
                $algorithm,
                900,
                604800,
                $leeway
            )
        );
    }

    /**
     * Crea claims válidos para las pruebas.
     *
     * @param string|list<string> $audience
     * @param array<string, mixed> $customClaims
     */
    private function createClaims(
        string|array $audience = self::AUDIENCE,
        ?int $notBefore = null,
        ?string $jwtId = 'test-jwt-123',
        array $customClaims = [],
        ?int $issuedAt = null,
        ?int $expiresAt = null,
        ?string $issuer = null
    ): JWTClaims {
        $now = time();

        return new JWTClaims(
            $issuer ?? self::ISSUER,
            'user-123',
            $audience,
            $issuedAt ?? $now,
            $expiresAt ?? ($now + 3600),
            $notBefore,
            $jwtId,
            $customClaims
        );
    }

    /**
     * Genera un token válido.
     */
    private function createValidToken(): string
    {
        return $this->jwtManager->issue(
            $this->createClaims()
        );
    }

    /**
     * Codifica un segmento Base64URL.
     */
    private function encodeSegment(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }

    /**
     * Comprueba que se pueda crear JWTManager.
     */
    public function testCreatesJWTManager(): void
    {
        $this->assertInstanceOf(
            JWTManager::class,
            $this->jwtManager
        );
    }

    /**
     * Comprueba que issue() devuelva un JWT como cadena.
     */
    public function testIssueReturnsString(): void
    {
        $token = $this->createValidToken();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Comprueba que el token emitido tenga
     * los tres segmentos de un JWT compacto.
     */
    public function testIssueReturnsCompactJwtFormat(): void
    {
        $token = $this->createValidToken();

        $segments = explode('.', $token);

        $this->assertCount(3, $segments);

        foreach ($segments as $segment) {
            $this->assertNotEmpty($segment);
        }
    }

    /**
     * Comprueba que decode() devuelva un objeto stdClass.
     */
    public function testDecodeReturnsStdClass(): void
    {
        $token = $this->createValidToken();

        $decoded = $this->jwtManager->decode($token);

        $this->assertInstanceOf(stdClass::class, $decoded);
    }

    /**
     * Comprueba que se conserven los claims registrados.
     */
    public function testDecodePreservesRegisteredClaims(): void
    {
        $now = time();

        $claims = $this->createClaims(
            issuedAt: $now,
            expiresAt: $now + 3600
        );

        $token = $this->jwtManager->issue($claims);

        $decoded = $this->jwtManager->decode($token);

        $this->assertSame(self::ISSUER, $decoded->iss);
        $this->assertSame('user-123', $decoded->sub);
        $this->assertSame(self::AUDIENCE, $decoded->aud);
        $this->assertSame($now, $decoded->iat);
        $this->assertSame($now + 3600, $decoded->exp);
        $this->assertSame('test-jwt-123', $decoded->jti);
    }

    /**
     * Comprueba que se conserven los claims personalizados.
     */
    public function testDecodePreservesCustomClaims(): void
    {
        $customClaims = [
            'role' => 'admin',
            'permissions' => [
                'users.read',
                'users.create',
            ],
            'verified' => true,
        ];

        $token = $this->jwtManager->issue(
            $this->createClaims(
                customClaims: $customClaims
            )
        );

        $decoded = $this->jwtManager->decode($token);

        $this->assertSame('admin', $decoded->role);

        $this->assertSame(
            [
                'users.read',
                'users.create',
            ],
            $decoded->permissions
        );

        $this->assertTrue($decoded->verified);
    }

    /**
     * Comprueba que validate() acepte un token válido.
     */
    public function testValidateReturnsTrueForValidToken(): void
    {
        $token = $this->createValidToken();

        $this->assertTrue(
            $this->jwtManager->validate($token)
        );
    }

    /**
     * Comprueba que validate() rechace un token inválido.
     */
    public function testValidateReturnsFalseForInvalidToken(): void
    {
        $this->assertFalse(
            $this->jwtManager->validate('invalid.jwt.token')
        );
    }

    /**
     * Comprueba que decode() rechace tokens malformados.
     */
    public function testDecodeRejectsMalformedToken(): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode('invalid.jwt.token');
    }

    /**
     * Comprueba que una modificación del payload
     * invalide la firma del JWT.
     */
    public function testDecodeRejectsModifiedPayload(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $payloadJson = base64_decode(
            strtr(
                $parts[1],
                '-_',
                '+/'
            ) . str_repeat(
                '=',
                (4 - strlen($parts[1]) % 4) % 4
            ),
            true
        );

        $this->assertNotFalse($payloadJson);

        $payload = json_decode(
            $payloadJson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $payload['sub'] = 'attacker-999';

        $parts[1] = $this->encodeSegment(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $tamperedToken = implode('.', $parts);

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($tamperedToken);
    }

    /**
     * Comprueba que modificar la firma invalide el JWT.
     */
    public function testDecodeRejectsModifiedSignature(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $signature = base64_decode(
            strtr($parts[2], '-_', '+/') .
            str_repeat(
                '=',
                (4 - strlen($parts[2]) % 4) % 4
            ),
            true
        );

        $this->assertNotFalse($signature);

        $signature[0] = chr(
            ord($signature[0]) ^ 0x01
        );

        $parts[2] = $this->encodeSegment($signature);

        $tamperedToken = implode('.', $parts);

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($tamperedToken);
    }

    /**
     * Comprueba que un token firmado con otra clave
     * sea rechazado.
     */
    public function testRejectsTokenSignedWithDifferentKey(): void
    {
        $token = $this->createValidToken();

        $differentKey = str_repeat('X', 64);

        $anotherManager = $this->createManager(
            key: $differentKey
        );

        $this->expectException(InvalidTokenException::class);

        $anotherManager->decode($token);
    }

    /**
     * Comprueba que se rechace un token con otro issuer.
     */
    public function testRejectsTokenWithDifferentIssuer(): void
    {
        $anotherManager = $this->createManager(
            issuer: 'https://attacker.example'
        );

        $token = $anotherManager->issue(
            $this->createClaims(
                issuer: 'https://attacker.example'
            )
        );

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que se rechace un token con otra audience.
     */
    public function testRejectsTokenWithDifferentAudience(): void
    {
        $anotherManager = $this->createManager(
            audience: 'another-api'
        );

        $token = $anotherManager->issue(
            $this->createClaims(
                audience: 'another-api'
            )
        );

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que se rechace un token firmado
     * con un algoritmo diferente al configurado.
     */
    public function testRejectsDifferentSigningAlgorithm(): void
    {
        $anotherManager = $this->createManager(
            algorithm: 'HS384'
        );

        $token = $anotherManager->issue(
            $this->createClaims()
        );

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que decode() lance ExpiredTokenException
     * cuando el JWT ya expiró.
     */
    public function testRejectsExpiredToken(): void
    {
        $now = time();

        $claims = $this->createClaims(
            issuedAt: $now - 3600,
            expiresAt: $now - 60
        );

        $token = $this->jwtManager->issue($claims);

        $this->expectException(ExpiredTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que validate() devuelva false
     * para un token expirado.
     */
    public function testValidateReturnsFalseForExpiredToken(): void
    {
        $now = time();

        $token = $this->jwtManager->issue(
            $this->createClaims(
                issuedAt: $now - 3600,
                expiresAt: $now - 60
            )
        );

        $this->assertFalse(
            $this->jwtManager->validate($token)
        );
    }

    /**
     * Comprueba que se rechace un token cuya activación
     * está programada para un momento futuro.
     */
    public function testRejectsTokenBeforeNotBefore(): void
    {
        $now = time();

        $token = $this->jwtManager->issue(
            $this->createClaims(
                notBefore: $now + 3600,
                issuedAt: $now,
                expiresAt: $now + 7200
            )
        );

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que se acepte un token cuyo nbf
     * ya se encuentra en el pasado.
     */
    public function testAcceptsTokenAfterNotBefore(): void
    {
        $now = time();

        $token = $this->jwtManager->issue(
            $this->createClaims(
                notBefore: $now - 60,
                issuedAt: $now - 60,
                expiresAt: $now + 3600
            )
        );

        $decoded = $this->jwtManager->decode($token);

        $this->assertSame('user-123', $decoded->sub);
    }

    /**
     * Comprueba que se acepten tokens con múltiples audiencias
     * cuando incluyen la audiencia configurada.
     */
    public function testSupportsMultipleAudiences(): void
    {
        $token = $this->jwtManager->issue(
            $this->createClaims(
                audience: [
                    'mobile-api',
                    self::AUDIENCE,
                    'admin-api',
                ]
            )
        );

        $decoded = $this->jwtManager->decode($token);

        $this->assertIsArray($decoded->aud);

        $this->assertContains(
            self::AUDIENCE,
            $decoded->aud
        );
    }

    /**
     * Comprueba que se rechace un JWT sin subject.
     */
    public function testRejectsTokenWithoutSubject(): void
    {
        $now = time();

        $payload = [
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'iat' => $now,
            'exp' => $now + 3600,
            'jti' => 'token-without-subject',
        ];

        $token = JWT::encode(
            $payload,
            self::SIGNING_KEY,
            'HS256'
        );

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que JWTManager restaure
     * el leeway global después de decodificar correctamente.
     */
    public function testRestoresGlobalLeewayAfterSuccessfulDecode(): void
    {
        $originalLeeway = JWT::$leeway;

        try {
            JWT::$leeway = 7;

            $token = $this->createValidToken();

            $this->jwtManager->decode($token);

            $this->assertSame(7, JWT::$leeway);
        } finally {
            JWT::$leeway = $originalLeeway;
        }
    }

    /**
     * Comprueba que JWTManager también restaure
     * el leeway global cuando la decodificación falla.
     */
    public function testRestoresGlobalLeewayAfterFailedDecode(): void
    {
        $originalLeeway = JWT::$leeway;

        try {
            JWT::$leeway = 11;

            try {
                $this->jwtManager->decode('invalid.jwt.token');

                self::fail(
                    'Invalid token should have been rejected.'
                );
            } catch (InvalidTokenException) {
                $this->assertSame(11, JWT::$leeway);
            }
        } finally {
            JWT::$leeway = $originalLeeway;
        }
    }
}
