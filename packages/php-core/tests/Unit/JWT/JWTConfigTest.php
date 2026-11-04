
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\JWT;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\JWT\JWTConfig;

final class JWTConfigTest extends TestCase
{
    private const ISSUER = 'https://shieldphp.test';

    private const AUDIENCE = 'shieldphp-api';

    private const MAX_REFRESH_TTL = 31536000;

    private const MAX_ACCESS_TTL = 86400;

    private const MAX_LEEWAY = 300;

    private string $signingKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signingKey = str_repeat('K', 64);
    }

    /**
     * Crea una configuración JWT válida.
     */
    private function createConfig(
        ?string $signingKey = null,
        string $issuer = self::ISSUER,
        string $audience = self::AUDIENCE,
        string $algorithm = 'HS256',
        int $accessTokenTtl = 900,
        int $refreshTokenTtl = 604800,
        int $leeway = 0
    ): JWTConfig {
        return new JWTConfig(
            $signingKey ?? $this->signingKey,
            $issuer,
            $audience,
            $algorithm,
            $accessTokenTtl,
            $refreshTokenTtl,
            $leeway
        );
    }

    /**
     * Comprueba que se pueda crear JWTConfig.
     */
    public function testCreatesJWTConfigInstance(): void
    {
        $config = $this->createConfig();

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se pueda crear una configuración
     * utilizando los parámetros predeterminados.
     */
    public function testCreatesConfigWithDefaultParameters(): void
    {
        $config = new JWTConfig(
            $this->signingKey,
            self::ISSUER,
            self::AUDIENCE
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepten valores personalizados.
     */
    public function testAcceptsCustomConfiguration(): void
    {
        $config = $this->createConfig(
            issuer: 'https://api.example.com',
            audience: 'mobile-api',
            algorithm: 'HS384',
            accessTokenTtl: 1800,
            refreshTokenTtl: 864000,
            leeway: 60
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte HS256.
     */
    public function testAcceptsHS256Algorithm(): void
    {
        $key = str_repeat('A', 32);

        $config = $this->createConfig(
            signingKey: $key,
            algorithm: 'HS256'
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte HS384.
     */
    public function testAcceptsHS384Algorithm(): void
    {
        $key = str_repeat('B', 48);

        $config = $this->createConfig(
            signingKey: $key,
            algorithm: 'HS384'
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte HS512.
     */
    public function testAcceptsHS512Algorithm(): void
    {
        $key = str_repeat('C', 64);

        $config = $this->createConfig(
            signingKey: $key,
            algorithm: 'HS512'
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se rechace un algoritmo desconocido.
     */
    public function testRejectsUnknownAlgorithm(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            algorithm: 'HS1024'
        );
    }

    /**
     * Comprueba que se rechace el algoritmo none.
     *
     * Este algoritmo no proporciona autenticidad
     * criptográfica de la firma.
     */
    public function testRejectsNoneAlgorithm(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            algorithm: 'none'
        );
    }

    /**
     * Comprueba que los algoritmos distingan
     * correctamente entre mayúsculas y minúsculas.
     */
    public function testRejectsIncorrectAlgorithmCasing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            algorithm: 'hs256'
        );
    }

    /**
     * Comprueba que HS256 rechace claves inferiores
     * a 32 bytes.
     */
    public function testRejectsShortHS256SigningKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            signingKey: str_repeat('A', 31),
            algorithm: 'HS256'
        );
    }

    /**
     * Comprueba que HS384 rechace claves inferiores
     * a 48 bytes.
     */
    public function testRejectsShortHS384SigningKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            signingKey: str_repeat('B', 47),
            algorithm: 'HS384'
        );
    }

    /**
     * Comprueba que HS512 rechace claves inferiores
     * a 64 bytes.
     */
    public function testRejectsShortHS512SigningKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            signingKey: str_repeat('C', 63),
            algorithm: 'HS512'
        );
    }

    /**
     * Comprueba que se rechace una clave vacía.
     */
    public function testRejectsEmptySigningKey(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            signingKey: ''
        );
    }

    /**
     * Comprueba que se acepte una clave HS256
     * con exactamente 32 bytes.
     */
    public function testAcceptsMinimumHS256KeyLength(): void
    {
        $config = $this->createConfig(
            signingKey: str_repeat('A', 32),
            algorithm: 'HS256'
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte una clave HS384
     * con exactamente 48 bytes.
     */
    public function testAcceptsMinimumHS384KeyLength(): void
    {
        $config = $this->createConfig(
            signingKey: str_repeat('B', 48),
            algorithm: 'HS384'
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte una clave HS512
     * con exactamente 64 bytes.
     */
    public function testAcceptsMinimumHS512KeyLength(): void
    {
        $config = $this->createConfig(
            signingKey: str_repeat('C', 64),
            algorithm: 'HS512'
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte un issuer válido.
     */
    public function testAcceptsValidIssuer(): void
    {
        $config = $this->createConfig(
            issuer: 'https://api.example.com'
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se rechace un issuer vacío.
     */
    public function testRejectsEmptyIssuer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            issuer: ''
        );
    }

    /**
     * Comprueba que se rechace un issuer
     * que contenga únicamente espacios.
     */
    public function testRejectsWhitespaceIssuer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            issuer: '   '
        );
    }

    /**
     * Comprueba que se acepte una audience válida.
     */
    public function testAcceptsValidAudience(): void
    {
        $config = $this->createConfig(
            audience: 'my-secure-api'
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se rechace una audience vacía.
     */
    public function testRejectsEmptyAudience(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            audience: ''
        );
    }

    /**
     * Comprueba que se rechace una audience
     * que contenga únicamente espacios.
     */
    public function testRejectsWhitespaceAudience(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            audience: '   '
        );
    }

    /**
     * Comprueba que se acepte un access token TTL válido.
     */
    public function testAcceptsValidAccessTokenTtl(): void
    {
        $config = $this->createConfig(
            accessTokenTtl: 900,
            refreshTokenTtl: 604800
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se rechace un access token TTL igual a cero.
     */
    public function testRejectsZeroAccessTokenTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            accessTokenTtl: 0
        );
    }

    /**
     * Comprueba que se rechace un access token TTL negativo.
     */
    public function testRejectsNegativeAccessTokenTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            accessTokenTtl: -1
        );
    }

    /**
     * Comprueba que se rechace un access token TTL
     * superior a 86400 segundos.
     */
    public function testRejectsAccessTokenTtlAboveMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            accessTokenTtl: self::MAX_ACCESS_TTL + 1,
            refreshTokenTtl: self::MAX_ACCESS_TTL + 3600
        );
    }

    /**
     * Comprueba que se acepte un access token TTL
     * exactamente igual al límite máximo.
     */
    public function testAcceptsMaximumAccessTokenTtl(): void
    {
        $config = $this->createConfig(
            accessTokenTtl: self::MAX_ACCESS_TTL,
            refreshTokenTtl: self::MAX_ACCESS_TTL + 1
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte un refresh token TTL válido.
     */
    public function testAcceptsValidRefreshTokenTtl(): void
    {
        $config = $this->createConfig(
            accessTokenTtl: 900,
            refreshTokenTtl: 604800
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se rechace un refresh token TTL igual a cero.
     */
    public function testRejectsZeroRefreshTokenTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            refreshTokenTtl: 0
        );
    }

    /**
     * Comprueba que se rechace un refresh token TTL negativo.
     */
    public function testRejectsNegativeRefreshTokenTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            refreshTokenTtl: -1
        );
    }

    /**
     * Comprueba que el refresh token dure más
     * que el access token.
     */
    public function testRefreshTokenMustOutliveAccessToken(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            accessTokenTtl: 3600,
            refreshTokenTtl: 3600
        );
    }

    /**
     * Comprueba que se rechace un refresh token
     * con una duración inferior al access token.
     */
    public function testRejectsRefreshTokenShorterThanAccessToken(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            accessTokenTtl: 3600,
            refreshTokenTtl: 1800
        );
    }

    /**
     * Comprueba que se rechace un refresh token TTL
     * superior a 365 días.
     */
    public function testRejectsRefreshTokenTtlAboveMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            accessTokenTtl: 900,
            refreshTokenTtl: self::MAX_REFRESH_TTL + 1
        );
    }

    /**
     * Comprueba que se acepte un refresh token TTL
     * exactamente igual a 365 días.
     */
    public function testAcceptsMaximumRefreshTokenTtl(): void
    {
        $config = $this->createConfig(
            accessTokenTtl: 900,
            refreshTokenTtl: self::MAX_REFRESH_TTL
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte una tolerancia temporal válida.
     */
    public function testAcceptsValidLeeway(): void
    {
        $config = $this->createConfig(
            leeway: 60
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte una tolerancia temporal igual a cero.
     */
    public function testAcceptsZeroLeeway(): void
    {
        $config = $this->createConfig(
            leeway: 0
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se acepte el leeway máximo de 300 segundos.
     */
    public function testAcceptsMaximumLeeway(): void
    {
        $config = $this->createConfig(
            leeway: self::MAX_LEEWAY
        );

        $this->assertInstanceOf(
            JWTConfig::class,
            $config
        );
    }

    /**
     * Comprueba que se rechace un leeway negativo.
     */
    public function testRejectsNegativeLeeway(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            leeway: -1
        );
    }

    /**
     * Comprueba que se rechace un leeway superior
     * a 300 segundos.
     */
    public function testRejectsLeewayAboveMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createConfig(
            leeway: self::MAX_LEEWAY + 1
        );
    }

    /**
     * Comprueba que diferentes instancias
     * mantengan configuraciones independientes.
     */
    public function testConfigInstancesRemainIndependent(): void
    {
        $firstConfig = $this->createConfig(
            issuer: 'https://first.example.com',
            audience: 'first-api'
        );

        $secondConfig = $this->createConfig(
            issuer: 'https://second.example.com',
            audience: 'second-api'
        );

        $firstIssuer = new \ReflectionProperty(
            JWTConfig::class,
            'issuer'
        );

        $secondIssuer = new \ReflectionProperty(
            JWTConfig::class,
            'issuer'
        );

        $this->assertSame(
            'https://first.example.com',
            $firstIssuer->getValue($firstConfig)
        );

        $this->assertSame(
            'https://second.example.com',
            $secondIssuer->getValue($secondConfig)
        );
    }
}
