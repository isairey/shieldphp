
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Tests\Unit\OAuth;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ShieldPHP\Core\OAuth\OAuthClient;
use Throwable;

final class OAuthClientTest extends TestCase
{
    private const ALLOWED_HOST = 'oauth.example.com';

    private const BASE_URL = 'https://oauth.example.com';

    private HttpFactory $httpFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpFactory = new HttpFactory();
    }

    /**
     * Crea un OAuthClient con un cliente HTTP simulado.
     *
     * @param list<string> $allowedHosts
     */
    private function createOAuthClient(
        ClientInterface $httpClient,
        array $allowedHosts = [self::ALLOWED_HOST]
    ): OAuthClient {
        return new OAuthClient(
            $httpClient,
            $this->httpFactory,
            $this->httpFactory,
            $allowedHosts
        );
    }

    /**
     * Genera una respuesta HTTP JSON.
     *
     * @param array<string, mixed> $data
     */
    private function createJsonResponse(
        array $data,
        int $statusCode = 200
    ): Response {
        return new Response(
            $statusCode,
            [
                'Content-Type' => 'application/json',
            ],
            json_encode($data, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Comprueba que se pueda crear OAuthClient.
     */
    public function testCreatesOAuthClient(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $client = $this->createOAuthClient($httpClient);

        $this->assertInstanceOf(
            OAuthClient::class,
            $client
        );
    }

    /**
     * Comprueba que getJson() devuelva un array.
     */
    public function testGetJsonReturnsArray(): void
    {
        $expectedData = [
            'id' => 'user-123',
            'name' => 'ShieldPHP',
        ];

        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                $this->createJsonResponse($expectedData)
            );

        $client = $this->createOAuthClient($httpClient);

        $result = $client->getJson(
            self::BASE_URL . '/userinfo'
        );

        $this->assertIsArray($result);
        $this->assertSame($expectedData, $result);
    }

    /**
     * Comprueba que getJson() utilice el método HTTP GET.
     */
    public function testGetJsonUsesGetMethod(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(
                    function (RequestInterface $request): bool {
                        $this->assertSame(
                            'GET',
                            $request->getMethod()
                        );

                        return true;
                    }
                )
            )
            ->willReturn(
                $this->createJsonResponse(['success' => true])
            );

        $client = $this->createOAuthClient($httpClient);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que getJson() conserve la URL solicitada.
     */
    public function testGetJsonPreservesRequestUrl(): void
    {
        $url = self::BASE_URL . '/userinfo?fields=id,name';

        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(
                    function (RequestInterface $request) use ($url): bool {
                        $this->assertSame(
                            $url,
                            (string) $request->getUri()
                        );

                        return true;
                    }
                )
            )
            ->willReturn(
                $this->createJsonResponse(['id' => '123'])
            );

        $client = $this->createOAuthClient($httpClient);

        $client->getJson($url);
    }

    /**
     * Comprueba que getJson() permita enviar
     * encabezados personalizados.
     */
    public function testGetJsonSupportsCustomHeaders(): void
    {
        $headers = [
            'Authorization' => 'Bearer test-access-token',
            'X-Client-Version' => '1.0.0',
        ];

        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(
                    function (RequestInterface $request) use ($headers): bool {
                        foreach ($headers as $name => $value) {
                            $this->assertSame(
                                $value,
                                $request->getHeaderLine($name)
                            );
                        }

                        return true;
                    }
                )
            )
            ->willReturn(
                $this->createJsonResponse(['success' => true])
            );

        $client = $this->createOAuthClient($httpClient);

        $client->getJson(
            self::BASE_URL . '/userinfo',
            $headers
        );
    }

    /**
     * Comprueba que postForm() devuelva un array.
     */
    public function testPostFormReturnsArray(): void
    {
        $expectedData = [
            'access_token' => 'access-123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];

        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                $this->createJsonResponse($expectedData)
            );

        $client = $this->createOAuthClient($httpClient);

        $result = $client->postForm(
            self::BASE_URL . '/token',
            [
                'grant_type' => 'authorization_code',
                'code' => 'authorization-code-123',
            ]
        );

        $this->assertIsArray($result);
        $this->assertSame($expectedData, $result);
    }

    /**
     * Comprueba que postForm() utilice el método HTTP POST.
     */
    public function testPostFormUsesPostMethod(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(
                    function (RequestInterface $request): bool {
                        $this->assertSame(
                            'POST',
                            $request->getMethod()
                        );

                        return true;
                    }
                )
            )
            ->willReturn(
                $this->createJsonResponse(['success' => true])
            );

        $client = $this->createOAuthClient($httpClient);

        $client->postForm(
            self::BASE_URL . '/token',
            ['grant_type' => 'authorization_code']
        );
    }

    /**
     * Comprueba que postForm() codifique
     * los datos como application/x-www-form-urlencoded.
     */
    public function testPostFormUsesFormUrlEncodedContentType(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(
                    function (RequestInterface $request): bool {
                        $this->assertStringContainsString(
                            'application/x-www-form-urlencoded',
                            $request->getHeaderLine('Content-Type')
                        );

                        return true;
                    }
                )
            )
            ->willReturn(
                $this->createJsonResponse(['success' => true])
            );

        $client = $this->createOAuthClient($httpClient);

        $client->postForm(
            self::BASE_URL . '/token',
            ['grant_type' => 'client_credentials']
        );
    }

    /**
     * Comprueba que postForm() transmita correctamente
     * los parámetros del formulario.
     */
    public function testPostFormPreservesFormParameters(): void
    {
        $formData = [
            'grant_type' => 'authorization_code',
            'code' => 'authorization-code-123',
            'redirect_uri' => 'https://app.example.com/callback',
            'client_id' => 'shieldphp-client',
        ];

        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(
                    function (RequestInterface $request) use ($formData): bool {
                        parse_str(
                            (string) $request->getBody(),
                            $receivedData
                        );

                        $this->assertSame(
                            $formData,
                            $receivedData
                        );

                        return true;
                    }
                )
            )
            ->willReturn(
                $this->createJsonResponse(['success' => true])
            );

        $client = $this->createOAuthClient($httpClient);

        $client->postForm(
            self::BASE_URL . '/token',
            $formData
        );
    }

    /**
     * Comprueba que se transmitan correctamente
     * valores que contienen caracteres especiales.
     */
    public function testPostFormEncodesSpecialCharacters(): void
    {
        $formData = [
            'code' => 'abc+123/test=456',
            'redirect_uri' => 'https://app.example.com/callback?source=web',
        ];

        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(
                    function (RequestInterface $request) use ($formData): bool {
                        parse_str(
                            (string) $request->getBody(),
                            $receivedData
                        );

                        $this->assertSame(
                            $formData,
                            $receivedData
                        );

                        return true;
                    }
                )
            )
            ->willReturn(
                $this->createJsonResponse(['success' => true])
            );

        $client = $this->createOAuthClient($httpClient);

        $client->postForm(
            self::BASE_URL . '/token',
            $formData
        );
    }

    /**
     * Comprueba que postForm() permita encabezados
     * personalizados.
     */
    public function testPostFormSupportsCustomHeaders(): void
    {
        $headers = [
            'Authorization' => 'Basic test-credentials',
            'X-Request-ID' => 'request-123',
        ];

        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(
                    function (RequestInterface $request) use ($headers): bool {
                        foreach ($headers as $name => $value) {
                            $this->assertSame(
                                $value,
                                $request->getHeaderLine($name)
                            );
                        }

                        return true;
                    }
                )
            )
            ->willReturn(
                $this->createJsonResponse(['success' => true])
            );

        $client = $this->createOAuthClient($httpClient);

        $client->postForm(
            self::BASE_URL . '/token',
            ['grant_type' => 'client_credentials'],
            $headers
        );
    }

    /**
     * Comprueba que se acepte un host registrado.
     */
    public function testAcceptsAllowedHost(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                $this->createJsonResponse(['success' => true])
            );

        $client = $this->createOAuthClient($httpClient);

        $result = $client->getJson(
            self::BASE_URL . '/userinfo'
        );

        $this->assertTrue($result['success']);
    }

    /**
     * Comprueba que se rechace un host no autorizado
     * antes de enviar la solicitud HTTP.
     */
    public function testRejectsUnauthorizedHost(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->never())
            ->method('sendRequest');

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            'https://attacker.example.com/userinfo'
        );
    }

    /**
     * Comprueba que no se acepten dominios que solamente
     * contengan el nombre de un host autorizado como sufijo.
     */
    public function testRejectsHostWithAllowedHostAsSuffix(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->never())
            ->method('sendRequest');

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            'https://oauth.example.com.attacker.test/userinfo'
        );
    }

    /**
     * Comprueba que no se acepten subdominios
     * que no hayan sido registrados explícitamente.
     */
    public function testRejectsUnregisteredSubdomain(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->never())
            ->method('sendRequest');

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            'https://sub.oauth.example.com/userinfo'
        );
    }

    /**
     * Comprueba que se rechacen conexiones HTTP sin TLS.
     */
    public function testRejectsInsecureHttpUrl(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->never())
            ->method('sendRequest');

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            'http://oauth.example.com/userinfo'
        );
    }

    /**
     * Comprueba que se rechacen URLs malformadas.
     */
    public function testRejectsMalformedUrl(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->never())
            ->method('sendRequest');

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson('not-a-valid-url');
    }

    /**
     * Comprueba que se rechacen direcciones URL sin host.
     */
    public function testRejectsUrlWithoutHost(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->never())
            ->method('sendRequest');

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson('https:///userinfo');
    }

    /**
     * Comprueba que se rechacen respuestas HTTP 400.
     */
    public function testRejectsBadRequestResponse(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                $this->createJsonResponse(
                    ['error' => 'invalid_request'],
                    400
                )
            );

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que se rechacen respuestas HTTP 401.
     */
    public function testRejectsUnauthorizedResponse(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                $this->createJsonResponse(
                    ['error' => 'invalid_token'],
                    401
                )
            );

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que se rechacen respuestas HTTP 500.
     */
    public function testRejectsServerErrorResponse(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                $this->createJsonResponse(
                    ['error' => 'server_error'],
                    500
                )
            );

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que las respuestas de redirección
     * no se interpreten como respuestas JSON exitosas.
     */
    public function testRejectsRedirectResponse(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $response = new Response(
            302,
            [
                'Location' => 'https://attacker.example.com',
            ],
            ''
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que se rechace una respuesta
     * cuyo contenido no sea JSON válido.
     */
    public function testRejectsInvalidJsonResponse(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{invalid-json}'
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que se rechace una respuesta
     * con un array JSON vacío no válido para el proveedor.
     */
    public function testRejectsEmptyResponseBody(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            ''
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que se rechace una respuesta
     * cuyo tamaño supera el límite configurado
     * por el cliente HTTP.
     */
    public function testRejectsOversizedResponse(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $largeResponse = [
            'data' => str_repeat('A', 2 * 1024 * 1024),
        ];

        $response = $this->createJsonResponse($largeResponse);

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que los errores de transporte HTTP
     * se propaguen o se conviertan en excepciones.
     */
    public function testHandlesHttpTransportFailure(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new \RuntimeException(
                    'Connection timeout.'
                )
            );

        $client = $this->createOAuthClient($httpClient);

        $this->expectException(Throwable::class);

        $client->getJson(
            self::BASE_URL . '/userinfo'
        );
    }

    /**
     * Comprueba que getJson() y postForm()
     * funcionen de manera independiente.
     */
    public function testGetJsonAndPostFormRemainIndependent(): void
    {
        $httpClient = $this->createMock(
            ClientInterface::class
        );

        $httpClient
            ->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                $this->createJsonResponse([
                    'id' => 'user-123',
                ]),
                $this->createJsonResponse([
                    'access_token' => 'access-123',
                    'token_type' => 'Bearer',
                ])
            );

        $client = $this->createOAuthClient($httpClient);

        $userInfo = $client->getJson(
            self::BASE_URL . '/userinfo'
        );

        $tokenResponse = $client->postForm(
            self::BASE_URL . '/token',
            [
                'grant_type' => 'authorization_code',
                'code' => 'test-code',
            ]
        );

        $this->assertSame('user-123', $userInfo['id']);

        $this->assertSame(
            'access-123',
            $tokenResponse['access_token']
        );

        $this->assertSame(
            'Bearer',
            $tokenResponse['token_type']
        );
    }
}
