
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Integration;

use DateTimeImmutable;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ShieldPHP\Core\Contracts\OAuthStateStoreInterface;
use ShieldPHP\Core\Contracts\PKCEStoreInterface;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\OAuth\OAuthClient;
use ShieldPHP\Core\OAuth\OAuthManager;
use ShieldPHP\Core\OAuth\OAuthStateManager;
use ShieldPHP\Core\OAuth\PKCEManager;
use ShieldPHP\Core\OAuth\Providers\GoogleProvider;

final class GoogleOAuthTest extends TestCase
{
    private const CLIENT_ID = 'google-test-client-id';

    private const CLIENT_SECRET = 'google-test-client-secret';

    private const REDIRECT_URI =
        'https://example.test/oauth/google/callback';

    /**
     * Comprueba que Google genere correctamente
     * la URL de autorización con PKCE S256.
     */
    public function testGeneratesGoogleAuthorizationUrl(): void
    {
        $harness = $this->createOAuthEnvironment([]);

        $manager = $harness['manager'];

        $url = $manager->getAuthorizationUrl('google');

        self::assertStringStartsWith(
            'https://accounts.google.com/o/oauth2/v2/auth?',
            $url
        );

        $query = parse_url($url, PHP_URL_QUERY);

        self::assertIsString($query);

        parse_str($query, $parameters);

        self::assertSame('code', $parameters['response_type']);
        self::assertSame(self::CLIENT_ID, $parameters['client_id']);

        self::assertSame(
            self::REDIRECT_URI,
            $parameters['redirect_uri']
        );

        self::assertSame('S256', $parameters['code_challenge_method']);

        self::assertMatchesRegularExpression(
            '/\A[A-Za-z0-9_-]{43}\z/',
            $parameters['state']
        );

        self::assertMatchesRegularExpression(
            '/\A[A-Za-z0-9_-]{43}\z/',
            $parameters['code_challenge']
        );

        self::assertSame(
            'openid email profile',
            $parameters['scope']
        );

        self::assertSame('offline', $parameters['access_type']);
    }

    /**
     * Comprueba que se intercambie correctamente
     * el código de autorización por tokens.
     */
    public function testExchangesAuthorizationCodeForToken(): void
    {
        $harness = $this->createOAuthEnvironment([
            $this->jsonResponse([
                'access_token' => 'google-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'scope' => 'openid email profile',
            ]),
            $this->jsonResponse([
                'sub' => 'google-user-123',
                'email' => 'user@example.com',
                'email_verified' => true,
                'name' => 'Test User',
                'picture' => 'https://example.com/avatar.png',
            ]),
        ]);

        $manager = $harness['manager'];

        $url = $manager->getAuthorizationUrl('google');

        $query = parse_url($url, PHP_URL_QUERY);

        self::assertIsString($query);

        parse_str($query, $parameters);

        $profile = $manager->handleCallback(
            'google',
            [
                'state' => $parameters['state'],
                'code' => 'google-authorization-code',
            ]
        );

        self::assertSame('google', $profile['provider']);

        self::assertSame(
            'google-user-123',
            $profile['provider_user_id']
        );

        self::assertSame(
            'user@example.com',
            $profile['email']
        );

        self::assertSame('Test User', $profile['name']);

        self::assertSame(
            'https://example.com/avatar.png',
            $profile['avatar_url']
        );

        // Comprobar las dos solicitudes HTTP simuladas.
        $requests = $harness['httpClient']->getRequests();

        self::assertCount(2, $requests);

        self::assertSame(
            'oauth2.googleapis.com',
            parse_url((string) $requests[0]->getUri(), PHP_URL_HOST)
        );

        self::assertSame(
            'openidconnect.googleapis.com',
            parse_url((string) $requests[1]->getUri(), PHP_URL_HOST)
        );

        // Comprobar que el verifier PKCE se envió en el intercambio.
        parse_str(
            (string) $requests[0]->getBody(),
            $tokenRequest
        );

        self::assertSame(
            'authorization_code',
            $tokenRequest['grant_type']
        );

        self::assertSame(
            'google-authorization-code',
            $tokenRequest['code']
        );

        self::assertSame(
            self::REDIRECT_URI,
            $tokenRequest['redirect_uri']
        );

        self::assertArrayHasKey(
            'code_verifier',
            $tokenRequest
        );

        self::assertMatchesRegularExpression(
            '/\A[A-Za-z0-9._~-]{43,128}\z/',
            $tokenRequest['code_verifier']
        );
    }

