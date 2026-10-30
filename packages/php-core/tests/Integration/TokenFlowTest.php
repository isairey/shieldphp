
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Integration;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Exceptions\ExpiredTokenException;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\JWT\JWTClaims;
use ShieldPHP\Core\JWT\JWTConfig;
use ShieldPHP\Core\JWT\JWTManager;
use ShieldPHP\Core\JWT\TokenBlacklist;
use ShieldPHP\Core\JWT\TokenValidator;

final class TokenFlowTest extends TestCase
{
    private const ISSUER = 'https://api.shieldphp.test';

    private const AUDIENCE = 'shieldphp-api';

    private const SUBJECT = 'user-123';

    private string $signingKey;

    private JWTConfig $config;

    private JWTManager $jwtManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Clave de prueba independiente para cada ejecución.
        $this->signingKey = random_bytes(32);

        $this->config = new JWTConfig(
            signingKey: $this->signingKey,
            issuer: self::ISSUER,
            audience: self::AUDIENCE,
            algorithm: 'HS256',
            accessTokenTtl: 900,
            refreshTokenTtl: 604800,
            leeway: 0
        );

        $this->jwtManager = new JWTManager($this->config);
    }

    /**
     * Comprueba que ShieldPHP genere un JWT correctamente.
     */
    public function testIssuesValidJwt(): void
    {
        $claims = $this->createClaims();

        $token = $this->jwtManager->issue($claims);

        self::assertIsString($token);
        self::assertNotEmpty($token);

        self::assertCount(
            3,
            explode('.', $token)
        );
    }

    /**
     * Comprueba que un JWT generado pueda decodificarse.
     */
    public function testDecodesValidJwt(): void
    {
        $claims = $this->createClaims();

        $token = $this->jwtManager->issue($claims);

        $decoded = $this->jwtManager->decode($token);

        self::assertSame(self::ISSUER, $decoded->iss);
        self::assertSame(self::SUBJECT, $decoded->sub);
        self::assertSame(self::AUDIENCE, $decoded->aud);

        self::assertSame('user', $decoded->role);

        self::assertSame(
            $claims->getTokenId(),
            $decoded->jti
        );

        self::assertIsInt($decoded->iat);
        self::assertIsInt($decoded->exp);
    }

    /**
     * Comprueba que la firma del JWT sea válida.
     */
    public function testValidatesCorrectSignature(): void
    {
        $token = $this->jwtManager->issue(
            $this->createClaims()
        );

        $validator = new TokenValidator(
            jwtManager: $this->jwtManager
        );

        self::assertTrue($validator->isValid($token));
    }

    /**
     * Comprueba que no se acepten JWT modificados.
     */
    public function testRejectsTamperedJwt(): void
    {
        $token = $this->jwtManager->issue(
            $this->createClaims()
        );

        $parts = explode('.', $token);

        // Alterar la firma sin modificar el formato del JWT.
        $parts[2] = ($parts[2][0] === 'A' ? 'B' : 'A')
            . substr($parts[2], 1);

        $tamperedToken = implode('.', $parts);

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($tamperedToken);
    }

    /**
     * Comprueba que se rechacen tokens firmados con otra clave.
     */
    public function testRejectsJwtSignedWithDifferentKey(): void
    {
        $token = $this->jwtManager->issue(
            $this->createClaims()
        );

        $differentConfig = new JWTConfig(
            signingKey: random_bytes(32),
            issuer: self::ISSUER,
            audience: self::AUDIENCE,
            algorithm: 'HS256'
        );

        $differentManager = new JWTManager($differentConfig);

        $this->expectException(InvalidTokenException::class);

        $differentManager->decode($token);
    }

    /**
     * Comprueba que los JWT expirados sean rechazados.
     */
    public function testRejectsExpiredJwt(): void
    {
        $token = $this->createRawToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time() - 120,
            'exp' => time() - 60,
            'jti' => 'expired-token-001',
        ]);

        $this->expectException(ExpiredTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que el emisor sea validado.
     */
    public function testRejectsIncorrectIssuer(): void
    {
        $token = $this->createRawToken([
            'iss' => 'https://malicious.example.com',
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time(),
            'exp' => time() + 900,
            'jti' => 'invalid-issuer-token',
        ]);

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que la audiencia sea validada.
     */
    public function testRejectsIncorrectAudience(): void
    {
        $token = $this->createRawToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => 'unauthorized-application',
            'iat' => time(),
            'exp' => time() + 900,
            'jti' => 'invalid-audience-token',
        ]);

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que no se puedan emitir tokens ya expirados.
     */
    public function testRejectsExpiredClaimsDuringIssuance(): void
    {
        $now = new DateTimeImmutable();

        $claims = new JWTClaims(
            issuer: self::ISSUER,
            subject: self::SUBJECT,
            audience: self::AUDIENCE,
            issuedAt: $now->modify('-2 hours'),
            expiresAt: $now->modify('-1 hour'),
            tokenId: 'expired-claims-001'
        );

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->issue($claims);
    }

    /**
     * Comprueba que los claims no puedan utilizar otro emisor.
     */
    public function testRejectsIncorrectIssuerDuringIssuance(): void
    {
        $now = new DateTimeImmutable();

        $claims = new JWTClaims(
            issuer: 'https://another-api.example.com',
            subject: self::SUBJECT,
            audience: self::AUDIENCE,
            issuedAt: $now,
            expiresAt: $now->modify('+15 minutes'),
            tokenId: 'wrong-issuer-001'
        );

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->issue($claims);
    }

    /**
     * Comprueba que la audiencia incorrecta sea rechazada
     * durante la emisión.
     */
    public function testRejectsIncorrectAudienceDuringIssuance(): void
    {
        $now = new DateTimeImmutable();

        $claims = new JWTClaims(
            issuer: self::ISSUER,
            subject: self::SUBJECT,
            audience: 'another-application',
            issuedAt: $now,
            expiresAt: $now->modify('+15 minutes'),
            tokenId: 'wrong-audience-001'
        );

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->issue($claims);
    }

    /**
     * Comprueba que los tokens revocados sean rechazados.
     */
    public function testRejectsRevokedJwt(): void
    {
        $claims = $this->createClaims();

        $token = $this->jwtManager->issue($claims);

        $blacklist = new TokenBlacklist();

        $blacklist->revoke(
            $claims->getTokenId(),
            $claims->getExpiresAt()
        );

        $validator = new TokenValidator(
            jwtManager: $this->jwtManager,
            revocationStore: $blacklist
        );

        $this->expectException(InvalidTokenException::class);

        $validator->validate($token);
    }

    /**
     * Comprueba que un JWT no revocado sea aceptado.
     */
    public function testAcceptsNonRevokedJwt(): void
    {
        $claims = $this->createClaims();

        $token = $this->jwtManager->issue($claims);

        $blacklist = new TokenBlacklist();

        $validator = new TokenValidator(
            jwtManager: $this->jwtManager,
            revocationStore: $blacklist
        );

        $decoded = $validator->validate($token);

        self::assertSame(self::SUBJECT, $decoded->sub);
        self::assertFalse(
            $blacklist->isRevoked($claims->getTokenId())
        );
    }

    /**
     * Comprueba que se requiera jti cuando hay revocación habilitada.
     */
    public function testRequiresJtiWhenRevocationIsEnabled(): void
    {
        $now = new DateTimeImmutable();

        $claims = new JWTClaims(
            issuer: self::ISSUER,
            subject: self::SUBJECT,
            audience: self::AUDIENCE,
            issuedAt: $now,
            expiresAt: $now->modify('+15 minutes'),
            tokenId: null
        );

        $token = $this->jwtManager->issue($claims);

        $validator = new TokenValidator(
            jwtManager: $this->jwtManager,
            revocationStore: new TokenBlacklist()
        );

        $this->expectException(InvalidTokenException::class);

        $validator->validate($token);
    }

    /**
     * Comprueba que validate() devuelva false para JWT expirados.
     */
    public function testValidatorReturnsFalseForExpiredJwt(): void
    {
        $token = $this->createRawToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time() - 120,
            'exp' => time() - 60,
            'jti' => 'expired-token-002',
        ]);

        $validator = new TokenValidator(
            jwtManager: $this->jwtManager
        );

        self::assertFalse($validator->isValid($token));
    }

    /**
     * Comprueba que no se acepten tokens mal formados.
     */
    public function testRejectsMalformedJwt(): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode('invalid.jwt');
    }

    /**
     * Comprueba que no se acepte un JWT todavía no válido.
     */
    public function testRejectsJwtBeforeNotBeforeTime(): void
    {
        $token = $this->createRawToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time(),
            'nbf' => time() + 3600,
            'exp' => time() + 7200,
            'jti' => 'future-token-001',
        ]);

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que se rechacen tokens sin sujeto.
     */
    public function testRejectsJwtWithoutSubject(): void
    {
        $token = $this->createRawToken([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'iat' => time(),
            'exp' => time() + 900,
            'jti' => 'missing-subject-001',
        ]);

        $this->expectException(InvalidTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que las revocaciones se eliminen al expirar.
     */
    public function testRemovesExpiredRevocations(): void
    {
        $blacklist = new TokenBlacklist();

        $blacklist->revoke(
            'expired-revocation-001',
            new DateTimeImmutable('+5 minutes')
        );

        $blacklist->revoke(
            'expired-revocation-002',
            new DateTimeImmutable('-5 minutes')
        );

        self::assertSame(2, $blacklist->count());

        $removed = $blacklist->removeExpired(
            new DateTimeImmutable()
        );

        self::assertSame(1, $removed);
        self::assertSame(1, $blacklist->count());
        self::assertFalse(
            $blacklist->isRevoked('expired-revocation-002')
        );
    }

    /**
     * Genera los claims de prueba.
     */
    private function createClaims(): JWTClaims
    {
        $now = new DateTimeImmutable();

        return new JWTClaims(
            issuer: self::ISSUER,
            subject: self::SUBJECT,
            audience: self::AUDIENCE,
            issuedAt: $now,
            expiresAt: $now->modify('+15 minutes'),
            tokenId: bin2hex(random_bytes(16)),
            customClaims: [
                'role' => 'user',
                'scope' => [
                    'profile.read',
                    'profile.update',
                ],
            ]
        );
    }

    /**
     * Genera directamente un JWT firmado para pruebas negativas.
     *
     * Permite simular claims inválidos que JWTManager no debería emitir.
     *
     * @param array<string, mixed> $payload
     */
    private function createRawToken(array $payload): string
    {
        return JWT::encode(
            $payload,
            $this->signingKey,
            'HS256'
        );
    }
}
