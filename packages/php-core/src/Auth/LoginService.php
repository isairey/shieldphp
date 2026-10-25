<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Auth;

final class LoginService
{
    public function __construct(
        private readonly AuthManager $authManager
    ) {
    }

    /**
     * Inicia sesión utilizando las credenciales proporcionadas.
     */
    public function login(
        string $email,
        string $password
    ): AuthResult {
        return $this->authManager->attempt(
            $email,
            $password
        );
    }
}