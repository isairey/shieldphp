
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Session;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Session\SessionConfig;
use ShieldPHP\Core\Session\SessionManager;

final class SessionManagerTest extends TestCase
{
    private SessionManager $manager;

    private array $originalSession = [];

    private array $originalCookieParams = [];

    private string $originalSessionName;

    private array $originalIniSettings = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Guarda el estado original de PHP para no afectar otras pruebas.
        $this->originalSession = $_SESSION ?? [];

        $this->originalSessionName = session_name();

        $this->originalCookieParams = session_get_cookie_params();

        $iniOptions = [
            'session.use_strict_mode',
            'session.use_only_cookies',
            'session.use_trans_sid',
        ];

        foreach ($iniOptions as $option) {
            $this->originalIniSettings[$option] = ini_get($option);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('');
        $_SESSION = [];

        $this->manager = $this->createManager();
    }

    protected function tearDown(): void
    {
        // Cierra la sesión iniciada por la prueba.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        session_id('');

        // Restaura la configuración global de PHP.
        session_name($this->originalSessionName);

        session_set_cookie_params(
            $this->originalCookieParams
        );

        foreach ($this->originalIniSettings as $option => $value) {
            if ($value !== false) {
                ini_set($option, (string) $value);
            }
        }

        $_SESSION = $this->originalSession;

        parent::tearDown();
    }

    /**
     * Crea un SessionManager con configuración personalizada.
     */
    private function createManager(
        array $overrides = []
    ): SessionManager {
        $options = array_merge(
            [
                'session_name' => 'ShieldPHPTest',
                'cookie_lifetime' => 0,
                'cookie_path' => '/',
                'cookie_domain' => '',
                'cookie_secure' => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
                'use_only_cookies' => true,
                'use_trans_sid' => false,
            ],
            $overrides
        );

        return new SessionManager(
            new SessionConfig($options)
        );
    }

    /**
     * Crea e inicia un administrador de sesiones.
     */
    private function createStartedManager(
        array $overrides = []
    ): SessionManager {
        $manager = $this->createManager($overrides);

        $manager->start();

        return $manager;
    }

    /**
     * Comprueba que SessionManager se pueda instanciar.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            SessionManager::class,
            $this->manager
        );
    }

    /**
     * Comprueba que se pueda iniciar una sesión.
     */
    public function testCanStartSession(): void
    {
        $this->manager->start();

        $this->assertSame(
            PHP_SESSION_ACTIVE,
            session_status()
        );
    }

    /**
     * Comprueba que isActive detecte una sesión iniciada.
     */
    public function testSessionIsActiveAfterStarting(): void
    {
        $this->manager->start();

        $this->assertTrue(
            $this->manager->isActive()
        );
    }

    /**
     * Comprueba que el administrador utilice el nombre configurado.
     */
    public function testUsesConfiguredSessionName(): void
    {
        $manager = $this->createStartedManager([
            'session_name' => 'ShieldPHPCustom',
        ]);

        $this->assertSame(
            'ShieldPHPCustom',
            session_name()
        );

        $this->assertTrue(
            $manager->isActive()
        );
    }

    /**
     * Comprueba que se apliquen las opciones de seguridad de las cookies.
     */
    public function testAppliesSecureCookieOptions(): void
    {
        $this->manager->start();

        $cookieParams = session_get_cookie_params();

        $this->assertTrue(
            $cookieParams['secure']
        );

        $this->assertTrue(
            $cookieParams['httponly']
        );

        $this->assertSame(
            'Lax',
            $cookieParams['samesite']
        );

        $this->assertSame(
            '/',
            $cookieParams['path']
        );
    }

    /**
     * Comprueba que se pueda guardar información en la sesión.
     */
    public function testCanSetSessionValue(): void
    {
        $manager = $this->createStartedManager();

        $manager->set(
            'user_id',
            'user-123'
        );

        $this->assertSame(
            'user-123',
            $_SESSION['user_id']
        );
    }

