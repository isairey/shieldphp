
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\OAuth\Providers;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\OAuth\OAuthClient;
use ShieldPHP\Core\OAuth\Providers\GoogleProvider;
use ShieldPHP\Core\OAuth\TokenResponse;

final class GoogleProviderTest extends TestCase
{
    private const CLIENT_ID = 'google-test-client-id';

    private const CLIENT_SECRET = 'google-test-client-secret';

    private const REDIRECT_URI = 'https://example.com/auth/google/callback';

    private const STATE = 'google-oauth-state-123456789';

    private const ACCESS_TOKEN = 'google-access-token-example-123456789';

    private OAuthClient&MockObject $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(OAuthClient::class);
    }

    /**
     * Crea una instancia del proveedor para las pruebas.
     */
    private function createProvider(): GoogleProvider
    {
        return new GoogleProvider(
            $this->client,
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );
    }

    /**
     * Crea un TokenResponse de prueba.
     */
    private function createTokenResponse(
        string $accessToken = self::ACCESS_TOKEN
    ): TokenResponse {
        return new TokenResponse(
            $accessToken,
            'Bearer',
            3600,
            'google-refresh-token',
            'openid email profile',
            'google-id-token',
            new DateTimeImmutable()
        );
    }

    /**
     * Obtiene los parámetros de una URL.
     */
    private function getUrlParameters(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);

        parse_str($query ?? '', $parameters);

        return $parameters;
    }

    /**
     * Comprueba que se pueda instanciar GoogleProvider.
     */
    public function testCanBeInstantiated(): void
    {
        $provider = $this->createProvider();

        $this->assertInstanceOf(
            GoogleProvider::class,
            $provider
        );
    }

    /**
     * Comprueba que el proveedor tenga el nombre correcto.
     */
    public function testReturnsGoogleName(): void
    {
        $provider = $this->createProvider();

        $this->assertSame(
            'google',
            strtolower($provider->getName())
        );
    }

    /**
     * Comprueba que se genere una URL de autorización.
     */
    public function testGeneratesAuthorizationUrl(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $this->assertIsString($url);
        $this->assertNotEmpty($url);

        $this->assertSame(
            'https',
            parse_url($url, PHP_URL_SCHEME)
        );
    }

    /**
     * La autorización debe utilizar el dominio oficial de Google.
     */
    public function testAuthorizationUrlUsesGoogleDomain(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $host = parse_url($url, PHP_URL_HOST);

        $this->assertContains(
            $host,
            [
                'accounts.google.com',
            ]
        );
    }

    /**
     * La URL debe incluir el client_id.
     */
    public function testAuthorizationUrlContainsClientId(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $parameters = $this->getUrlParameters($url);

        $this->assertSame(
            self::CLIENT_ID,
            $parameters['client_id'] ?? null
        );
    }

    /**
     * La URL debe incluir el redirect_uri.
     */
    public function testAuthorizationUrlContainsRedirectUri(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $parameters = $this->getUrlParameters($url);

        $this->assertSame(
            self::REDIRECT_URI,
            $parameters['redirect_uri'] ?? null
        );
    }

    /**
     * La URL debe incluir el parámetro state.
     */
    public function testAuthorizationUrlContainsState(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $parameters = $this->getUrlParameters($url);

        $this->assertSame(
            self::STATE,
            $parameters['state'] ?? null
        );
    }

    /**
     * Google debe utilizar el flujo de autorización por código.
     */
    public function testAuthorizationUrlUsesCodeResponseType(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $parameters = $this->getUrlParameters($url);

        $this->assertSame(
            'code',
            $parameters['response_type'] ?? null
        );
    }

    /**
     * Comprueba que se soliciten los scopes de OpenID Connect.
     */
    public function testAuthorizationUrlContainsExpectedScopes(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $parameters = $this->getUrlParameters($url);

        $scopes = preg_split(
            '/\s+/',
            trim($parameters['scope'] ?? '')
        );

        $this->assertContains('openid', $scopes);
        $this->assertContains('email', $scopes);
        $this->assertContains('profile', $scopes);
    }

    /**
     * Comprueba que se pueda solicitar acceso sin conexión.
     */
    public function testAuthorizationUrlRequestsOfflineAccess(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $parameters = $this->getUrlParameters($url);

        $this->assertSame(
            'offline',
            $parameters['access_type'] ?? null
        );
    }

    /**
     * Comprueba que se incluya el challenge PKCE cuando se proporciona.
     */
    public function testAuthorizationUrlIncludesPkceChallenge(): void
    {
        $provider = $this->createProvider();

        $challenge = 'google-pkce-challenge-123456789';

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI,
            $challenge
        );

        $parameters = $this->getUrlParameters($url);

        $this->assertSame(
            $challenge,
            $parameters['code_challenge'] ?? null
        );

        $this->assertSame(
            'S256',
            $parameters['code_challenge_method'] ?? null
        );
    }

    /**
     * Comprueba que se pueda generar una autorización sin challenge PKCE.
     */
    public function testAuthorizationUrlSupportsMissingPkceChallenge(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $parameters = $this->getUrlParameters($url);

        $this->assertArrayNotHasKey(
            'code_challenge',
            $parameters
        );
    }

    /**
     * Comprueba que se intercambie el código por un token.
     */
    public function testExchangesCodeForToken(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('postForm')
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'google-refresh-token',
                'scope' => 'openid email profile',
                'id_token' => 'google-id-token',
            ]);

        $response = $provider->exchangeCodeForToken(
            'google-authorization-code',
            self::REDIRECT_URI
        );

        $this->assertInstanceOf(
            TokenResponse::class,
            $response
        );

        $this->assertSame(
            self::ACCESS_TOKEN,
            $response->getAccessToken()
        );
    }

    /**
     * El intercambio debe utilizar el endpoint oficial de Google.
     */
    public function testTokenExchangeUsesGoogleEndpoint(): void
    {
        $provider = $this->createProvider();

        $requestUrl = null;

        $this->client
            ->expects($this->once())
            ->method('postForm')
            ->willReturnCallback(
                function (
                    string $url,
                    array $data
                ) use (&$requestUrl): array {
                    $requestUrl = $url;

                    return [
                        'access_token' => self::ACCESS_TOKEN,
                    ];
                }
            );

        $provider->exchangeCodeForToken(
            'google-code',
            self::REDIRECT_URI
        );

        $this->assertIsString($requestUrl);

        $this->assertSame(
            'oauth2.googleapis.com',
            parse_url($requestUrl, PHP_URL_HOST)
        );
    }

    /**
     * Comprueba que se envíe el código de autorización.
     */
    public function testTokenExchangeSendsAuthorizationCode(): void
    {
        $provider = $this->createProvider();

        $requestData = null;

        $this->client
            ->expects($this->once())
            ->method('postForm')
            ->willReturnCallback(
                function (
                    string $url,
                    array $data
                ) use (&$requestData): array {
                    $requestData = $data;

                    return [
                        'access_token' => self::ACCESS_TOKEN,
                    ];
                }
            );

        $provider->exchangeCodeForToken(
            'google-authorization-code',
            self::REDIRECT_URI
        );

        $this->assertSame(
            'google-authorization-code',
            $requestData['code'] ?? null
        );
    }

    /**
     * Comprueba que se envíe el client_id.
     */
    public function testTokenExchangeSendsClientId(): void
    {
        $provider = $this->createProvider();

        $requestData = null;

        $this->client
            ->expects($this->once())
            ->method('postForm')
            ->willReturnCallback(
                function (
                    string $url,
                    array $data
                ) use (&$requestData): array {
                    $requestData = $data;

                    return [
                        'access_token' => self::ACCESS_TOKEN,
                    ];
                }
            );

        $provider->exchangeCodeForToken(
            'google-code',
            self::REDIRECT_URI
        );

        $this->assertSame(
            self::CLIENT_ID,
            $requestData['client_id'] ?? null
        );
    }

    /**
     * Comprueba que se envíe el client_secret.
     */
    public function testTokenExchangeSendsClientSecret(): void
    {
        $provider = $this->createProvider();

        $requestData = null;

        $this->client
            ->expects($this->once())
            ->method('postForm')
            ->willReturnCallback(
                function (
                    string $url,
                    array $data
                ) use (&$requestData): array {
                    $requestData = $data;

                    return [
                        'access_token' => self::ACCESS_TOKEN,
                    ];
                }
            );

        $provider->exchangeCodeForToken(
            'google-code',
            self::REDIRECT_URI
        );

        $this->assertSame(
            self::CLIENT_SECRET,
            $requestData['client_secret'] ?? null
        );
    }

    /**
     * Comprueba que se envíe el redirect_uri.
     */
    public function testTokenExchangeSendsRedirectUri(): void
    {
        $provider = $this->createProvider();

        $requestData = null;

        $this->client
            ->expects($this->once())
            ->method('postForm')
            ->willReturnCallback(
                function (
                    string $url,
                    array $data
                ) use (&$requestData): array {
                    $requestData = $data;

                    return [
                        'access_token' => self::ACCESS_TOKEN,
                    ];
                }
            );

        $provider->exchangeCodeForToken(
            'google-code',
            self::REDIRECT_URI
        );

        $this->assertSame(
            self::REDIRECT_URI,
            $requestData['redirect_uri'] ?? null
        );
    }

    /**
     * Comprueba que se envíe grant_type authorization_code.
     */
    public function testTokenExchangeUsesAuthorizationCodeGrant(): void
    {
        $provider = $this->createProvider();

        $requestData = null;

        $this->client
            ->expects($this->once())
            ->method('postForm')
            ->willReturnCallback(
                function (
                    string $url,
                    array $data
                ) use (&$requestData): array {
                    $requestData = $data;

                    return [
                        'access_token' => self::ACCESS_TOKEN,
                    ];
                }
            );

        $provider->exchangeCodeForToken(
            'google-code',
            self::REDIRECT_URI
        );

        $this->assertSame(
            'authorization_code',
            $requestData['grant_type'] ?? null
        );
    }

    /**
     * Comprueba el envío del verifier PKCE.
     */
    public function testTokenExchangeSupportsPkceVerifier(): void
    {
        $provider = $this->createProvider();

        $verifier = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNO';

        $requestData = null;

        $this->client
            ->expects($this->once())
            ->method('postForm')
            ->willReturnCallback(
                function (
                    string $url,
                    array $data
                ) use (&$requestData): array {
                    $requestData = $data;

                    return [
                        'access_token' => self::ACCESS_TOKEN,
                    ];
                }
            );

        $provider->exchangeCodeForToken(
            'google-code',
            self::REDIRECT_URI,
            $verifier
        );

        $this->assertSame(
            $verifier,
            $requestData['code_verifier'] ?? null
        );
    }

    /**
     * Comprueba que se procesen los campos de la respuesta OAuth.
     */
    public function testProcessesCompleteTokenResponse(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('postForm')
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
                'token_type' => 'Bearer',
                'expires_in' => 7200,
                'refresh_token' => 'google-refresh-token',
                'scope' => 'openid email profile',
                'id_token' => 'google-id-token',
            ]);

        $response = $provider->exchangeCodeForToken(
            'google-code',
            self::REDIRECT_URI
        );

        $this->assertSame(
            self::ACCESS_TOKEN,
            $response->getAccessToken()
        );

        $this->assertSame(
            'Bearer',
            $response->getTokenType()
        );

        $this->assertSame(
            7200,
            $response->getExpiresIn()
        );

        $this->assertSame(
            'google-refresh-token',
            $response->getRefreshToken()
        );

        $this->assertSame(
            'openid email profile',
            $response->getScope()
        );

        $this->assertSame(
            'google-id-token',
            $response->getIdToken()
        );
    }

    /**
     * Comprueba que se recupere el perfil del usuario.
     */
    public function testFetchesUserProfile(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test Google User',
                'given_name' => 'Test',
                'family_name' => 'User',
                'email' => 'test@example.com',
                'email_verified' => true,
                'picture' => 'https://example.com/avatar.jpg',
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertIsArray($profile);
        $this->assertNotEmpty($profile);
    }

    /**
     * Comprueba que se consulte el endpoint oficial de UserInfo.
     */
    public function testProfileRequestUsesGoogleUserInfoEndpoint(): void
    {
        $provider = $this->createProvider();

        $requestUrl = null;

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->willReturnCallback(
                function (
                    string $url,
                    array $query = [],
                    array $headers = []
                ) use (&$requestUrl): array {
                    $requestUrl = $url;

                    return [
                        'sub' => 'google-user-123456',
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'email_verified' => true,
                    ];
                }
            );

        $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertIsString($requestUrl);

        $this->assertSame(
            'openidconnect.googleapis.com',
            parse_url($requestUrl, PHP_URL_HOST)
        );
    }

    /**
     * Comprueba que la solicitud del perfil utilice el access token.
     */
    public function testProfileRequestUsesAccessToken(): void
    {
        $provider = $this->createProvider();

        $requestArguments = [];

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->willReturnCallback(
                function (...$arguments) use (&$requestArguments): array {
                    $requestArguments = $arguments;

                    return [
                        'sub' => 'google-user-123456',
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                        'email_verified' => true,
                    ];
                }
            );

        $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $serializedArguments = json_encode(
            $requestArguments,
            JSON_THROW_ON_ERROR
        );

        $this->assertStringContainsString(
            self::ACCESS_TOKEN,
            $serializedArguments
        );
    }

    /**
     * El perfil normalizado debe contener un identificador.
     */
    public function testProfileContainsUserId(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified' => true,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            'google-user-123456',
            $profile['id'] ?? null
        );
    }

    /**
     * El perfil debe conservar el nombre completo.
     */
    public function testProfileContainsName(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test Google User',
                'email' => 'test@example.com',
                'email_verified' => true,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            'Test Google User',
            $profile['name'] ?? null
        );
    }

    /**
     * El perfil debe conservar el nombre.
     */
    public function testProfileContainsFirstName(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test Google User',
                'given_name' => 'Test',
                'email' => 'test@example.com',
                'email_verified' => true,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            'Test',
            $profile['first_name'] ?? null
        );
    }

    /**
     * El perfil debe conservar los apellidos.
     */
    public function testProfileContainsLastName(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test Google User',
                'family_name' => 'User',
                'email' => 'test@example.com',
                'email_verified' => true,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            'User',
            $profile['last_name'] ?? null
        );
    }

    /**
     * El correo verificado debe conservarse.
     */
    public function testProfileContainsVerifiedEmail(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified' => true,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            'test@example.com',
            $profile['email'] ?? null
        );
    }

    /**
     * Un correo no verificado no debe considerarse confiable.
     */
    public function testUnverifiedEmailIsReturnedAsNull(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test User',
                'email' => 'unverified@example.com',
                'email_verified' => false,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertNull(
            $profile['email'] ?? null
        );
    }

    /**
     * La ausencia del correo no debe invalidar un perfil con ID válido.
     */
    public function testProfileSupportsMissingEmail(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test User',
                'email_verified' => false,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            'google-user-123456',
            $profile['id'] ?? null
        );

        $this->assertNull(
            $profile['email'] ?? null
        );
    }

    /**
     * Comprueba que el perfil pueda contener una fotografía.
     */
    public function testProfileContainsPicture(): void
    {
        $provider = $this->createProvider();

        $picture = 'https://example.com/google-avatar.jpg';

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified' => true,
                'picture' => $picture,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            $picture,
            $profile['picture'] ?? null
        );
    }

    /**
     * Comprueba que el perfil pueda procesarse sin fotografía.
     */
    public function testProfileSupportsMissingPicture(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'sub' => 'google-user-123456',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified' => true,
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertNull(
            $profile['picture'] ?? null
        );
    }

    /**
     * Un perfil sin identificador no debe aceptarse.
     */
    public function testProfileWithoutSubjectIsRejected(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'email_verified' => true,
            ]);

        $this->expectException(AuthenticationException::class);

        $provider->fetchUserProfile(
            $this->createTokenResponse()
        );
    }

    /**
     * Comprueba que los errores de intercambio se propaguen correctamente.
     */
    public function testPropagatesAuthenticationExceptionDuringTokenExchange(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('postForm')
            ->willThrowException(
                new AuthenticationException(
                    'Google token exchange failed.'
                )
            );

        $this->expectException(AuthenticationException::class);

        $provider->exchangeCodeForToken(
            'invalid-google-code',
            self::REDIRECT_URI
        );
    }

    /**
     * Comprueba que los errores al recuperar el perfil se propaguen.
     */
    public function testPropagatesAuthenticationExceptionDuringProfileFetch(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willThrowException(
                new AuthenticationException(
                    'Unable to retrieve Google profile.'
                )
            );

        $this->expectException(AuthenticationException::class);

        $provider->fetchUserProfile(
            $this->createTokenResponse()
        );
    }
}
