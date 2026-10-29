
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth;

use DateTimeImmutable;
use InvalidArgumentException;

final class TokenResponse
{
    private readonly string $accessToken;
    private readonly string $tokenType;
    private readonly ?int $expiresIn;
    private readonly ?string $refreshToken;
    private readonly ?string $scope;
    private readonly ?string $idToken;
    private readonly DateTimeImmutable $receivedAt;

    public function __construct(
        string $accessToken,
        string $tokenType = 'Bearer',
        ?int $expiresIn = null,
        ?string $refreshToken = null,
        ?string $scope = null,
        ?string $idToken = null,
        ?DateTimeImmutable $receivedAt = null
    ) {
        $this->validateToken($accessToken, 'access_token');

        if (
            preg_match(
                '/\A[A-Za-z][A-Za-z0-9._~-]*\z/',
                $tokenType
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'El token_type de la respuesta OAuth no es válido.'
            );
        }

        if ($expiresIn !== null && $expiresIn <= 0) {
            throw new InvalidArgumentException(
                'expires_in debe ser un entero positivo.'
            );
        }

        if ($refreshToken !== null) {
            $this->validateToken($refreshToken, 'refresh_token');
        }

        if ($idToken !== null) {
            $this->validateToken($idToken, 'id_token');
        }

        if (
            $scope !== null
            && (
                strlen($scope) > 8192
                || preg_match('/[\x00-\x1F\x7F]/', $scope) === 1
            )
        ) {
            throw new InvalidArgumentException(
                'El scope de la respuesta OAuth no es válido.'
            );
        }

        $this->accessToken = $accessToken;
        $this->tokenType = $tokenType;
        $this->expiresIn = $expiresIn;
        $this->refreshToken = $refreshToken;
        $this->scope = $scope;
        $this->idToken = $idToken;
        $this->receivedAt = $receivedAt ?? new DateTimeImmutable();
    }

    /**
     * Construye una respuesta desde el array JSON devuelto
     * por OAuthClient::postForm().
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data,
        ?DateTimeImmutable $receivedAt = null
    ): self {
        $accessToken = $data['access_token'] ?? null;
        $tokenType = $data['token_type'] ?? null;
        $expiresIn = $data['expires_in'] ?? null;
        $refreshToken = $data['refresh_token'] ?? null;
        $scope = $data['scope'] ?? null;
        $idToken = $data['id_token'] ?? null;

        if (!is_string($accessToken) || !is_string($tokenType)) {
            throw new InvalidArgumentException(
                'La respuesta OAuth debe contener access_token y token_type.'
            );
        }

        if ($expiresIn !== null && !is_int($expiresIn)) {
            throw new InvalidArgumentException(
                'expires_in debe ser un entero.'
            );
        }

        if ($refreshToken !== null && !is_string($refreshToken)) {
            throw new InvalidArgumentException(
                'refresh_token debe ser una cadena.'
            );
        }

        if ($scope !== null && !is_string($scope)) {
            throw new InvalidArgumentException(
                'scope debe ser una cadena.'
            );
        }

        if ($idToken !== null && !is_string($idToken)) {
            throw new InvalidArgumentException(
                'id_token debe ser una cadena.'
            );
        }

        return new self(
            accessToken: $accessToken,
            tokenType: $tokenType,
            expiresIn: $expiresIn,
            refreshToken: $refreshToken,
            scope: $scope,
            idToken: $idToken,
            receivedAt: $receivedAt
        );
    }

    /**
     * Obtiene el access token.
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Obtiene el tipo de token, normalmente Bearer.
     */
    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    /**
     * Obtiene la duración original del access token en segundos.
     */
    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    /**
     * Obtiene la fecha de expiración estimada.
     *
     * Devuelve null si el proveedor no comunicó expires_in.
     */
    public function getExpiresAt(): ?DateTimeImmutable
    {
        if ($this->expiresIn === null) {
            return null;
        }

        $expiresAt = $this->receivedAt->modify(
            sprintf('+%d seconds', $this->expiresIn)
        );

        return $expiresAt === false ? null : $expiresAt;
    }

    /**
     * Indica si el token ha expirado según la hora local.
     *
     * Si no se recibió expires_in, no se conoce la expiración.
     */
    public function isExpired(
        ?DateTimeImmutable $now = null
    ): bool {
        $expiresAt = $this->getExpiresAt();

        if ($expiresAt === null) {
            return false;
        }

        $now ??= new DateTimeImmutable();

        return $now->getTimestamp() >= $expiresAt->getTimestamp();
    }

    /**
     * Obtiene el refresh token, si el proveedor lo devolvió.
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * Obtiene el scope original informado por el proveedor.
     */
    public function getScope(): ?string
    {
        return $this->scope;
    }

    /**
     * Devuelve los scopes como una lista de cadenas.
     *
     * @return list<string>
     */
    public function getScopes(): array
    {
        if ($this->scope === null || trim($this->scope) === '') {
            return [];
        }

        $scopes = preg_split('/\s+/', trim($this->scope));

        return $scopes === false
            ? []
            : array_values($scopes);
    }

    /**
     * Obtiene el ID token, cuando el proveedor lo devuelve.
     */
    public function getIdToken(): ?string
    {
        return $this->idToken;
    }

    /**
     * Obtiene la fecha de recepción de la respuesta.
     */
    public function getReceivedAt(): DateTimeImmutable
    {
        return $this->receivedAt;
    }

    /**
     * Valida el formato básico de los tokens opacos.
     */
    private function validateToken(
        string $token,
        string $field
    ): void {
        if (
            $token === ''
            || strlen($token) > 65536
            || preg_match('/[\x00-\x20\x7F]/', $token) === 1
        ) {
            throw new InvalidArgumentException(
                sprintf('El campo OAuth "%s" no es válido.', $field)
            );
        }
    }
}
