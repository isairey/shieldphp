
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\OAuth;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\OAuth\TokenResponse;

final class TokenResponseTest extends TestCase
{
    private const ACCESS_TOKEN = 'oauth-access-token-example-123456789';

    private const TOKEN_TYPE = 'Bearer';

    private const REFRESH_TOKEN = 'oauth-refresh-token-example-987654321';

    private const SCOPE = 'openid profile email';

    private const ID_TOKEN = 'oauth-id-token-example-123456789';

    private DateTimeImmutable $receivedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->receivedAt = new DateTimeImmutable(
            '2026-01-15 12:00:00 UTC'
        );
    }

    /**
     * Crea una respuesta OAuth para reutilizar en las pruebas.
     */
    private function createTokenResponse(
        ?int $expiresIn = 3600,
        ?string $refreshToken = self::REFRESH_TOKEN,
        ?string $scope = self::SCOPE,
        ?string $idToken = self::ID_TOKEN,
        ?DateTimeImmutable $receivedAt = null
    ): TokenResponse {
        return new TokenResponse(
            self::ACCESS_TOKEN,
            self::TOKEN_TYPE,
            $expiresIn,
            $refreshToken,
            $scope,
            $idToken,
            $receivedAt ?? $this->receivedAt
        );
    }

    /**
     * Comprueba que se pueda crear una respuesta OAuth.
     */
    public function testCanBeInstantiated(): void
    {
        $response = $this->createTokenResponse();

        $this->assertInstanceOf(
            TokenResponse::class,
            $response
        );
    }

    /**
     * Comprueba que se almacene correctamente el access token.
     */
    public function testStoresAccessToken(): void
    {
        $response = $this->createTokenResponse();

        $this->assertSame(
            self::ACCESS_TOKEN,
            $response->getAccessToken()
        );
    }

    /**
     * Comprueba que se almacene correctamente el tipo de token.
     */
    public function testStoresTokenType(): void
    {
        $response = $this->createTokenResponse();

        $this->assertSame(
            self::TOKEN_TYPE,
            $response->getTokenType()
        );
    }

    /**
     * Comprueba que se almacene correctamente el tiempo de expiración.
     */
    public function testStoresExpiresIn(): void
    {
        $response = $this->createTokenResponse(1800);

        $this->assertSame(
            1800,
            $response->getExpiresIn()
        );
    }

    /**
     * Comprueba que se almacene correctamente el refresh token.
     */
    public function testStoresRefreshToken(): void
    {
        $response = $this->createTokenResponse();

        $this->assertSame(
            self::REFRESH_TOKEN,
            $response->getRefreshToken()
        );
    }

    /**
     * Comprueba que se almacene correctamente el scope.
     */
    public function testStoresScope(): void
    {
        $response = $this->createTokenResponse();

        $this->assertSame(
            self::SCOPE,
            $response->getScope()
        );
    }

    /**
     * Comprueba que se almacene correctamente el ID token.
     */
    public function testStoresIdToken(): void
    {
        $response = $this->createTokenResponse();

        $this->assertSame(
            self::ID_TOKEN,
            $response->getIdToken()
        );
    }

    /**
     * Comprueba que se almacene correctamente la fecha de recepción.
     */
    public function testStoresReceivedAt(): void
    {
        $response = $this->createTokenResponse();

        $this->assertSame(
            $this->receivedAt,
            $response->getReceivedAt()
        );
    }

    /**
     * Comprueba que los campos opcionales puedan ser null.
     */
    public function testSupportsOptionalNullValues(): void
    {
        $response = $this->createTokenResponse(
            expiresIn: null,
            refreshToken: null,
            scope: null,
            idToken: null
        );

        $this->assertNull($response->getExpiresIn());
        $this->assertNull($response->getRefreshToken());
        $this->assertNull($response->getScope());
        $this->assertNull($response->getIdToken());
    }

    /**
     * Comprueba que se pueda crear una respuesta sin refresh token.
     */
    public function testSupportsMissingRefreshToken(): void
    {
        $response = $this->createTokenResponse(
            refreshToken: null
        );

        $this->assertNull(
            $response->getRefreshToken()
        );

        $this->assertNotNull(
            $response->getAccessToken()
        );
    }

    /**
     * Comprueba que se pueda crear una respuesta sin ID token.
     */
    public function testSupportsMissingIdToken(): void
    {
        $response = $this->createTokenResponse(
            idToken: null
        );

        $this->assertNull(
            $response->getIdToken()
        );
    }

    /**
     * Comprueba que se pueda crear una respuesta sin scope.
     */
    public function testSupportsMissingScope(): void
    {
        $response = $this->createTokenResponse(
            scope: null
        );

        $this->assertNull(
            $response->getScope()
        );
    }

    /**
     * Un token dentro de su periodo de validez no debe estar expirado.
     */
    public function testTokenIsNotExpired(): void
    {
        $receivedAt = new DateTimeImmutable('-10 minutes');

        $response = $this->createTokenResponse(
            expiresIn: 3600,
            receivedAt: $receivedAt
        );

        $this->assertFalse(
            $response->isExpired()
        );
    }

    /**
     * Un token cuyo periodo de validez terminó debe estar expirado.
     */
    public function testTokenIsExpired(): void
    {
        $receivedAt = new DateTimeImmutable('-2 hours');

        $response = $this->createTokenResponse(
            expiresIn: 3600,
            receivedAt: $receivedAt
        );

        $this->assertTrue(
            $response->isExpired()
        );
    }

    /**
     * Un token con una fecha de recepción reciente debe continuar válido.
     */
    public function testRecentlyReceivedTokenIsNotExpired(): void
    {
        $receivedAt = new DateTimeImmutable('-5 seconds');

        $response = $this->createTokenResponse(
            expiresIn: 3600,
            receivedAt: $receivedAt
        );

        $this->assertFalse(
            $response->isExpired()
        );
    }

    /**
     * Comprueba que el token pueda tener un tiempo de expiración corto.
     */
    public function testSupportsShortExpirationTime(): void
    {
        $receivedAt = new DateTimeImmutable('-10 seconds');

        $response = $this->createTokenResponse(
            expiresIn: 60,
            receivedAt: $receivedAt
        );

        $this->assertSame(
            60,
            $response->getExpiresIn()
        );

        $this->assertFalse(
            $response->isExpired()
        );
    }

    /**
     * Comprueba que el token pueda tener un tiempo de expiración largo.
     */
    public function testSupportsLongExpirationTime(): void
    {
        $response = $this->createTokenResponse(
            expiresIn: 86400
        );

        $this->assertSame(
            86400,
            $response->getExpiresIn()
        );
    }

    /**
     * Una respuesta sin expires_in no debe considerarse expirada
     * por una expiración que no fue proporcionada.
     */
    public function testTokenWithoutExpirationIsNotExpired(): void
    {
        $response = $this->createTokenResponse(
            expiresIn: null
        );

        $this->assertFalse(
            $response->isExpired()
        );
    }

    /**
     * Comprueba que fromArray pueda crear una respuesta OAuth
     * utilizando las claves habituales de OAuth 2.0.
     */
    public function testCreatesResponseFromArray(): void
    {
        $data = [
            'access_token' => self::ACCESS_TOKEN,
            'token_type' => self::TOKEN_TYPE,
            'expires_in' => 3600,
            'refresh_token' => self::REFRESH_TOKEN,
            'scope' => self::SCOPE,
            'id_token' => self::ID_TOKEN,
        ];

        $response = TokenResponse::fromArray($data);

        $this->assertInstanceOf(
            TokenResponse::class,
            $response
        );

        $this->assertSame(
            self::ACCESS_TOKEN,
            $response->getAccessToken()
        );

        $this->assertSame(
            self::TOKEN_TYPE,
            $response->getTokenType()
        );

        $this->assertSame(
            3600,
            $response->getExpiresIn()
        );

        $this->assertSame(
            self::REFRESH_TOKEN,
            $response->getRefreshToken()
        );

        $this->assertSame(
            self::SCOPE,
            $response->getScope()
        );

        $this->assertSame(
            self::ID_TOKEN,
            $response->getIdToken()
        );
    }

    /**
     * Comprueba que fromArray pueda trabajar con campos opcionales ausentes.
     */
    public function testCreatesResponseFromArrayWithOptionalFieldsMissing(): void
    {
        $data = [
            'access_token' => self::ACCESS_TOKEN,
            'token_type' => self::TOKEN_TYPE,
        ];

        $response = TokenResponse::fromArray($data);

        $this->assertSame(
            self::ACCESS_TOKEN,
            $response->getAccessToken()
        );

        $this->assertSame(
            self::TOKEN_TYPE,
            $response->getTokenType()
        );

        $this->assertNull($response->getExpiresIn());
        $this->assertNull($response->getRefreshToken());
        $this->assertNull($response->getScope());
        $this->assertNull($response->getIdToken());
    }

    /**
     * Comprueba que dos respuestas puedan mantener tokens independientes.
     */
    public function testDifferentResponsesHaveIndependentValues(): void
    {
        $firstResponse = new TokenResponse(
            'first-access-token',
            'Bearer',
            3600,
            'first-refresh-token',
            'profile',
            null,
            $this->receivedAt
        );

        $secondResponse = new TokenResponse(
            'second-access-token',
            'Bearer',
            7200,
            'second-refresh-token',
            'email',
            null,
            $this->receivedAt
        );

        $this->assertNotSame(
            $firstResponse->getAccessToken(),
            $secondResponse->getAccessToken()
        );

        $this->assertNotSame(
            $firstResponse->getRefreshToken(),
            $secondResponse->getRefreshToken()
        );

        $this->assertNotSame(
            $firstResponse->getExpiresIn(),
            $secondResponse->getExpiresIn()
        );
    }
}
