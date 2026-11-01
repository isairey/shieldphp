
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Authorization;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Authorization\Permission;
use ShieldPHP\Core\Authorization\Role;
use ShieldPHP\Core\Authorization\RoleManager;

final class RoleManagerTest extends TestCase
{
    private RoleManager $roleManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = new RoleManager();
    }

    /**
     * Crea un rol para las pruebas.
     */
    private function createRole(
        string $name = 'admin',
        string $description = 'Administrator'
    ): Role {
        return new Role($name, $description);
    }

    /**
     * Crea un permiso para asignarlo a los roles.
     */
    private function createPermission(
        string $name = 'users.read',
        string $description = 'Read users'
    ): Permission {
        return new Permission($name, $description);
    }

    /**
     * Comprueba que se pueda crear RoleManager.
     */
    public function testCreatesRoleManager(): void
    {
        $manager = new RoleManager();

        $this->assertInstanceOf(RoleManager::class, $manager);
    }

    /**
     * Comprueba que el administrador comience vacío.
     */
    public function testStartsWithEmptyRoleCollection(): void
    {
        $this->assertSame([], $this->roleManager->all());
        $this->assertSame([], $this->roleManager->names());
        $this->assertSame(0, $this->roleManager->count());
    }

    /**
     * Comprueba que se pueda registrar un rol.
     */
    public function testRegistersRole(): void
    {
        $role = $this->createRole();

        $this->roleManager->register($role);

        $this->assertTrue(
            $this->roleManager->has('admin')
        );
    }

    /**
     * Comprueba que get() devuelva el rol registrado.
     */
    public function testGetReturnsRegisteredRole(): void
    {
        $role = $this->createRole();

        $this->roleManager->register($role);

        $result = $this->roleManager->get('admin');

        $this->assertInstanceOf(Role::class, $result);
        $this->assertSame($role, $result);
    }

    /**
     * Comprueba que get() devuelva null para un rol inexistente.
     */
    public function testGetReturnsNullForUnknownRole(): void
    {
        $result = $this->roleManager->get('unknown');

        $this->assertNull($result);
    }

    /**
     * Comprueba que getOrFail() devuelva el rol registrado.
     */
    public function testGetOrFailReturnsRegisteredRole(): void
    {
        $role = $this->createRole();

        $this->roleManager->register($role);

        $result = $this->roleManager->getOrFail('admin');

        $this->assertInstanceOf(Role::class, $result);
        $this->assertSame($role, $result);
    }

    /**
     * Comprueba que has() encuentre un rol registrado.
     */
    public function testHasReturnsTrueForRegisteredRole(): void
    {
        $this->roleManager->register(
            $this->createRole()
        );

        $this->assertTrue(
            $this->roleManager->has('admin')
        );
    }

    /**
     * Comprueba que has() rechace un rol inexistente.
     */
    public function testHasReturnsFalseForUnknownRole(): void
    {
        $this->assertFalse(
            $this->roleManager->has('superadmin')
        );
    }

    /**
     * Comprueba que define() cree y registre un rol.
     */
    public function testDefineCreatesAndRegistersRole(): void
    {
        $role = $this->roleManager->define(
            'moderator',
            'Content moderator'
        );

        $this->assertInstanceOf(Role::class, $role);

        $this->assertTrue(
            $this->roleManager->has('moderator')
        );

        $this->assertSame(
            $role,
            $this->roleManager->get('moderator')
        );
    }

    /**
     * Comprueba que define() permita crear roles
     * sin proporcionar una descripción personalizada.
     */
    public function testDefineAcceptsRoleWithoutDescription(): void
    {
        $role = $this->roleManager->define('user');

        $this->assertInstanceOf(Role::class, $role);

        $this->assertTrue(
            $this->roleManager->has('user')
        );
    }

    /**
     * Comprueba que all() devuelva todos los roles.
     */
    public function testAllReturnsRegisteredRoles(): void
    {
        $admin = $this->createRole(
            'admin',
            'Administrator'
        );

        $moderator = $this->createRole(
            'moderator',
            'Moderator'
        );

        $user = $this->createRole(
            'user',
            'Regular user'
        );

        $this->roleManager->register($admin);
        $this->roleManager->register($moderator);
        $this->roleManager->register($user);

        $roles = $this->roleManager->all();

        $this->assertCount(3, $roles);
        $this->assertContains($admin, $roles, true);
        $this->assertContains($moderator, $roles, true);
        $this->assertContains($user, $roles, true);
    }

    /**
     * Comprueba que names() devuelva los nombres de los roles.
     */
    public function testNamesReturnsRoleNames(): void
    {
        $this->roleManager->define('admin');
        $this->roleManager->define('moderator');
        $this->roleManager->define('user');

        $names = $this->roleManager->names();

        $this->assertIsArray($names);
        $this->assertCount(3, $names);

        $this->assertContains('admin', $names);
        $this->assertContains('moderator', $names);
        $this->assertContains('user', $names);
    }

    /**
     * Comprueba que count() devuelva la cantidad
     * correcta de roles registrados.
     */
    public function testCountReturnsRegisteredRoleCount(): void
    {
        $this->assertSame(0, $this->roleManager->count());

        $this->roleManager->define('user');

        $this->assertSame(1, $this->roleManager->count());

        $this->roleManager->define('moderator');

        $this->assertSame(2, $this->roleManager->count());

        $this->roleManager->define('admin');

        $this->assertSame(3, $this->roleManager->count());
    }

    /**
     * Comprueba que remove() elimine un rol existente.
     */
    public function testRemoveDeletesRegisteredRole(): void
    {
        $this->roleManager->define('admin');

        $result = $this->roleManager->remove('admin');

        $this->assertTrue($result);

        $this->assertFalse(
            $this->roleManager->has('admin')
        );
    }

    /**
     * Comprueba que remove() devuelva false
     * cuando el rol no existe.
     */
    public function testRemoveReturnsFalseForUnknownRole(): void
    {
        $result = $this->roleManager->remove('unknown');

        $this->assertFalse($result);
    }

    /**
     * Comprueba que eliminar un rol actualice el conteo.
     */
    public function testRemoveUpdatesRoleCount(): void
    {
        $this->roleManager->define('admin');
        $this->roleManager->define('moderator');
        $this->roleManager->define('user');

        $this->roleManager->remove('moderator');

        $this->assertSame(2, $this->roleManager->count());
    }

    /**
     * Comprueba que eliminar un rol no afecte a los demás.
     */
    public function testRemoveDoesNotDeleteOtherRoles(): void
    {
        $this->roleManager->define('admin');
        $this->roleManager->define('moderator');
        $this->roleManager->define('user');

        $this->roleManager->remove('moderator');

        $this->assertTrue($this->roleManager->has('admin'));
        $this->assertFalse($this->roleManager->has('moderator'));
        $this->assertTrue($this->roleManager->has('user'));
    }

    /**
     * Comprueba que hasPermission() reconozca
     * un permiso asignado al rol.
     */
    public function testHasPermissionReturnsTrueForAssignedPermission(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $this->roleManager->register($role);

        $this->assertTrue(
            $this->roleManager->hasPermission(
                'admin',
                'users.read'
            )
        );
    }

    /**
     * Comprueba que hasPermission() rechace
     * un permiso que no está asignado.
     */
    public function testHasPermissionReturnsFalseForUnassignedPermission(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $this->roleManager->register($role);

        $this->assertFalse(
            $this->roleManager->hasPermission(
                'admin',
                'users.delete'
            )
        );
    }

    /**
     * Comprueba que un rol inexistente no conceda permisos.
     */
    public function testUnknownRoleDoesNotGrantPermissions(): void
    {
        $this->assertFalse(
            $this->roleManager->hasPermission(
                'superadmin',
                'users.read'
            )
        );
    }

    /**
     * Comprueba que un rol sin permisos registrados
     * no conceda acceso a ninguna operación.
     */
    public function testRoleWithoutPermissionsHasNoPermissions(): void
    {
        $role = $this->createRole();

        $this->roleManager->register($role);

        $this->assertFalse(
            $this->roleManager->hasPermission(
                'admin',
                'users.read'
            )
        );
    }

    /**
     * Comprueba que un rol pueda contener múltiples permisos.
     */
    public function testRoleCanHaveMultiplePermissions(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $role->grantPermission(
            $this->createPermission('users.create')
        );

        $role->grantPermission(
            $this->createPermission('users.delete')
        );

        $this->roleManager->register($role);

        $this->assertTrue(
            $this->roleManager->hasPermission(
                'admin',
                'users.read'
            )
        );

        $this->assertTrue(
            $this->roleManager->hasPermission(
                'admin',
                'users.create'
            )
        );

        $this->assertTrue(
            $this->roleManager->hasPermission(
                'admin',
                'users.delete'
            )
        );
    }

    /**
     * Comprueba que toArray() devuelva una representación
     * serializable de los roles.
     */
    public function testToArrayReturnsRegisteredRoles(): void
    {
        $admin = $this->createRole(
            'admin',
            'Administrator'
        );

        $admin->grantPermission(
            $this->createPermission('users.read')
        );

        $this->roleManager->register($admin);
        $this->roleManager->define(
            'moderator',
            'Content moderator'
        );

        $result = $this->roleManager->toArray();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * Comprueba que toArray() devuelva una colección vacía
     * cuando no existen roles registrados.
     */
    public function testToArrayReturnsEmptyArrayForEmptyManager(): void
    {
        $this->assertSame(
            [],
            $this->roleManager->toArray()
        );
    }

    /**
     * Comprueba que dos administradores mantengan
     * sus colecciones de roles independientes.
     */
    public function testRoleManagersRemainIndependent(): void
    {
        $firstManager = new RoleManager();
        $secondManager = new RoleManager();

        $firstManager->define('admin');
        $secondManager->define('user');

        $this->assertTrue($firstManager->has('admin'));
        $this->assertFalse($firstManager->has('user'));

        $this->assertTrue($secondManager->has('user'));
        $this->assertFalse($secondManager->has('admin'));
    }

    /**
     * Comprueba que sea posible registrar nuevamente
     * un rol después de eliminarlo.
     */
    public function testCanRegisterRoleAfterRemoval(): void
    {
        $this->roleManager->define('admin');

        $this->roleManager->remove('admin');

        $newRole = $this->createRole(
            'admin',
            'Updated administrator'
        );

        $this->roleManager->register($newRole);

        $this->assertSame(
            $newRole,
            $this->roleManager->get('admin')
        );

        $this->assertSame(1, $this->roleManager->count());
    }
}
