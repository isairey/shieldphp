
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Session;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Session\SessionConfig;

final class SessionConfigTest extends TestCase
{
    private SessionConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new SessionConfig();
    }

    /**
     * Comprueba que SessionConfig pueda instanciarse.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            SessionConfig::class,
            $this->config
        );
    }

    /**
     * Comprueba que la configuración predeterminada
     * tenga un nombre de sesión.
     */
    public function testHasDefaultSessionName(): void
    {
        $this->assertNotEmpty(
            $this->config->get('session_name')
        );
    }

    /**
     * Comprueba que la duración predeterminada sea válida.
     */
    public function testHasDefaultCookieLifetime(): void
    {
        $this->assertGreaterThanOrEqual(
            0,
            $this->config->get('cookie_lifetime')
        );
    }

    /**
     * Comprueba que la ruta predeterminada de la cookie sea absoluta.
     */
    public function testHasDefaultCookiePath(): void
    {
        $this->assertSame(
            '/',
            $this->config->get('cookie_path')
        );
    }

    /**
     * Comprueba que el dominio predeterminado esté definido.
     */
    public function testHasDefaultCookieDomain(): void
    {
        $this->assertSame(
            '',
            $this->config->get('cookie_domain')
        );
    }

    /**
     * Comprueba que las cookies seguras estén habilitadas
     * por defecto.
     */
    public function testSecureCookiesAreEnabledByDefault(): void
    {
        $this->assertTrue(
            $this->config->get('cookie_secure')
        );
    }

    /**
     * Comprueba que HttpOnly esté habilitado por defecto.
     */
    public function testHttpOnlyCookiesAreEnabledByDefault(): void
    {
        $this->assertTrue(
            $this->config->get('cookie_httponly')
        );
    }

    /**
     * Comprueba que SameSite tenga un valor seguro por defecto.
     */
    public function testHasDefaultSameSitePolicy(): void
    {
        $this->assertSame(
            'Lax',
            $this->config->get('cookie_samesite')
        );
    }

    /**
     * Comprueba que el modo estricto esté habilitado.
     */
    public function testStrictModeIsEnabledByDefault(): void
    {
        $this->assertTrue(
            $this->config->get('use_strict_mode')
        );
    }

    /**
     * Comprueba que el uso exclusivo de cookies esté habilitado.
     */
    public function testCookiesOnlyModeIsEnabledByDefault(): void
    {
        $this->assertTrue(
            $this->config->get('use_only_cookies')
        );
    }

    /**
     * Comprueba que el uso de IDs de sesión en URL esté deshabilitado.
     */
    public function testTransSidIsDisabledByDefault(): void
    {
        $this->assertFalse(
            $this->config->get('use_trans_sid')
        );
    }

    /**
     * Comprueba que se pueda proporcionar una configuración personalizada.
     */
    public function testCanCreateCustomConfiguration(): void
    {
        $customConfig = new SessionConfig([
            'session_name' => 'MYAPP_SESSION',
            'cookie_lifetime' => 3600,
            'cookie_path' => '/',
            'cookie_domain' => 'example.com',
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
        ]);

        $this->assertSame(
            'MYAPP_SESSION',
            $customConfig->get('session_name')
        );

        $this->assertSame(
            3600,
            $customConfig->get('cookie_lifetime')
        );

        $this->assertSame(
            'example.com',
            $customConfig->get('cookie_domain')
        );

        $this->assertSame(
            'Strict',
            $customConfig->get('cookie_samesite')
        );
    }

    /**
     * Comprueba que get() recupere valores personalizados.
     */
    public function testCanGetCustomConfigurationValue(): void
    {
        $customConfig = new SessionConfig([
            'cookie_lifetime' => 7200,
        ]);

        $this->assertSame(
            7200,
            $customConfig->get('cookie_lifetime')
        );
    }

    /**
     * Comprueba que has() identifique las opciones existentes.
     */
    public function testHasReturnsTrueForExistingConfiguration(): void
    {
        $this->assertTrue(
            $this->config->has('session_name')
        );

        $this->assertTrue(
            $this->config->has('cookie_secure')
        );
    }

    /**
     * Comprueba que has() identifique opciones inexistentes.
     */
    public function testHasReturnsFalseForUnknownConfiguration(): void
    {
        $this->assertFalse(
            $this->config->has('unknown_option')
        );
    }

    /**
     * Comprueba que all() devuelva la configuración completa.
     */
    public function testAllReturnsConfigurationArray(): void
    {
        $configuration = $this->config->all();

        $this->assertIsArray($configuration);

        $this->assertArrayHasKey(
            'session_name',
            $configuration
        );

        $this->assertArrayHasKey(
            'cookie_secure',
            $configuration
        );

        $this->assertArrayHasKey(
            'cookie_httponly',
            $configuration
        );

        $this->assertArrayHasKey(
            'cookie_samesite',
            $configuration
        );
    }

    /**
     * Comprueba que la configuración personalizada
     * no elimine opciones predeterminadas.
     */
    public function testCustomConfigurationPreservesDefaults(): void
    {
        $customConfig = new SessionConfig([
            'cookie_lifetime' => 3600,
        ]);

        $this->assertSame(
            3600,
            $customConfig->get('cookie_lifetime')
        );

        $this->assertTrue(
            $customConfig->get('cookie_httponly')
        );

        $this->assertTrue(
            $customConfig->get('use_strict_mode')
        );

        $this->assertSame(
            'Lax',
            $customConfig->get('cookie_samesite')
        );
    }

    /**
     * Comprueba que se pueda deshabilitar Secure
     * para entornos locales de desarrollo.
     */
    public function testCanDisableSecureCookiesForDevelopment(): void
    {
        $customConfig = new SessionConfig([
            'cookie_secure' => false,
        ]);

        $this->assertFalse(
            $customConfig->get('cookie_secure')
        );
    }

    /**
     * Comprueba que se pueda modificar HttpOnly.
     */
    public function testCanCustomizeHttpOnlySetting(): void
    {
        $customConfig = new SessionConfig([
            'cookie_httponly' => false,
        ]);

        $this->assertFalse(
            $customConfig->get('cookie_httponly')
        );
    }

    /**
     * Comprueba que se pueda cambiar la política SameSite.
     */
    public function testCanCustomizeSameSitePolicy(): void
    {
        $customConfig = new SessionConfig([
            'cookie_samesite' => 'Strict',
        ]);

        $this->assertSame(
            'Strict',
            $customConfig->get('cookie_samesite')
        );
    }

    /**
     * Comprueba que se pueda configurar SameSite=None.
     */
    public function testSupportsSameSiteNone(): void
    {
        $customConfig = new SessionConfig([
            'cookie_samesite' => 'None',
            'cookie_secure' => true,
        ]);

        $this->assertSame(
            'None',
            $customConfig->get('cookie_samesite')
        );

        $this->assertTrue(
            $customConfig->get('cookie_secure')
        );
    }

    /**
     * Comprueba que se pueda configurar el dominio de la cookie.
     */
    public function testCanCustomizeCookieDomain(): void
    {
        $customConfig = new SessionConfig([
            'cookie_domain' => 'app.example.com',
        ]);

        $this->assertSame(
            'app.example.com',
            $customConfig->get('cookie_domain')
        );
    }

    /**
     * Comprueba que se pueda configurar una ruta personalizada.
     */
    public function testCanCustomizeCookiePath(): void
    {
        $customConfig = new SessionConfig([
            'cookie_path' => '/admin',
        ]);

        $this->assertSame(
            '/admin',
            $customConfig->get('cookie_path')
        );
    }

    /**
     * Comprueba que se pueda configurar una duración de sesión.
     */
    public function testCanCustomizeCookieLifetime(): void
    {
        $customConfig = new SessionConfig([
            'cookie_lifetime' => 86400,
        ]);

        $this->assertSame(
            86400,
            $customConfig->get('cookie_lifetime')
        );
    }

    /**
     * Comprueba que se pueda utilizar una sesión de duración
     * limitada a la sesión del navegador.
     */
    public function testSupportsBrowserSessionLifetime(): void
    {
        $customConfig = new SessionConfig([
            'cookie_lifetime' => 0,
        ]);

        $this->assertSame(
            0,
            $customConfig->get('cookie_lifetime')
        );
    }

    /**
     * Comprueba que se pueda cambiar el nombre de sesión.
     */
    public function testCanCustomizeSessionName(): void
    {
        $customConfig = new SessionConfig([
            'session_name' => 'CUSTOM_SESSION_ID',
        ]);

        $this->assertSame(
            'CUSTOM_SESSION_ID',
            $customConfig->get('session_name')
        );
    }

    /**
     * Comprueba que se pueda personalizar el modo estricto.
     */
    public function testCanCustomizeStrictMode(): void
    {
        $customConfig = new SessionConfig([
            'use_strict_mode' => false,
        ]);

        $this->assertFalse(
            $customConfig->get('use_strict_mode')
        );
    }

    /**
     * Comprueba que se pueda deshabilitar el uso exclusivo de cookies.
     */
    public function testCanCustomizeCookiesOnlyMode(): void
    {
        $customConfig = new SessionConfig([
            'use_only_cookies' => false,
        ]);

        $this->assertFalse(
            $customConfig->get('use_only_cookies')
        );
    }

    /**
     * Comprueba que se pueda personalizar use_trans_sid.
     */
    public function testCanCustomizeTransSid(): void
    {
        $customConfig = new SessionConfig([
            'use_trans_sid' => true,
        ]);

        $this->assertTrue(
            $customConfig->get('use_trans_sid')
        );
    }

    /**
     * Comprueba que dos instancias tengan configuraciones independientes.
     */
    public function testDifferentInstancesHaveIndependentConfiguration(): void
    {
        $firstConfig = new SessionConfig([
            'session_name' => 'FIRST_SESSION',
        ]);

        $secondConfig = new SessionConfig([
            'session_name' => 'SECOND_SESSION',
        ]);

        $this->assertSame(
            'FIRST_SESSION',
            $firstConfig->get('session_name')
        );

        $this->assertSame(
            'SECOND_SESSION',
            $secondConfig->get('session_name')
        );
    }

    /**
     * Comprueba que la configuración completa conserve
     * los valores personalizados.
     */
    public function testAllContainsCustomValues(): void
    {
        $customConfig = new SessionConfig([
            'session_name' => 'CUSTOM_SESSION',
            'cookie_lifetime' => 1800,
        ]);

        $configuration = $customConfig->all();

        $this->assertSame(
            'CUSTOM_SESSION',
            $configuration['session_name']
        );

        $this->assertSame(
            1800,
            $configuration['cookie_lifetime']
        );
    }
}