    /**
     * Comprueba que se pueda recuperar información de la sesión.
     */
    public function testCanGetSessionValue(): void
    {
        $manager = $this->createStartedManager();

        $manager->set(
            'user_id',
            'user-123'
        );

        $this->assertSame(
            'user-123',
            $manager->get('user_id')
        );
    }

    /**
     * Una clave inexistente debe devolver null por defecto.
     */
    public function testReturnsNullForMissingValue(): void
    {
        $manager = $this->createStartedManager();

        $this->assertNull(
            $manager->get('nonexistent')
        );
    }

    /**
     * Comprueba que get pueda devolver un valor predeterminado.
     */
    public function testReturnsDefaultValueForMissingKey(): void
    {
        $manager = $this->createStartedManager();

        $this->assertSame(
            'guest',
            $manager->get('username', 'guest')
        );
    }

    /**
     * Comprueba que has detecte una clave existente.
     */
    public function testHasReturnsTrueForExistingKey(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('username', 'isai');

        $this->assertTrue(
            $manager->has('username')
        );
    }

    /**
     * Comprueba que has detecte una clave inexistente.
     */
    public function testHasReturnsFalseForMissingKey(): void
    {
        $manager = $this->createStartedManager();

        $this->assertFalse(
            $manager->has('unknown_key')
        );
    }

    /**
     * Comprueba que se pueda sobrescribir un valor.
     */
    public function testCanUpdateSessionValue(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('username', 'old-user');

        $manager->set('username', 'new-user');

        $this->assertSame(
            'new-user',
            $manager->get('username')
        );
    }

    /**
     * Comprueba que se puedan guardar arreglos.
     */
    public function testCanStoreArrayValues(): void
    {
        $manager = $this->createStartedManager();

        $user = [
            'id' => 123,
            'name' => 'Isai',
            'roles' => ['USER', 'ADMIN'],
        ];

        $manager->set('user', $user);

        $this->assertSame(
            $user,
            $manager->get('user')
        );
    }

    /**
     * Comprueba que se puedan almacenar valores booleanos.
     */
    public function testCanStoreBooleanValues(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('remember_me', true);

        $this->assertTrue(
            $manager->get('remember_me')
        );
    }

    /**
     * Comprueba que se puedan almacenar valores nulos.
     */
    public function testCanStoreNullValues(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('optional_value', null);

        $this->assertNull(
            $manager->get('optional_value')
        );

        $this->assertTrue(
            $manager->has('optional_value')
        );
    }

    /**
     * Comprueba que se puedan almacenar cadenas vacías.
     */
    public function testCanStoreEmptyString(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('empty_value', '');

        $this->assertSame(
            '',
            $manager->get('empty_value')
        );

        $this->assertTrue(
            $manager->has('empty_value')
        );
    }

    /**
     * Comprueba que se puedan guardar estructuras anidadas.
     */
    public function testCanStoreNestedArrayValues(): void
    {
        $manager = $this->createStartedManager();

        $data = [
            'user' => [
                'profile' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
            ],
            'permissions' => [
                'users.read',
                'users.write',
            ],
        ];

        $manager->set('application_data', $data);

        $this->assertSame(
            $data,
            $manager->get('application_data')
        );
    }

    /**
     * Comprueba que se pueda eliminar una clave.
     */
    public function testCanRemoveSessionValue(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('user_id', 'user-123');

        $manager->remove('user_id');

        $this->assertFalse(
            $manager->has('user_id')
        );
    }

    /**
     * Comprueba que eliminar una clave no afecte las demás.
     */
    public function testRemovingValueDoesNotAffectOtherValues(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('user_id', 'user-123');
        $manager->set('csrf_token', 'csrf-456');
        $manager->set('theme', 'light');

        $manager->remove('user_id');

        $this->assertFalse(
            $manager->has('user_id')
        );

        $this->assertSame(
            'csrf-456',
            $manager->get('csrf_token')
        );

        $this->assertSame(
            'light',
            $manager->get('theme')
        );
    }

