
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * Atributo establecido por el middleware de sesión.
     *
     * Debe contener el token CSRF obtenido del almacenamiento
     * confiable de la sesión actual.
     */
    public const TOKEN_ATTRIBUTE = 'shieldphp.csrf_token';

    /**
     * Encabezado que debe enviar el cliente.
     */
    private const TOKEN_HEADER = 'X-CSRF-Token';

    /**
     * Métodos que no modifican el estado del servidor.
     *
     * Las peticiones TRACE no se consideran seguras aquí.
     *
     * @var list<string>
     */
    private const SAFE_METHODS = [
        'GET',
        'HEAD',
        'OPTIONS',
    ];

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * Comprueba el token CSRF antes de continuar la petición.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $method = strtoupper($request->getMethod());

        // Las peticiones de lectura no requieren token CSRF.
        if (in_array($method, self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        /*
         * Este valor debe haber sido colocado por un middleware
         * de sesión que obtiene el token del almacenamiento del servidor.
         * Nunca debe confiarse en un atributo enviado por el cliente.
         */
        $expectedToken = $request->getAttribute(
            self::TOKEN_ATTRIBUTE
        );

        // Leer el token enviado por el cliente en el encabezado.
        $providedToken = trim(
            $request->getHeaderLine(self::TOKEN_HEADER)
        );

        if (
            !is_string($expectedToken)
            || $expectedToken === ''
            || $providedToken === ''
            || !hash_equals($expectedToken, $providedToken)
        ) {
            return $this->forbidden();
        }

        return $handler->handle($request);
    }

    /**
     * Devuelve una respuesta genérica cuando falla la validación CSRF.
     */
    private function forbidden(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(403);

        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Pragma', 'no-cache');

        $response->getBody()->write(
            json_encode(
                [
                    'error' => 'csrf_failed',
                    'message' => 'La validación de seguridad de la solicitud falló.',
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $response;
    }
}
