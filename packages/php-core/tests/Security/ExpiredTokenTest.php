
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Security;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Exceptions\ExpiredTokenException;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\JWT\JWTConfig;
use ShieldPHP\Core\JWT\JWTManager;
use ShieldPHP\Core\JWT\TokenValidator;

final class ExpiredTokenTest extends TestCase
{
    private const ISSUER = 'https://api.shieldphp.test';

    private const AUDIENCE = 'shieldphp-api';

    private const SUBJECT = 'user-expiration-test';

    private string $signingKey;

    private JWTManager $jwtManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Generar una clave independiente para las pruebas.
        $this->signingKey = random_bytes(32);

        $config = new JWTConfig(
            signingKey: $this->signingKey,
            issuer: self::ISSUER,
            audience: self::AUDIENCE,
            algorithm: 'HS256',
            accessTokenTtl: 900,
            refreshTokenTtl: 604800,
            leeway: 0
        );

        $this->jwtManager = new JWTManager($config);
    }

    /**
     * Comprueba que un JWT expirado sea rechazado.
     */
    public function testRejectsExpiredToken(): void
    {
        $token = $this->createExpiredToken();

        $this->expectException(ExpiredTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que un JWT con firma válida pero expiración
     * vencida tampoco pueda utilizarse.
     */
    public function testRejectsExpiredTokenWithValidSignature(): void
    {
        $token = $this->createToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'jti' => 'expired-valid-signature-001',
        ]);

        $this->expectException(ExpiredTokenException::class);

        $this->jwtManager->decode($token);
    }

    /**
     * Comprueba que TokenValidator rechace los tokens expirados.
     */
    public function testValidatorRejectsExpiredToken(): void
    {
        $token = $this->createExpiredToken();

        $validator = new TokenValidator(
            jwtManager: $this->jwtManager
        );

        $this->expectException(ExpiredTokenException::class);

        $validator->validate($token);
    }

    /**
     * Comprueba que isValid() devuelva false para un JWT expirado.
     */
    public function testIsValidReturnsFalseForExpiredToken(): void
    {
        $token = $this->createExpiredToken();

        $validator = new TokenValidator(
            jwtManager: $this->jwtManager
        );

        self::assertFalse(
            $validator->isValid($token)
        );
    }

    /**
     * Comprueba que un token vigente no sea rechazado.
     */
    public function testAcceptsTokenThatHasNotExpired(): void
    {
        $token = $this->createToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time(),
            'exp' => time() + 900,
            'jti' => 'valid-token-001',
        ]);

        $claims = $this->jwtManager->decode($token);

        self::assertSame(
            self::SUBJECT,
            $claims->sub
        );

        self::assertGreaterThan(
            time(),
            $claims->exp
        );
    }

    /**
     * Comprueba que un token todavía no válido sea rechazado.
     */
    public function testRejectsTokenBeforeExpirationWindowStarts(): void
    {
        $token = $this->createToken([
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
     * Comprueba que los tokens expirados no puedan emitirse.
     */
    public function testCannotIssueAlreadyExpiredClaims(): void
    {
        $now = new DateTimeImmutable();

        $claims = new \ShieldPHP\Core\JWT\JWTClaims(
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
     * Comprueba que se respete la tolerancia de reloj configurada.
     *
     * Un token expirado hace menos tiempo que el leeway puede
     * aceptarse temporalmente por diferencias entre relojes.
     */
    public function testAcceptsRecentlyExpiredTokenWithinLeeway(): void
    {
        $config = new JWTConfig(
            signingKey: $this->signingKey,
            issuer: self::ISSUER,
            audience: self::AUDIENCE,
            algorithm: 'HS256',
            accessTokenTtl: 900,
            refreshTokenTtl: 604800,
            leeway: 60
        );

        $manager = new JWTManager($config);

        $token = $this->createToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time() - 120,
            'exp' => time() - 30,
            'jti' => 'leeway-token-001',
        ]);

        $claims = $manager->decode($token);

        self::assertSame(
            self::SUBJECT,
            $claims->sub
        );
    }

    /**
     * Comprueba que un token excedido del leeway sea rechazado.
     */
    public function testRejectsTokenBeyondConfiguredLeeway(): void
    {
        $config = new JWTConfig(
            signingKey: $this->signingKey,
            issuer: self::ISSUER,
            audience: self::AUDIENCE,
            algorithm: 'HS256',
            accessTokenTtl: 900,
            refreshTokenTtl: 604800,
            leeway: 30
        );

        $manager = new JWTManager($config);

        $token = $this->createToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time() - 300,
            'exp' => time() - 120,
            'jti' => 'expired-beyond-leeway-001',
        ]);

        $this->expectException(ExpiredTokenException::class);

        $manager->decode($token);
    }

    /**
     * Comprueba que la excepción de expiración pertenezca
     * a la jerarquía de excepciones de tokens.
     */
    public function testExpiredExceptionInheritsFromInvalidTokenException(): void
    {
        $exception = new ExpiredTokenException();

        self::assertInstanceOf(
            InvalidTokenException::class,
            $exception
        );
    }

    /**
     * Genera un JWT cuya fecha de expiración ya pasó.
     */
    private function createExpiredToken(): string
    {
        return $this->createToken([
            'iss' => self::ISSUER,
            'sub' => self::SUBJECT,
            'aud' => self::AUDIENCE,
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'jti' => bin2hex(random_bytes(16)),
        ]);
    }

    /**
     * Firma directamente un JWT para probar escenarios negativos.
     *
     * @param array<string, mixed> $payload
     */
    private function createToken(array $payload): string
    {
        return JWT::encode(
            $payload,
            $this->signingKey,
            'HS256'
        );
    }
}
