<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Authorization;

use InvalidArgumentException;

final class Role
{
    private readonly string $name;
    private readonly ?string $description;

    /**
     * Permisos asociados al rol, indexados por su identificador.
     *
     * @var array<string, Permission>
     */
    private array $permissions = [];

    /**
     * @param list<Permission> $permissions
     */
    public function __construct(
        string $name,
        ?string $description = null,
        array $permissions = []
    ) {
        $name = trim($name);

        if (
            preg_match(
                '/\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}\z/',
                $name
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'El identificador del rol no es válido.'
            );
        }

        if ($description !== null) {
            $description = trim($description);

            if ($description === '') {
                $description = null;
            }

            if (
                $description !== null
                && strlen($description) > 500
            ) {
                throw new InvalidArgumentException(
                    'La descripción no puede superar los 500 bytes.'
                );
            }
        }

        $this->name = $name;
        $this->description = $description;

        foreach ($permissions as $permission) {
            if (!$permission instanceof Permission) {
                throw new InvalidArgumentException(
                    'Todos los permisos deben ser instancias de Permission.'
                );
            }

            $this->grantPermission($permission);
        }
    }

    /**
     * Obtiene el identificador del rol.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Obtiene la descripción del rol.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Concede un permiso al rol.
     *
     * Si el permiso ya existe, conserva el permiso registrado.
     */
    public function grantPermission(Permission $permission): void
    {
        $name = $permission->getName();

        $this->permissions[$name] = $permission;
    }

    /**
     * Revoca un permiso del rol.
     *
     * @return bool True si existía y fue eliminado.
     */
    public function revokePermission(string $permissionName): bool
    {
        $permissionName = trim($permissionName);

        if (!isset($this->permissions[$permissionName])) {
            return false;
        }

        unset($this->permissions[$permissionName]);

        return true;
    }

    /**
     * Comprueba si el rol tiene un permiso.
     */
    public function hasPermission(string $permissionName): bool
    {
        return isset($this->permissions[trim($permissionName)]);
    }

    /**
     * Obtiene todos los permisos del rol.
     *
     * @return list<Permission>
     */
    public function getPermissions(): array
    {
        return array_values($this->permissions);
    }

    /**
     * Obtiene los identificadores de los permisos.
     *
     * @return list<string>
     */
    public function getPermissionNames(): array
    {
        return array_keys($this->permissions);
    }

    /**
     * Comprueba si dos roles tienen el mismo identificador.
     */
    public function equals(Role $role): bool
    {
        return $this->name === $role->name;
    }

    /**
     * Convierte el rol en un array.
     *
     * @return array{
     *     name: string,
     *     description: ?string,
     *     permissions: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->getPermissionNames(),
        ];
    }
}