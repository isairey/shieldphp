<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Auth;

use InvalidArgumentException;

final class UserIdentity
{
    private readonly string $id;
    private readonly string $email;
    private readonly string $passwordHash;
    private readonly bool $active;

    /**
     * @var list<string>
     */
    private readonly array $roles;

    /**
     * Representa la identidad de un usuario almacenado.
     *
     * @param list<string> $roles Roles asignados al usuario.
     */
    public function __construct(
        string $id,
        string $email,
        string $passwordHash,
        bool $active = true,
        array $roles = []
    ) {
        $id = trim($id);
        $email = strtolower(trim($email));

        if ($id === '' || strlen($id) > 255) {
            throw new InvalidArgumentException(
                'El identificador del usuario no es válido.'
            );
        }

        if (
            filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || strlen($email) > 254
        ) {
            throw new InvalidArgumentException(
                'El correo electrónico no es válido.'
            );
        }

        if ($passwordHash === '' || strlen($passwordHash) > 4096) {
            throw new InvalidArgumentException(
                'El hash de la contraseña no es válido.'
            );
        }

        $normalizedRoles = [];

        foreach ($roles as $role) {
            if (!is_string($role) || trim($role) === '') {
                throw new InvalidArgumentException(
                    'Cada rol debe ser una cadena no vacía.'
                );
            }

            $normalizedRoles[] = trim($role);
        }

        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->active = $active;
        $this->roles = array_values(array_unique($normalizedRoles));
    }

    /**
     * Obtiene el identificador único del usuario.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Obtiene el correo electrónico normalizado.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Obtiene el hash almacenado para verificar la contraseña.
     *
     * No debe enviarse al frontend ni incluirse en respuestas JSON.
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Indica si la cuenta está activa.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Obtiene los roles del usuario.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Comprueba si el usuario tiene un rol determinado.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
}