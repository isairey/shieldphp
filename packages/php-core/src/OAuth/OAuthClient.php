
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\OAuth;

use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ShieldPHP\Core\Exceptions\AuthenticationException;
use ShieldPHP\Core\Exceptions\ConfigurationException;
use InvalidArgumentException;
use Throwable;

final class OAuthClient
{
    private const DEFAULT_MAX_RESPONSE_BYTES = 1_048_576;
    private const MAX_REQUEST_BODY_BYTES = 1_048_576;

    /**
     * Hosts autorizados para las solicitudes HTTP.
     *
     * @var list<string>
     */
    private readonly array $allowedHosts;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        array $allowedHosts,
        private readonly int $maxResponseBytes =
            self::DEFAULT_MAX_RESPONSE_BYTES
    ) {
        if ($allowedHosts === [] || !array_is_list($allowedHosts)) {
            throw new InvalidArgumentException(
                'Debes proporcionar una lista de hosts OAuth permitidos.'
            );
        }

        if (
            $this->maxResponseBytes < 1
            || $this->maxResponseBytes > self::MAX_REQUEST_BODY_BYTES
        ) {
            throw new InvalidArgumentException(
                'maxResponseBytes debe estar entre 1 byte y 1 MiB.'
            );
        }

        $normalizedHosts = [];

        foreach ($allowedHosts as $host) {
            if (!is_string($host)) {
                throw new InvalidArgumentException(
                    'Cada host permitido debe ser una cadena.'
                );
            }

            $host = strtolower(rtrim(trim($host), '.'));

            if (
                preg_match(
                    '/\A(?=.{1,253}\z)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*\z/',
                    $host
                ) !== 1
            ) {
                throw new InvalidArgumentException(
                    'La lista contiene un nombre de host no válido.'
                );
            }

            $normalizedHosts[] = $host;
        }

        $this->allowedHosts = array_values(
            array_unique($normalizedHosts)
        );
    }

    /**
     * Realiza una solicitud GET y devuelve una respuesta JSON.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function getJson(
        string $url,
        array $headers = []
    ): array {
        return $this->requestJson(
            method: 'GET',
            url: $url,
            headers: $headers
        );
    }

    /**
     * Realiza una solicitud POST con parámetros de formulario.
     *
     * Es el formato habitual para intercambiar un código OAuth
     * por un conjunto de tokens.
     *
     * @param array<string, string|int> $formParameters
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function postForm(
        string $url,
        array $formParameters,
        array $headers = []
    ): array {
        $normalizedParameters = [];

        foreach ($formParameters as $name => $value) {
            if (
                !is_string($name)
                || preg_match('/\A[A-Za-z0-9._~-]{1,128}\z/', $name) !== 1
            ) {
                throw new InvalidArgumentException(
                    'El nombre de un parámetro de formulario no es válido.'
                );
            }

            if (!is_string($value) && !is_int($value)) {
                throw new InvalidArgumentException(
                    'Los parámetros del formulario deben ser cadenas o enteros.'
                );
            }

            $normalizedParameters[$name] = (string) $value;
        }

        $body = http_build_query(
            $normalizedParameters,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        if (strlen($body) > self::MAX_REQUEST_BODY_BYTES) {
            throw new InvalidArgumentException(
                'El cuerpo de la solicitud supera el tamaño permitido.'
            );
        }

        return $this->requestJson(
            method: 'POST',
            url: $url,
            headers: $headers,
            body: $body,
            contentType: 'application/x-www-form-urlencoded'
        );
    }

    /**
     * Envía una solicitud HTTP y procesa una respuesta JSON.
     *
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function requestJson(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null,
        ?string $contentType = null
    ): array {
        $this->validateEndpoint($url);
        $this->validateHeaders($headers);

        try {
            $request = $this->requestFactory->createRequest(
                $method,
                $url
            )->withHeader('Accept', 'application/json');

            foreach ($headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            if ($body !== null) {
                $request = $request
                    ->withHeader('Content-Type', $contentType)
                    ->withBody(
                        $this->streamFactory->createStream($body)
                    );
            }

            $response = $this->httpClient->sendRequest($request);
        } catch (Throwable $exception) {
            throw new AuthenticationException(
                'No fue posible comunicarse con el proveedor OAuth.',
                0,
                $exception
            );
        }

        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new AuthenticationException(
                sprintf(
                    'El endpoint OAuth respondió con HTTP %d.',
                    $status
                )
            );
        }

        $this->validateJsonContentType($response);

        $responseBody = $this->readLimitedBody($response);

        if ($responseBody === '') {
            throw new AuthenticationException(
                'El proveedor OAuth devolvió una respuesta vacía.'
            );
        }

        try {
            // Exigir un objeto JSON como respuesta principal.
            $decodedObject = json_decode(
                $responseBody,
                false,
                512,
                JSON_THROW_ON_ERROR
            );

            if (!$decodedObject instanceof \stdClass) {
                throw new AuthenticationException(
                    'La respuesta OAuth no contiene un objeto JSON.'
                );
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode(
                $responseBody,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return $decoded;
        } catch (JsonException $exception) {
            throw new AuthenticationException(
                'El proveedor OAuth devolvió un JSON no válido.',
                0,
                $exception
            );
        }
    }

    /**
     * Verifica el endpoint antes de enviar credenciales o tokens.
     */
    private function validateEndpoint(string $url): void
    {
        if ($url === '' || strlen($url) > 4096) {
            throw new ConfigurationException(
                'El endpoint OAuth no es válido.'
            );
        }

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
                'El endpoint OAuth debe ser HTTPS, sin credenciales '
                . 'ni fragmentos.'
            );
        }

        if (
            isset($parts['port'])
            && $parts['port'] !== 443
        ) {
            throw new ConfigurationException(
                'El endpoint OAuth solo puede utilizar el puerto HTTPS 443.'
            );
        }

        $host = strtolower(rtrim($parts['host'], '.'));

        if (!in_array($host, $this->allowedHosts, true)) {
            throw new ConfigurationException(
                sprintf(
                    'El host "%s" no está autorizado para solicitudes OAuth.',
                    $host
                )
            );
        }
    }

    /**
     * Comprueba los nombres y valores de los encabezados HTTP.
     *
     * @param array<string, string> $headers
     */
    private function validateHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            if (
                !is_string($name)
                || preg_match('/\A[A-Za-z0-9-]+\z/', $name) !== 1
            ) {
                throw new InvalidArgumentException(
                    'El nombre de un encabezado HTTP no es válido.'
                );
            }

            if (
                !is_string($value)
                || strlen($value) > 8192
                || preg_match('/[\r\n\0]/', $value) === 1
            ) {
                throw new InvalidArgumentException(
                    sprintf('El encabezado "%s" no es válido.', $name)
                );
            }
        }
    }

    /**
     * Rechaza respuestas que declaren un tipo de contenido no JSON.
     */
    private function validateJsonContentType(
        ResponseInterface $response
    ): void {
        $contentType = strtolower(
            trim(
                explode(
                    ';',
                    $response->getHeaderLine('Content-Type')
                )[0]
            )
        );

        // Algunos proveedores no incluyen Content-Type.
        // En ese caso, el cuerpo todavía debe pasar la validación JSON.
        if ($contentType === '') {
            return;
        }

        if (
            $contentType !== 'application/json'
            && preg_match(
                '/\Aapplication\/[a-z0-9._+-]+\+json\z/',
                $contentType
            ) !== 1
        ) {
            throw new AuthenticationException(
                'El proveedor OAuth no devolvió contenido JSON.'
            );
        }
    }

    /**
     * Lee el cuerpo sin superar el límite de tamaño configurado.
     */
    private function readLimitedBody(
        ResponseInterface $response
    ): string {
        $stream = $response->getBody();
        $size = $stream->getSize();

        if (
            $size !== null
            && $size > $this->maxResponseBytes
        ) {
            throw new AuthenticationException(
                'La respuesta OAuth supera el tamaño permitido.'
            );
        }

        $body = '';

        while (!$stream->eof()) {
            $remaining = $this->maxResponseBytes - strlen($body);

            // Leer un byte adicional permite detectar si se superó el límite.
            $chunk = $stream->read(min(8192, $remaining + 1));

            if ($chunk === '' && !$stream->eof()) {
                throw new AuthenticationException(
                    'No fue posible leer completamente la respuesta OAuth.'
                );
            }

            $body .= $chunk;

            if (strlen($body) > $this->maxResponseBytes) {
                throw new AuthenticationException(
                    'La respuesta OAuth supera el tamaño permitido.'
                );
            }
        }

        return $body;
    }
}
