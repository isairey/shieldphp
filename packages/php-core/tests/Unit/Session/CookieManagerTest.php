
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Session;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Session\CookieManager;

final class CookieManagerTest extends TestCase
{
    private CookieManager $cookieManager;

    private array $originalCookies;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalCookies = $_COOKIE;

        $_COOKIE = [];

        $this->cookieManager = new CookieManager();
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->originalCookies;

        parent::tearDown();
    }

    /**
     * Comprueba que se pueda instanciar CookieManager.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            CookieManager::class,
            $this->cookieManager
        );
    }

    /**
     * Comprueba que se pueda establecer una cookie.
     */
    public function testCanSetCookie(): void
    {
        $this->cookieManager->set(
            'session_id',
            'test-session-value'
        );

        $this->assertSame(
            'test-session-value',
            $_COOKIE['session_id']
        );
    }

    /**
     * Comprueba que se pueda recuperar una cookie.
     */
    public function testCanGetCookie(): void
    {
        $_COOKIE['session_id'] = 'test-session-value';

        $value = $this->cookieManager->get('session_id');

        $this->assertSame(
            'test-session-value',
            $value
        );
    }

    /**
     * Una cookie inexistente debe devolver null.
     */
    public function testReturnsNullForMissingCookie(): void
    {
        $this->assertNull(
            $this->cookieManager->get('nonexistent_cookie')
        );
    }

    /**
     * Comprueba que se pueda verificar la existencia de una cookie.
     */
    public function testHasCookieReturnsTrueWhenCookieExists(): void
    {
        $_COOKIE['session_id'] = 'test-value';

        $this->assertTrue(
            $this->cookieManager->has('session_id')
        );
    }

    /**
     * Comprueba que se detecte una cookie inexistente.
     */
    public function testHasCookieReturnsFalseWhenCookieDoesNotExist(): void
    {
        $this->assertFalse(
            $this->cookieManager->has('nonexistent_cookie')
        );
    }

    /**
     * Comprueba que se pueda actualizar una cookie.
     */
    public function testCanUpdateCookie(): void
    {
        $_COOKIE['session_id'] = 'old-value';

        $this->cookieManager->set(
            'session_id',
            'new-value'
        );

        $this->assertSame(
            'new-value',
            $_COOKIE['session_id']
        );
    }

    /**
     * Comprueba que se pueda eliminar una cookie.
     */
    public function testCanDeleteCookie(): void
    {
        $_COOKIE['session_id'] = 'test-value';

        $this->cookieManager->delete('session_id');

        $this->assertArrayNotHasKey(
            'session_id',
            $_COOKIE
        );
    }

    /**
     * Eliminar una cookie inexistente no debe provocar errores.
     */
    public function testCanDeleteNonexistentCookie(): void
    {
        $this->cookieManager->delete('nonexistent_cookie');

        $this->assertArrayNotHasKey(
            'nonexistent_cookie',
            $_COOKIE
        );

        $this->assertTrue(true);
    }

    /**
     * Comprueba que se pueda eliminar una cookie
     * sin afectar las demás.
     */
    public function testDeletingCookieDoesNotAffectOtherCookies(): void
    {
        $_COOKIE = [
            'session_id' => 'session-value',
            'csrf_token' => 'csrf-value',
            'preferences' => 'preferences-value',
        ];

        $this->cookieManager->delete('session_id');

        $this->assertArrayNotHasKey(
            'session_id',
            $_COOKIE
        );

        $this->assertSame(
            'csrf-value',
            $_COOKIE['csrf_token']
        );

        $this->assertSame(
            'preferences-value',
            $_COOKIE['preferences']
        );
    }

    /**
     * Comprueba que se puedan limpiar todas las cookies.
     */
    public function testCanClearCookies(): void
    {
        $_COOKIE = [
            'session_id' => 'session-value',
            'csrf_token' => 'csrf-value',
            'preferences' => 'preferences-value',
        ];

        $this->cookieManager->clear();

        $this->assertEmpty($_COOKIE);
    }

    /**
     * Limpiar las cookies cuando no existen debe ser seguro.
     */
    public function testCanClearEmptyCookies(): void
    {
        $_COOKIE = [];

        $this->cookieManager->clear();

        $this->assertSame([], $_COOKIE);
    }

    /**
     * Comprueba que se puedan almacenar valores vacíos.
     */
    public function testCanSetEmptyCookieValue(): void
    {
        $this->cookieManager->set(
            'optional_value',
            ''
        );

        $this->assertSame(
            '',
            $_COOKIE['optional_value']
        );

        $this->assertTrue(
            $this->cookieManager->has('optional_value')
        );
    }

    /**
     * Comprueba que se puedan almacenar valores numéricos
     * representados como cadenas.
     */
    public function testCanSetNumericCookieValue(): void
    {
        $this->cookieManager->set(
            'user_id',
            '12345'
        );

        $this->assertSame(
            '12345',
            $this->cookieManager->get('user_id')
        );
    }

    /**
     * Comprueba que se puedan almacenar valores con caracteres especiales.
     */
    public function testCanSetSpecialCharacters(): void
    {
        $value = 'user@example.com';

        $this->cookieManager->set(
            'user_email',
            $value
        );

        $this->assertSame(
            $value,
            $this->cookieManager->get('user_email')
        );
    }

    /**
     * Comprueba que se puedan almacenar cadenas Unicode.
     */
    public function testCanSetUnicodeValue(): void
    {
        $value = 'Usuario de prueba: México';

        $this->cookieManager->set(
            'user_name',
            $value
        );

        $this->assertSame(
            $value,
            $this->cookieManager->get('user_name')
        );
    }

    /**
     * Comprueba que se puedan almacenar varias cookies.
     */
    public function testCanManageMultipleCookies(): void
    {
        $this->cookieManager->set(
            'session_id',
            'session-123'
        );

        $this->cookieManager->set(
            'csrf_token',
            'csrf-456'
        );

        $this->cookieManager->set(
            'user_preferences',
            'light'
        );

        $this->assertSame(
            'session-123',
            $this->cookieManager->get('session_id')
        );

        $this->assertSame(
            'csrf-456',
            $this->cookieManager->get('csrf_token')
        );

        $this->assertSame(
            'light',
            $this->cookieManager->get('user_preferences')
        );
    }

    /**
     * Comprueba que establecer una cookie no elimine las existentes.
     */
    public function testSettingCookiePreservesOtherCookies(): void
    {
        $_COOKIE['existing_cookie'] = 'existing-value';

        $this->cookieManager->set(
            'new_cookie',
            'new-value'
        );

        $this->assertSame(
            'existing-value',
            $_COOKIE['existing_cookie']
        );

        $this->assertSame(
            'new-value',
            $_COOKIE['new_cookie']
        );
    }

    /**
     * Comprueba que una cookie pueda sobrescribirse varias veces.
     */
    public function testCanOverwriteCookieMultipleTimes(): void
    {
        $this->cookieManager->set('session_id', 'value-one');
        $this->cookieManager->set('session_id', 'value-two');
        $this->cookieManager->set('session_id', 'value-three');

        $this->assertSame(
            'value-three',
            $this->cookieManager->get('session_id')
        );
    }

    /**
     * Comprueba que una cookie con valor "0" se considere existente.
     */
    public function testZeroValueCookieIsConsideredPresent(): void
    {
        $this->cookieManager->set(
            'counter',
            '0'
        );

        $this->assertTrue(
            $this->cookieManager->has('counter')
        );

        $this->assertSame(
            '0',
            $this->cookieManager->get('counter')
        );
    }

    /**
     * Comprueba que una cookie con valor false representado
     * como cadena no se considere inexistente.
     */
    public function testFalseStringCookieIsConsideredPresent(): void
    {
        $this->cookieManager->set(
            'remember_me',
            'false'
        );

        $this->assertTrue(
            $this->cookieManager->has('remember_me')
        );

        $this->assertSame(
            'false',
            $this->cookieManager->get('remember_me')
        );
    }

    /**
     * Comprueba que get distinga entre una cookie inexistente
     * y una cookie existente con valor vacío.
     */
    public function testDistinguishesMissingAndEmptyCookies(): void
    {
        $_COOKIE['empty_cookie'] = '';

        $this->assertNull(
            $this->cookieManager->get('missing_cookie')
        );

        $this->assertSame(
            '',
            $this->cookieManager->get('empty_cookie')
        );
    }
}
