<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Contracts;

use ShieldPHP\Core\OAuth\TokenResponse;

interface OAuthProviderInterface
{
    /**
     * Devuelve el identificador del proveedor.
     *
     * Ejemplos: google, facebook.
     */
    public function getName(): string;

    /**
     * Construye la URL para iniciar la autorización OAuth 2.0.
     *
     * El proveedor debe incluir el estado y, cuando corresponda,
     * los parámetros PKCE en la solicitud de autorización.
     */
    public function getAuthorizationUrl(
        string $state,
        string $redirectUri,
        ?string $codeChallenge = null
    ): string;

    /**
     * Intercambia el código de autorización por tokens.
     *
     * El proveedor debe comprobar la respuesta HTTP y validar
     * los datos recibidos antes de construir TokenResponse.
     */
    public function exchangeCodeForToken(
        string $code,
        string $redirectUri,
        ?string $codeVerifier = null
    ): TokenResponse;

    /**
     * Obtiene el perfil del usuario autenticado en el proveedor.
     *
     * El resultado debe normalizar los campos de los distintos
     * proveedores a una estructura común.
     *
     * @return array{
     *     id: string,
     *     email: string|null,
     *     name: string|null,
     *     avatar_url: string|null
     * }
     */
    public function fetchUserProfile(
        TokenResponse $token
    ): array;
}