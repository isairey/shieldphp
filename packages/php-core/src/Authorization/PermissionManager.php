<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Authorization;

use InvalidArgumentException;

final class PermissionManager
{
    /**
     * Catálogo de permisos registrados en memoria.
     *
     * @var array<string, Permission>
     */
    private array $permissions = [];

    /**
     * Registra un permiso.
     *
     * @throws InvalidArgumentException Si el permiso ya existe.
     */
    public function register(Permission $permission): void
    {
        $name = $permission->getName();

        if (isset($this->permissions[$name])) {
            throw new InvalidArgumentException(
                sprintf('El permiso "%s" ya está registrado.', $name)
            );
        }

        $this->permissions[$name] = $permission;
    }

    /**
     * Crea y registra un permiso.
     */
    public function define(
        string $name,
        ?string $description = null
    ): Permission {
        $permission = new Permission($name, $description);

        $this->register($permission);

        return $permission;
    }

    /**
     * Obtiene un permiso por su identificador.
     */
    public function get(string $name): ?Permission
    {
        $name = $this->normalizeName($name);

        return $this->permissions[$name] ?? null;
    }

    /**
     * Comprueba si un permiso está registrado.
     */
    public function has(string $name): bool
    {
        $name = $this->normalizeName($name);

        return isset($this->permissions[$name]);
    }

    /**
     * Elimina un permiso del catálogo.
     *
     * Devuelve true si se eliminó y false si no existía.
     */
    public function remove(string $name): bool
    {
        $name = $this->normalizeName($name);

        if (!isset($this->permissions[$name])) {
            return false;
        }

        unset($this->permissions[$name]);

        return true;
    }

    /**
     * Obtiene todos los permisos registrados.
     *
     * @return list<Permission>
     */
    public function all(): array
    {
        return array_values($this->permissions);
    }

    /**
     * Obtiene los identificadores de todos los permisos.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->permissions);
    }

    /**
     * Devuelve el número de permisos registrados.
     */
    public function count(): int
    {
        return count($this->permissions);
    }

    /**
     * Exporta los permisos a una estructura de arrays.
     *
     * @return list<array{name: string, description: ?string}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (Permission $permission): array =>
                $permission->toArray(),
            $this->all()
        );
    }

    /**
     * Normaliza y valida un identificador.
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
                'El identificador del permiso no es válido.'
            );
        }

        return $name;
    }
}