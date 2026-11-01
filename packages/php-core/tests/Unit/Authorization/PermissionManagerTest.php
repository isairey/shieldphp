
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Authorization;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Authorization\Permission;
use ShieldPHP\Core\Authorization\PermissionManager;

final class PermissionManagerTest extends TestCase
{
    private PermissionManager $permissionManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionManager = new PermissionManager();
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
     * Comprueba que se pueda crear PermissionManager.
     */
    public function testCreatesPermissionManager(): void
    {
        $manager = new PermissionManager();

        $this->assertInstanceOf(
            PermissionManager::class,
            $manager
        );
    }

    /**
     * Comprueba que el administrador comience vacío.
     */
    public function testStartsWithEmptyPermissionCollection(): void
    {
        $this->assertSame([], $this->permissionManager->all());
        $this->assertSame([], $this->permissionManager->names());
        $this->assertSame(0, $this->permissionManager->count());
    }

    /**
     * Comprueba que se pueda registrar un permiso.
     */
    public function testRegistersPermission(): void
    {
        $permission = $this->createPermission();

        $this->permissionManager->register($permission);

        $this->assertTrue(
            $this->permissionManager->has('users.read')
        );
    }

    /**
     * Comprueba que get() devuelva el permiso registrado.
     */
    public function testGetReturnsRegisteredPermission(): void
    {
        $permission = $this->createPermission();

        $this->permissionManager->register($permission);

        $result = $this->permissionManager->get('users.read');

        $this->assertInstanceOf(Permission::class, $result);
        $this->assertSame($permission, $result);
    }

    /**
     * Comprueba que get() devuelva null para un permiso inexistente.
     */
    public function testGetReturnsNullForUnknownPermission(): void
    {
        $result = $this->permissionManager->get('users.delete');

        $this->assertNull($result);
    }

    /**
     * Comprueba que has() devuelva true para permisos registrados.
     */
    public function testHasReturnsTrueForRegisteredPermission(): void
    {
        $this->permissionManager->register(
            $this->createPermission()
        );

        $this->assertTrue(
            $this->permissionManager->has('users.read')
        );
    }

    /**
     * Comprueba que has() devuelva false para permisos inexistentes.
     */
    public function testHasReturnsFalseForUnknownPermission(): void
    {
        $this->assertFalse(
            $this->permissionManager->has('users.delete')
        );
    }

    /**
     * Comprueba que define() cree y registre un permiso.
     */
    public function testDefineCreatesAndRegistersPermission(): void
    {
        $permission = $this->permissionManager->define(
            'users.create',
            'Create users'
        );

        $this->assertInstanceOf(Permission::class, $permission);

        $this->assertTrue(
            $this->permissionManager->has('users.create')
        );

        $this->assertSame(
            $permission,
            $this->permissionManager->get('users.create')
        );
    }

    /**
     * Comprueba que define() admita una descripción opcional.
     */
    public function testDefineAcceptsPermissionWithoutDescription(): void
    {
        $permission = $this->permissionManager->define(
            'users.read'
        );

        $this->assertInstanceOf(Permission::class, $permission);

        $this->assertTrue(
            $this->permissionManager->has('users.read')
        );
    }

    /**
     * Comprueba que all() devuelva todos los permisos registrados.
     */
    public function testAllReturnsRegisteredPermissions(): void
    {
        $firstPermission = $this->createPermission(
            'users.read',
            'Read users'
        );

        $secondPermission = $this->createPermission(
            'users.delete',
            'Delete users'
        );

        $this->permissionManager->register($firstPermission);
        $this->permissionManager->register($secondPermission);

        $permissions = $this->permissionManager->all();

        $this->assertCount(2, $permissions);
        $this->assertContains(
            $firstPermission,
            $permissions,
            true
        );
        $this->assertContains(
            $secondPermission,
            $permissions,
            true
        );
    }

    /**
     * Comprueba que names() devuelva los nombres registrados.
     */
    public function testNamesReturnsPermissionNames(): void
    {
        $this->permissionManager->define('users.read');
        $this->permissionManager->define('users.create');
        $this->permissionManager->define('users.delete');

        $names = $this->permissionManager->names();

        $this->assertIsArray($names);
        $this->assertCount(3, $names);

        $this->assertContains('users.read', $names);
        $this->assertContains('users.create', $names);
        $this->assertContains('users.delete', $names);
    }

    /**
     * Comprueba que count() refleje la cantidad de permisos.
     */
    public function testCountReturnsRegisteredPermissionCount(): void
    {
        $this->assertSame(0, $this->permissionManager->count());

        $this->permissionManager->define('users.read');

        $this->assertSame(1, $this->permissionManager->count());

        $this->permissionManager->define('users.create');

        $this->assertSame(2, $this->permissionManager->count());
    }

