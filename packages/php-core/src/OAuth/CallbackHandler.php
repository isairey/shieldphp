
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth;

use InvalidArgumentException;
use ShieldPHP\Core\Auth\AuthenticationException;
use ShieldPHP\Core\Contracts\OAuthProviderInterface;
use ShieldPHP\Core\Exceptions\ShieldException;
use Throwable;

final class CallbackHandler
{
    private const MAX_STATE_LENGTH = 1024;
    private const MAX_CODE_LENGTH = 8192;
    private const MAX_PROFILE_ID_LENGTH = 255;
    private const MAX_PROFILE_NAME_LENGTH = 500;

    public function __construct(
        private readonly OAuthProviderInterface $provider,
        private readonly OAuthStateManager $stateManager,
        private readonly PKCEManager $pkceManager,
        private readonly string $redirectUri
    ) {
        $this->validateRedirectUri($this->redirectUri);
    }

    /**
     * Procesa los parámetros recibidos en el callback OAuth.
     *
     * Primero valida y consume el state de un solo uso.
     * Después recupera el verificador PKCE, intercambia el código
     * y obtiene el perfil normalizado del proveedor.
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
    public function handle(array $queryParams): array
    {
        $state = $queryParams['state'] ?? null;

        $this->validateState($state);

        /*
         * consume() debe comprobar el state almacenado y eliminarlo
         * atómicamente para impedir la reutilización del callback.
         */
        if (!$this->stateManager->consume($state)) {
            throw new AuthenticationException(
                'La validación del estado OAuth ha fallado.'
            );
        }

        /*
         * El verificador debe estar asociado a este state y almacenado
         * de forma segura en el servidor durante el inicio del flujo.
         */
        $codeVerifier = $this->pkceManager->consumeVerifier($state);

        // No revelar información detallada del proveedor al cliente.
        if (isset($queryParams['error'])) {
            if (
                !is_string($queryParams['error'])
                || $queryParams['error'] === ''
            ) {
                throw new AuthenticationException(
                    'La respuesta del proveedor OAuth no es válida.'
                );
            }

            throw new AuthenticationException(
                'La autorización OAuth fue cancelada o rechazada.'
            );
        }

        $code = $queryParams['code'] ?? null;

        $this->validateAuthorizationCode($code);
        $this->validateCodeVerifier($codeVerifier);

        try {
            // La URI procede de la configuración del servidor,
            // nunca de un parámetro controlado por el navegador.
            $tokenResponse = $this->provider->exchangeCodeForToken(
                $code,
                $this->redirectUri,
                $codeVerifier
            );

            $profile = $this->provider->fetchUserProfile(
                $tokenResponse
            );

            $normalizedProfile = $this->normalizeProfile($profile);

            $providerName = $this->provider->getName();

            if (
                trim($providerName) === ''
                || strlen($providerName) > 100
            ) {
                throw new ShieldException(
                    'El identificador del proveedor OAuth no es válido.'
                );
            }

            return [
                'provider' => $providerName,
                'provider_user_id' => $normalizedProfile['id'],
                'email' => $normalizedProfile['email'],
                'name' => $normalizedProfile['name'],
                'avatar_url' => $normalizedProfile['avatar_url'],
            ];
        } catch (AuthenticationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            /*
             * La excepción original se conserva como causa para que
             * el manejador interno de errores pueda registrarla.
             * No debe enviarse su mensaje al usuario final.
             */
            throw new AuthenticationException(
                'No fue posible completar la autenticación OAuth.',
                0,
                $exception
            );
        }
    }

    /**
     * Comprueba el formato del state sin modificar su valor.
     */
    private function validateState(mixed $state): void
    {
        if (
            !is_string($state)
            || strlen($state) < 32
            || strlen($state) > self::MAX_STATE_LENGTH
            || preg_match('/\A[A-Za-z0-9._~-]+\z/', $state) !== 1
        ) {
            throw new AuthenticationException(
                'La respuesta OAuth no contiene un state válido.'
            );
        }
    }

    /**
     * Valida el código de autorización recibido.
     */
    private function validateAuthorizationCode(mixed $code): void
    {
        if (
            !is_string($code)
            || $code === ''
            || strlen($code) > self::MAX_CODE_LENGTH
            || preg_match('/[\x00-\x20\x7F]/', $code) === 1
        ) {
            throw new AuthenticationException(
                'El código de autorización OAuth no es válido.'
            );
        }
    }

    /**
     * PKCE utiliza un code_verifier de entre 43 y 128 caracteres.
     */
    private function validateCodeVerifier(mixed $verifier): void
    {
        if (
            !is_string($verifier)
            || preg_match(
                '/\A[A-Za-z0-9._~-]{43,128}\z/',
                $verifier
            ) !== 1
        ) {
            throw new AuthenticationException(
                'No se encontró un verificador PKCE válido para este flujo.'
            );
        }
    }

    /**
     * Comprueba que los datos proporcionados por el proveedor
     * cumplan el contrato normalizado de ShieldPHP.
     *
     * @param array<string, mixed> $profile
     *
     * @return array{
     *     id: string,
     *     email: ?string,
     *     name: ?string,
     *     avatar_url: ?string
     * }
     */
    private function normalizeProfile(array $profile): array
    {
        $id = $profile['id'] ?? null;
        $email = $profile['email'] ?? null;
        $name = $profile['name'] ?? null;
        $avatarUrl = $profile['avatar_url'] ?? null;

        if (
            !is_string($id)
            || trim($id) === ''
            || strlen($id) > self::MAX_PROFILE_ID_LENGTH
        ) {
            throw new AuthenticationException(
                'El proveedor no devolvió una identidad válida.'
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
                'El proveedor devolvió un correo no válido.'
            );
        }

        if (
            $name !== null
            && (
                !is_string($name)
                || strlen($name) > self::MAX_PROFILE_NAME_LENGTH
            )
        ) {
            throw new AuthenticationException(
                'El proveedor devolvió un nombre no válido.'
            );
        }

        if (
            $avatarUrl !== null
            && (
                !is_string($avatarUrl)
                || filter_var($avatarUrl, FILTER_VALIDATE_URL) === false
                || strtolower((string) parse_url($avatarUrl, PHP_URL_SCHEME))
                    !== 'https'
                || parse_url($avatarUrl, PHP_URL_HOST) === null
            )
        ) {
            throw new AuthenticationException(
                'La URL del avatar del proveedor no es válida.'
            );
        }

        return [
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'avatar_url' => $avatarUrl,
        ];
    }

    /**
     * Valida la URI de retorno configurada por el servidor.
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
            throw new InvalidArgumentException(
                'La redirect_uri debe utilizar HTTPS, '
                . 'excepto en localhost durante desarrollo.'
            );
        }
    }
}
