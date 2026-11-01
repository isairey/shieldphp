
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\AuthManager;
use ShieldPHP\Core\Auth\AuthResult;
use ShieldPHP\Core\Auth\PasswordHasher;
use ShieldPHP\Core\Auth\UserIdentity;
use ShieldPHP\Core\Contracts\UserProviderInterface;

final class AuthManagerTest extends TestCase
{
    private const USER_ID = 'user-123';

    private const USER_EMAIL = 'isai@example.com';

    private const PASSWORD = 'SecurePassword123!';

    private PasswordHasher $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordHasher = new PasswordHasher();
    }

    /**
     * Crea una identidad de usuario para las pruebas.
     */
    private function createUser(
        ?string $password = null,
        bool $active = true,
        array $roles = ['user']
    ): UserIdentity {
        return new UserIdentity(
            self::USER_ID,
            self::USER_EMAIL,
            $this->passwordHasher->hash(
                $password ?? self::PASSWORD
            ),
            $active,
            $roles
        );
    }

    /**
     * Crea un AuthManager con el proveedor indicado.
     */
    private function createAuthManager(
        UserProviderInterface $provider
    ): AuthManager {
        return new AuthManager(
            $provider,
            $this->passwordHasher
        );
    }

    /**
     * Comprueba que se autentique un usuario con credenciales válidas.
     */
    public function testAuthenticatesUserWithValidCredentials(): void
    {
        $user = $this->createUser();

        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->expects($this->once())
            ->method('findByEmail')
            ->with(self::USER_EMAIL)
            ->willReturn($user);

        $result = $this->createAuthManager($provider)->attempt(
            self::USER_EMAIL,
            self::PASSWORD
        );

        $this->assertInstanceOf(AuthResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertSame($user, $result->user());
    }

    /**
     * Comprueba que el correo se normalice antes de buscar al usuario.
     */
    public function testNormalizesEmailBeforeAuthentication(): void
    {
        $user = $this->createUser();

        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->expects($this->once())
            ->method('findByEmail')
            ->with(self::USER_EMAIL)
            ->willReturn($user);

        $result = $this->createAuthManager($provider)->attempt(
            '  ISAI@EXAMPLE.COM  ',
            self::PASSWORD
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertSame($user, $result->user());
    }

    /**
     * Comprueba que se rechace una contraseña incorrecta.
     */
    public function testRejectsIncorrectPassword(): void
    {
        $user = $this->createUser();

        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->method('findByEmail')
            ->willReturn($user);

        $result = $this->createAuthManager($provider)->attempt(
            self::USER_EMAIL,
            'WrongPassword123!'
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertNull($result->user());
    }

    /**
     * Comprueba que se rechace un usuario inexistente.
     */
    public function testRejectsNonexistentUser(): void
    {
        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->expects($this->once())
            ->method('findByEmail')
            ->with(self::USER_EMAIL)
            ->willReturn(null);

        $result = $this->createAuthManager($provider)->attempt(
            self::USER_EMAIL,
            self::PASSWORD
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertNull($result->user());
    }

    /**
     * Comprueba que no se permita iniciar sesión con una cuenta inactiva.
     */
    public function testRejectsInactiveUser(): void
    {
        $user = $this->createUser(active: false);

        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->method('findByEmail')
            ->willReturn($user);

        $result = $this->createAuthManager($provider)->attempt(
            self::USER_EMAIL,
            self::PASSWORD
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertNull($result->user());
    }

    /**
     * Comprueba que una contraseña incorrecta no autentique
     * una cuenta aunque el usuario exista y esté activo.
     */
    public function testDoesNotAuthenticateUserWithWrongPassword(): void
    {
        $user = $this->createUser();

        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->method('findByEmail')
            ->willReturn($user);

        $result = $this->createAuthManager($provider)->attempt(
            self::USER_EMAIL,
            ''
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertNull($result->user());
    }

    /**
     * Comprueba que todas las causas de autenticación fallida
     * produzcan el mismo resultado público.
     */
    public function testReturnsConsistentFailureResult(): void
    {
        $wrongPasswordUser = $this->createUser();

        $inactiveUser = $this->createUser(active: false);

        $scenarios = [
            [
                'provider_result' => null,
                'password' => self::PASSWORD,
            ],
            [
                'provider_result' => $wrongPasswordUser,
                'password' => 'IncorrectPassword!',
            ],
            [
                'provider_result' => $inactiveUser,
                'password' => self::PASSWORD,
            ],
        ];

        foreach ($scenarios as $scenario) {
            $provider = $this->createMock(UserProviderInterface::class);

            $provider
                ->method('findByEmail')
                ->willReturn($scenario['provider_result']);

            $result = $this->createAuthManager($provider)->attempt(
                self::USER_EMAIL,
                $scenario['password']
            );

            $this->assertFalse($result->isSuccessful());
            $this->assertNull($result->user());
        }
    }

    /**
     * Comprueba que el usuario autenticado conserve sus roles.
     */
    public function testAuthenticatedUserPreservesRoles(): void
    {
        $user = $this->createUser(
            roles: ['user', 'admin']
        );

        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->method('findByEmail')
            ->willReturn($user);

        $result = $this->createAuthManager($provider)->attempt(
            self::USER_EMAIL,
            self::PASSWORD
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(
            ['user', 'admin'],
            $result->user()->getRoles()
        );
    }

    /**
     * Comprueba que el proveedor no busque al usuario por ID
     * durante una autenticación por correo.
     */
    public function testDoesNotSearchUserByIdDuringAuthentication(): void
    {
        $user = $this->createUser();

        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->expects($this->never())
            ->method('findById');

        $provider
            ->method('findByEmail')
            ->willReturn($user);

        $result = $this->createAuthManager($provider)->attempt(
            self::USER_EMAIL,
            self::PASSWORD
        );

        $this->assertTrue($result->isSuccessful());
    }

    /**
     * Comprueba que dos intentos fallidos no produzcan
     * accidentalmente una autenticación exitosa.
     */
    public function testRepeatedFailedAttemptsRemainUnauthenticated(): void
    {
        $provider = $this->createMock(UserProviderInterface::class);

        $provider
            ->method('findByEmail')
            ->willReturn(null);

        $authManager = $this->createAuthManager($provider);

        $firstResult = $authManager->attempt(
            self::USER_EMAIL,
            self::PASSWORD
        );

        $secondResult = $authManager->attempt(
            self::USER_EMAIL,
            self::PASSWORD
        );

        $this->assertFalse($firstResult->isSuccessful());
        $this->assertFalse($secondResult->isSuccessful());

        $this->assertNull($firstResult->user());
        $this->assertNull($secondResult->user());
    }
}