    /**
     * Comprueba que remove() elimine un permiso existente.
     */
    public function testRemoveDeletesRegisteredPermission(): void
    {
        $this->permissionManager->define('users.read');

        $result = $this->permissionManager->remove('users.read');

        $this->assertTrue($result);
        $this->assertFalse(
            $this->permissionManager->has('users.read')
        );
    }

    /**
     * Comprueba que remove() devuelva false
     * cuando el permiso no existe.
     */
    public function testRemoveReturnsFalseForUnknownPermission(): void
    {
        $result = $this->permissionManager->remove(
            'users.unknown'
        );

        $this->assertFalse($result);
    }

    /**
     * Comprueba que eliminar un permiso actualice el conteo.
     */
    public function testRemoveUpdatesPermissionCount(): void
    {
        $this->permissionManager->define('users.read');
        $this->permissionManager->define('users.create');
        $this->permissionManager->define('users.delete');

        $this->permissionManager->remove('users.create');

        $this->assertSame(2, $this->permissionManager->count());
    }

    /**
     * Comprueba que eliminar un permiso no afecte a los demás.
     */
    public function testRemoveDoesNotDeleteOtherPermissions(): void
    {
        $this->permissionManager->define('users.read');
        $this->permissionManager->define('users.create');
        $this->permissionManager->define('users.delete');

        $this->permissionManager->remove('users.create');

        $this->assertTrue(
            $this->permissionManager->has('users.read')
        );

        $this->assertFalse(
            $this->permissionManager->has('users.create')
        );

        $this->assertTrue(
            $this->permissionManager->has('users.delete')
        );
    }

    /**
     * Comprueba que toArray() devuelva una representación
     * serializable de los permisos registrados.
     */
    public function testToArrayReturnsRegisteredPermissions(): void
    {
        $this->permissionManager->define(
            'users.read',
            'Read users'
        );

        $this->permissionManager->define(
            'users.delete',
            'Delete users'
        );

        $result = $this->permissionManager->toArray();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $serializedReadPermission = (new Permission(
            'users.read',
            'Read users'
        ))->toArray();

        $serializedDeletePermission = (new Permission(
            'users.delete',
            'Delete users'
        ))->toArray();

        $this->assertContains(
            $serializedReadPermission,
            $result
        );

        $this->assertContains(
            $serializedDeletePermission,
            $result
        );
    }

    /**
     * Comprueba que toArray() devuelva un array vacío
     * cuando no existen permisos registrados.
     */
    public function testToArrayReturnsEmptyArrayForEmptyManager(): void
    {
        $this->assertSame(
            [],
            $this->permissionManager->toArray()
        );
    }

    /**
     * Comprueba que varios permisos puedan registrarse
     * sin perder sus respectivas identidades.
     */
    public function testRegistersMultiplePermissions(): void
    {
        $permissions = [
            $this->createPermission('users.read', 'Read users'),
            $this->createPermission('users.create', 'Create users'),
            $this->createPermission('users.update', 'Update users'),
            $this->createPermission('users.delete', 'Delete users'),
        ];

        foreach ($permissions as $permission) {
            $this->permissionManager->register($permission);
        }

        $this->assertSame(4, $this->permissionManager->count());

        foreach ($permissions as $permission) {
            $this->assertTrue(
                $this->permissionManager->has(
                    $permission->toArray()['name']
                )
            );
        }
    }

    /**
     * Comprueba que dos administradores mantengan
     * sus colecciones de permisos independientes.
     */
    public function testPermissionManagersRemainIndependent(): void
    {
        $firstManager = new PermissionManager();
        $secondManager = new PermissionManager();

        $firstManager->define('users.read');

        $secondManager->define('users.delete');

        $this->assertTrue(
            $firstManager->has('users.read')
        );

        $this->assertFalse(
            $firstManager->has('users.delete')
        );

        $this->assertTrue(
            $secondManager->has('users.delete')
        );

        $this->assertFalse(
            $secondManager->has('users.read')
        );
    }

    /**
     * Comprueba que eliminar y volver a registrar un permiso
     * permita recuperarlo correctamente.
     */
    public function testCanRegisterPermissionAfterRemoval(): void
    {
        $this->permissionManager->define('users.read');

        $this->permissionManager->remove('users.read');

        $newPermission = $this->createPermission(
            'users.read',
            'Read users again'
        );

        $this->permissionManager->register($newPermission);

        $this->assertSame(
            $newPermission,
            $this->permissionManager->get('users.read')
        );

        $this->assertSame(1, $this->permissionManager->count());
    }
}
