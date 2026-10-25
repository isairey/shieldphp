<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Auth;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use ShieldPHP\Core\JWT\TokenRevocationStoreInterface;
use ShieldPHP\Core\Session\SessionManager;

final class LogoutService
{
    public function __construct(
        private readonly ?SessionManager $sessionManager = null,
        private readonly ?TokenRevocationStoreInterface $tokenRevocationStore = null
    ) {
    }

    /**
     * Cierra la sesión y, opcionalmente, revoca un JWT.
     *
     * @param string|null $tokenId Identificador jti del JWT validado.
     * @param DateTimeImmutable|null $expiresAt Fecha de expiración del JWT.
     */
    public function logout(
        ?string $tokenId = null,
        ?DateTimeImmutable $expiresAt = null
    ): void {
        if (
            $this->sessionManager === null
            && $this->tokenRevocationStore === null
        ) {
            throw new LogicException(
                'ShieldPHP requiere un gestor de sesiones '
                . 'o un almacén de revocación.'
            );
        }

        if ($tokenId === null && $expiresAt !== null) {
            throw new InvalidArgumentException(
                'No puedes proporcionar la expiración sin un identificador de token.'
            );
        }

        if ($tokenId !== null) {
            if (trim($tokenId) === '') {
                throw new InvalidArgumentException(
                    'El identificador del token no puede estar vacío.'
                );
            }

            if ($expiresAt === null) {
                throw new InvalidArgumentException(
                    'La fecha de expiración es obligatoria para revocar el token.'
                );
            }

            if ($this->tokenRevocationStore === null) {
                throw new LogicException(
                    'No se ha configurado un almacén de revocación de tokens.'
                );
            }

            $this->tokenRevocationStore->revoke(
                $tokenId,
                $expiresAt
            );
        }

        if ($this->sessionManager !== null) {
            $this->sessionManager->destroy();
        }
    }
}