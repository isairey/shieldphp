
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\Authorization;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Auth\UserIdentity;
use ShieldPHP\Core\Authorization\AccessControl;

final class AccessControlTest extends TestCase
{
    private AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accessControl = new AccessControl([
            'admin' => [
                'users.read',
                'users.create',
                'users.update',
                'users.delete',
                'roles.manage',
                'permissions.manage',
            ],
            'moderator' => [
                'users.read',
                'users.update',
                'comments.moderate',
            ],
            'user' => [
                'profile.read',
                'profile.update',
            ],
        ]);
    }

    /**
     * Crea una identidad de usuario para las pruebas.
     *
     * @param list<string> $roles
     */
    private function createUser(
        string $id = 'user-123',
        bool $active = true,
        array $roles = ['user']
    ): UserIdentity {
        return new UserIdentity(
            $id,
            strtolower($id) . '@example.com',
            'test-password-hash',
            $active,
            $roles
        );
    }

    /**
     * Comprueba que se pueda crear AccessControl.
     */
    public function testCreatesAccessControl(): void
    {
        $accessControl = new AccessControl([]);

        $this->assertInstanceOf(
            AccessControl::class,
            $accessControl
        );
    }

    /**
     * Comprueba que un usuario tenga el rol asignado.
     */
    public function testHasRoleReturnsTrueForAssignedRole(): void
    {
        $user = $this->createUser(roles: ['admin', 'user']);

        $this->assertTrue(
            $this->accessControl->hasRole($user, 'admin')
        );
    }

    /**
     * Comprueba que se rechace un rol no asignado.
     */
    public function testHasRoleReturnsFalseForUnassignedRole(): void
    {
        $user = $this->createUser(roles: ['user']);

        $this->assertFalse(
            $this->accessControl->hasRole($user, 'admin')
        );
    }

    /**
     * Comprueba que hasAnyRole() acepte cuando
     * al menos uno de los roles está asignado.
     */
    public function testHasAnyRoleReturnsTrueWhenOneRoleMatches(): void
    {
        $user = $this->createUser(
            roles: ['moderator']
        );

        $this->assertTrue(
            $this->accessControl->hasAnyRole(
                $user,
                ['admin', 'moderator', 'superadmin']
            )
        );
    }

    /**
     * Comprueba que hasAnyRole() rechace cuando
     * ninguno de los roles coincide.
     */
    public function testHasAnyRoleReturnsFalseWhenNoRoleMatches(): void
    {
        $user = $this->createUser(
            roles: ['user']
        );

        $this->assertFalse(
            $this->accessControl->hasAnyRole(
                $user,
                ['admin', 'moderator']
            )
        );
    }

    /**
     * Comprueba que hasAllRoles() acepte cuando
     * el usuario tiene todos los roles solicitados.
     */
    public function testHasAllRolesReturnsTrueWhenAllRolesMatch(): void
    {
        $user = $this->createUser(
            roles: ['admin', 'moderator', 'user']
        );

        $this->assertTrue(
            $this->accessControl->hasAllRoles(
                $user,
                ['admin', 'user']
            )
        );
    }

    /**
     * Comprueba que hasAllRoles() rechace cuando
     * falta alguno de los roles solicitados.
     */
    public function testHasAllRolesReturnsFalseWhenRoleIsMissing(): void
    {
        $user = $this->createUser(
            roles: ['user', 'moderator']
        );

        $this->assertFalse(
            $this->accessControl->hasAllRoles(
                $user,
                ['admin', 'moderator']
            )
        );
    }

    /**
     * Comprueba que un administrador pueda acceder
     * a los permisos definidos para su rol.
     */
    public function testAdminHasConfiguredPermissions(): void
    {
        $user = $this->createUser(
            roles: ['admin']
        );

        $this->assertTrue(
            $this->accessControl->hasPermission(
                $user,
                'users.read'
            )
        );

        $this->assertTrue(
            $this->accessControl->hasPermission(
                $user,
                'users.delete'
            )
        );
    }

    /**
     * Comprueba que un usuario normal no tenga
     * permisos exclusivos de administrador.
     */
    public function testUserCannotAccessAdminPermissions(): void
    {
        $user = $this->createUser(
            roles: ['user']
        );

        $this->assertFalse(
            $this->accessControl->hasPermission(
                $user,
                'users.delete'
            )
        );

        $this->assertFalse(
            $this->accessControl->hasPermission(
                $user,
                'roles.manage'
            )
        );
    }

    /**
     * Comprueba que los permisos de varios roles
     * se combinen correctamente.
     */
    public function testCombinesPermissionsFromMultipleRoles(): void
    {
        $user = $this->createUser(
            roles: ['user', 'moderator']
        );

        $this->assertTrue(
            $this->accessControl->hasPermission(
                $user,
                'profile.read'
            )
        );

        $this->assertTrue(
            $this->accessControl->hasPermission(
                $user,
                'comments.moderate'
            )
        );
    }

    /**
     * Comprueba que hasAnyPermission() acepte
     * cuando al menos uno de los permisos coincide.
     */
    public function testHasAnyPermissionReturnsTrueWhenOneMatches(): void
    {
        $user = $this->createUser(
            roles: ['moderator']
        );

        $this->assertTrue(
            $this->accessControl->hasAnyPermission(
                $user,
                [
                    'users.delete',
                    'users.read',
                    'permissions.manage',
                ]
            )
        );
    }

    /**
     * Comprueba que hasAnyPermission() rechace
     * cuando ninguno de los permisos coincide.
     */
    public function testHasAnyPermissionReturnsFalseWhenNoneMatch(): void
    {
        $user = $this->createUser(
            roles: ['user']
        );

        $this->assertFalse(
            $this->accessControl->hasAnyPermission(
                $user,
                [
                    'users.delete',
                    'roles.manage',
                ]
            )
        );
    }

    /**
     * Comprueba que hasAllPermissions() acepte
     * cuando están presentes todos los permisos.
     */
    public function testHasAllPermissionsReturnsTrueWhenAllMatch(): void
    {
        $user = $this->createUser(
            roles: ['admin']
        );

        $this->assertTrue(
            $this->accessControl->hasAllPermissions(
                $user,
                [
                    'users.read',
                    'users.create',
                    'users.delete',
                ]
            )
        );
    }

    /**
     * Comprueba que hasAllPermissions() rechace
     * cuando falta algún permiso.
     */
    public function testHasAllPermissionsReturnsFalseWhenPermissionIsMissing(): void
    {
        $user = $this->createUser(
            roles: ['moderator']
        );

        $this->assertFalse(
            $this->accessControl->hasAllPermissions(
                $user,
                [
                    'users.read',
                    'users.delete',
                ]
            )
        );
    }

    /**
     * Comprueba que can() permita realizar
     * una acción autorizada.
     */
    public function testCanReturnsTrueForAuthorizedAction(): void
    {
        $user = $this->createUser(
            roles: ['admin']
        );

        $this->assertTrue(
            $this->accessControl->can(
                $user,
                'users.delete'
            )
        );
    }

    /**
     * Comprueba que can() rechace una acción
     * para la que el usuario no tiene permisos.
     */
    public function testCanReturnsFalseForUnauthorizedAction(): void
    {
        $user = $this->createUser(
            roles: ['user']
        );

        $this->assertFalse(
            $this->accessControl->can(
                $user,
                'users.delete'
            )
        );
    }

    /**
     * Comprueba que un usuario inactivo no pueda
     * acceder mediante un rol asignado.
     */
    public function testInactiveUserCannotUseAssignedRole(): void
    {
        $user = $this->createUser(
            active: false,
            roles: ['admin']
        );

        $this->assertFalse(
            $this->accessControl->hasRole($user, 'admin')
        );
    }

    /**
     * Comprueba que un usuario inactivo no pueda
     * utilizar permisos de administrador.
     */
    public function testInactiveUserCannotUsePermissions(): void
    {
        $user = $this->createUser(
            active: false,
            roles: ['admin']
        );

        $this->assertFalse(
            $this->accessControl->hasPermission(
                $user,
                'users.delete'
            )
        );
    }

    /**
     * Comprueba que un usuario sin roles
     * no tenga permisos asignados.
     */
    public function testUserWithoutRolesHasNoPermissions(): void
    {
        $user = $this->createUser(
            roles: []
        );

        $this->assertFalse(
            $this->accessControl->hasPermission(
                $user,
                'users.read'
            )
        );
    }

    /**
     * Comprueba que un rol desconocido no conceda permisos.
     */
    public function testUnknownRoleDoesNotGrantPermissions(): void
    {
        $user = $this->createUser(
            roles: ['superadmin']
        );

        $this->assertFalse(
            $this->accessControl->hasPermission(
                $user,
                'users.read'
            )
        );
    }

    /**
     * Comprueba que dos usuarios con roles diferentes
     * tengan permisos independientes.
     */
    public function testPermissionsRemainIsolatedBetweenUsers(): void
    {
        $admin = $this->createUser(
            id: 'admin-001',
            roles: ['admin']
        );

        $normalUser = $this->createUser(
            id: 'user-002',
            roles: ['user']
        );

        $this->assertTrue(
            $this->accessControl->can(
                $admin,
                'users.delete'
            )
        );

        $this->assertFalse(
            $this->accessControl->can(
                $normalUser,
                'users.delete'
            )
        );
    }

    /**
     * Comprueba que los permisos no se concedan
     * por coincidencias parciales en sus nombres.
     */
    public function testPermissionMatchingIsExact(): void
    {
        $user = $this->createUser(
            roles: ['admin']
        );

        $this->assertTrue(
            $this->accessControl->hasPermission(
                $user,
                'users.read'
            )
        );

        $this->assertFalse(
            $this->accessControl->hasPermission(
                $user,
                'users'
            )
        );
    }

    /**
     * Comprueba que un permiso desconocido sea rechazado.
     */
    public function testRejectsUnknownPermission(): void
    {
        $user = $this->createUser(
            roles: ['admin']
        );

        $this->assertFalse(
            $this->accessControl->hasPermission(
                $user,
                'system.superuser'
            )
        );
    }

    /**
     * Comprueba que una instancia sin roles configurados
     * no conceda permisos.
     */
    public function testEmptyConfigurationDoesNotGrantPermissions(): void
    {
        $accessControl = new AccessControl([]);

        $user = $this->createUser(
            roles: ['admin']
        );

        $this->assertFalse(
            $accessControl->hasRole($user, 'admin')
        );

        $this->assertFalse(
            $accessControl->hasPermission(
                $user,
                'users.read'
            )
        );
    }
}