    /**
     * Comprueba que no se pueda reutilizar un callback OAuth.
     */
    public function testRejectsReplayedOAuthState(): void
    {
        $harness = $this->createOAuthEnvironment([
            $this->jsonResponse([
                'access_token' => 'google-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            $this->jsonResponse([
                'sub' => 'google-user-123',
                'email' => 'user@example.com',
                'email_verified' => true,
                'name' => 'Test User',
                'picture' => null,
            ]),
        ]);

        $manager = $harness['manager'];

        $url = $manager->getAuthorizationUrl('google');

        $query = parse_url($url, PHP_URL_QUERY);

        self::assertIsString($query);

        parse_str($query, $parameters);

        $callbackParams = [
            'state' => $parameters['state'],
            'code' => 'google-authorization-code',
        ];

        // Primer callback: debe funcionar.
        $profile = $manager->handleCallback(
            'google',
            $callbackParams
        );

        self::assertSame('google-user-123', $profile['provider_user_id']);

        // Segundo callback: debe ser rechazado.
        $this->expectException(AuthenticationException::class);

        $manager->handleCallback(
            'google',
            $callbackParams
        );
    }

    /**
     * Comprueba que se rechace un state desconocido.
     */
    public function testRejectsInvalidOAuthState(): void
    {
        $harness = $this->createOAuthEnvironment([]);

        $manager = $harness['manager'];

        $this->expectException(AuthenticationException::class);

        $manager->handleCallback(
            'google',
            [
                'state' => str_repeat('A', 43),
                'code' => 'authorization-code',
            ]
        );

        // No debe haber solicitudes HTTP al proveedor.
    }

    /**
     * Comprueba que los correos no verificados no se utilicen
     * como identificadores de vinculación de cuentas.
     */
    public function testDoesNotTrustUnverifiedEmail(): void
    {
        $harness = $this->createOAuthEnvironment([
            $this->jsonResponse([
                'access_token' => 'google-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            $this->jsonResponse([
                'sub' => 'google-user-456',
                'email' => 'unverified@example.com',
                'email_verified' => false,
                'name' => 'Unverified User',
                'picture' => null,
            ]),
        ]);

        $manager = $harness['manager'];

        $url = $manager->getAuthorizationUrl('google');

        $query = parse_url($url, PHP_URL_QUERY);

        self::assertIsString($query);

        parse_str($query, $parameters);

        $profile = $manager->handleCallback(
            'google',
            [
                'state' => $parameters['state'],
                'code' => 'google-authorization-code',
            ]
        );

        self::assertSame(
            'google-user-456',
            $profile['provider_user_id']
        );

        self::assertNull($profile['email']);
    }

    /**
     * Comprueba que se rechace un proveedor no registrado.
     */
    public function testRejectsUnregisteredProvider(): void
    {
        $harness = $this->createOAuthEnvironment([]);

        $this->expectException(\ShieldPHP\Core\Exceptions\ConfigurationException::class);

        $harness['manager']->getAuthorizationUrl('github');
    }

