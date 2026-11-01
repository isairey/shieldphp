
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\UserIdentity;

final class UserIdentityTest extends TestCase
{
    private const USER_ID = 'user-123';

    private const EMAIL = 'isai@example.com';

    private const PASSWORD_HASH = '$2y$12$example-password-hash';

    private UserIdentity $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
    }

    /**
     * Crea una identidad de usuario para las pruebas.
     *
     * @param list<string> $roles
     */
    private function createUser(
        string $id = self::USER_ID,
        string $email = self::EMAIL,
        string $passwordHash = self::PASSWORD_HASH,
        bool $active = true,
        array $roles = ['user']
    ): UserIdentity {
        return new UserIdentity(
            $id,
            $email,
            $passwordHash,
            $active,
            $roles
        );
    }

    /**
     * Comprueba que se pueda crear una identidad de usuario.
     */
    public function testCreatesUserIdentity(): void
    {
        $user = $this->createUser();

        $this->assertInstanceOf(UserIdentity::class, $user);
    }

    /**
     * Comprueba que getId() devuelva el identificador correcto.
     */
    public function testGetIdReturnsCorrectIdentifier(): void
    {
        $this->assertSame(
            self::USER_ID,
            $this->user->getId()
        );
    }

    /**
     * Comprueba que getEmail() devuelva el correo correcto.
     */
    public function testGetEmailReturnsCorrectEmail(): void
    {
        $this->assertSame(
            self::EMAIL,
            $this->user->getEmail()
        );
    }

    /**
     * Comprueba que el correo se normalice al crear la identidad.
     */
    public function testNormalizesEmail(): void
    {
        $user = $this->createUser(
            email: '  ISAI@EXAMPLE.COM  '
        );

        $this->assertSame(
            'isai@example.com',
            $user->getEmail()
        );
    }

    /**
     * Comprueba que getPasswordHash() devuelva el hash almacenado.
     */
    public function testGetPasswordHashReturnsCorrectHash(): void
    {
        $this->assertSame(
            self::PASSWORD_HASH,
            $this->user->getPasswordHash()
        );
    }

    /**
     * Comprueba que isActive() devuelva true para una cuenta activa.
     */
    public function testIsActiveReturnsTrueForActiveUser(): void
    {
        $user = $this->createUser(active: true);

        $this->assertTrue($user->isActive());
    }

    /**
     * Comprueba que isActive() devuelva false para una cuenta inactiva.
     */
    public function testIsActiveReturnsFalseForInactiveUser(): void
    {
        $user = $this->createUser(active: false);

        $this->assertFalse($user->isActive());
    }

    /**
     * Comprueba que getRoles() devuelva los roles asignados.
     */
    public function testGetRolesReturnsAssignedRoles(): void
    {
        $roles = ['user', 'admin'];

        $user = $this->createUser(roles: $roles);

        $this->assertSame($roles, $user->getRoles());
    }

    /**
     * Comprueba que hasRole() encuentre un rol asignado.
     */
    public function testHasRoleReturnsTrueForAssignedRole(): void
    {
        $user = $this->createUser(
            roles: ['user', 'admin']
        );

        $this->assertTrue($user->hasRole('admin'));
    }

    /**
     * Comprueba que hasRole() rechace un rol no asignado.
     */
    public function testHasRoleReturnsFalseForUnassignedRole(): void
    {
        $this->assertFalse(
            $this->user->hasRole('admin')
        );
    }

    /**
     * Comprueba que una identidad sin roles devuelva un array vacío.
     */
    public function testSupportsUserWithoutRoles(): void
    {
        $user = $this->createUser(roles: []);

        $this->assertSame([], $user->getRoles());
        $this->assertFalse($user->hasRole('user'));
    }

    /**
     * Comprueba que una identidad pueda contener varios roles.
     */
    public function testSupportsMultipleRoles(): void
    {
        $roles = [
            'user',
            'moderator',
            'admin',
        ];

        $user = $this->createUser(roles: $roles);

        $this->assertSame($roles, $user->getRoles());

        foreach ($roles as $role) {
            $this->assertTrue($user->hasRole($role));
        }
    }

    /**
     * Comprueba que un rol asignado no implique que
     * la cuenta esté activa.
     */
    public function testInactiveUserCanStillHaveAssignedRoles(): void
    {
        $user = $this->createUser(
            active: false,
            roles: ['admin']
        );

        $this->assertFalse($user->isActive());
        $this->assertTrue($user->hasRole('admin'));
    }

    /**
     * Comprueba que diferentes usuarios mantengan
     * sus respectivas identidades independientes.
     */
    public function testUserIdentitiesRemainIndependent(): void
    {
        $firstUser = $this->createUser(
            id: 'user-001',
            email: 'first@example.com',
            roles: ['user']
        );

        $secondUser = $this->createUser(
            id: 'user-002',
            email: 'second@example.com',
            roles: ['admin']
        );

        $this->assertNotSame(
            $firstUser->getId(),
            $secondUser->getId()
        );

        $this->assertNotSame(
            $firstUser->getEmail(),
            $secondUser->getEmail()
        );

        $this->assertTrue($firstUser->hasRole('user'));
        $this->assertFalse($firstUser->hasRole('admin'));

        $this->assertTrue($secondUser->hasRole('admin'));
        $this->assertFalse($secondUser->hasRole('user'));
    }

    /**
     * Comprueba que los datos originales de la identidad
     * permanezcan iguales después de consultar sus propiedades.
     */
    public function testReadingPropertiesDoesNotChangeUserIdentity(): void
    {
        $originalId = $this->user->getId();
        $originalEmail = $this->user->getEmail();
        $originalHash = $this->user->getPasswordHash();
        $originalActive = $this->user->isActive();
        $originalRoles = $this->user->getRoles();

        $this->user->getId();
        $this->user->getEmail();
        $this->user->getPasswordHash();
        $this->user->isActive();
        $this->user->getRoles();

        $this->assertSame($originalId, $this->user->getId());
        $this->assertSame($originalEmail, $this->user->getEmail());
        $this->assertSame($originalHash, $this->user->getPasswordHash());
        $this->assertSame($originalActive, $this->user->isActive());
        $this->assertSame($originalRoles, $this->user->getRoles());
    }

    /**
     * Comprueba que getRoles() devuelva un array.
     */
    public function testGetRolesReturnsArray(): void
    {
        $this->assertIsArray($this->user->getRoles());
    }

    /**
     * Comprueba que hasRole() devuelva un booleano.
     */
    public function testHasRoleReturnsBoolean(): void
    {
        $this->assertIsBool(
            $this->user->hasRole('user')
        );
    }
}
