
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\OAuth\Providers;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\OAuth\OAuthClient;
use ShieldPHP\Core\OAuth\Providers\FacebookProvider;
use ShieldPHP\Core\OAuth\TokenResponse;

final class FacebookProviderTest extends TestCase
{
    private const CLIENT_ID = 'facebook-test-client-id';

    private const CLIENT_SECRET = 'facebook-test-client-secret';

    private const REDIRECT_URI = 'https://example.com/auth/facebook/callback';

    private const STATE = 'facebook-oauth-state-123456789';

    private const ACCESS_TOKEN = 'facebook-access-token-example-123456789';

    private OAuthClient&MockObject $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(OAuthClient::class);
    }

    /**
     * Crea una instancia del proveedor para las pruebas.
     */
    private function createProvider(): FacebookProvider
    {
        return new FacebookProvider(
            $this->client,
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );
    }

    /**
     * Crea una respuesta de token de prueba.
     */
    private function createTokenResponse(
        string $accessToken = self::ACCESS_TOKEN
    ): TokenResponse {
        return new TokenResponse(
            $accessToken,
            'Bearer',
            3600,
            null,
            null,
            null,
            new DateTimeImmutable()
        );
    }

    /**
     * Convierte una URL en sus parámetros de consulta.
     */
    private function getUrlParameters(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);

        parse_str($query ?? '', $parameters);

        return $parameters;
    }

    /**
     * Comprueba que el proveedor pueda instanciarse.
     */
    public function testCanBeInstantiated(): void
    {
        $provider = $this->createProvider();

        $this->assertInstanceOf(
            FacebookProvider::class,
            $provider
        );
    }

    /**
     * Comprueba que el nombre del proveedor sea Facebook.
     */
    public function testReturnsFacebookName(): void
    {
        $provider = $this->createProvider();

        $this->assertSame(
            'facebook',
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
     * La autorización debe utilizar un dominio oficial de Facebook.
     */
    public function testAuthorizationUrlUsesFacebookDomain(): void
    {
        $provider = $this->createProvider();

        $url = $provider->getAuthorizationUrl(
            self::STATE,
            self::REDIRECT_URI
        );

        $this->assertContains(
            parse_url($url, PHP_URL_HOST),
            [
                'www.facebook.com',
                'facebook.com',
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
     * La URL debe solicitar el flujo de autorización por código.
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
     * Comprueba que se incluya el challenge PKCE cuando se proporciona.
     */
    public function testAuthorizationUrlIncludesPkceChallenge(): void
    {
        $provider = $this->createProvider();

        $challenge = 'test-pkce-challenge-123456789';

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
     * Comprueba que se pueda generar una autorización sin challenge.
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
     * Comprueba que se pueda intercambiar un código por un access token.
     */
    public function testExchangesCodeForToken(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]);

        $response = $provider->exchangeCodeForToken(
            'facebook-authorization-code',
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
     * Comprueba que se envíe el código de autorización.
     */
    public function testTokenExchangeSendsAuthorizationCode(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with(
                $this->isType('string'),
                $this->callback(
                    fn (array $parameters): bool =>
                        ($parameters['code'] ?? null) ===
                        'facebook-authorization-code'
                )
            )
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]);

        $provider->exchangeCodeForToken(
            'facebook-authorization-code',
            self::REDIRECT_URI
        );
    }

    /**
     * Comprueba que se envíe el client_id.
     */
    public function testTokenExchangeSendsClientId(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with(
                $this->anything(),
                $this->callback(
                    fn (array $parameters): bool =>
                        ($parameters['client_id'] ?? null) ===
                        self::CLIENT_ID
                )
            )
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
            ]);

        $provider->exchangeCodeForToken(
            'facebook-code',
            self::REDIRECT_URI
        );
    }

    /**
     * Comprueba que se envíe el client_secret durante el intercambio.
     */
    public function testTokenExchangeSendsClientSecret(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with(
                $this->anything(),
                $this->callback(
                    fn (array $parameters): bool =>
                        ($parameters['client_secret'] ?? null) ===
                        self::CLIENT_SECRET
                )
            )
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
            ]);

        $provider->exchangeCodeForToken(
            'facebook-code',
            self::REDIRECT_URI
        );
    }

    /**
     * Comprueba que se envíe el redirect_uri durante el intercambio.
     */
    public function testTokenExchangeSendsRedirectUri(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with(
                $this->anything(),
                $this->callback(
                    fn (array $parameters): bool =>
                        ($parameters['redirect_uri'] ?? null) ===
                        self::REDIRECT_URI
                )
            )
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
            ]);

        $provider->exchangeCodeForToken(
            'facebook-code',
            self::REDIRECT_URI
        );
    }

    /**
     * Comprueba que se envíe el verifier PKCE cuando existe.
     */
    public function testTokenExchangeSupportsPkceVerifier(): void
    {
        $provider = $this->createProvider();

        $verifier = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNO';

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with(
                $this->anything(),
                $this->callback(
                    fn (array $parameters): bool =>
                        ($parameters['code_verifier'] ?? null) ===
                        $verifier
                )
            )
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
            ]);

        $provider->exchangeCodeForToken(
            'facebook-code',
            self::REDIRECT_URI,
            $verifier
        );
    }

    /**
     * Comprueba que se procese la respuesta completa de tokens.
     */
    public function testProcessesCompleteTokenResponse(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'access_token' => self::ACCESS_TOKEN,
                'token_type' => 'Bearer',
                'expires_in' => 7200,
            ]);

        $response = $provider->exchangeCodeForToken(
            'facebook-code',
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
    }

    /**
     * Comprueba que se pueda recuperar el perfil de Facebook.
     */
    public function testFetchesUserProfile(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertIsArray($profile);
        $this->assertNotEmpty($profile);
    }

    /**
     * El perfil debe contener el identificador del usuario.
     */
    public function testProfileContainsUserId(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertNotEmpty($profile['id'] ?? null);
    }

    /**
     * El perfil debe conservar el correo electrónico.
     */
    public function testProfileContainsEmail(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
                'email' => 'test@example.com',
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
     * El perfil debe conservar el nombre del usuario.
     */
    public function testProfileContainsName(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            'Test User',
            $profile['name'] ?? null
        );
    }

    /**
     * El perfil debe poder procesar el nombre y apellidos.
     */
    public function testProfileContainsFirstAndLastName(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            'Test',
            $profile['first_name'] ?? null
        );

        $this->assertSame(
            'User',
            $profile['last_name'] ?? null
        );
    }

    /**
     * Comprueba que se procese la imagen de perfil de Facebook.
     */
    public function testProfileContainsPicture(): void
    {
        $provider = $this->createProvider();

        $pictureUrl = 'https://example.com/facebook-avatar.jpg';

        $this->client
            ->method('getJson')
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
                'email' => 'test@example.com',
                'picture' => [
                    'data' => [
                        'url' => $pictureUrl,
                    ],
                ],
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            $pictureUrl,
            $profile['picture'] ?? null
        );
    }

    /**
     * Comprueba que el token se utilice para consultar el perfil.
     */
    public function testProfileRequestUsesAccessToken(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with(
                $this->anything(),
                $this->callback(
                    fn (array $parameters): bool =>
                        ($parameters['access_token'] ?? null) ===
                        self::ACCESS_TOKEN
                )
            )
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
            ]);

        $provider->fetchUserProfile(
            $this->createTokenResponse()
        );
    }

    /**
     * El perfil debe solicitar los campos necesarios.
     */
    public function testProfileRequestIncludesExpectedFields(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->expects($this->once())
            ->method('getJson')
            ->with(
                $this->anything(),
                $this->callback(
                    function (array $parameters): bool {
                        $fields = $parameters['fields'] ?? '';

                        return str_contains($fields, 'id')
                            && str_contains($fields, 'name')
                            && str_contains($fields, 'email');
                    }
                )
            )
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $provider->fetchUserProfile(
            $this->createTokenResponse()
        );
    }

    /**
     * Comprueba que el perfil funcione cuando Facebook no proporciona email.
     */
    public function testProfileSupportsMissingEmail(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertSame(
            '123456789',
            $profile['id'] ?? null
        );

        $this->assertNull(
            $profile['email'] ?? null
        );
    }

    /**
     * Comprueba que el perfil soporte una respuesta con foto ausente.
     */
    public function testProfileSupportsMissingPicture(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'id' => '123456789',
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $profile = $provider->fetchUserProfile(
            $this->createTokenResponse()
        );

        $this->assertNull(
            $profile['picture'] ?? null
        );
    }

    /**
     * Comprueba que los datos del perfil no estén vacíos.
     */
    public function testProfileDoesNotReturnEmptyUserId(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willReturn([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        try {
            $provider->fetchUserProfile(
                $this->createTokenResponse()
            );
        } catch (AuthenticationException) {
            $this->assertTrue(true);

            return;
        }

        $this->fail(
            'Se esperaba una excepción por falta del identificador de Facebook.'
        );
    }

    /**
     * Comprueba que un error de autenticación se pueda propagar
     * durante el intercambio del código.
     */
    public function testPropagatesAuthenticationExceptionDuringTokenExchange(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willThrowException(
                new AuthenticationException(
                    'Facebook token exchange failed.'
                )
            );

        $this->expectException(AuthenticationException::class);

        $provider->exchangeCodeForToken(
            'invalid-facebook-code',
            self::REDIRECT_URI
        );
    }

    /**
     * Comprueba que un error al consultar el perfil se propague.
     */
    public function testPropagatesAuthenticationExceptionDuringProfileFetch(): void
    {
        $provider = $this->createProvider();

        $this->client
            ->method('getJson')
            ->willThrowException(
                new AuthenticationException(
                    'Unable to retrieve Facebook profile.'
                )
            );

        $this->expectException(AuthenticationException::class);

        $provider->fetchUserProfile(
            $this->createTokenResponse()
        );
    }
}
