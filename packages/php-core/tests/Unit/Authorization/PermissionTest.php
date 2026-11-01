
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Authorization;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Authorization\Permission;

final class PermissionTest extends TestCase
{
    private const PERMISSION_NAME = 'users.read';

    private const PERMISSION_DESCRIPTION = 'Read users';

    /**
     * Crea un permiso para las pruebas.
     */
    private function createPermission(
        string $name = self::PERMISSION_NAME,
        string $description = self::PERMISSION_DESCRIPTION
    ): Permission {
        return new Permission($name, $description);
    }

    /**
     * Comprueba que se pueda crear una instancia de Permission.
     */
    public function testCreatesPermission(): void
    {
        $permission = $this->createPermission();

        $this->assertInstanceOf(Permission::class, $permission);
    }

    /**
     * Comprueba que getName() devuelva el nombre correcto.
     */
    public function testGetNameReturnsCorrectName(): void
    {
        $permission = $this->createPermission();

        $this->assertSame(
            self::PERMISSION_NAME,
            $permission->getName()
        );
    }

    /**
     * Comprueba que getDescription() devuelva
     * la descripción correcta.
     */
    public function testGetDescriptionReturnsCorrectDescription(): void
    {
        $permission = $this->createPermission();

        $this->assertSame(
            self::PERMISSION_DESCRIPTION,
            $permission->getDescription()
        );
    }

    /**
     * Comprueba que se puedan crear permisos
     * con diferentes nombres.
     */
    public function testCreatesPermissionsWithDifferentNames(): void
    {
        $readPermission = $this->createPermission(
            'users.read',
            'Read users'
        );

        $deletePermission = $this->createPermission(
            'users.delete',
            'Delete users'
        );

        $this->assertSame('users.read', $readPermission->getName());
        $this->assertSame('users.delete', $deletePermission->getName());
    }

    /**
     * Comprueba que cada permiso conserve su descripción.
     */
    public function testPreservesPermissionDescription(): void
    {
        $permission = $this->createPermission(
            'users.create',
            'Allows creating new users'
        );

        $this->assertSame(
            'Allows creating new users',
            $permission->getDescription()
        );
    }

    /**
     * Comprueba que equals() reconozca dos permisos
     * con los mismos datos.
     */
    public function testEqualsReturnsTrueForEquivalentPermissions(): void
    {
        $firstPermission = $this->createPermission(
            'users.read',
            'Read users'
        );

        $secondPermission = $this->createPermission(
            'users.read',
            'Read users'
        );

        $this->assertTrue(
            $firstPermission->equals($secondPermission)
        );
    }

    /**
     * Comprueba que equals() rechace permisos
     * con nombres diferentes.
     */
    public function testEqualsReturnsFalseForDifferentNames(): void
    {
        $firstPermission = $this->createPermission(
            'users.read',
            'Read users'
        );

        $secondPermission = $this->createPermission(
            'users.delete',
            'Delete users'
        );

        $this->assertFalse(
            $firstPermission->equals($secondPermission)
        );
    }

    /**
     * Comprueba que equals() reconozca un permiso
     * comparado consigo mismo.
     */
    public function testPermissionEqualsItself(): void
    {
        $permission = $this->createPermission();

        $this->assertTrue(
            $permission->equals($permission)
        );
    }

    /**
     * Comprueba que la comparación sea independiente
     * del orden de creación de los objetos.
     */
    public function testEqualityIsSymmetric(): void
    {
        $firstPermission = $this->createPermission(
            'users.update',
            'Update users'
        );

        $secondPermission = $this->createPermission(
            'users.update',
            'Update users'
        );

        $this->assertTrue(
            $firstPermission->equals($secondPermission)
        );

        $this->assertTrue(
            $secondPermission->equals($firstPermission)
        );
    }

    /**
     * Comprueba que equals() sea transitivo
     * para tres permisos equivalentes.
     */
    public function testEqualityIsTransitive(): void
    {
        $first = $this->createPermission();
        $second = $this->createPermission();
        $third = $this->createPermission();

        $this->assertTrue($first->equals($second));
        $this->assertTrue($second->equals($third));
        $this->assertTrue($first->equals($third));
    }

    /**
     * Comprueba que toArray() devuelva un array.
     */
    public function testToArrayReturnsArray(): void
    {
        $permission = $this->createPermission();

        $this->assertIsArray($permission->toArray());
    }

    /**
     * Comprueba que toArray() incluya el nombre
     * y la descripción del permiso.
     */
    public function testToArrayContainsNameAndDescription(): void
    {
        $permission = $this->createPermission(
            'users.read',
            'Read users'
        );

        $result = $permission->toArray();

        $this->assertSame(
            'users.read',
            $result['name']
        );

        $this->assertSame(
            'Read users',
            $result['description']
        );
    }

    /**
     * Comprueba que toArray() genere una estructura
     * consistente entre diferentes permisos.
     */
    public function testToArrayUsesConsistentStructure(): void
    {
        $firstPermission = $this->createPermission(
            'users.read',
            'Read users'
        );

        $secondPermission = $this->createPermission(
            'users.delete',
            'Delete users'
        );

        $firstArray = $firstPermission->toArray();
        $secondArray = $secondPermission->toArray();

        $this->assertSame(
            array_keys($firstArray),
            array_keys($secondArray)
        );
    }

    /**
     * Comprueba que serializar un permiso a JSON
     * produzca información válida.
     */
    public function testPermissionCanBeSerializedToJson(): void
    {
        $permission = $this->createPermission();

        $json = json_encode(
            $permission->toArray(),
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

        $this->assertSame(
            'users.read',
            $decoded['name']
        );
    }

    /**
     * Comprueba que consultar las propiedades
     * no modifique los datos del permiso.
     */
    public function testReadingPermissionDoesNotChangeItsData(): void
    {
        $permission = $this->createPermission();

        $originalName = $permission->getName();
        $originalDescription = $permission->getDescription();

        $permission->getName();
        $permission->getDescription();
        $permission->toArray();

        $this->assertSame(
            $originalName,
            $permission->getName()
        );

        $this->assertSame(
            $originalDescription,
            $permission->getDescription()
        );
    }

    /**
     * Comprueba que dos permisos diferentes
     * mantengan sus datos independientes.
     */
    public function testPermissionsRemainIndependent(): void
    {
        $firstPermission = $this->createPermission(
            'users.read',
            'Read users'
        );

        $secondPermission = $this->createPermission(
            'users.delete',
            'Delete users'
        );

        $this->assertNotSame(
            $firstPermission->getName(),
            $secondPermission->getName()
        );

        $this->assertNotSame(
            $firstPermission->getDescription(),
            $secondPermission->getDescription()
        );
    }
}
