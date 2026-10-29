
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth\Providers;

use InvalidArgumentException;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\OAuth\AuthorizationUrlBuilder;
use ShieldPHP\Core\OAuth\OAuthClient;
use ShieldPHP\Core\OAuth\OAuthProviderInterface;
use ShieldPHP\Core\OAuth\TokenResponse;

final class FacebookProvider implements OAuthProviderInterface
{
    private const DEFAULT_API_VERSION = 'v26.0';

    private readonly string $clientId;
    private readonly string $clientSecret;
    private readonly string $apiVersion;

    /**
     * @var list<string>
     */
    private readonly array $scopes;

    public function __construct(
        string $clientId,
        string $clientSecret,
        private readonly OAuthClient $client,
        string $apiVersion = self::DEFAULT_API_VERSION,
        array $scopes = ['email', 'public_profile']
    ) {
        $clientId = trim($clientId);

        if ($clientId === '' || strlen($clientId) > 1024) {
            throw new InvalidArgumentException(
                'El client_id de Facebook no es válido.'
            );
        }

        if (
            $clientSecret === ''
            || strlen($clientSecret) > 4096
            || str_contains($clientSecret, "\0")
        ) {
            throw new InvalidArgumentException(
                'El client_secret de Facebook no es válido.'
            );
        }

        if (
            preg_match('/\Av[0-9]+\.[0-9]+\z/', $apiVersion) !== 1
        ) {
            throw new InvalidArgumentException(
                'La versión de Graph API debe tener el formato vN.N.'
            );
        }

        if (!array_is_list($scopes)) {
            throw new InvalidArgumentException(
                'Los permisos de Facebook deben ser una lista.'
            );
        }

        foreach ($scopes as $scope) {
            if (
                !is_string($scope)
                || preg_match('/\A[a-zA-Z][a-zA-Z0-9_]*\z/', $scope) !== 1
            ) {
                throw new InvalidArgumentException(
                    'Uno de los permisos de Facebook no es válido.'
                );
            }
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->apiVersion = $apiVersion;
        $this->scopes = array_values(array_unique($scopes));
    }

    /**
     * Identificador utilizado por OAuthManager.
     */
    public function getName(): string
    {
        return 'facebook';
    }

    /**
     * Construye la URL del diálogo de autorización de Facebook.
     */
    public function getAuthorizationUrl(
        string $state,
        string $redirectUri,
        ?string $codeChallenge = null
    ): string {
        /*
         * Facebook utiliza permisos separados por comas en el
         * parámetro scope. Se pasa como un único elemento para
         * mantener la compatibilidad con AuthorizationUrlBuilder.
         */
        $builderScopes = $this->scopes === []
            ? []
            : [implode(',', $this->scopes)];

        $builder = new AuthorizationUrlBuilder(
            authorizationEndpoint: $this->authorizationEndpoint(),
            clientId: $this->clientId,
            scopes: $builderScopes
        );

        return $builder->build(
            redirectUri: $redirectUri,
            state: $state,
            codeChallenge: $codeChallenge
        );
    }

    /**
     * Intercambia el código de autorización por un access token.
     */
    public function exchangeCodeForToken(
        string $code,
        string $redirectUri,
        ?string $codeVerifier = null
    ): TokenResponse {
        $this->validateAuthorizationCode($code);
        $this->validateRedirectUri($redirectUri);

        $form = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ];

        if ($codeVerifier !== null) {
            $this->validateCodeVerifier($codeVerifier);
            $form['code_verifier'] = $codeVerifier;
        }

        $data = $this->client->postForm(
            $this->tokenEndpoint(),
            $form
        );

        $accessToken = $data['access_token'] ?? null;

        if (
            !is_string($accessToken)
            || $accessToken === ''
        ) {
            throw new AuthenticationException(
                'Facebook no devolvió un access token válido.'
            );
        }

        /*
         * Algunas respuestas no incluyen token_type.
         * OAuth 2.0 utiliza Bearer como valor habitual.
         */
        $tokenType = $data['token_type'] ?? 'Bearer';

        if (!is_string($tokenType)) {
            throw new AuthenticationException(
                'La respuesta de Facebook contiene un token_type inválido.'
            );
        }

        $expiresIn = $data['expires_in'] ?? null;

        if (
            is_string($expiresIn)
            && preg_match('/\A[0-9]{1,10}\z/', $expiresIn) === 1
        ) {
            $expiresIn = (int) $expiresIn;
        }

        if ($expiresIn !== null && !is_int($expiresIn)) {
            throw new AuthenticationException(
                'Facebook devolvió una expiración inválida.'
            );
        }

        $refreshToken = $data['refresh_token'] ?? null;
        $scope = $data['scope'] ?? null;
        $idToken = $data['id_token'] ?? null;

        if (
            $refreshToken !== null && !is_string($refreshToken)
            || $scope !== null && !is_string($scope)
            || $idToken !== null && !is_string($idToken)
        ) {
            throw new AuthenticationException(
                'La respuesta de tokens de Facebook no tiene un formato válido.'
            );
        }

        try {
            return TokenResponse::fromArray([
                'access_token' => $accessToken,
                'token_type' => $tokenType,
                'expires_in' => $expiresIn,
                'refresh_token' => $refreshToken,
                'scope' => $scope,
                'id_token' => $idToken,
            ]);
        } catch (InvalidArgumentException $exception) {
            throw new AuthenticationException(
                'No fue posible interpretar la respuesta de tokens de Facebook.',
                0,
                $exception
            );
        }
    }