    /**
     * Crea una respuesta HTTP JSON simulada.
     *
     * @param array<string, mixed> $data
     */
    private function jsonResponse(array $data): ResponseInterface
    {
        return new Response(
            200,
            [
                'Content-Type' => 'application/json',
            ],
            json_encode($data, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Construye un entorno OAuth aislado para las pruebas.
     *
     * @param list<ResponseInterface> $responses
     *
     * @return array{
     *     manager: OAuthManager,
     *     httpClient: object
     * }
     */
    private function createOAuthEnvironment(array $responses): array
    {
        /**
         * Almacenamiento temporal de estados OAuth.
         *
         * Simula un almacenamiento persistente con consumo único.
         */
        $stateStore = new class implements OAuthStateStoreInterface {

            /**
             * @var array<string, DateTimeImmutable>
             */
            private array $states = [];

            public function store(
                string $stateHash,
                DateTimeImmutable $expiresAt
            ): void {
                $this->states[$stateHash] = $expiresAt;
            }

            public function consume(
                string $stateHash,
                DateTimeImmutable $now
            ): bool {
                $expiresAt = $this->states[$stateHash] ?? null;

                // Consumir el estado incluso cuando ya expiró.
                unset($this->states[$stateHash]);

                return $expiresAt instanceof DateTimeImmutable
                    && $expiresAt > $now;
            }

            public function removeExpired(
                DateTimeImmutable $now
            ): int {
                $removed = 0;

                foreach ($this->states as $hash => $expiresAt) {
                    if ($expiresAt <= $now) {
                        unset($this->states[$hash]);
                        $removed++;
                    }
                }

                return $removed;
            }
        };

        /**
         * Almacenamiento temporal de verificadores PKCE.
         */
        $pkceStore = new class implements PKCEStoreInterface {

            /**
             * @var array<string, array{
             *     verifier: string,
             *     expires_at: DateTimeImmutable
             * }>
             */
            private array $verifiers = [];

            public function store(
                string $stateHash,
                string $codeVerifier,
                DateTimeImmutable $expiresAt
            ): void {
                $this->verifiers[$stateHash] = [
                    'verifier' => $codeVerifier,
                    'expires_at' => $expiresAt,
                ];
            }

            public function consume(
                string $stateHash,
                DateTimeImmutable $now
            ): ?string {
                $entry = $this->verifiers[$stateHash] ?? null;

                // Consumir siempre el registro encontrado.
                unset($this->verifiers[$stateHash]);

                if (
                    $entry === null
                    || $entry['expires_at'] <= $now
                ) {
                    return null;
                }

                return $entry['verifier'];
            }

            public function removeExpired(
                DateTimeImmutable $now
            ): int {
                $removed = 0;

                foreach ($this->verifiers as $hash => $entry) {
                    if ($entry['expires_at'] <= $now) {
                        unset($this->verifiers[$hash]);
                        $removed++;
                    }
                }

                return $removed;
            }
        };

        // Cliente HTTP simulado: nunca realiza peticiones reales.
        $httpClient = new class($responses) implements ClientInterface {

            /**
             * @var list<ResponseInterface>
             */
            private array $responses;

            /**
             * @var list<RequestInterface>
             */
            private array $requests = [];

            /**
             * @param list<ResponseInterface> $responses
             */
            public function __construct(array $responses)
            {
                $this->responses = $responses;
            }

            public function sendRequest(
                RequestInterface $request
            ): ResponseInterface {
                $this->requests[] = $request;

                $response = array_shift($this->responses);

                if (!$response instanceof ResponseInterface) {
                    throw new \RuntimeException(
                        'No hay más respuestas HTTP simuladas.'
                    );
                }

                return $response;
            }

            /**
             * @return list<RequestInterface>
             */
            public function getRequests(): array
            {
                return $this->requests;
            }
        };

        // Fábrica PSR-17 de Guzzle.
        $httpFactory = new HttpFactory();

        // Cliente HTTP de ShieldPHP.
        $oauthClient = new OAuthClient(
            httpClient: $httpClient,
            requestFactory: $httpFactory,
            streamFactory: $httpFactory,
            allowedHosts: [
                'oauth2.googleapis.com',
                'openidconnect.googleapis.com',
            ]
        );

        // Construir los administradores del flujo OAuth.
        $stateManager = new OAuthStateManager($stateStore);

        $pkceManager = new PKCEManager($pkceStore);

        // Registrar Google.
        $googleProvider = new GoogleProvider(
            clientId: self::CLIENT_ID,
            clientSecret: self::CLIENT_SECRET,
            client: $oauthClient,
            scopes: [
                'openid',
                'email',
                'profile',
            ],
            offlineAccess: true,
            forceConsent: false
        );

        $manager = new OAuthManager(
            stateManager: $stateManager,
            pkceManager: $pkceManager
        );

        $manager->registerProvider(
            $googleProvider,
            self::REDIRECT_URI
        );

        return [
            'manager' => $manager,
            'httpClient' => $httpClient,
        ];
    }
}
