<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Authorization;

use InvalidArgumentException;

final class RoleManager
{
    /**
     * Roles registrados en memoria, indexados por nombre.
     *
     * @var array<string, Role>
     */
    private array $roles = [];

    /**
     * Registra un rol existente.
     *
     * @throws InvalidArgumentException Si el rol ya está registrado.
     */
    public function register(Role $role): void
    {
        $name = $role->getName();

        if (isset($this->roles[$name])) {
            throw new InvalidArgumentException(
                sprintf('El rol "%s" ya está registrado.', $name)
            );
        }

        $this->roles[$name] = $role;
    }

    /**
     * Crea y registra un rol.
     *
     * @param list<Permission> $permissions
     */
    public function define(
        string $name,
        ?string $description = null,
        array $permissions = []
    ): Role {
        $role = new Role(
            name: $name,
            description: $description,
            permissions: $permissions
        );

        $this->register($role);

        return $role;
    }

    /**
     * Busca un rol por su identificador.
     */
    public function get(string $name): ?Role
    {
        $name = $this->normalizeName($name);

        return $this->roles[$name] ?? null;
    }

    /**
     * Busca un rol o lanza una excepción si no existe.
     */
    public function getOrFail(string $name): Role
    {
        $role = $this->get($name);

        if ($role === null) {
            throw new InvalidArgumentException(
                sprintf('El rol "%s" no está registrado.', $name)
            );
        }

        return $role;
    }

    /**
     * Comprueba si un rol está registrado.
     */
    public function has(string $name): bool
    {
        $name = $this->normalizeName($name);

        return isset($this->roles[$name]);
    }

    /**
     * Elimina un rol del catálogo.
     *
     * Devuelve true si existía y fue eliminado.
     */
    public function remove(string $name): bool
    {
        $name = $this->normalizeName($name);

        if (!isset($this->roles[$name])) {
            return false;
        }

        unset($this->roles[$name]);

        return true;
    }

    /**
     * Obtiene todos los roles registrados.
     *
     * @return list<Role>
     */
    public function all(): array
    {
        return array_values($this->roles);
    }

    /**
     * Obtiene los identificadores de todos los roles.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->roles);
    }

    /**
     * Devuelve el número de roles registrados.
     */
    public function count(): int
    {
        return count($this->roles);
    }

    /**
     * Comprueba si el rol contiene un permiso.
     */
    public function hasPermission(
        string $roleName,
        string $permissionName
    ): bool {
        $role = $this->get($roleName);

        if ($role === null) {
            return false;
        }

        return $role->hasPermission($permissionName);
    }

    /**
     * Exporta todos los roles con sus permisos.
     *
     * @return list<array{
     *     name: string,
     *     description: ?string,
     *     permissions: list<string>
     * }>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (Role $role): array => $role->toArray(),
            $this->all()
        );
    }

    /**
     * Valida y normaliza un identificador de rol.
     */
    private function normalizeName(string $name): string
    {
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

        return $name;
    }
}