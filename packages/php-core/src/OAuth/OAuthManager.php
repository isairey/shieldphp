
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth;

use InvalidArgumentException;
use ShieldPHP\Core\Contracts\OAuthProviderInterface;
use ShieldPHP\Core\Exceptions\ConfigurationException;

final class OAuthManager
{
    /**
     * Proveedores registrados.
     *
     * @var array<string, array{
     *     provider: OAuthProviderInterface,
     *     redirect_uri: string
     * }>
     */
    private array $providers = [];

    public function __construct(
        private readonly OAuthStateManager $stateManager,
        private readonly PKCEManager $pkceManager
    ) {
    }

    /**
     * Registra un proveedor OAuth y su URI de callback.
     *
     * Ejemplo de nombre: google, facebook.
     */
    public function registerProvider(
        OAuthProviderInterface $provider,
        string $redirectUri
    ): void {
        $name = $this->normalizeProviderName(
            $provider->getName()
        );

        if (isset($this->providers[$name])) {
            throw new ConfigurationException(
                sprintf(
                    'El proveedor OAuth "%s" ya está registrado.',
                    $name
                )
            );
        }

        $this->validateRedirectUri($redirectUri);

        $this->providers[$name] = [
            'provider' => $provider,
            'redirect_uri' => $redirectUri,
        ];
    }

    /**
     * Comprueba si un proveedor está registrado.
     */
    public function hasProvider(string $name): bool
    {
        $name = $this->normalizeProviderName($name);

        return isset($this->providers[$name]);
    }

    /**
     * Devuelve los nombres de los proveedores registrados.
     *
     * @return list<string>
     */
    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Genera la URL de autorización para iniciar el flujo OAuth.
     *
     * El state debe guardarse en el servidor y el verificador PKCE
     * debe asociarse al mismo state.
     */
    public function getAuthorizationUrl(string $providerName): string
    {
        $registration = $this->getRegistration($providerName);

        $state = $this->stateManager->generateAndStore();

        $this->validateState($state);

        /*
         * Contrato esperado:
         * createChallenge($state) genera un verifier aleatorio,
         * lo guarda asociado al state y devuelve el challenge S256.
         */
        $codeChallenge = $this->pkceManager->createChallenge($state);

        $this->validateCodeChallenge($codeChallenge);

        $url = $registration['provider']->getAuthorizationUrl(
            $state,
            $registration['redirect_uri'],
            $codeChallenge
        );

        $this->validateAuthorizationUrl($url);

        return $url;
    }

    /**
     * Procesa el callback de un proveedor OAuth.
     *
     * Devuelve la identidad externa normalizada. No crea por sí mismo
     * un usuario local ni inicia una sesión de ShieldPHP.
     *
     * @param array<string, mixed> $queryParams
     *
     * @return array{
     *     provider: string,
     *     provider_user_id: string,
     *     email: ?string,
     *     name: ?string,
     *     avatar_url: ?string
     * }
     */
    public function handleCallback(
        string $providerName,
        array $queryParams
    ): array {
        $registration = $this->getRegistration($providerName);

        $handler = new CallbackHandler(
            provider: $registration['provider'],
            stateManager: $this->stateManager,
            pkceManager: $this->pkceManager,
            redirectUri: $registration['redirect_uri']
        );

        return $handler->handle($queryParams);
    }

    /**
     * Recupera un proveedor previamente registrado.
     *
     * @return array{
     *     provider: OAuthProviderInterface,
     *     redirect_uri: string
     * }
     */
    private function getRegistration(string $providerName): array
    {
        $name = $this->normalizeProviderName($providerName);

        if (!isset($this->providers[$name])) {
            throw new ConfigurationException(
                sprintf(
                    'El proveedor OAuth "%s" no está registrado.',
                    $name
                )
            );
        }

        return $this->providers[$name];
    }

    /**
     * Normaliza y valida el nombre del proveedor.
     */
    private function normalizeProviderName(string $name): string
    {
        $name = strtolower(trim($name));

        if (
            preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/', $name) !== 1
        ) {
            throw new InvalidArgumentException(
                'El nombre del proveedor OAuth no es válido.'
            );
        }

        return $name;
    }

    /**
     * Valida la URI de callback configurada por el servidor.
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
            throw new ConfigurationException(
                'La redirect_uri debe ser una URI absoluta válida.'
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
            throw new ConfigurationException(
                'La redirect_uri debe utilizar HTTPS, '
                . 'excepto en localhost durante desarrollo.'
            );
        }
    }

    /**
     * Comprueba el formato del state generado.
     */
    private function validateState(string $state): void
    {
        if (
            strlen($state) < 32
            || strlen($state) > 1024
            || preg_match('/\A[A-Za-z0-9._~-]+\z/', $state) !== 1
        ) {
            throw new ConfigurationException(
                'OAuthStateManager generó un state no válido.'
            );
        }
    }

    /**
     * Comprueba el formato de un challenge PKCE S256.
     */
    private function validateCodeChallenge(string $challenge): void
    {
        if (
            preg_match('/\A[A-Za-z0-9_-]{43}\z/', $challenge) !== 1
        ) {
            throw new ConfigurationException(
                'PKCEManager generó un challenge S256 no válido.'
            );
        }
    }

    /**
     * Solo permite URLs de autorización HTTPS.
     */
    private function validateAuthorizationUrl(string $url): void
    {
        $parts = parse_url($url);

        if (
            $parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new ConfigurationException(
                'El proveedor generó una URL de autorización no válida.'
            );
        }
    }
}
