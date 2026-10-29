
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth\Providers;

use InvalidArgumentException;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\OAuth\AuthorizationUrlBuilder;
use ShieldPHP\Core\OAuth\OAuthClient;
use ShieldPHP\Core\OAuth\OAuthProviderInterface;
use ShieldPHP\Core\OAuth\TokenResponse;

final class GoogleProvider implements OAuthProviderInterface
{
    private const AUTHORIZATION_ENDPOINT =
        'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_ENDPOINT =
        'https://oauth2.googleapis.com/token';

    private const USERINFO_ENDPOINT =
        'https://openidconnect.googleapis.com/v1/userinfo';

    private readonly string $clientId;
    private readonly string $clientSecret;

    /**
     * @var list<string>
     */
    private readonly array $scopes;

    public function __construct(
        string $clientId,
        string $clientSecret,
        private readonly OAuthClient $client,
        array $scopes = ['openid', 'email', 'profile'],
        private readonly bool $offlineAccess = true,
        private readonly bool $forceConsent = false
    ) {
        $clientId = trim($clientId);

        if ($clientId === '' || strlen($clientId) > 1024) {
            throw new InvalidArgumentException(
                'El client_id de Google no es válido.'
            );
        }

        if (
            $clientSecret === ''
            || strlen($clientSecret) > 4096
            || str_contains($clientSecret, "\0")
        ) {
            throw new InvalidArgumentException(
                'El client_secret de Google no es válido.'
            );
        }

        if (!array_is_list($scopes) || $scopes === []) {
            throw new InvalidArgumentException(
                'Los scopes de Google deben ser una lista no vacía.'
            );
        }

        foreach ($scopes as $scope) {
            if (
                !is_string($scope)
                || preg_match(
                    '/\A[A-Za-z0-9._:-]{1,255}\z/',
                    $scope
                ) !== 1
            ) {
                throw new InvalidArgumentException(
                    'Uno de los scopes de Google no es válido.'
                );
            }
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->scopes = array_values(array_unique($scopes));
    }

    /**
     * Identificador utilizado por OAuthManager.
     */
    public function getName(): string
    {
        return 'google';
    }

    /**
     * Construye la URL de autorización de Google.
     */
    public function getAuthorizationUrl(
        string $state,
        string $redirectUri,
        ?string $codeChallenge = null
    ): string {
        $additionalParameters = [
            'include_granted_scopes' => 'true',
        ];

        if ($this->offlineAccess) {
            $additionalParameters['access_type'] = 'offline';
        }

        if ($this->forceConsent) {
            $additionalParameters['prompt'] = 'consent';
        }

        $builder = new AuthorizationUrlBuilder(
            authorizationEndpoint: self::AUTHORIZATION_ENDPOINT,
            clientId: $this->clientId,
            scopes: $this->scopes,
            additionalParameters: $additionalParameters
        );

        return $builder->build(
            redirectUri: $redirectUri,
            state: $state,
            codeChallenge: $codeChallenge
        );
    }

    /**
     * Intercambia el código de autorización por tokens de Google.
     */
    public function exchangeCodeForToken(
        string $code,
        string $redirectUri,
        ?string $codeVerifier = null
    ): TokenResponse {
        $this->validateAuthorizationCode($code);

        $this->validateRedirectUri($redirectUri);

        $form = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
        ];

        if ($codeVerifier !== null) {
            $this->validateCodeVerifier($codeVerifier);
            $form['code_verifier'] = $codeVerifier;
        }

        $data = $this->client->postForm(
            self::TOKEN_ENDPOINT,
            $form
        );

        try {
            return TokenResponse::fromArray($data);
        } catch (InvalidArgumentException $exception) {
            throw new AuthenticationException(
                'Google devolvió una respuesta de tokens no válida.',
                0,
                $exception
            );
        }
    }

    /**
     * Obtiene el perfil del usuario mediante OpenID Connect UserInfo.
     *
     * El identificador "sub" de Google se normaliza como "id".
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
        if (strcasecmp($token->getTokenType(), 'Bearer') !== 0) {
            throw new AuthenticationException(
                'Google devolvió un tipo de token no admitido.'
            );
        }

        if ($token->isExpired()) {
            throw new AuthenticationException(
                'El access token de Google ha expirado.'
            );
        }

        $profile = $this->client->getJson(
            self::USERINFO_ENDPOINT,
            [
                'Authorization' => 'Bearer ' . $token->getAccessToken(),
            ]
        );

        $subject = $profile['sub'] ?? null;
        $email = $profile['email'] ?? null;
        $emailVerified = $profile['email_verified'] ?? false;
        $name = $profile['name'] ?? null;
        $picture = $profile['picture'] ?? null;

        if (
            !is_string($subject)
            || trim($subject) === ''
            || strlen($subject) > 255
        ) {
            throw new AuthenticationException(
                'Google no devolvió un identificador de usuario válido.'
            );
        }

        if (
            $email !== null
            && (
                !is_string($email)
                || strlen($email) > 254
                || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            )
        ) {
            throw new AuthenticationException(
                'Google devolvió un correo electrónico no válido.'
            );
        }

        if (!is_bool($emailVerified)) {
            throw new AuthenticationException(
                'El estado de verificación del correo no es válido.'
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
                'Google devolvió un nombre no válido.'
            );
        }

        if (
            $picture !== null
            && (
                !is_string($picture)
                || filter_var($picture, FILTER_VALIDATE_URL) === false
                || strtolower(
                    (string) parse_url($picture, PHP_URL_SCHEME)
                ) !== 'https'
                || parse_url($picture, PHP_URL_HOST) === null
            )
        ) {
            throw new AuthenticationException(
                'Google devolvió una URL de imagen no válida.'
            );
        }

        /*
         * Solo exponemos el correo si Google indica explícitamente
         * que está verificado. El identificador estable es "sub",
         * no la dirección de correo.
         */
        $verifiedEmail = (
            $emailVerified === true && is_string($email)
        )
            ? $email
            : null;

        return [
            'id' => $subject,
            'email' => $verifiedEmail,
            'name' => $name,
            'avatar_url' => $picture,
        ];
    }

    /**
     * Valida el código recibido en el callback.
     */
    private function validateAuthorizationCode(string $code): void
    {
        if (
            $code === ''
            || strlen($code) > 8192
            || preg_match('/[\x00-\x20\x7F]/', $code) === 1
        ) {
            throw new AuthenticationException(
                'El código de autorización de Google no es válido.'
            );
        }
    }

    /**
     * Valida el formato del verifier PKCE.
     */
    private function validateCodeVerifier(string $verifier): void
    {
        if (
            preg_match(
                '/\A[A-Za-z0-9._~-]{43,128}\z/',
                $verifier
            ) !== 1
        ) {
            throw new AuthenticationException(
                'El verificador PKCE no tiene un formato válido.'
            );
        }
    }

    /**
     * Valida la URI configurada por el servidor.
     */
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
                'La redirect_uri de Google no es válida.'
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
            'La redirect_uri debe utilizar HTTPS, '
            . 'excepto en localhost durante desarrollo.'
        );
    }
}
