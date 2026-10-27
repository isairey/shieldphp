
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\JWT;

use stdClass;
use ShieldPHP\Core\Exceptions\InvalidTokenException;

final class TokenValidator
{
    public function __construct(
        private readonly JWTManager $jwtManager,
        private readonly ?TokenRevocationStoreInterface $revocationStore = null
    ) {
    }

    /**
     * Valida el JWT y devuelve sus claims.
     *
     * Comprueba la firma, el algoritmo, la expiración, el emisor,
     * la audiencia y, si existe un almacén configurado, la revocación.
     *
     * @throws InvalidTokenException Si el token no es válido
     *                               o ha sido revocado.
     */
    public function validate(string $token): stdClass
    {
        // JWTManager verifica la firma y los claims registrados.
        $claims = $this->jwtManager->decode($token);

        // Si existe un almacén de revocación, exigimos un jti
        // para poder identificar y revocar el token individualmente.
        if ($this->revocationStore !== null) {
            if (
                !isset($claims->jti)
                || !is_string($claims->jti)
                || trim($claims->jti) === ''
            ) {
                throw new InvalidTokenException(
                    'El JWT no contiene un identificador jti válido.'
                );
            }

            if ($this->revocationStore->isRevoked($claims->jti)) {
                throw new InvalidTokenException(
                    'El JWT ha sido revocado.'
                );
            }
        }

        return $claims;
    }

    /**
     * Comprueba si un JWT es válido.
     *
     * Devuelve false para tokens inválidos, expirados o revocados.
     */
    public function isValid(string $token): bool
    {
        try {
            $this->validate($token);

            return true;
        } catch (InvalidTokenException) {
            return false;
        }
    }
}
