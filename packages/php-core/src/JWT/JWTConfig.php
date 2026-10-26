<?php

declare(strict_types=1);

namespace ShieldPHP\Core\JWT;

use ShieldPHP\Core\Exceptions\ConfigurationException;

final class JWTConfig
{
    /**
     * Longitud mínima de la clave, en bytes, para cada algoritmo.
     *
     * @var array<string, int>
     */
    private const SUPPORTED_ALGORITHMS = [
        'HS256' => 32,
        'HS384' => 48,
        'HS512' => 64,
    ];

    private const MAX_ACCESS_TOKEN_TTL = 86400;
    private const MAX_REFRESH_TOKEN_TTL = 31536000;
    private const MAX_LEEWAY = 300;

    private readonly string $signingKey;
    private readonly string $algorithm;
    private readonly string $issuer;

    /**
     * @var string|list<string>
     */
    private readonly string|array $audience;

    private readonly int $accessTokenTtl;
    private readonly int $refreshTokenTtl;
    private readonly int $leeway;

    /**
     * @param string|list<string> $audience
     */
    public function __construct(
        string $signingKey,
        string $issuer,
        string|array $audience,
        string $algorithm = 'HS256',
        int $accessTokenTtl = 900,
        int $refreshTokenTtl = 604800,
        int $leeway = 0
    ) {
        $issuer = trim($issuer);

        if ($issuer === '') {
            throw new ConfigurationException(
                'El emisor del JWT no puede estar vacío.'
            );
        }

        if (!isset(self::SUPPORTED_ALGORITHMS[$algorithm])) {
            throw new ConfigurationException(
                'El algoritmo JWT no está permitido.'
            );
        }

        $minimumKeyLength = self::SUPPORTED_ALGORITHMS[$algorithm];

        if (strlen($signingKey) < $minimumKeyLength) {
            throw new ConfigurationException(
                sprintf(
                    'La clave para %s debe tener al menos %d bytes.',
                    $algorithm,
                    $minimumKeyLength
                )
            );
        }

        $this->validateAudience($audience);

        if (
            $accessTokenTtl < 1
            || $accessTokenTtl > self::MAX_ACCESS_TOKEN_TTL
        ) {
            throw new ConfigurationException(
                'El tiempo del access token debe estar entre 1 segundo y 24 horas.'
            );
        }

        if (
            $refreshTokenTtl <= $accessTokenTtl
            || $refreshTokenTtl > self::MAX_REFRESH_TOKEN_TTL
        ) {
            throw new ConfigurationException(
                'El refresh token debe durar más que el access token '
                . 'y no superar 365 días.'
            );
        }

        if ($leeway < 0 || $leeway > self::MAX_LEEWAY) {
            throw new ConfigurationException(
                'La tolerancia de reloj debe estar entre 0 y 300 segundos.'
            );
        }

        $this->signingKey = $signingKey;
        $this->issuer = $issuer;
        $this->audience = $audience;
        $this->algorithm = $algorithm;
        $this->accessTokenTtl = $accessTokenTtl;
        $this->refreshTokenTtl = $refreshTokenTtl;
        $this->leeway = $leeway;
    }

    /**
     * Obtiene la clave para firmar y verificar JWT.
     *
     * No debe registrarse ni enviarse al frontend.
     */
    public function getSigningKey(): string
    {
        return $this->signingKey;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * @return string|list<string>
     */
    public function getAudience(): string|array
    {
        return $this->audience;
    }

    public function getAccessTokenTtl(): int
    {
        return $this->accessTokenTtl;
    }

    public function getRefreshTokenTtl(): int
    {
        return $this->refreshTokenTtl;
    }

    public function getLeeway(): int
    {
        return $this->leeway;
    }

    /**
     * Valida el formato de la audiencia.
     *
     * @param string|list<string> $audience
     */
    private function validateAudience(string|array $audience): void
    {
        if (is_string($audience)) {
            if (trim($audience) === '') {
                throw new ConfigurationException(
                    'La audiencia del JWT no puede estar vacía.'
                );
            }

            return;
        }

        if (!array_is_list($audience) || $audience === []) {
            throw new ConfigurationException(
                'La audiencia debe ser una cadena o una lista no vacía.'
            );
        }

        foreach ($audience as $value) {
            if (!is_string($value) || trim($value) === '') {
                throw new ConfigurationException(
                    'Cada audiencia debe ser una cadena no vacía.'
                );
            }
        }
    }
}