    /**
     * Obtiene y normaliza el perfil del usuario autenticado.
     *
     * @return array{
     *     id: string,
     *     email: string|null,
     *     name: string|null,
     *     avatar_url: string|null
     * }
     */
    public function fetchUserProfile(TokenResponse $token): array
    {
        $query = http_build_query(
            [
                'fields' => 'id,name,email,picture',
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $url = $this->profileEndpoint() . '?' . $query;

        // Enviar el token en Authorization, no en la URL.
        $profile = $this->client->getJson(
            $url,
            [
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
            ]
        );

        $id = $profile['id'] ?? null;
        $email = $profile['email'] ?? null;
        $name = $profile['name'] ?? null;
        $avatarUrl = $profile['picture']['data']['url'] ?? null;

        if (
            !is_string($id)
            || trim($id) === ''
            || strlen($id) > 255
        ) {
            throw new AuthenticationException(
                'Facebook no devolvió un identificador de usuario válido.'
            );
        }

        if (
            $email !== null
            && (
                !is_string($email)
                || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            )
        ) {
            throw new AuthenticationException(
                'Facebook devolvió un correo electrónico inválido.'
            );
        }

        if (
            $name !== null
            && (
                !is_string($name)
                || strlen($name) > 500
            )
        ) {
            throw new AuthenticationException(
                'Facebook devolvió un nombre no válido.'
            );
        }

        if ($avatarUrl !== null) {
            if (
                !is_string($avatarUrl)
                || filter_var($avatarUrl, FILTER_VALIDATE_URL) === false
                || strtolower(
                    (string) parse_url($avatarUrl, PHP_URL_SCHEME)
                ) !== 'https'
                || parse_url($avatarUrl, PHP_URL_HOST) === null
            ) {
                throw new AuthenticationException(
                    'Facebook devolvió una URL de avatar no válida.'
                );
            }
        }

        return [
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'avatar_url' => $avatarUrl,
        ];
    }

    private function authorizationEndpoint(): string
    {
        return sprintf(
            'https://www.facebook.com/%s/dialog/oauth',
            $this->apiVersion
        );
    }

    private function tokenEndpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/oauth/access_token',
            $this->apiVersion
        );
    }

    private function profileEndpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/me',
            $this->apiVersion
        );
    }

    private function validateAuthorizationCode(string $code): void
    {
        if (
            $code === ''
            || strlen($code) > 8192
            || preg_match('/[\x00-\x20\x7F]/', $code) === 1
        ) {
            throw new AuthenticationException(
                'El código de autorización de Facebook no es válido.'
            );
        }
    }

    private function validateCodeVerifier(string $verifier): void
    {
        if (
            preg_match('/\A[A-Za-z0-9._~-]{43,128}\z/', $verifier) !== 1
        ) {
            throw new AuthenticationException(
                'El verificador PKCE no tiene un formato válido.'
            );
        }
    }

    private function validateRedirectUri(string $redirectUri): void
    {
        $parts = parse_url($redirectUri);

        if (
            $parts === false
            || empty($parts['scheme'])
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException(
                'La redirect_uri de Facebook no es válida.'
            );
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(trim($parts['host'], '[]'));

        if ($scheme === 'https') {
            return;
        }

        if (
            $scheme === 'http'
            && in_array(
                $host,
                ['localhost', '127.0.0.1', '::1'],
                true
            )
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'La redirect_uri debe usar HTTPS, excepto en localhost.'
        );
    }
}
