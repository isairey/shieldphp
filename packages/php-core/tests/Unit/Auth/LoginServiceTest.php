
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\AuthManager;
use ShieldPHP\Core\Auth\AuthResult;
use ShieldPHP\Core\Auth\LoginService;
use ShieldPHP\Core\Auth\UserIdentity;

final class LoginServiceTest extends TestCase
{
    private const EMAIL = 'isai@example.com';

    private const PASSWORD = 'SecurePassword123!';

    private AuthManager $authManager;

    private LoginService $loginService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authManager = $this->createMock(AuthManager::class);

        $this->loginService = new LoginService(
            $this->authManager
        );
    }

    /**
     * Crea una identidad de usuario para las pruebas.
     */
    private function createUser(): UserIdentity
    {
        return new UserIdentity(
            'user-123',
            self::EMAIL,
            '$2y$12$example-password-hash',
            true,
            ['user']
        );
    }

    /**
     * Comprueba que login() devuelva un AuthResult.
     */
    public function testLoginReturnsAuthResult(): void
    {
        $expectedResult = AuthResult::failure();

        $this->authManager
            ->expects($this->once())
            ->method('attempt')
            ->with(self::EMAIL, self::PASSWORD)
            ->willReturn($expectedResult);

        $result = $this->loginService->login(
            self::EMAIL,
            self::PASSWORD
        );

        $this->assertInstanceOf(AuthResult::class, $result);
    }

    /**
     * Comprueba que las credenciales sean delegadas a AuthManager.
     */
    public function testLoginDelegatesCredentialsToAuthManager(): void
    {
        $this->authManager
            ->expects($this->once())
            ->method('attempt')
            ->with(self::EMAIL, self::PASSWORD)
            ->willReturn(AuthResult::failure());

        $this->loginService->login(
            self::EMAIL,
            self::PASSWORD
        );
    }

    /**
     * Comprueba que se conserve el resultado de autenticación exitosa.
     */
    public function testLoginReturnsSuccessfulAuthenticationResult(): void
    {
        $user = $this->createUser();

        $expectedResult = AuthResult::success($user);

        $this->authManager
            ->expects($this->once())
            ->method('attempt')
            ->with(self::EMAIL, self::PASSWORD)
            ->willReturn($expectedResult);

        $result = $this->loginService->login(
            self::EMAIL,
            self::PASSWORD
        );

        $this->assertTrue($result->isSuccessful());
        $this->assertSame($user, $result->user());
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Comprueba que se conserve el resultado de autenticación fallida.
     */
    public function testLoginReturnsFailedAuthenticationResult(): void
    {
        $expectedResult = AuthResult::failure();

        $this->authManager
            ->expects($this->once())
            ->method('attempt')
            ->with(self::EMAIL, self::PASSWORD)
            ->willReturn($expectedResult);

        $result = $this->loginService->login(
            self::EMAIL,
            self::PASSWORD
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertNull($result->user());
        $this->assertSame($expectedResult, $result);
    }

    /**
     * Comprueba que LoginService no modifique el correo recibido.
     */
    public function testLoginPreservesEmailArgument(): void
    {
        $email = '  ISAI@EXAMPLE.COM  ';

        $this->authManager
            ->expects($this->once())
            ->method('attempt')
            ->with($email, self::PASSWORD)
            ->willReturn(AuthResult::failure());

        $this->loginService->login(
            $email,
            self::PASSWORD
        );
    }

    /**
     * Comprueba que LoginService no modifique la contraseña.
     */
    public function testLoginPreservesPasswordArgument(): void
    {
        $password = '  My Password  ';

        $this->authManager
            ->expects($this->once())
            ->method('attempt')
            ->with(self::EMAIL, $password)
            ->willReturn(AuthResult::failure());

        $this->loginService->login(
            self::EMAIL,
            $password
        );
    }

    /**
     * Comprueba que también se deleguen credenciales vacías.
     *
     * La validación de las credenciales corresponde a AuthManager.
     */
    public function testLoginDelegatesEmptyCredentials(): void
    {
        $this->authManager
            ->expects($this->once())
            ->method('attempt')
            ->with('', '')
            ->willReturn(AuthResult::failure());

        $result = $this->loginService->login('', '');

        $this->assertFalse($result->isSuccessful());
    }

    /**
     * Comprueba que cada llamada a login genere una llamada
     * independiente a AuthManager.
     */
    public function testMultipleLoginAttemptsAreDelegatedIndependently(): void
    {
        $this->authManager
            ->expects($this->exactly(2))
            ->method('attempt')
            ->willReturnOnConsecutiveCalls(
                AuthResult::failure(),
                AuthResult::failure()
            );

        $firstResult = $this->loginService->login(
            self::EMAIL,
            'WrongPassword123!'
        );

        $secondResult = $this->loginService->login(
            self::EMAIL,
            'AnotherWrongPassword123!'
        );

        $this->assertFalse($firstResult->isSuccessful());
        $this->assertFalse($secondResult->isSuccessful());
    }
}
