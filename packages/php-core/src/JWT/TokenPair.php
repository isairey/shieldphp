
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\JWT;

use InvalidArgumentException;

final readonly class TokenPair
{
    public function __construct(
        private string $accessToken,
        private string $refreshToken,
        private int $expiresIn,
        private int $refreshExpiresIn,
        private string $tokenType = 'Bearer'
    ) {
        if (trim($this->accessToken) === '') {
            throw new InvalidArgumentException(
                'El access token no puede estar vacío.'
            );
        }

        if (trim($this->refreshToken) === '') {
            throw new InvalidArgumentException(
                'El refresh token no puede estar vacío.'
            );
        }

        if ($this->expiresIn <= 0) {
            throw new InvalidArgumentException(
                'La duración del access token debe ser positiva.'
            );
        }

        if ($this->refreshExpiresIn <= 0) {
            throw new InvalidArgumentException(
                'La duración del refresh token debe ser positiva.'
            );
        }

        if ($this->refreshExpiresIn <= $this->expiresIn) {
            throw new InvalidArgumentException(
                'El refresh token debe tener una duración mayor '
                . 'que el access token.'
            );
        }

        if (
            trim($this->tokenType) === ''
            || preg_match(
                '/\A[A-Za-z][A-Za-z0-9._~-]*\z/',
                $this->tokenType
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'El tipo de token no es válido.'
            );
        }
    }

    /**
     * Obtiene el access token.
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Obtiene el refresh token.
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * Obtiene la duración del access token en segundos.
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * Obtiene la duración del refresh token en segundos.
     */
    public function getRefreshExpiresIn(): int
    {
        return $this->refreshExpiresIn;
    }

    /**
     * Obtiene el tipo de token.
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * Convierte el par de tokens a un formato para respuestas JSON.
     *
     * @return array{
     *     access_token: string,
     *     refresh_token: string,
     *     token_type: string,
     *     expires_in: int,
     *     refresh_expires_in: int
     * }
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'refresh_expires_in' => $this->refreshExpiresIn,
        ];
    }
}
