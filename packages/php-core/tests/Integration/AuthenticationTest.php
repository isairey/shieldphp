
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\AuthManager;
use ShieldPHP\Core\Auth\AuthResult;
use ShieldPHP\Core\Auth\LoginService;
use ShieldPHP\Core\Auth\PasswordHasher;
use ShieldPHP\Core\Auth\UserIdentity;
use ShieldPHP\Core\Contracts\UserProviderInterface;

final class AuthenticationTest extends TestCase
{
    private const CORRECT_PASSWORD = 'ShieldPHP_Secure2026!';

    private LoginService $loginService;

    private PasswordHasher $passwordHasher;

    protected function setUp(): void
    {
        $this->passwordHasher = new PasswordHasher();

        // Crear hashes de prueba.
        $activeHash = $this->passwordHasher->hash(
            self::CORRECT_PASSWORD
        );

        $inactiveHash = $this->passwordHasher->hash(
            self::CORRECT_PASSWORD
        );

        // Usuarios de prueba.
        $users = [
            'isai@example.com' => new UserIdentity(
                id: 'user-001',
                email: 'isai@example.com',
                passwordHash: $activeHash,
                active: true,
                roles: ['user']
            ),

            'inactive@example.com' => new UserIdentity(
                id: 'user-002',
                email: 'inactive@example.com',
                passwordHash: $inactiveHash,
                active: false,
                roles: ['user']
            ),

            'admin@example.com' => new UserIdentity(
                id: 'user-003',
                email: 'admin@example.com',
                passwordHash: $this->passwordHasher->hash(
                    'Admin_Secure2026!'
                ),
                active: true,
                roles: ['admin', 'user']
            ),
        ];

        // Proveedor de usuarios simulado.
        $userProvider = new class($users) implements UserProviderInterface {

            /**
             * @param array<string, UserIdentity> $users
             */
            public function __construct(
                private readonly array $users
            ) {
            }

            public function findByEmail(
                string $email
            ): ?UserIdentity {
                $email = strtolower(trim($email));

                return $this->users[$email] ?? null;
            }

            public function findById(
                string $id
            ): ?UserIdentity {
                foreach ($this->users as $user) {
                    if ($user->getId() === $id) {
                        return $user;
                    }
                }

                return null;
            }
        };

        // Crear el administrador de autenticación.
        $authManager = new AuthManager(
            userProvider: $userProvider,
            passwordHasher: $this->passwordHasher
        );

        // Crear el servicio de inicio de sesión.
        $this->loginService = new LoginService(
            $authManager
        );
    }

    /**
     * Comprueba que un usuario activo pueda iniciar sesión.
     */
    public function testAuthenticatesUserWithCorrectCredentials(): void
    {
        $result = $this->loginService->login(
            'isai@example.com',
            self::CORRECT_PASSWORD
        );

        self::assertInstanceOf(AuthResult::class, $result);
        self::assertTrue($result->isSuccessful());

        $user = $result->user();

        self::assertInstanceOf(UserIdentity::class, $user);
        self::assertSame('user-001', $user->getId());
        self::assertSame('isai@example.com', $user->getEmail());
        self::assertTrue($user->isActive());
        self::assertSame(['user'], $user->getRoles());
    }

    /**
     * Comprueba que las credenciales incorrectas sean rechazadas.
     */
    public function testRejectsIncorrectPassword(): void
    {
        $result = $this->loginService->login(
            'isai@example.com',
            'IncorrectPassword2026!'
        );

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->user());
    }

    /**
     * Comprueba que un usuario inexistente no pueda autenticarse.
     */
    public function testRejectsNonexistentUser(): void
    {
        $result = $this->loginService->login(
            'unknown@example.com',
            self::CORRECT_PASSWORD
        );

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->user());
    }

    /**
     * Comprueba que las cuentas desactivadas sean rechazadas.
     */
    public function testRejectsInactiveUser(): void
    {
        $result = $this->loginService->login(
            'inactive@example.com',
            self::CORRECT_PASSWORD
        );

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->user());
    }

    /**
     * Comprueba que se normalicen los correos electrónicos.
     */
    public function testNormalizesEmailBeforeAuthentication(): void
    {
        $result = $this->loginService->login(
            '  ISAI@EXAMPLE.COM  ',
            self::CORRECT_PASSWORD
        );

        self::assertTrue($result->isSuccessful());

        self::assertSame(
            'isai@example.com',
            $result->user()?->getEmail()
        );
    }

    /**
     * Comprueba que un administrador pueda autenticarse.
     */
    public function testAuthenticatesAdministrator(): void
    {
        $result = $this->loginService->login(
            'admin@example.com',
            'Admin_Secure2026!'
        );

        self::assertTrue($result->isSuccessful());

        $user = $result->user();

        self::assertNotNull($user);
        self::assertSame('user-003', $user->getId());
        self::assertTrue($user->hasRole('admin'));
        self::assertTrue($user->hasRole('user'));
    }

    /**
     * Comprueba que el resultado fallido nunca exponga un usuario.
     */
    public function testFailedAuthenticationDoesNotExposeUser(): void
    {
        $result = $this->loginService->login(
            'isai@example.com',
            'WrongPassword!'
        );

        self::assertInstanceOf(AuthResult::class, $result);
        self::assertFalse($result->isSuccessful());
        self::assertNull($result->user());
    }

    /**
     * Comprueba que el hash almacenado no sea la contraseña original.
     */
    public function testPasswordIsStoredAsHash(): void
    {
        $hash = $this->passwordHasher->hash(
            self::CORRECT_PASSWORD
        );

        self::assertNotSame(
            self::CORRECT_PASSWORD,
            $hash
        );

        self::assertTrue(
            $this->passwordHasher->verify(
                self::CORRECT_PASSWORD,
                $hash
            )
        );

        self::assertFalse(
            $this->passwordHasher->verify(
                'WrongPassword!',
                $hash
            )
        );
    }

    /**
     * Comprueba que los datos inválidos sean rechazados.
     *
     * @dataProvider invalidCredentialsProvider
     */
    public function testRejectsInvalidCredentials(
        string $email,
        string $password
    ): void {
        $result = $this->loginService->login(
            $email,
            $password
        );

        self::assertFalse($result->isSuccessful());
        self::assertNull($result->user());
    }

    /**
     * Casos de credenciales inválidas.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function invalidCredentialsProvider(): iterable
    {
        yield 'correo vacío' => ['', self::CORRECT_PASSWORD];

        yield 'correo inválido' => [
            'correo-invalido',
            self::CORRECT_PASSWORD,
        ];

        yield 'contraseña vacía' => [
            'isai@example.com',
            '',
        ];

        yield 'correo demasiado largo' => [
            str_repeat('a', 250) . '@example.com',
            self::CORRECT_PASSWORD,
        ];
    }
}
