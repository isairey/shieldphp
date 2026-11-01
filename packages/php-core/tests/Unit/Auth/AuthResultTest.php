
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\AuthResult;
use ShieldPHP\Core\Auth\UserIdentity;

final class AuthResultTest extends TestCase
{
    private function createUser(
        string $id = 'user-123',
        string $email = 'isai@example.com',
        bool $active = true,
        array $roles = ['user']
    ): UserIdentity {
        return new UserIdentity(
            $id,
            $email,
            '$2y$12$example-password-hash',
            $active,
            $roles
        );
    }

    /**
     * Comprueba que success() genere una instancia de AuthResult.
     */
    public function testSuccessCreatesAuthResultInstance(): void
    {
        $user = $this->createUser();

        $result = AuthResult::success($user);

        $this->assertInstanceOf(AuthResult::class, $result);
    }

    /**
     * Comprueba que failure() genere una instancia de AuthResult.
     */
    public function testFailureCreatesAuthResultInstance(): void
    {
        $result = AuthResult::failure();

        $this->assertInstanceOf(AuthResult::class, $result);
    }

    /**
     * Comprueba que un resultado exitoso indique autenticación correcta.
     */
    public function testSuccessfulResultReturnsTrue(): void
    {
        $user = $this->createUser();

        $result = AuthResult::success($user);

        $this->assertTrue($result->isSuccessful());
    }

    /**
     * Comprueba que un resultado fallido indique autenticación incorrecta.
     */
    public function testFailedResultReturnsFalse(): void
    {
        $result = AuthResult::failure();

        $this->assertFalse($result->isSuccessful());
    }

    /**
     * Comprueba que success() conserve exactamente la identidad recibida.
     */
    public function testSuccessfulResultPreservesUserIdentity(): void
    {
        $user = $this->createUser();

        $result = AuthResult::success($user);

        $this->assertSame($user, $result->user());
    }

    /**
     * Comprueba que failure() no devuelva una identidad de usuario.
     */
    public function testFailedResultReturnsNullUser(): void
    {
        $result = AuthResult::failure();

        $this->assertNull($result->user());
    }

    /**
     * Comprueba que la identidad conserve sus datos.
     */
    public function testSuccessfulResultPreservesUserData(): void
    {
        $user = $this->createUser(
            id: 'user-456',
            email: 'developer@example.com',
            roles: ['user', 'admin']
        );

        $result = AuthResult::success($user);

        $authenticatedUser = $result->user();

        $this->assertNotNull($authenticatedUser);
        $this->assertSame('user-456', $authenticatedUser->getId());
        $this->assertSame(
            'developer@example.com',
            $authenticatedUser->getEmail()
        );
        $this->assertSame(
            ['user', 'admin'],
            $authenticatedUser->getRoles()
        );
    }

    /**
     * Comprueba que los resultados exitosos y fallidos
     * mantengan estados independientes.
     */
    public function testSuccessfulAndFailedResultsAreIndependent(): void
    {
        $user = $this->createUser();

        $successfulResult = AuthResult::success($user);
        $failedResult = AuthResult::failure();

        $this->assertTrue($successfulResult->isSuccessful());
        $this->assertFalse($failedResult->isSuccessful());

        $this->assertSame($user, $successfulResult->user());
        $this->assertNull($failedResult->user());
    }

    /**
     * Comprueba que un resultado fallido no revele
     * información sobre la identidad del usuario.
     */
    public function testFailedResultDoesNotExposeUserInformation(): void
    {
        $result = AuthResult::failure();

        $this->assertFalse($result->isSuccessful());
        $this->assertNull($result->user());
    }

    /**
     * Comprueba que pueda crearse un resultado exitoso
     * con una identidad que tenga varios roles.
     */
    public function testSuccessAcceptsUserWithMultipleRoles(): void
    {
        $user = $this->createUser(
            roles: ['user', 'moderator', 'admin']
        );

        $result = AuthResult::success($user);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame(
            ['user', 'moderator', 'admin'],
            $result->user()->getRoles()
        );
    }
}
