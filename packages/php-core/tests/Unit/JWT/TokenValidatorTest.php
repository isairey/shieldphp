
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\JWT;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Exceptions\ExpiredTokenException;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\JWT\JWTClaims;
use ShieldPHP\Core\JWT\JWTConfig;
use ShieldPHP\Core\JWT\JWTManager;
use ShieldPHP\Core\JWT\TokenBlacklist;
use ShieldPHP\Core\JWT\TokenValidator;
use stdClass;

final class TokenValidatorTest extends TestCase
{
    private const SIGNING_KEY =
        'shieldphp-test-signing-key-minimum-32-bytes';

    private const ISSUER = 'https://shieldphp.test';

    private const AUDIENCE = 'shieldphp-api';

    private const USER_ID = 'user-123';

    private JWTManager $jwtManager;

    private TokenBlacklist $revocationStore;

    private TokenValidator $tokenValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtManager = $this->createManager();

        $this->revocationStore = new TokenBlacklist();

        $this->tokenValidator = new TokenValidator(
            $this->jwtManager,
            $this->revocationStore
        );
    }

    /**
     * Crea un JWTManager con una configuración personalizada.
     */
    private function createManager(
        ?string $key = null,
        string $issuer = self::ISSUER,
        string $audience = self::AUDIENCE
    ): JWTManager {
        return new JWTManager(
            new JWTConfig(
                $key ?? self::SIGNING_KEY,
                $issuer,
                $audience
            )
        );
    }

    /**
     * Genera claims para las pruebas.
     *
     * @param string|list<string> $audience
     * @param array<string, mixed> $customClaims
     */
    private function createClaims(
        ?string $jwtId = 'jwt-test-123',
        ?int $expiresAt = null,
        ?int $notBefore = null,
        string|array $audience = self::AUDIENCE,
        array $customClaims = [],
        ?string $issuer = null,
        ?int $issuedAt = null
    ): JWTClaims {
        $now = time();

        return new JWTClaims(
            $issuer ?? self::ISSUER,
            self::USER_ID,
            $audience,
            $issuedAt ?? $now,
            $expiresAt ?? ($now + 3600),
            $notBefore,
            $jwtId,
            $customClaims
        );
    }

    /**
     * Genera un token válido para las pruebas.
     */
    private function createValidToken(
        ?string $jwtId = 'jwt-test-123'
    ): string {
        return $this->jwtManager->issue(
            $this->createClaims($jwtId)
        );
    }

    /**
     * Crea una fecha de expiración futura.
     */
    private function futureExpiration(
        int $seconds = 3600
    ): DateTimeImmutable {
        return new DateTimeImmutable(
            sprintf('+%d seconds', $seconds)
        );
    }

    /**
     * Codifica un segmento en Base64URL.
     */
    private function encodeSegment(string $data): string
    {
        return rtrim(
            strtr(base64_encode($data), '+/', '-_'),
            '='
        );
    }

    /**
     * Comprueba que validate() acepte el token.
     */
    private function assertValidToken(string $token): stdClass
    {
        $result = $this->tokenValidator->validate($token);

        $this->assertInstanceOf(stdClass::class, $result);

        return $result;
    }

    /**
     * Comprueba que se pueda crear TokenValidator.
     */
    public function testCreatesTokenValidator(): void
    {
        $validator = new TokenValidator(
            $this->jwtManager
        );

        $this->assertInstanceOf(
            TokenValidator::class,
            $validator
        );
    }

    /**
     * Comprueba que validate() acepte un JWT válido.
     */
    public function testValidateAcceptsValidToken(): void
    {
        $token = $this->createValidToken();

        $result = $this->tokenValidator->validate($token);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    /**
     * Comprueba que validate() devuelva los claims
     * del usuario autenticado.
     */
    public function testValidateReturnsCorrectClaims(): void
    {
        $token = $this->createValidToken('user-token-123');

        $result = $this->tokenValidator->validate($token);

        $this->assertSame(self::ISSUER, $result->iss);
        $this->assertSame(self::USER_ID, $result->sub);
        $this->assertSame(self::AUDIENCE, $result->aud);
        $this->assertSame('user-token-123', $result->jti);
    }

    /**
     * Comprueba que validate() preserve
     * los claims personalizados.
     */
    public function testValidatePreservesCustomClaims(): void
    {
        $token = $this->jwtManager->issue(
            $this->createClaims(
                customClaims: [
                    'role' => 'admin',
                    'verified' => true,
                ]
            )
        );

        $result = $this->tokenValidator->validate($token);

        $this->assertSame('admin', $result->role);
        $this->assertTrue($result->verified);
    }

    /**
     * Comprueba que isValid() devuelva true
     * para un token válido.
     */
    public function testIsValidReturnsTrueForValidToken(): void
    {
        $token = $this->createValidToken();

        $this->assertTrue(
            $this->tokenValidator->isValid($token)
        );
    }

    /**
     * Comprueba que validate() rechace un token malformado.
     */
    public function testValidateRejectsMalformedToken(): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->tokenValidator->validate('invalid.jwt.token');
    }

    /**
     * Comprueba que isValid() devuelva false
     * para un token malformado.
     */
    public function testIsValidReturnsFalseForMalformedToken(): void
    {
        $this->assertFalse(
            $this->tokenValidator->isValid('invalid.jwt.token')
        );
    }

    /**
     * Comprueba que se rechace un token con firma modificada.
     */
    public function testValidateRejectsTamperedToken(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $this->assertCount(3, $parts);

        $parts[2] = $this->encodeSegment(
            'invalid-signature'
        );

        $tamperedToken = implode('.', $parts);

        $this->expectException(InvalidTokenException::class);

        $this->tokenValidator->validate($tamperedToken);
    }

    /**
     * Comprueba que isValid() rechace un token
     * con la firma modificada.
     */
    public function testIsValidReturnsFalseForTamperedToken(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $parts[2] = $this->encodeSegment(
            'invalid-signature'
        );

        $this->assertFalse(
            $this->tokenValidator->isValid(
                implode('.', $parts)
            )
        );
    }

    /**
     * Comprueba que se rechace un token firmado
     * con una clave diferente.
     */
    public function testValidateRejectsTokenWithDifferentKey(): void
    {
        $anotherManager = $this->createManager(
            key: str_repeat('X', 64)
        );

        $token = $anotherManager->issue(
            $this->createClaims()
        );

        $this->expectException(InvalidTokenException::class);

        $this->tokenValidator->validate($token);
    }

    /**
     * Comprueba que se rechace un token expirado.
     */
    public function testValidateRejectsExpiredToken(): void
    {
        $now = time();

        $token = $this->jwtManager->issue(
            $this->createClaims(
                jwtId: 'expired-token',
                expiresAt: $now - 60,
                issuedAt: $now - 3600
            )
        );

        $this->expectException(ExpiredTokenException::class);

        $this->tokenValidator->validate($token);
    }

    /**
     * Comprueba que isValid() devuelva false
     * para un token expirado.
     */
    public function testIsValidReturnsFalseForExpiredToken(): void
    {
        $now = time();

        $token = $this->jwtManager->issue(
            $this->createClaims(
                jwtId: 'expired-token',
                expiresAt: $now - 60,
                issuedAt: $now - 3600
            )
        );

        $this->assertFalse(
            $this->tokenValidator->isValid($token)
        );
    }

    /**
     * Comprueba que se rechace un token
     * que todavía no alcanza su fecha de activación.
     */
    public function testValidateRejectsTokenBeforeNotBefore(): void
    {
        $now = time();

        $token = $this->jwtManager->issue(
            $this->createClaims(
                jwtId: 'future-token',
                expiresAt: $now + 7200,
                notBefore: $now + 3600
            )
        );

        $this->expectException(InvalidTokenException::class);

        $this->tokenValidator->validate($token);
    }

    /**
     * Comprueba que se acepte un token cuyo nbf
     * ya se encuentra en el pasado.
     */
    public function testValidateAcceptsTokenAfterNotBefore(): void
    {
        $now = time();

        $token = $this->jwtManager->issue(
            $this->createClaims(
                jwtId: 'active-token',
                expiresAt: $now + 3600,
                notBefore: $now - 60
            )
        );

        $result = $this->assertValidToken($token);

        $this->assertSame('user-123', $result->sub);
    }

    /**
     * Comprueba que un token válido no sea rechazado
     * cuando no existe un almacén de revocación.
     */
    public function testAcceptsValidTokenWithoutRevocationStore(): void
    {
        $validator = new TokenValidator(
            $this->jwtManager,
            null
        );

        $token = $this->createValidToken();

        $result = $validator->validate($token);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    /**
     * Comprueba que se acepte un token sin jti
     * cuando no hay almacenamiento de revocación.
     */
    public function testAcceptsTokenWithoutJtiWithoutRevocationStore(): void
    {
        $validator = new TokenValidator(
            $this->jwtManager,
            null
        );

        $token = $this->jwtManager->issue(
            $this->createClaims(jwtId: null)
        );

        $result = $validator->validate($token);

        $this->assertSame(self::USER_ID, $result->sub);
    }

    /**
     * Comprueba que el validador rechace un token
     * sin jti cuando se utiliza revocación.
     */
    public function testRejectsTokenWithoutJtiWithRevocationStore(): void
    {
        $token = $this->jwtManager->issue(
            $this->createClaims(jwtId: null)
        );

        $this->expectException(InvalidTokenException::class);

        $this->tokenValidator->validate($token);
    }

    /**
     * Comprueba que se acepte un token
     * cuyo jti no está revocado.
     */
    public function testAcceptsNonRevokedToken(): void
    {
        $jwtId = 'active-jwt-123';

        $token = $this->createValidToken($jwtId);

        $this->assertFalse(
            $this->revocationStore->isRevoked($jwtId)
        );

        $result = $this->tokenValidator->validate($token);

        $this->assertSame($jwtId, $result->jti);
    }

    /**
     * Comprueba que se rechace un token revocado.
     */
    public function testRejectsRevokedToken(): void
    {
        $jwtId = 'revoked-jwt-123';

        $token = $this->createValidToken($jwtId);

        $this->revocationStore->revoke(
            $jwtId,
            $this->futureExpiration()
        );

        $this->expectException(InvalidTokenException::class);

        $this->tokenValidator->validate($token);
    }

    /**
     * Comprueba que isValid() devuelva false
     * para un token revocado.
     */
    public function testIsValidReturnsFalseForRevokedToken(): void
    {
        $jwtId = 'revoked-jwt-456';

        $token = $this->createValidToken($jwtId);

        $this->revocationStore->revoke(
            $jwtId,
            $this->futureExpiration()
        );

        $this->assertFalse(
            $this->tokenValidator->isValid($token)
        );
    }

    /**
     * Comprueba que revocar un token no afecte
     * a otro token con diferente jti.
     */
    public function testRevocationDoesNotAffectOtherTokens(): void
    {
        $revokedId = 'revoked-jwt';
        $activeId = 'active-jwt';

        $revokedToken = $this->createValidToken($revokedId);
        $activeToken = $this->createValidToken($activeId);

        $this->revocationStore->revoke(
            $revokedId,
            $this->futureExpiration()
        );

        $this->assertFalse(
            $this->tokenValidator->isValid($revokedToken)
        );

        $this->assertTrue(
            $this->tokenValidator->isValid($activeToken)
        );
    }

    /**
     * Comprueba que isValid() acepte un token
     * correctamente firmado con claims válidos.
     */
    public function testIsValidReturnsTrueForCompleteValidToken(): void
    {
        $token = $this->jwtManager->issue(
            $this->createClaims(
                jwtId: 'complete-token',
                customClaims: [
                    'role' => 'admin',
                    'verified' => true,
                ]
            )
        );

        $this->assertTrue(
            $this->tokenValidator->isValid($token)
        );
    }

    /**
     * Comprueba que un identificador revocado
     * no afecte a los claims de otros usuarios.
     */
    public function testRevokedTokenDoesNotAffectAnotherSubject(): void
    {
        $firstId = 'user-token-001';
        $secondId = 'user-token-002';

        $firstToken = $this->createValidToken($firstId);
        $secondToken = $this->createValidToken($secondId);

        $this->revocationStore->revoke(
            $firstId,
            $this->futureExpiration()
        );

        $result = $this->tokenValidator->validate($secondToken);

        $this->assertSame(self::USER_ID, $result->sub);
        $this->assertSame($secondId, $result->jti);

        $this->assertFalse(
            $this->tokenValidator->isValid($firstToken)
        );
    }

    /**
     * Comprueba que el token original siga siendo válido
     * después de un intento de validación manipulado.
     */
    public function testOriginalTokenRemainsValidAfterTamperingAttempt(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $parts[2] = $this->encodeSegment('fake-signature');

        $tamperedToken = implode('.', $parts);

        $this->assertFalse(
            $this->tokenValidator->isValid($tamperedToken)
        );

        $this->assertTrue(
            $this->tokenValidator->isValid($token)
        );
    }
}
