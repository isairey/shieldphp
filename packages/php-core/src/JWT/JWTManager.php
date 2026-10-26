
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\JWT;

use Firebase\JWT\ExpiredException as FirebaseExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ShieldPHP\Core\Exceptions\ExpiredTokenException;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\Exceptions\ShieldException;
use stdClass;
use Throwable;

final class JWTManager
{
    /**
     * Longitud máxima admitida para un JWT.
     */
    private const MAX_TOKEN_LENGTH = 16384;

    public function __construct(
        private readonly JWTConfig $config
    ) {
    }

    /**
     * Genera y firma un JWT a partir de los claims proporcionados.
     */
    public function issue(JWTClaims $claims): string
    {
        $payload = $claims->toArray();

        // El emisor debe corresponder a la configuración.
        if ($claims->getIssuer() !== $this->config->getIssuer()) {
            throw new InvalidTokenException(
                'El emisor de los claims no coincide con la configuración.'
            );
        }

        // La audiencia debe coincidir con la configuración.
        if (!$this->audienceMatches(
            $claims->getAudience(),
            $this->config->getAudience()
        )) {
            throw new InvalidTokenException(
                'La audiencia de los claims no coincide con la configuración.'
            );
        }

        if ($claims->isExpired()) {
            throw new InvalidTokenException(
                'No se puede emitir un token que ya ha expirado.'
            );
        }

        try {
            return JWT::encode(
                $payload,
                $this->config->getSigningKey(),
                $this->config->getAlgorithm()
            );
        } catch (Throwable $exception) {
            throw new ShieldException(
                'ShieldPHP no pudo firmar el JWT.',
                0,
                $exception
            );
        }
    }

    /**
     * Decodifica y verifica la firma y los claims de un JWT.
     *
     * @throws ExpiredTokenException Si el token ha expirado.
     * @throws InvalidTokenException Si el token no es válido.
     */
    public function decode(string $token): stdClass
    {
        if (
            $token === ''
            || strlen($token) > self::MAX_TOKEN_LENGTH
            || preg_match('/\A[^\s]+\.[^\s]+\.[^\s]+\z/', $token) !== 1
        ) {
            throw new InvalidTokenException(
                'El formato del JWT no es válido.'
            );
        }

        // firebase/php-jwt utiliza una tolerancia global.
        // Restauramos su valor original tras verificar el token.
        $previousLeeway = JWT::$leeway;

        try {
            JWT::$leeway = $this->config->getLeeway();

            try {
                $decoded = JWT::decode(
                    $token,
                    new Key(
                        $this->config->getSigningKey(),
                        $this->config->getAlgorithm()
                    )
                );
            } catch (FirebaseExpiredException $exception) {
                throw new ExpiredTokenException(
                    'El JWT ha expirado.',
                    0,
                    $exception
                );
            } catch (Throwable $exception) {
                throw new InvalidTokenException(
                    'El JWT no es válido o no pudo verificarse.',
                    0,
                    $exception
                );
            }
        } finally {
            JWT::$leeway = $previousLeeway;
        }

        $this->validateRegisteredClaims($decoded);

        return $decoded;
    }

    /**
     * Comprueba un token y devuelve true si es válido.
     */
    public function validate(string $token): bool
    {
        try {
            $this->decode($token);

            return true;
        } catch (InvalidTokenException) {
            return false;
        }
    }

    /**
     * Valida los claims obligatorios y la configuración esperada.
     */
    private function validateRegisteredClaims(stdClass $claims): void
    {
        if (
            !isset($claims->iss)
            || !is_string($claims->iss)
            || $claims->iss !== $this->config->getIssuer()
        ) {
            throw new InvalidTokenException(
                'El emisor del JWT no es válido.'
            );
        }

        if (
            !isset($claims->sub)
            || !is_string($claims->sub)
            || trim($claims->sub) === ''
        ) {
            throw new InvalidTokenException(
                'El JWT no contiene un sujeto válido.'
            );
        }

        if (
            !isset($claims->iat)
            || !is_int($claims->iat)
            || !isset($claims->exp)
            || !is_int($claims->exp)
        ) {
            throw new InvalidTokenException(
                'El JWT no contiene fechas de emisión y expiración válidas.'
            );
        }

        if (
            isset($claims->nbf)
            && !is_int($claims->nbf)
        ) {
            throw new InvalidTokenException(
                'El claim nbf no es válido.'
            );
        }

        if (
            isset($claims->jti)
            && (!is_string($claims->jti) || $claims->jti === '')
        ) {
            throw new InvalidTokenException(
                'El identificador del JWT no es válido.'
            );
        }

        if (
            !property_exists($claims, 'aud')
            || !$this->audienceMatches(
                $claims->aud,
                $this->config->getAudience()
            )
        ) {
            throw new InvalidTokenException(
                'La audiencia del JWT no es válida.'
            );
        }
    }

    /**
     * Comprueba si las audiencias coinciden.
     *
     * Se admite una audiencia como cadena o como lista de cadenas.
     */
    private function audienceMatches(
        mixed $actual,
        string|array $expected
    ): bool {
        if (is_string($actual)) {
            $actualAudiences = [$actual];
        } elseif (is_array($actual) && array_is_list($actual)) {
            foreach ($actual as $audience) {
                if (!is_string($audience)) {
                    return false;
                }
            }

            $actualAudiences = $actual;
        } else {
            return false;
        }

        $expectedAudiences = is_string($expected)
            ? [$expected]
            : $expected;

        return array_intersect(
            $actualAudiences,
            $expectedAudiences
        ) !== [];
    }
}
