
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\OAuth;

use PHPUnit\Framework\TestCase;
use ShieldPHP\Core\OAuth\AuthorizationUrlBuilder;

final class AuthorizationUrlBuilderTest extends TestCase
{
    private const AUTHORIZATION_ENDPOINT =
        'https://accounts.example.com/oauth/authorize';

    private const CLIENT_ID = 'shieldphp-client-123';

    private const REDIRECT_URI =
        'https://app.example.com/oauth/callback';

    private const STATE = 'random-secure-state-123456789';

    private const CODE_CHALLENGE =
        'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

    private AuthorizationUrlBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->createBuilder();
    }

    /**
     * Crea un AuthorizationUrlBuilder para las pruebas.
     *
     * @param list<string> $scopes
     * @param array<string, mixed> $additionalParameters
     */
    private function createBuilder(
        array $scopes = ['openid', 'email', 'profile'],
        array $additionalParameters = []
    ): AuthorizationUrlBuilder {
        return new AuthorizationUrlBuilder(
            self::AUTHORIZATION_ENDPOINT,
            self::CLIENT_ID,
            $scopes,
            $additionalParameters
        );
    }

    /**
     * Extrae los parámetros de consulta de una URL.
     *
     * @return array<string, mixed>
     */
    private function getQueryParameters(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);

        $this->assertIsString($query);

        parse_str($query, $parameters);

        return $parameters;
    }

    /**
     * Construye una URL de autorización con los valores habituales.
     */
    private function buildAuthorizationUrl(
        ?string $challenge = null,
        ?string $state = null,
        ?string $redirectUri = null
    ): string {
        return $this->builder->build(
            $redirectUri ?? self::REDIRECT_URI,
            $state ?? self::STATE,
            $challenge
        );
    }

    /**
     * Comprueba que se pueda crear AuthorizationUrlBuilder.
     */
    public function testCreatesAuthorizationUrlBuilder(): void
    {
        $builder = $this->createBuilder();

        $this->assertInstanceOf(
            AuthorizationUrlBuilder::class,
            $builder
        );
    }

    /**
     * Comprueba que build() devuelva una cadena.
     */
    public function testBuildReturnsString(): void
    {
        $url = $this->buildAuthorizationUrl();

        $this->assertIsString($url);
        $this->assertNotEmpty($url);
    }

    /**
     * Comprueba que la URL utilice el endpoint configurado.
     */
    public function testBuildUsesConfiguredAuthorizationEndpoint(): void
    {
        $url = $this->buildAuthorizationUrl();

        $this->assertStringStartsWith(
            self::AUTHORIZATION_ENDPOINT,
            $url
        );
    }

    /**
     * Comprueba que la URL conserve el esquema HTTPS.
     */
    public function testBuildUsesHttpsEndpoint(): void
    {
        $url = $this->buildAuthorizationUrl();

        $this->assertSame(
            'https',
            parse_url($url, PHP_URL_SCHEME)
        );
    }

    /**
     * Comprueba que la URL conserve el host del proveedor.
     */
    public function testBuildPreservesAuthorizationHost(): void
    {
        $url = $this->buildAuthorizationUrl();

        $this->assertSame(
            'accounts.example.com',
            parse_url($url, PHP_URL_HOST)
        );
    }

    /**
     * Comprueba que se incluya response_type=code.
     */
    public function testIncludesAuthorizationCodeResponseType(): void
    {
        $url = $this->buildAuthorizationUrl();

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            'code',
            $parameters['response_type']
        );
    }

    /**
     * Comprueba que se incluya el client_id configurado.
     */
    public function testIncludesClientId(): void
    {
        $url = $this->buildAuthorizationUrl();

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            self::CLIENT_ID,
            $parameters['client_id']
        );
    }

    /**
     * Comprueba que se incluya la URI de redirección.
     */
    public function testIncludesRedirectUri(): void
    {
        $url = $this->buildAuthorizationUrl();

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            self::REDIRECT_URI,
            $parameters['redirect_uri']
        );
    }

    /**
     * Comprueba que se incluya el parámetro state.
     */
    public function testIncludesState(): void
    {
        $url = $this->buildAuthorizationUrl();

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            self::STATE,
            $parameters['state']
        );
    }

    /**
     * Comprueba que se conserve un state personalizado.
     */
    public function testSupportsCustomState(): void
    {
        $customState = 'custom-state-987654321';

        $url = $this->buildAuthorizationUrl(
            state: $customState
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            $customState,
            $parameters['state']
        );
    }

    /**
     * Comprueba que la URI de redirección se codifique
     * y se recupere correctamente.
     */
    public function testEncodesRedirectUriCorrectly(): void
    {
        $redirectUri =
            'https://app.example.com/oauth/callback?source=web&lang=es';

        $url = $this->buildAuthorizationUrl(
            redirectUri: $redirectUri
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            $redirectUri,
            $parameters['redirect_uri']
        );
    }

    /**
     * Comprueba que se incluyan los scopes configurados.
     */
    public function testIncludesConfiguredScopes(): void
    {
        $url = $this->buildAuthorizationUrl();

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            'openid email profile',
            $parameters['scope']
        );
    }

    /**
     * Comprueba que los scopes se construyan
     * como una cadena separada por espacios.
     */
    public function testScopesAreSeparatedBySpaces(): void
    {
        $builder = $this->createBuilder([
            'users.read',
            'users.write',
            'email',
        ]);

        $url = $builder->build(
            self::REDIRECT_URI,
            self::STATE
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            'users.read users.write email',
            $parameters['scope']
        );
    }

    /**
     * Comprueba que se pueda construir una autorización
     * con un scope personalizado.
     */
    public function testSupportsCustomScopes(): void
    {
        $builder = $this->createBuilder([
            'openid',
            'email',
        ]);

        $url = $builder->build(
            self::REDIRECT_URI,
            self::STATE
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            'openid email',
            $parameters['scope']
        );
    }

    /**
     * Comprueba que se incluya el code_challenge
     * cuando se proporciona.
     */
    public function testIncludesPkceCodeChallenge(): void
    {
        $url = $this->buildAuthorizationUrl(
            challenge: self::CODE_CHALLENGE
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            self::CODE_CHALLENGE,
            $parameters['code_challenge']
        );
    }

    /**
     * Comprueba que se establezca el método S256
     * cuando se utiliza PKCE.
     */
    public function testUsesS256PkceMethod(): void
    {
        $url = $this->buildAuthorizationUrl(
            challenge: self::CODE_CHALLENGE
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            'S256',
            $parameters['code_challenge_method']
        );
    }

    /**
     * Comprueba que los parámetros PKCE se omitan
     * cuando no se proporciona code_challenge.
     */
    public function testOmitsPkceParametersWithoutChallenge(): void
    {
        $url = $this->buildAuthorizationUrl();

        $parameters = $this->getQueryParameters($url);

        $this->assertArrayNotHasKey(
            'code_challenge',
            $parameters
        );

        $this->assertArrayNotHasKey(
            'code_challenge_method',
            $parameters
        );
    }

    /**
     * Comprueba que se incluyan parámetros adicionales.
     */
    public function testIncludesAdditionalParameters(): void
    {
        $builder = $this->createBuilder(
            additionalParameters: [
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]
        );

        $url = $builder->build(
            self::REDIRECT_URI,
            self::STATE
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            'offline',
            $parameters['access_type']
        );

        $this->assertSame(
            'consent',
            $parameters['prompt']
        );
    }

    /**
     * Comprueba que los valores de los parámetros adicionales
     * se conserven correctamente.
     */
    public function testPreservesAdditionalParameterValues(): void
    {
        $builder = $this->createBuilder(
            additionalParameters: [
                'prompt' => 'select_account',
                'login_hint' => 'developer@example.com',
                'include_granted_scopes' => 'true',
            ]
        );

        $url = $builder->build(
            self::REDIRECT_URI,
            self::STATE
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            'select_account',
            $parameters['prompt']
        );

        $this->assertSame(
            'developer@example.com',
            $parameters['login_hint']
        );

        $this->assertSame(
            'true',
            $parameters['include_granted_scopes']
        );
    }

    /**
     * Comprueba que los parámetros adicionales
     * no desaparezcan al utilizar PKCE.
     */
    public function testPreservesAdditionalParametersWithPkce(): void
    {
        $builder = $this->createBuilder(
            additionalParameters: [
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]
        );

        $url = $builder->build(
            self::REDIRECT_URI,
            self::STATE,
            self::CODE_CHALLENGE
        );

        $parameters = $this->getQueryParameters($url);

        $this->assertSame(
            self::CODE_CHALLENGE,
            $parameters['code_challenge']
        );

        $this->assertSame(
            'S256',
            $parameters['code_challenge_method']
        );

        $this->assertSame(
            'offline',
            $parameters['access_type']
        );

        $this->assertSame(
            'consent',
            $parameters['prompt']
        );
    }

    /**
     * Comprueba que diferentes estados produzcan
     * URLs de autorización diferentes.
     */
    public function testDifferentStatesProduceDifferentUrls(): void
    {
        $firstUrl = $this->buildAuthorizationUrl(
            state: 'state-first-123'
        );

        $secondUrl = $this->buildAuthorizationUrl(
            state: 'state-second-456'
        );

        $this->assertNotSame(
            $firstUrl,
            $secondUrl
        );

        $firstParameters = $this->getQueryParameters($firstUrl);
        $secondParameters = $this->getQueryParameters($secondUrl);

        $this->assertSame(
            'state-first-123',
            $firstParameters['state']
        );

        $this->assertSame(
            'state-second-456',
            $secondParameters['state']
        );
    }

    /**
     * Comprueba que diferentes code_challenge produzcan
     * diferentes URLs.
     */
    public function testDifferentChallengesProduceDifferentUrls(): void
    {
        $firstUrl = $this->buildAuthorizationUrl(
            challenge: 'first-challenge-value'
        );

        $secondUrl = $this->buildAuthorizationUrl(
            challenge: 'second-challenge-value'
        );

        $this->assertNotSame(
            $firstUrl,
            $secondUrl
        );
    }

    /**
     * Comprueba que generar varias URLs no modifique
     * la configuración original del builder.
     */
    public function testMultipleBuildCallsRemainIndependent(): void
    {
        $builder = $this->createBuilder([
            'openid',
            'email',
        ]);

        $firstUrl = $builder->build(
            self::REDIRECT_URI,
            'state-first'
        );

        $secondUrl = $builder->build(
            self::REDIRECT_URI,
            'state-second'
        );

        $firstParameters = $this->getQueryParameters($firstUrl);
        $secondParameters = $this->getQueryParameters($secondUrl);

        $this->assertSame(
            'state-first',
            $firstParameters['state']
        );

        $this->assertSame(
            'state-second',
            $secondParameters['state']
        );

        $this->assertSame(
            'openid email',
            $firstParameters['scope']
        );

        $this->assertSame(
            'openid email',
            $secondParameters['scope']
        );
    }

    /**
     * Comprueba que una URL de autorización pueda
     * convertirse a una cadena y analizarse nuevamente.
     */
    public function testAuthorizationUrlCanBeParsed(): void
    {
        $url = $this->buildAuthorizationUrl(
            challenge: self::CODE_CHALLENGE
        );

        $parsedUrl = parse_url($url);

        $this->assertIsArray($parsedUrl);

        $this->assertSame(
            'https',
            $parsedUrl['scheme']
        );

        $this->assertSame(
            'accounts.example.com',
            $parsedUrl['host']
        );

        $this->assertArrayHasKey(
            'query',
            $parsedUrl
        );
    }

    /**
     * Comprueba que los parámetros obligatorios
     * estén presentes simultáneamente.
     */
    public function testIncludesAllRequiredOAuthParameters(): void
    {
        $url = $this->buildAuthorizationUrl();

        $parameters = $this->getQueryParameters($url);

        $requiredParameters = [
            'response_type',
            'client_id',
            'redirect_uri',
            'scope',
            'state',
        ];

        foreach ($requiredParameters as $parameter) {
            $this->assertArrayHasKey(
                $parameter,
                $parameters,
                sprintf(
                    'Missing OAuth parameter: %s',
                    $parameter
                )
            );
        }
    }
}
