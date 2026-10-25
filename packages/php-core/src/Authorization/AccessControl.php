<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Authorization;

use InvalidArgumentException;
use ShieldPHP\Core\Auth\UserIdentity;

final class AccessControl
{
    /**
     * Mapa de roles y permisos.
     *
     * @var array<string, list<string>>
     */
    private readonly array $rolePermissions;

    /**
     * @param array<string, list<string>> $rolePermissions
     * Ejemplo: ['admin' => ['users.read', 'users.delete']]
     */
    public function __construct(array $rolePermissions = [])
    {
        $normalizedPermissions = [];

        foreach ($rolePermissions as $role => $permissions) {
            $this->assertIdentifier($role, 'rol');

            if (!is_array($permissions)) {
                throw new InvalidArgumentException(
                    sprintf('Los permisos del rol "%s" deben ser un array.', $role)
                );
            }

            $normalizedPermissions[$role] = [];

            foreach ($permissions as $permission) {
                if (!is_string($permission)) {
                    throw new InvalidArgumentException(
                        'Cada permiso debe ser una cadena.'
                    );
                }

                $this->assertIdentifier($permission, 'permiso');

                $normalizedPermissions[$role][] = $permission;
            }

            $normalizedPermissions[$role] = array_values(
                array_unique($normalizedPermissions[$role])
            );
        }

        $this->rolePermissions = $normalizedPermissions;
    }

    /**
     * Comprueba si un usuario tiene un rol.
     */
    public function hasRole(
        UserIdentity $user,
        string $role
    ): bool {
        $this->assertIdentifier($role, 'rol');

        if (!$user->isActive()) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Comprueba si el usuario tiene al menos uno de los roles indicados.
     *
     * @param list<string> $roles
     */
    public function hasAnyRole(
        UserIdentity $user,
        array $roles
    ): bool {
        if (!$user->isActive() || $roles === []) {
            return false;
        }

        foreach ($roles as $role) {
            if (!is_string($role)) {
                throw new InvalidArgumentException(
                    'Cada rol debe ser una cadena.'
                );
            }

            if ($this->hasRole($user, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprueba si el usuario tiene todos los roles indicados.
     *
     * @param list<string> $roles
     */
    public function hasAllRoles(
        UserIdentity $user,
        array $roles
    ): bool {
        if (!$user->isActive() || $roles === []) {
            return false;
        }

        foreach ($roles as $role) {
            if (!is_string($role)) {
                throw new InvalidArgumentException(
                    'Cada rol debe ser una cadena.'
                );
            }

            if (!$this->hasRole($user, $role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Comprueba si alguno de los roles del usuario concede un permiso.
     */
    public function hasPermission(
        UserIdentity $user,
        string $permission
    ): bool {
        $this->assertIdentifier($permission, 'permiso');

        if (!$user->isActive()) {
            return false;
        }

        foreach ($user->getRoles() as $role) {
            $permissions = $this->rolePermissions[$role] ?? [];

            if (in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprueba si el usuario posee al menos uno de los permisos.
     *
     * @param list<string> $permissions
     */
    public function hasAnyPermission(
        UserIdentity $user,
        array $permissions
    ): bool {
        if (!$user->isActive() || $permissions === []) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (!is_string($permission)) {
                throw new InvalidArgumentException(
                    'Cada permiso debe ser una cadena.'
                );
            }

            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprueba si el usuario posee todos los permisos.
     *
     * @param list<string> $permissions
     */
    public function hasAllPermissions(
        UserIdentity $user,
        array $permissions
    ): bool {
        if (!$user->isActive() || $permissions === []) {
            return false;
        }

        foreach ($permissions as $permission) {
            if (!is_string($permission)) {
                throw new InvalidArgumentException(
                    'Cada permiso debe ser una cadena.'
                );
            }

            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Alias legible para comprobar si una operación está permitida.
     */
    public function can(
        UserIdentity $user,
        string $permission
    ): bool {
        return $this->hasPermission($user, $permission);
    }

    /**
     * Valida identificadores de roles y permisos.
     */
    private function assertIdentifier(
        string $identifier,
        string $type
    ): void {
        if (
            preg_match(
                '/\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}\z/',
                $identifier
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                sprintf('El identificador de %s no es válido.', $type)
            );
        }
    }
}