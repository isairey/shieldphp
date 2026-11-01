
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Config\ShieldConfig;
use ShieldPHP\Core\Exceptions\ConfigurationException;

final class ShieldConfigTest extends TestCase
{
    /**
     * Valores predeterminados esperados de ShieldPHP.
     */
    private const DEFAULT_CONFIG = [
        'app_name' => 'ShieldPHP',
        'access_token_ttl' => 900,
        'refresh_token_ttl' => 604800,
        'secure_cookies' => true,
        'same_site' => 'Lax',
    ];

    private ShieldConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new ShieldConfig();
    }

    /**
     * Comprueba que se pueda crear ShieldConfig.
     */
    public function testCreatesShieldConfig(): void
    {
        $config = new ShieldConfig();

        $this->assertInstanceOf(ShieldConfig::class, $config);
    }

    /**
     * Comprueba que la configuración predeterminada
     * contenga los valores esperados.
     */
    public function testProvidesDefaultConfiguration(): void
    {
        $this->assertSame(
            self::DEFAULT_CONFIG,
            $this->config->all()
        );
    }

    /**
     * Comprueba el nombre predeterminado de la aplicación.
     */
    public function testDefaultApplicationName(): void
    {
        $this->assertSame(
            'ShieldPHP',
            $this->config->get('app_name')
        );
    }

    /**
     * Comprueba el TTL predeterminado del access token.
     */
    public function testDefaultAccessTokenTtl(): void
    {
        $this->assertSame(
            900,
            $this->config->get('access_token_ttl')
        );
    }

    /**
     * Comprueba el TTL predeterminado del refresh token.
     */
    public function testDefaultRefreshTokenTtl(): void
    {
        $this->assertSame(
            604800,
            $this->config->get('refresh_token_ttl')
        );
    }

    /**
     * Comprueba que Secure esté habilitado
     * en las cookies de manera predeterminada.
     */
    public function testSecureCookiesAreEnabledByDefault(): void
    {
        $this->assertTrue(
            $this->config->get('secure_cookies')
        );
    }

    /**
     * Comprueba la política SameSite predeterminada.
     */
    public function testDefaultSameSitePolicy(): void
    {
        $this->assertSame(
            'Lax',
            $this->config->get('same_site')
        );
    }

    /**
     * Comprueba que se puedan establecer
     * valores personalizados.
     */
    public function testAcceptsCustomConfiguration(): void
    {
        $config = new ShieldConfig([
            'app_name' => 'My Application',
            'access_token_ttl' => 1200,
            'refresh_token_ttl' => 864000,
            'secure_cookies' => false,
            'same_site' => 'Strict',
        ]);

        $this->assertSame(
            'My Application',
            $config->get('app_name')
        );

        $this->assertSame(
            1200,
            $config->get('access_token_ttl')
        );

        $this->assertSame(
            864000,
            $config->get('refresh_token_ttl')
        );

        $this->assertFalse(
            $config->get('secure_cookies')
        );

        $this->assertSame(
            'Strict',
            $config->get('same_site')
        );
    }

    /**
     * Comprueba que los valores personalizados
     * sustituyan únicamente las opciones indicadas.
     */
    public function testCustomConfigurationPreservesOtherDefaults(): void
    {
        $config = new ShieldConfig([
            'app_name' => 'My Secure App',
        ]);

        $this->assertSame(
            'My Secure App',
            $config->get('app_name')
        );

        $this->assertSame(
            900,
            $config->get('access_token_ttl')
        );

        $this->assertSame(
            604800,
            $config->get('refresh_token_ttl')
        );

        $this->assertTrue(
            $config->get('secure_cookies')
        );

        $this->assertSame(
            'Lax',
            $config->get('same_site')
        );
    }

    /**
     * Comprueba que get() recupere correctamente
     * los valores de configuración.
     */
    public function testGetReturnsConfigurationValue(): void
    {
        $this->assertSame(
            'ShieldPHP',
            $this->config->get('app_name')
        );

        $this->assertSame(
            900,
            $this->config->get('access_token_ttl')
        );

        $this->assertSame(
            'Lax',
            $this->config->get('same_site')
        );
    }

    /**
     * Comprueba que has() reconozca las opciones existentes.
     */
    public function testHasReturnsTrueForExistingOptions(): void
    {
        foreach (array_keys(self::DEFAULT_CONFIG) as $key) {
            $this->assertTrue(
                $this->config->has($key),
                sprintf('Configuration key "%s" should exist.', $key)
            );
        }
    }

    /**
     * Comprueba que has() rechace opciones inexistentes.
     */
    public function testHasReturnsFalseForUnknownOptions(): void
    {
        $this->assertFalse(
            $this->config->has('unknown_option')
        );

        $this->assertFalse(
            $this->config->has('database_password')
        );
    }

    /**
     * Comprueba que all() devuelva un array.
     */
    public function testAllReturnsArray(): void
    {
        $result = $this->config->all();

        $this->assertIsArray($result);
    }

    /**
     * Comprueba que all() incluya todas las opciones predeterminadas.
     */
    public function testAllContainsEveryDefaultOption(): void
    {
        $result = $this->config->all();

        foreach (self::DEFAULT_CONFIG as $key => $value) {
            $this->assertArrayHasKey($key, $result);

            $this->assertSame($value, $result[$key]);
        }
    }

    /**
     * Comprueba que all() refleje los valores personalizados.
     */
    public function testAllReturnsCustomConfiguration(): void
    {
        $customValues = [
            'app_name' => 'ShieldPHP API',
            'access_token_ttl' => 1800,
        ];

        $config = new ShieldConfig($customValues);

        $result = $config->all();

        $this->assertSame(
            'ShieldPHP API',
            $result['app_name']
        );

        $this->assertSame(
            1800,
            $result['access_token_ttl']
        );
    }

    /**
     * Comprueba que una configuración parcialmente personalizada
     * conserve el resto de las opciones.
     */
    public function testPartialConfigurationContainsAllDefaults(): void
    {
        $config = new ShieldConfig([
            'secure_cookies' => false,
        ]);

        $result = $config->all();

        $this->assertCount(
            count(self::DEFAULT_CONFIG),
            $result
        );

        $this->assertFalse($result['secure_cookies']);

        $this->assertSame(
            'ShieldPHP',
            $result['app_name']
        );

        $this->assertSame(
            900,
            $result['access_token_ttl']
        );
    }

    /**
     * Comprueba que se rechace un nombre de aplicación vacío.
     */
    public function testRejectsEmptyApplicationName(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig([
            'app_name' => '',
        ]);
    }

    /**
     * Comprueba que se rechace un TTL de access token inválido.
     */
    public function testRejectsInvalidAccessTokenTtl(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig([
            'access_token_ttl' => -1,
        ]);
    }

    /**
     * Comprueba que se rechace un TTL de refresh token inválido.
     */
    public function testRejectsInvalidRefreshTokenTtl(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig([
            'refresh_token_ttl' => 0,
        ]);
    }

    /**
     * Comprueba que se rechace una duración de refresh token
     * inferior a la duración del access token.
     */
    public function testRejectsInvalidTokenLifetimeRelationship(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig([
            'access_token_ttl' => 3600,
            'refresh_token_ttl' => 1800,
        ]);
    }

    /**
     * Comprueba que se rechace un valor incorrecto
     * para secure_cookies.
     */
    public function testRejectsInvalidSecureCookiesValue(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig([
            'secure_cookies' => 'true',
        ]);
    }

    /**
     * Comprueba que se rechace una política SameSite inválida.
     */
    public function testRejectsInvalidSameSiteValue(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig([
            'same_site' => 'Invalid',
        ]);
    }

    /**
     * Comprueba que una configuración con opciones desconocidas
     * sea rechazada.
     */
    public function testRejectsUnknownConfigurationOption(): void
    {
        $this->expectException(ConfigurationException::class);

        new ShieldConfig([
            'unknown_option' => true,
        ]);
    }

    /**
     * Comprueba que dos instancias mantengan
     * configuraciones independientes.
     */
    public function testConfigurationInstancesRemainIndependent(): void
    {
        $firstConfig = new ShieldConfig([
            'app_name' => 'Application One',
        ]);

        $secondConfig = new ShieldConfig([
            'app_name' => 'Application Two',
        ]);

        $this->assertSame(
            'Application One',
            $firstConfig->get('app_name')
        );

        $this->assertSame(
            'Application Two',
            $secondConfig->get('app_name')
        );
    }

    /**
     * Comprueba que consultar la configuración
     * no altere sus valores.
     */
    public function testReadingConfigurationDoesNotChangeValues(): void
    {
        $originalConfig = $this->config->all();

        $this->config->get('app_name');
        $this->config->has('access_token_ttl');
        $this->config->all();

        $this->assertSame(
            $originalConfig,
            $this->config->all()
        );
    }

    /**
     * Comprueba que se puedan realizar varias consultas
     * consecutivas sin alterar la configuración.
     */
    public function testMultipleConfigurationReadsRemainConsistent(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(
                'ShieldPHP',
                $this->config->get('app_name')
            );

            $this->assertTrue(
                $this->config->has('secure_cookies')
            );
        }

        $this->assertSame(
            self::DEFAULT_CONFIG,
            $this->config->all()
        );
    }
}
