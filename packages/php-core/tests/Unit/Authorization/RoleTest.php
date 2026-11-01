
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Authorization;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Authorization\Permission;
use ShieldPHP\Core\Authorization\Role;

final class RoleTest extends TestCase
{
    private const ROLE_NAME = 'admin';

    private const ROLE_DESCRIPTION = 'Administrator';

    /**
     * Crea un rol para las pruebas.
     */
    private function createRole(
        string $name = self::ROLE_NAME,
        string $description = self::ROLE_DESCRIPTION
    ): Role {
        return new Role($name, $description);
    }

    /**
     * Crea un permiso para las pruebas.
     */
    private function createPermission(
        string $name = 'users.read',
        string $description = 'Read users'
    ): Permission {
        return new Permission($name, $description);
    }

    /**
     * Comprueba que se pueda crear una instancia de Role.
     */
    public function testCreatesRole(): void
    {
        $role = $this->createRole();

        $this->assertInstanceOf(Role::class, $role);
    }

    /**
     * Comprueba que getName() devuelva el nombre correcto.
     */
    public function testGetNameReturnsCorrectName(): void
    {
        $role = $this->createRole();

        $this->assertSame(
            self::ROLE_NAME,
            $role->getName()
        );
    }

    /**
     * Comprueba que getDescription() devuelva
     * la descripción correcta.
     */
    public function testGetDescriptionReturnsCorrectDescription(): void
    {
        $role = $this->createRole();

        $this->assertSame(
            self::ROLE_DESCRIPTION,
            $role->getDescription()
        );
    }

    /**
     * Comprueba que se puedan crear roles diferentes.
     */
    public function testCreatesDifferentRoles(): void
    {
        $admin = $this->createRole(
            'admin',
            'Administrator'
        );

        $moderator = $this->createRole(
            'moderator',
            'Content moderator'
        );

        $this->assertSame('admin', $admin->getName());
        $this->assertSame('moderator', $moderator->getName());
    }

    /**
     * Comprueba que un rol comience sin permisos.
     */
    public function testStartsWithoutPermissions(): void
    {
        $role = $this->createRole();

        $this->assertSame([], $role->getPermissions());
        $this->assertSame([], $role->getPermissionNames());
    }

    /**
     * Comprueba que grantPermission() asigne un permiso.
     */
    public function testGrantPermissionAssignsPermission(): void
    {
        $role = $this->createRole();

        $permission = $this->createPermission();

        $role->grantPermission($permission);

        $this->assertTrue(
            $role->hasPermission('users.read')
        );
    }

    /**
     * Comprueba que un permiso asignado se conserve
     * como una instancia de Permission.
     */
    public function testGetPermissionsReturnsAssignedPermissions(): void
    {
        $role = $this->createRole();

        $permission = $this->createPermission();

        $role->grantPermission($permission);

        $permissions = $role->getPermissions();

        $this->assertIsArray($permissions);
        $this->assertCount(1, $permissions);
        $this->assertContains($permission, $permissions, true);
    }

    /**
     * Comprueba que getPermissionNames() devuelva
     * los nombres de los permisos asignados.
     */
    public function testGetPermissionNamesReturnsAssignedNames(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $role->grantPermission(
            $this->createPermission('users.create')
        );

        $names = $role->getPermissionNames();

        $this->assertIsArray($names);
        $this->assertCount(2, $names);
        $this->assertContains('users.read', $names);
        $this->assertContains('users.create', $names);
    }

    /**
     * Comprueba que hasPermission() reconozca
     * un permiso asignado.
     */
    public function testHasPermissionReturnsTrueForAssignedPermission(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $this->assertTrue(
            $role->hasPermission('users.read')
        );
    }

    /**
     * Comprueba que hasPermission() rechace
     * un permiso no asignado.
     */
    public function testHasPermissionReturnsFalseForUnassignedPermission(): void
    {
        $role = $this->createRole();

        $this->assertFalse(
            $role->hasPermission('users.delete')
        );
    }

    /**
     * Comprueba que un rol pueda tener múltiples permisos.
     */
    public function testSupportsMultiplePermissions(): void
    {
        $role = $this->createRole();

        $permissions = [
            $this->createPermission('users.read'),
            $this->createPermission('users.create'),
            $this->createPermission('users.update'),
            $this->createPermission('users.delete'),
        ];

        foreach ($permissions as $permission) {
            $role->grantPermission($permission);
        }

        $this->assertCount(4, $role->getPermissions());

        foreach ($permissions as $permission) {
            $this->assertTrue(
                $role->hasPermission($permission->getName())
            );
        }
    }

    /**
     * Comprueba que revokePermission() elimine
     * un permiso previamente asignado.
     */
    public function testRevokePermissionRemovesAssignedPermission(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $role->revokePermission('users.read');

        $this->assertFalse(
            $role->hasPermission('users.read')
        );
    }

    /**
     * Comprueba que revocar un permiso no afecte
     * a los demás permisos del rol.
     */
    public function testRevokePermissionPreservesOtherPermissions(): void
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

        $role->revokePermission('users.create');

        $this->assertTrue(
            $role->hasPermission('users.read')
        );

        $this->assertFalse(
            $role->hasPermission('users.create')
        );

        $this->assertTrue(
            $role->hasPermission('users.delete')
        );

