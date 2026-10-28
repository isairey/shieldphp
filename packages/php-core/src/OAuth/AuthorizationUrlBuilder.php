
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth;

use InvalidArgumentException;

final class AuthorizationUrlBuilder
{
    /**
     * Parámetros que esta clase controla y que no pueden sobrescribirse
     * mediante parámetros adicionales.
     *
     * @var list<string>
     */
    private const RESERVED_PARAMETERS = [
        'response_type',
        'client_id',
        'redirect_uri',
        'state',
        'scope',
        'code_challenge',
        'code_challenge_method',
    ];

    /**
     * @param list<string> $scopes Permisos solicitados al proveedor.
     * @param array<string, string|int|bool> $additionalParameters
     *        Parámetros opcionales específicos del proveedor.
     */
    public function __construct(
        private readonly string $authorizationEndpoint,
        private readonly string $clientId,
        private readonly array $scopes = [],
        private readonly array $additionalParameters = []
    ) {
        $this->validateAuthorizationEndpoint();
        $this->validateClientId();
        $this->validateScopes();
        $this->validateAdditionalParameters();
    }

    /**
     * Construye la URL de autorización OAuth 2.0.
     *
     * @param string $redirectUri URI de retorno registrada en el proveedor.
     * @param string $state Valor aleatorio generado por OAuthStateManager.
     * @param string|null $codeChallenge Desafío PKCE S256, si se utiliza.
     */
    public function build(
        string $redirectUri,
        string $state,
        ?string $codeChallenge = null
    ): string {
        $this->validateRedirectUri($redirectUri);
        $this->validateState($state);

        $parameters = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ];

        if ($this->scopes !== []) {
            $parameters['scope'] = implode(' ', $this->scopes);
        }

        if ($codeChallenge !== null) {
            $this->validateCodeChallenge($codeChallenge);

            $parameters['code_challenge'] = $codeChallenge;
            $parameters['code_challenge_method'] = 'S256';
        }

        // Los parámetros adicionales no pueden reemplazar los
        // parámetros de seguridad administrados por esta clase.
        foreach ($this->additionalParameters as $name => $value) {
            $parameters[$name] = $value;
        }

        $query = http_build_query(
            $parameters,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $separator = str_contains($this->authorizationEndpoint, '?')
            ? '&'
            : '?';

        return $this->authorizationEndpoint . $separator . $query;
    }

    /**
     * Comprueba que el endpoint de autorización utilice HTTPS.
     */
    private function validateAuthorizationEndpoint(): void
    {
        $parts = parse_url($this->authorizationEndpoint);

        if (
            $parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException(
                'El endpoint OAuth debe ser una URL HTTPS válida, '
                . 'sin credenciales ni fragmentos.'
            );
        }
    }

    /**
     * Valida el identificador del cliente OAuth.
     */
    private function validateClientId(): void
    {
        if (
            trim($this->clientId) === ''
            || strlen($this->clientId) > 1024
            || preg_match('/[\x00-\x20\x7F]/', $this->clientId) === 1
        ) {
            throw new InvalidArgumentException(
                'El client_id OAuth no es válido.'
            );
        }
    }

    /**
     * Valida los scopes solicitados.
     */
    private function validateScopes(): void
    {
        if (!array_is_list($this->scopes)) {
            throw new InvalidArgumentException(
                'Los scopes deben proporcionarse como una lista.'
            );
        }

        foreach ($this->scopes as $scope) {
            if (
                !is_string($scope)
                || $scope === ''
                || preg_match('/[\x00-\x20\x7F]/', $scope) === 1
            ) {
                throw new InvalidArgumentException(
                    'Cada scope debe ser una cadena no vacía y sin espacios.'
                );
            }
        }
    }

    /**
     * Valida la URI de retorno.
     *
     * HTTPS es obligatorio en producción. HTTP solo se admite
     * para localhost y direcciones de loopback durante desarrollo.
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
                'La redirect_uri debe ser una URI absoluta válida '
                . 'y no puede contener credenciales ni fragmentos.'
            );
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(trim($parts['host'], '[]'));

        if ($scheme === 'https') {
            return;
        }

        $isLoopback = in_array(
            $host,
            ['localhost', '127.0.0.1', '::1'],
            true
        );

        if ($scheme !== 'http' || !$isLoopback) {
            throw new InvalidArgumentException(
                'La redirect_uri debe utilizar HTTPS, excepto en localhost '
                . 'durante desarrollo.'
            );
        }
    }

    /**
     * Evita estados vacíos o con caracteres de control.
     */
    private function validateState(string $state): void
    {
        if (
            trim($state) === ''
            || strlen($state) > 1024
            || preg_match('/[\x00-\x1F\x7F]/', $state) === 1
        ) {
            throw new InvalidArgumentException(
                'El parámetro state no es válido.'
            );
        }
    }

    /**
     * Valida un challenge PKCE con codificación Base64 URL-safe.
     */
    private function validateCodeChallenge(string $codeChallenge): void
    {
        if (
            preg_match(
                '/\A[A-Za-z0-9_-]{43,128}\z/',
                $codeChallenge
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'El code_challenge PKCE debe ser Base64 URL-safe, '
                . 'sin padding, y tener entre 43 y 128 caracteres.'
            );
        }
    }

    /**
     * Valida parámetros adicionales específicos del proveedor.
     */
    private function validateAdditionalParameters(): void
    {
        foreach ($this->additionalParameters as $name => $value) {
            if (
                !is_string($name)
                || preg_match('/\A[A-Za-z][A-Za-z0-9._~-]{0,127}\z/', $name) !== 1
            ) {
                throw new InvalidArgumentException(
                    'El nombre de un parámetro OAuth adicional no es válido.'
                );
            }

            if (in_array($name, self::RESERVED_PARAMETERS, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'El parámetro OAuth "%s" está reservado.',
                        $name
                    )
                );
            }

            if (
                !is_string($value)
                && !is_int($value)
                && !is_bool($value)
            ) {
                throw new InvalidArgumentException(
                    'Los valores de parámetros adicionales deben ser escalares.'
                );
            }
        }
    }
}
