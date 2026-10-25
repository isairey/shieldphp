<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Auth;

final class AuthResult
{
    private function __construct(
        private readonly bool $successful,
        private readonly ?UserIdentity $user
    ) {
    }

    /**
     * Crea un resultado de autenticación exitosa.
     */
    public static function success(UserIdentity $user): self
    {
        return new self(true, $user);
    }

    /**
     * Crea un resultado de autenticación fallida.
     */
    public static function failure(): self
    {
        return new self(false, null);
    }

    /**
     * Indica si la autenticación fue exitosa.
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * Devuelve el usuario autenticado o null si falló.
     */
    public function user(): ?UserIdentity
    {
        return $this->user;
    }
}