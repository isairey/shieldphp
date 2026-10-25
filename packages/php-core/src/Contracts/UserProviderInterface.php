<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Contracts;

use ShieldPHP\Core\Auth\UserIdentity;

interface UserProviderInterface
{
    /**
     * Busca un usuario por su correo electrónico.
     *
     * El usuario devuelto debe incluir el hash de contraseña
     * almacenado, su estado de activación y sus roles.
     *
     * Debe devolver null si el usuario no existe.
     */
    public function findByEmail(string $email): ?UserIdentity;

    /**
     * Busca un usuario por su identificador único.
     *
     * Debe devolver null si el usuario no existe.
     */
    public function findById(string $id): ?UserIdentity;
}