        $this->assertCount(2, $role->getPermissions());
    }

    /**
     * Comprueba que getPermissionNames() se actualice
     * después de revocar un permiso.
     */
    public function testPermissionNamesUpdateAfterRevocation(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $role->grantPermission(
            $this->createPermission('users.delete')
        );

        $role->revokePermission('users.read');

        $this->assertSame(
            ['users.delete'],
            $role->getPermissionNames()
        );
    }

    /**
     * Comprueba que revocar un permiso inexistente
     * no afecte a los permisos existentes.
     */
    public function testRevokingUnknownPermissionDoesNotAffectOtherPermissions(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $role->revokePermission('users.delete');

        $this->assertTrue(
            $role->hasPermission('users.read')
        );

        $this->assertCount(1, $role->getPermissions());
    }

    /**
     * Comprueba que equals() reconozca dos roles equivalentes.
     */
    public function testEqualsReturnsTrueForEquivalentRoles(): void
    {
        $firstRole = $this->createRole(
            'admin',
            'Administrator'
        );

        $secondRole = $this->createRole(
            'admin',
            'Administrator'
        );

        $this->assertTrue(
            $firstRole->equals($secondRole)
        );
    }

    /**
     * Comprueba que equals() rechace roles con nombres diferentes.
     */
    public function testEqualsReturnsFalseForDifferentNames(): void
    {
        $firstRole = $this->createRole(
            'admin',
            'Administrator'
        );

        $secondRole = $this->createRole(
            'moderator',
            'Administrator'
        );

        $this->assertFalse(
            $firstRole->equals($secondRole)
        );
    }

    /**
     * Comprueba que un rol sea igual a sí mismo.
     */
    public function testRoleEqualsItself(): void
    {
        $role = $this->createRole();

        $this->assertTrue(
            $role->equals($role)
        );
    }

    /**
     * Comprueba que la comparación de roles
     * sea simétrica.
     */
    public function testRoleEqualityIsSymmetric(): void
    {
        $firstRole = $this->createRole();
        $secondRole = $this->createRole();

        $this->assertTrue(
            $firstRole->equals($secondRole)
        );

        $this->assertTrue(
            $secondRole->equals($firstRole)
        );
    }

    /**
     * Comprueba que toArray() devuelva un array.
     */
    public function testToArrayReturnsArray(): void
    {
        $role = $this->createRole();

        $this->assertIsArray($role->toArray());
    }

    /**
     * Comprueba que toArray() incluya los datos
     * principales del rol.
     */
    public function testToArrayContainsRoleInformation(): void
    {
        $role = $this->createRole(
            'admin',
            'Administrator'
        );

        $result = $role->toArray();

        $this->assertSame('admin', $result['name']);
        $this->assertSame('Administrator', $result['description']);
    }

    /**
     * Comprueba que toArray() incluya los permisos asignados.
     */
    public function testToArrayContainsAssignedPermissions(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $result = $role->toArray();

        $this->assertArrayHasKey('permissions', $result);
        $this->assertIsArray($result['permissions']);
        $this->assertCount(1, $result['permissions']);
    }

    /**
     * Comprueba que un rol vacío se serialice correctamente.
     */
    public function testToArraySupportsRoleWithoutPermissions(): void
    {
        $role = $this->createRole();

        $result = $role->toArray();

        $this->assertSame('admin', $result['name']);
        $this->assertSame([], $result['permissions']);
    }

    /**
     * Comprueba que la representación del rol
     * pueda convertirse a JSON.
     */
    public function testRoleCanBeSerializedToJson(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $json = json_encode(
            $role->toArray(),
            JSON_THROW_ON_ERROR
        );

        $this->assertIsString($json);
        $this->assertNotEmpty($json);

        $decoded = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('admin', $decoded['name']);
    }

    /**
     * Comprueba que dos roles mantengan
     * sus permisos de manera independiente.
     */
    public function testRolesMaintainIndependentPermissions(): void
    {
        $admin = $this->createRole('admin');
        $user = $this->createRole('user');

        $admin->grantPermission(
            $this->createPermission('users.delete')
        );

        $user->grantPermission(
            $this->createPermission('profile.read')
        );

        $this->assertTrue(
            $admin->hasPermission('users.delete')
        );

        $this->assertFalse(
            $admin->hasPermission('profile.read')
        );

        $this->assertTrue(
            $user->hasPermission('profile.read')
        );

        $this->assertFalse(
            $user->hasPermission('users.delete')
        );
    }

    /**
     * Comprueba que consultar las propiedades
     * no modifique los datos del rol.
     */
    public function testReadingRoleDoesNotChangeItsData(): void
    {
        $role = $this->createRole();

        $role->grantPermission(
            $this->createPermission('users.read')
        );

        $originalName = $role->getName();
        $originalDescription = $role->getDescription();
        $originalPermissions = $role->getPermissionNames();

        $role->getName();
        $role->getDescription();
        $role->getPermissions();
        $role->getPermissionNames();
        $role->toArray();

        $this->assertSame($originalName, $role->getName());
        $this->assertSame(
            $originalDescription,
            $role->getDescription()
        );
        $this->assertSame(
            $originalPermissions,
            $role->getPermissionNames()
        );
    }
}
