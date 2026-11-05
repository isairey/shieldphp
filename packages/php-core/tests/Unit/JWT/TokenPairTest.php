
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\JWT;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\JWT\TokenPair;

final class TokenPairTest extends TestCase
{
    private const ACCESS_TOKEN = 'access-token-test-123456';

    private const REFRESH_TOKEN = 'refresh-token-test-789012';

    private const ACCESS_TOKEN_TTL = 900;

    private const REFRESH_TOKEN_TTL = 604800;

    private TokenPair $tokenPair;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenPair = $this->createTokenPair();
    }

    /**
     * Crea un par de tokens para las pruebas.
     */
    private function createTokenPair(
        string $accessToken = self::ACCESS_TOKEN,
        string $refreshToken = self::REFRESH_TOKEN,
        int $accessTokenTtl = self::ACCESS_TOKEN_TTL,
        int $refreshTokenTtl = self::REFRESH_TOKEN_TTL
    ): TokenPair {
        return new TokenPair(
            $accessToken,
            $refreshToken,
            $accessTokenTtl,
            $refreshTokenTtl
        );
    }

    /**
     * Comprueba que se pueda crear TokenPair.
     */
    public function testCreatesTokenPairInstance(): void
    {
        $pair = $this->createTokenPair();

        $this->assertInstanceOf(
            TokenPair::class,
            $pair
        );
    }

    /**
     * Comprueba que getAccessToken() devuelva
     * el access token correcto.
     */
    public function testGetAccessTokenReturnsCorrectValue(): void
    {
        $this->assertSame(
            self::ACCESS_TOKEN,
            $this->tokenPair->getAccessToken()
        );
    }

    /**
     * Comprueba que getRefreshToken() devuelva
     * el refresh token correcto.
     */
    public function testGetRefreshTokenReturnsCorrectValue(): void
    {
        $this->assertSame(
            self::REFRESH_TOKEN,
            $this->tokenPair->getRefreshToken()
        );
    }

    /**
     * Comprueba que getAccessTokenTtl()
     * devuelva el tiempo de vida del access token.
     */
    public function testGetAccessTokenTtlReturnsCorrectValue(): void
    {
        $this->assertSame(
            self::ACCESS_TOKEN_TTL,
            $this->tokenPair->getAccessTokenTtl()
        );
    }

    /**
     * Comprueba que getRefreshTokenTtl()
     * devuelva el tiempo de vida del refresh token.
     */
    public function testGetRefreshTokenTtlReturnsCorrectValue(): void
    {
        $this->assertSame(
            self::REFRESH_TOKEN_TTL,
            $this->tokenPair->getRefreshTokenTtl()
        );
    }

    /**
     * Comprueba que el access token tenga
     * una duración diferente al refresh token.
     */
    public function testAccessAndRefreshTokenTtlAreIndependent(): void
    {
        $pair = $this->createTokenPair(
            accessTokenTtl: 900,
            refreshTokenTtl: 604800
        );

        $this->assertNotSame(
            $pair->getAccessTokenTtl(),
            $pair->getRefreshTokenTtl()
        );
    }

    /**
     * Comprueba que toArray() devuelva un array.
     */
    public function testToArrayReturnsArray(): void
    {
        $result = $this->tokenPair->toArray();

        $this->assertIsArray($result);
    }

    /**
     * Comprueba que toArray() incluya
     * los cuatro valores del par de tokens.
     */
    public function testToArrayContainsAllTokenPairValues(): void
    {
        $result = $this->tokenPair->toArray();

        $this->assertSame(
            self::ACCESS_TOKEN,
            $result['access_token']
        );

        $this->assertSame(
            self::REFRESH_TOKEN,
            $result['refresh_token']
        );

        $this->assertSame(
            self::ACCESS_TOKEN_TTL,
            $result['access_token_ttl']
        );

        $this->assertSame(
            self::REFRESH_TOKEN_TTL,
            $result['refresh_token_ttl']
        );
    }

    /**
     * Comprueba que toArray() tenga las claves esperadas.
     */
    public function testToArrayContainsExpectedKeys(): void
    {
        $result = $this->tokenPair->toArray();

        $expectedKeys = [
            'access_token',
            'refresh_token',
            'access_token_ttl',
            'refresh_token_ttl',
        ];

        $this->assertSame(
            $expectedKeys,
            array_keys($result)
        );
    }

    /**
     * Comprueba que los valores recuperados
     * sean cadenas y enteros respectivamente.
     */
    public function testReturnsCorrectValueTypes(): void
    {
        $this->assertIsString(
            $this->tokenPair->getAccessToken()
        );

        $this->assertIsString(
            $this->tokenPair->getRefreshToken()
        );

        $this->assertIsInt(
            $this->tokenPair->getAccessTokenTtl()
        );

        $this->assertIsInt(
            $this->tokenPair->getRefreshTokenTtl()
        );
    }

    /**
     * Comprueba que se conserven exactamente
     * los valores proporcionados al constructor.
     */
    public function testPreservesOriginalTokenValues(): void
    {
        $accessToken = 'eyJhbGciOiJIUzI1NiJ9.access.signature';

        $refreshToken = 'refresh_XYZ-123_ABC';

        $pair = $this->createTokenPair(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            accessTokenTtl: 1200,
            refreshTokenTtl: 864000
        );

        $this->assertSame(
            $accessToken,
            $pair->getAccessToken()
        );

        $this->assertSame(
            $refreshToken,
            $pair->getRefreshToken()
        );
    }

    /**
     * Comprueba que diferentes pares mantengan
     * sus respectivos tokens independientes.
     */
    public function testTokenPairInstancesRemainIndependent(): void
    {
        $firstPair = $this->createTokenPair(
            'access-first',
            'refresh-first',
            900,
            604800
        );

        $secondPair = $this->createTokenPair(
            'access-second',
            'refresh-second',
            1800,
            864000
        );

        $this->assertNotSame(
            $firstPair->getAccessToken(),
            $secondPair->getAccessToken()
        );

        $this->assertNotSame(
            $firstPair->getRefreshToken(),
            $secondPair->getRefreshToken()
        );

        $this->assertNotSame(
            $firstPair->getAccessTokenTtl(),
            $secondPair->getAccessTokenTtl()
        );

        $this->assertNotSame(
            $firstPair->getRefreshTokenTtl(),
            $secondPair->getRefreshTokenTtl()
        );
    }

    /**
     * Comprueba que consultar y serializar el par
     * no altere sus valores.
     */
    public function testReadingTokenPairDoesNotChangeItsValues(): void
    {
        $original = $this->tokenPair->toArray();

        $this->tokenPair->getAccessToken();
        $this->tokenPair->getRefreshToken();
        $this->tokenPair->getAccessTokenTtl();
        $this->tokenPair->getRefreshTokenTtl();

        $this->tokenPair->toArray();

        $this->assertSame(
            $original,
            $this->tokenPair->toArray()
        );
    }

    /**
     * Comprueba que el par de tokens pueda
     * serializarse correctamente a JSON.
     */
    public function testTokenPairCanBeSerializedToJson(): void
    {
        $json = json_encode(
            $this->tokenPair->toArray(),
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
            self::ACCESS_TOKEN,
            $decoded['access_token']
        );

        $this->assertSame(
            self::REFRESH_TOKEN,
            $decoded['refresh_token']
        );
    }

    /**
     * Comprueba que los tiempos de vida personalizados
     * se conserven correctamente.
     */
    public function testSupportsCustomTokenLifetimes(): void
    {
        $pair = $this->createTokenPair(
            accessTokenTtl: 300,
            refreshTokenTtl: 2592000
        );

        $this->assertSame(
            300,
            $pair->getAccessTokenTtl()
        );

        $this->assertSame(
            2592000,
            $pair->getRefreshTokenTtl()
        );
    }
}
