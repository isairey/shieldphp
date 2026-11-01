
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Config\ConfigValidator;
use ShieldPHP\Core\Exceptions\ConfigurationException;

final class ConfigValidatorTest extends TestCase
{
    /**
     * Configuración válida utilizada como base.
     *
     * @return array<string, mixed>
     */
    private function validConfig(): array
    {
        return [
            'app_name' => 'ShieldPHP Application',
            'access_token_ttl' => 900,
            'refresh_token_ttl' => 604800,
            'secure_cookies' => true,
            'same_site' => 'Lax',
        ];
    }

    /**
     * Ejecuta la validación de una configuración válida.
     *
     * @param array<string, mixed> $config
     */
    private function assertConfigurationIsValid(array $config): void
    {
        ConfigValidator::validate($config);

        $this->assertTrue(true);
    }

    /**
     * Comprueba que la configuración inválida
     * produzca ConfigurationException.
     *
     * @param array<string, mixed> $config
     */
    private function assertConfigurationIsInvalid(array $config): void
    {
        $this->expectException(ConfigurationException::class);

        ConfigValidator::validate($config);
    }

    /**
     * Comprueba que se acepte una configuración completa válida.
     */
    public function testAcceptsValidConfiguration(): void
    {
        $this->assertConfigurationIsValid(
            $this->validConfig()
        );
    }

    /**
     * Comprueba que se acepte una configuración vacía.
     */
    public function testAcceptsEmptyConfiguration(): void
    {
        $this->assertConfigurationIsValid([]);
    }

    /**
     * Comprueba que se acepten configuraciones parciales.
     */
    public function testAcceptsPartialConfiguration(): void
    {
        $config = [
            'app_name' => 'My Application',
        ];

        $this->assertConfigurationIsValid($config);
    }

    /**
     * Comprueba que se acepten los parámetros de tokens
     * con los valores habituales.
     */
    public function testAcceptsValidTokenConfiguration(): void
    {
        $config = [
            'access_token_ttl' => 900,
            'refresh_token_ttl' => 604800,
        ];

        $this->assertConfigurationIsValid($config);
    }

