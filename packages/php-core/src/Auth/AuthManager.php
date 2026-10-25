<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Auth;

use ShieldPHP\Core\Contracts\UserProviderInterface;

final class AuthManager
{
    private readonly string $dummyHash;

    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly PasswordHasher $passwordHasher
    ) {
        // Hash ficticio para reducir diferencias de tiempo cuando
        // el correo no corresponde a ningún usuario.
        $hash = password_hash(
            bin2hex(random_bytes(32)),
            PASSWORD_DEFAULT
        );

        if ($hash === false) {
            throw new \RuntimeException(
                'No fue posible inicializar la autenticación.'
            );
        }

        $this->dummyHash = $hash;
    }

    /**
     * Intenta autenticar un usuario con correo y contraseña.
     */
    public function attempt(
        string $email,
        string $password
    ): AuthResult {
        $email = strtolower(trim($email));

        // Rechazar entradas inválidas con un resultado genérico.
        if (
            strlen($email) > 254
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || $password === ''
            || strlen($password) > 4096
            || str_contains($password, "\0")
        ) {
            $this->performDummyVerification($password);

            return AuthResult::failure();
        }

        // Buscar al usuario mediante el proveedor configurado.
        $user = $this->userProvider->findByEmail($email);

        if ($user === null) {
            $this->performDummyVerification($password);

            return AuthResult::failure();
        }

        // Comprobar la contraseña usando el hash almacenado.
        if (
            !$this->passwordHasher->verify(
                $password,
                $user->getPasswordHash()
            )
        ) {
            return AuthResult::failure();
        }

        // No autenticar cuentas deshabilitadas.
        if (!$user->isActive()) {
            return AuthResult::failure();
        }

        return AuthResult::success($user);
    }

    /**
     * Ejecuta una verificación ficticia para entradas no válidas
     * o usuarios inexistentes.
     */
    private function performDummyVerification(
        string $password
    ): void {
        if (
            strlen($password) > 4096
            || str_contains($password, "\0")
        ) {
            $password = 'invalid-shieldphp-password';
        }

        $this->passwordHasher->verify(
            $password,
            $this->dummyHash
        );
    }
}