    /**
     * Eliminar una clave inexistente no debe provocar errores.
     */
    public function testCanRemoveNonexistentKey(): void
    {
        $manager = $this->createStartedManager();

        $manager->remove('unknown_key');

        $this->assertFalse(
            $manager->has('unknown_key')
        );
    }

    /**
     * Comprueba que se puedan limpiar los datos de sesión.
     */
    public function testCanClearSessionData(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('user_id', 'user-123');
        $manager->set('csrf_token', 'csrf-456');
        $manager->set('theme', 'light');

        $manager->clear();

        $this->assertEmpty($_SESSION);
    }

    /**
     * Limpiar una sesión vacía no debe provocar errores.
     */
    public function testCanClearEmptySession(): void
    {
        $manager = $this->createStartedManager();

        $manager->clear();

        $this->assertSame([], $_SESSION);
    }

    /**
     * Comprueba que se pueda destruir una sesión.
     */
    public function testCanDestroySession(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('user_id', 'user-123');

        $manager->destroy();

        $this->assertNotSame(
            PHP_SESSION_ACTIVE,
            session_status()
        );
    }

    /**
     * Destruir la sesión debe eliminar sus datos.
     */
    public function testDestroyRemovesSessionData(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('user_id', 'user-123');

        $manager->destroy();

        $this->assertEmpty($_SESSION);
    }

    /**
     * Después de destruir la sesión, isActive debe devolver false.
     */
    public function testSessionIsInactiveAfterDestroy(): void
    {
        $manager = $this->createStartedManager();

        $manager->destroy();

        $this->assertFalse(
            $manager->isActive()
        );
    }

    /**
     * Comprueba que la sesión pueda iniciarse nuevamente
     * después de destruirse.
     */
    public function testCanStartNewSessionAfterDestroy(): void
    {
        $manager = $this->createStartedManager();

        $manager->destroy();

        $manager->start();

        $this->assertSame(
            PHP_SESSION_ACTIVE,
            session_status()
        );

        $this->assertTrue(
            $manager->isActive()
        );
    }

    /**
     * Comprueba que se pueda regenerar el identificador.
     */
    public function testCanRegenerateSessionId(): void
    {
        $manager = $this->createStartedManager();

        $oldId = session_id();

        $manager->regenerateId();

        $newId = session_id();

        $this->assertNotEmpty($newId);

        $this->assertNotSame(
            $oldId,
            $newId
        );
    }

    /**
     * Regenerar el identificador no debe eliminar los datos.
     */
    public function testRegeneratingIdPreservesSessionData(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('user_id', 'user-123');
        $manager->set('username', 'isai');

        $manager->regenerateId();

        $this->assertSame(
            'user-123',
            $manager->get('user_id')
        );

        $this->assertSame(
            'isai',
            $manager->get('username')
        );
    }

    /**
     * Comprueba que iniciar dos veces no produzca problemas.
     */
    public function testStartingSessionTwiceIsSafe(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('user_id', 'user-123');

        $manager->start();

        $this->assertTrue(
            $manager->isActive()
        );

        $this->assertSame(
            'user-123',
            $manager->get('user_id')
        );
    }

    /**
     * Comprueba que los datos existentes se mantengan
     * al agregar nuevas claves.
     */
    public function testSettingNewValuePreservesExistingValues(): void
    {
        $manager = $this->createStartedManager();

        $manager->set('user_id', 'user-123');

        $manager->set('role', 'ADMIN');

        $this->assertSame(
            'user-123',
            $manager->get('user_id')
        );

        $this->assertSame(
            'ADMIN',
            $manager->get('role')
        );
    }

    /**
     * Comprueba que los valores con caracteres Unicode
     * puedan almacenarse correctamente.
     */
    public function testSupportsUnicodeValues(): void
    {
        $manager = $this->createStartedManager();

        $value = 'Usuario de prueba - México';

        $manager->set('username', $value);

        $this->assertSame(
            $value,
            $manager->get('username')
        );
    }
}