    /**
     * Comprueba que se rechacen claves desconocidas.
     */
    public function testRejectsUnknownConfigurationKey(): void
    {
        $config = $this->validConfig();

        $config['unknown_option'] = true;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace una clave con un nombre similar
     * a una opción permitida.
     */
    public function testRejectsMisspelledConfigurationKey(): void
    {
        $config = $this->validConfig();

        unset($config['app_name']);

        $config['appname'] = 'ShieldPHP';

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se acepte un nombre de aplicación válido.
     */
    public function testAcceptsValidApplicationName(): void
    {
        $config = $this->validConfig();

        $config['app_name'] = 'My Secure Application';

        $this->assertConfigurationIsValid($config);
    }

    /**
     * Comprueba que se rechace un nombre de aplicación vacío.
     */
    public function testRejectsEmptyApplicationName(): void
    {
        $config = $this->validConfig();

        $config['app_name'] = '';

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un nombre de aplicación
     * que contenga únicamente espacios.
     */
    public function testRejectsWhitespaceApplicationName(): void
    {
        $config = $this->validConfig();

        $config['app_name'] = '   ';

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un nombre de aplicación
     * con un tipo de dato incorrecto.
     */
    public function testRejectsNonStringApplicationName(): void
    {
        $config = $this->validConfig();

        $config['app_name'] = 123;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se acepte un access token TTL válido.
     */
    public function testAcceptsValidAccessTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['access_token_ttl'] = 900;

        $this->assertConfigurationIsValid($config);
    }

    /**
     * Comprueba que se rechace un access token TTL igual a cero.
     */
    public function testRejectsZeroAccessTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['access_token_ttl'] = 0;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un access token TTL negativo.
     */
    public function testRejectsNegativeAccessTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['access_token_ttl'] = -100;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un access token TTL superior
     * al límite de 86400 segundos.
     */
    public function testRejectsAccessTokenTtlAboveMaximum(): void
    {
        $config = $this->validConfig();

        $config['access_token_ttl'] = 86401;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un access token TTL
     * que no sea un entero.
     */
    public function testRejectsNonIntegerAccessTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['access_token_ttl'] = '900';

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se acepte un refresh token TTL válido.
     */
    public function testAcceptsValidRefreshTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['refresh_token_ttl'] = 604800;

        $this->assertConfigurationIsValid($config);
    }

    /**
     * Comprueba que se rechace un refresh token TTL igual a cero.
     */
    public function testRejectsZeroRefreshTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['refresh_token_ttl'] = 0;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un refresh token TTL negativo.
     */
    public function testRejectsNegativeRefreshTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['refresh_token_ttl'] = -604800;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que el refresh token tenga una duración
     * mayor que el access token.
     */
    public function testRejectsRefreshTokenTtlNotGreaterThanAccessTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['access_token_ttl'] = 3600;
        $config['refresh_token_ttl'] = 3600;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un refresh token TTL
     * inferior al access token TTL.
     */
    public function testRejectsRefreshTokenTtlBelowAccessTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['access_token_ttl'] = 3600;
        $config['refresh_token_ttl'] = 1800;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un refresh token TTL superior
     * al límite de 365 días.
     */
    public function testRejectsRefreshTokenTtlAboveMaximum(): void
    {
        $config = $this->validConfig();

        $config['refresh_token_ttl'] = 31536001;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un refresh token TTL
     * que no sea un entero.
     */
    public function testRejectsNonIntegerRefreshTokenTtl(): void
    {
        $config = $this->validConfig();

        $config['refresh_token_ttl'] = '604800';

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se acepten los valores booleanos
     * para secure_cookies.
     */
    public function testAcceptsTrueSecureCookies(): void
    {
        $config = $this->validConfig();

        $config['secure_cookies'] = true;

        $this->assertConfigurationIsValid($config);
    }

    /**
     * Comprueba que se acepte false para secure_cookies.
     */
    public function testAcceptsFalseSecureCookies(): void
    {
        $config = $this->validConfig();

        $config['secure_cookies'] = false;

        $this->assertConfigurationIsValid($config);
    }

    /**
     * Comprueba que se rechacen valores no booleanos
     * para secure_cookies.
     */
    public function testRejectsNonBooleanSecureCookies(): void
    {
        $config = $this->validConfig();

        $config['secure_cookies'] = 'true';

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se acepten las políticas SameSite permitidas.
     */
    public function testAcceptsValidSameSiteValues(): void
    {
        foreach (['Lax', 'Strict', 'None'] as $sameSite) {
            $config = $this->validConfig();

            $config['same_site'] = $sameSite;

            $this->assertConfigurationIsValid($config);
        }
    }

    /**
     * Comprueba que se rechace una política SameSite desconocida.
     */
    public function testRejectsUnknownSameSiteValue(): void
    {
        $config = $this->validConfig();

        $config['same_site'] = 'Invalid';

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace una política SameSite
     * con una cadena vacía.
     */
    public function testRejectsEmptySameSiteValue(): void
    {
        $config = $this->validConfig();

        $config['same_site'] = '';

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se rechace un valor SameSite
     * que no sea una cadena.
     */
    public function testRejectsNonStringSameSiteValue(): void
    {
        $config = $this->validConfig();

        $config['same_site'] = true;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que la combinación SameSite=None
     * y Secure=false sea rechazada.
     *
     * Las cookies SameSite=None requieren Secure.
     */
    public function testRejectsSameSiteNoneWithoutSecureCookies(): void
    {
        $config = $this->validConfig();

        $config['same_site'] = 'None';
        $config['secure_cookies'] = false;

        $this->assertConfigurationIsInvalid($config);
    }

    /**
     * Comprueba que se acepte SameSite=None
     * cuando Secure está habilitado.
     */
    public function testAcceptsSameSiteNoneWithSecureCookies(): void
    {
        $config = $this->validConfig();

        $config['same_site'] = 'None';
        $config['secure_cookies'] = true;

        $this->assertConfigurationIsValid($config);
    }

    /**
     * Comprueba que varias validaciones consecutivas
     * no afecten el resultado de otras configuraciones.
     */
    public function testValidationsRemainIndependent(): void
    {
        $validConfig = $this->validConfig();

        $invalidConfig = $this->validConfig();
        $invalidConfig['access_token_ttl'] = -1;

        $this->assertConfigurationIsValid($validConfig);

        try {
            ConfigValidator::validate($invalidConfig);

            self::fail('Invalid configuration should be rejected.');
        } catch (ConfigurationException) {
            $this->assertTrue(true);
        }

        $this->assertConfigurationIsValid($validConfig);
    }
}
