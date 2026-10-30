
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Security;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\JWT\JWTClaims;
use ShieldPHP\Core\JWT\JWTConfig;
use ShieldPHP\Core\JWT\JWTManager;

final class TokenTamperingTest extends TestCase
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
     * Crea un JWTManager con la clave indicada.
     */
    private function createManager(?string $key = null): JWTManager
    {
        return new JWTManager(
            new JWTConfig(
                $key ?? self::SIGNING_KEY,
                self::ISSUER,
                self::AUDIENCE
            )
        );
    }

    /**
     * Genera un token válido para las pruebas.
     */
    private function createValidToken(): string
    {
        $now = time();

        $claims = new JWTClaims(
            self::ISSUER,
            'user-123',
            self::AUDIENCE,
            $now,
            $now + 3600,
            null,
            'test-token-id'
        );

        return $this->jwtManager->issue($claims);
    }

    /**
     * Codifica un segmento en Base64 URL-safe.
     */
    private function encodeSegment(string $value): string
    {
        return rtrim(
            strtr(base64_encode($value), '+/', '-_'),
            '='
        );
    }

    /**
     * Decodifica un segmento Base64 URL-safe.
     *
     * @return array<string, mixed>
     */
    private function decodeJsonSegment(string $segment): array
    {
        $decoded = base64_decode(
            strtr($segment, '-_', '+/') .
            str_repeat('=', (4 - strlen($segment) % 4) % 4),
            true
        );

        if ($decoded === false) {
            throw new \RuntimeException('Invalid Base64 segment.');
        }

        $data = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JWT JSON segment.');
        }

        return $data;
    }

    /**
     * Comprueba que decode() rechace un token.
     */
    private function assertTokenRejected(string $token): void
    {
        try {
            $this->jwtManager->decode($token);

            self::fail(
                'The JWT should have been rejected.'
            );
        } catch (InvalidTokenException) {
            self::assertTrue(true);
        }
    }

    /**
     * Comprueba que un token original sea válido.
     */
    public function testAcceptsOriginalValidToken(): void
    {
        $token = $this->createValidToken();

        $decoded = $this->jwtManager->decode($token);

        $this->assertSame('user-123', $decoded->sub);
        $this->assertSame(self::ISSUER, $decoded->iss);
        $this->assertTrue($this->jwtManager->validate($token));
    }

    /**
     * Comprueba que una modificación del payload invalide la firma.
     */
    public function testRejectsModifiedPayload(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $this->assertCount(3, $parts);

        $payload = $this->decodeJsonSegment($parts[1]);

        $payload['sub'] = 'attacker-999';

        $modifiedPayload = $this->encodeSegment(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $tamperedToken = implode('.', [
            $parts[0],
            $modifiedPayload,
            $parts[2],
        ]);

        $this->assertTokenRejected($tamperedToken);
    }

    /**
     * Comprueba que no se pueda cambiar el usuario del token.
     */
    public function testRejectsSubjectTampering(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $payload = $this->decodeJsonSegment($parts[1]);

        $payload['sub'] = 'admin';

        $parts[1] = $this->encodeSegment(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->assertTokenRejected(implode('.', $parts));
    }

    /**
     * Comprueba que modificar el issuer invalide el token.
     */
    public function testRejectsIssuerTampering(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $payload = $this->decodeJsonSegment($parts[1]);

        $payload['iss'] = 'https://attacker.example';

        $parts[1] = $this->encodeSegment(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->assertTokenRejected(implode('.', $parts));
    }

    /**
     * Comprueba que modificar el audience invalide el token.
     */
    public function testRejectsAudienceTampering(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $payload = $this->decodeJsonSegment($parts[1]);

        $payload['aud'] = 'attacker-api';

        $parts[1] = $this->encodeSegment(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->assertTokenRejected(implode('.', $parts));
    }

    /**
     * Comprueba que modificar la expiración no permita extender
     * artificialmente la duración del token.
     */
    public function testRejectsExpirationTampering(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $payload = $this->decodeJsonSegment($parts[1]);

        $payload['exp'] = time() + 86400 * 365;

        $parts[1] = $this->encodeSegment(
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->assertTokenRejected(implode('.', $parts));
    }

    /**
     * Comprueba que cambiar el algoritmo por "none" sea rechazado.
     */
    public function testRejectsNoneAlgorithm(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $header = $this->decodeJsonSegment($parts[0]);

        $header['alg'] = 'none';

        $parts[0] = $this->encodeSegment(
            json_encode($header, JSON_THROW_ON_ERROR)
        );

        $this->assertTokenRejected(implode('.', $parts));
    }

    /**
     * Comprueba que no se pueda sustituir el algoritmo HS256
     * por otro algoritmo.
     */
    public function testRejectsAlgorithmSubstitution(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $header = $this->decodeJsonSegment($parts[0]);

        $header['alg'] = 'HS512';

        $parts[0] = $this->encodeSegment(
            json_encode($header, JSON_THROW_ON_ERROR)
        );

        $this->assertTokenRejected(implode('.', $parts));
    }

    /**
     * Comprueba que modificar un byte de la firma invalide el JWT.
     */
    public function testRejectsModifiedSignature(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $signature = base64_decode(
            strtr($parts[2], '-_', '+/') .
            str_repeat('=', (4 - strlen($parts[2]) % 4) % 4),
            true
        );

        $this->assertNotFalse($signature);
        $this->assertNotSame('', $signature);

        $signature[0] = chr(ord($signature[0]) ^ 0x01);

        $parts[2] = $this->encodeSegment($signature);

        $this->assertTokenRejected(implode('.', $parts));
    }

    /**
     * Comprueba que eliminar parte de la firma invalide el token.
     */
    public function testRejectsTruncatedSignature(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $parts[2] = substr($parts[2], 0, -5);

        $this->assertTokenRejected(implode('.', $parts));
    }

    /**
     * Comprueba que un token firmado con otra clave sea rechazado.
     */
    public function testRejectsTokenSignedWithDifferentKey(): void
    {
        $token = $this->createValidToken();

        $differentManager = $this->createManager(
            'another-different-secret-key-32-bytes'
        );

        try {
            $differentManager->decode($token);

            self::fail(
                'A token signed with another key must be rejected.'
            );
        } catch (InvalidTokenException) {
            self::assertTrue(true);
        }
    }

    /**
     * Comprueba que los tokens incompletos sean rechazados.
     */
    public function testRejectsIncompleteTokens(): void
    {
        $invalidTokens = [
            '',
            'invalid-token',
            'header.payload',
            'header.payload.signature.extra',
            '...',
            'a.b.c',
        ];

        foreach ($invalidTokens as $token) {
            $this->assertTokenRejected($token);
        }
    }

    /**
     * Comprueba que agregar contenido al final del JWT
     * no sea aceptado.
     */
    public function testRejectsAppendedTokenData(): void
    {
        $token = $this->createValidToken();

        $tamperedToken = $token . '.extra-data';

        $this->assertTokenRejected($tamperedToken);
    }

    /**
     * Comprueba que validate() devuelva false para un JWT alterado.
     */
    public function testValidateReturnsFalseForTamperedToken(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $parts[2] = $this->encodeSegment('invalid-signature');

        $tamperedToken = implode('.', $parts);

        $this->assertFalse(
            $this->jwtManager->validate($tamperedToken)
        );
    }

    /**
     * Comprueba que intentar utilizar un token alterado
     * no invalide el token original.
     */
    public function testOriginalTokenRemainsValidAfterTamperingAttempt(): void
    {
        $token = $this->createValidToken();

        $parts = explode('.', $token);

        $parts[2] = $this->encodeSegment('fake-signature');

        $tamperedToken = implode('.', $parts);

        $this->assertFalse(
            $this->jwtManager->validate($tamperedToken)
        );

        $this->assertTrue(
            $this->jwtManager->validate($token)
        );
    }
}
