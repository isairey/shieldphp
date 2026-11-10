
<?php

declare(strict_types=1);

namespace ShieldPHP\Slim;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShieldPHP\Core\JWT\JWTManager;
use ShieldPHP\Core\JWT\TokenValidator;
use Throwable;

/**
 * QuickAuthMiddleware
 *
 * Middleware de autenticación para Slim Framework 4.
 *
 * Valida los tokens JWT recibidos mediante el encabezado
 * Authorization: Bearer <token>.
 */
final class QuickAuthMiddleware implements MiddlewareInterface
{
    /**
     * Atributo donde se almacenan las declaraciones JWT.
     */
    public const CLAIMS_ATTRIBUTE = 'shieldphp.claims';

    /**
     * Atributo donde se almacena el identificador del usuario.
     */
    public const USER_ID_ATTRIBUTE = 'shieldphp.user_id';

    /**
     * Nombre del encabezado de autorización.
     */
    private const AUTHORIZATION_HEADER = 'Authorization';

    /**
     * Constructor.
     */
    public function __construct(
        private readonly TokenValidator $tokenValidator,
        private readonly JWTManager $jwtManager,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly string $realm = 'ShieldPHP API',
    ) {
    }

    /**
     * Procesa una solicitud HTTP.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $token = $this->extractToken($request);

        // No se proporcionó un token válido en el encabezado.
        if ($token === null) {
            return $this->unauthorizedResponse();
        }

        try {
            // Validar firma, expiración y revocación del token.
            if (!$this->tokenValidator->isValid($token)) {
                return $this->unauthorizedResponse();
            }

            // Decodificar las declaraciones del JWT.
            $claims = $this->jwtManager->decode($token);

        } catch (Throwable) {
            // No exponer detalles internos de validación.
            return $this->unauthorizedResponse();
        }

        // Obtener el identificador del usuario.
        $subject = $claims->sub ?? null;

        // Adjuntar las declaraciones a la solicitud.
        $request = $request->withAttribute(
            self::CLAIMS_ATTRIBUTE,
            $claims
        );

        if (is_string($subject) || is_int($subject)) {
            $request = $request->withAttribute(
                self::USER_ID_ATTRIBUTE,
                (string) $subject
            );
        }

        // Continuar con el siguiente middleware o controlador.
        return $handler->handle($request);
    }

    /**
     * Extrae el token Bearer del encabezado Authorization.
     */
    private function extractToken(
        ServerRequestInterface $request
    ): ?string {
        $authorization = trim(
            $request->getHeaderLine(self::AUTHORIZATION_HEADER)
        );

        if ($authorization === '') {
            return null;
        }

        if (
            preg_match(
                '/^Bearer\s+([A-Za-z0-9\-._~+\/]+=*)$/iD',
                $authorization,
                $matches
            ) !== 1
        ) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Construye una respuesta HTTP 401.
     *
     * No revela si el token expiró, fue revocado,
     * tiene una firma incorrecta o es desconocido.
     */
    private function unauthorizedResponse(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(401);

        $body = json_encode([
            'success' => false,
            'error' => 'unauthorized',
            'message' => 'Se requiere un token de acceso válido.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response->getBody()->write(
            $body !== false ? $body : '{"error":"unauthorized"}'
        );

        $realm = addcslashes($this->realm, '"\\');

        return $response
            ->withHeader(
                'Content-Type',
                'application/json; charset=utf-8'
            )
            ->withHeader(
                'Cache-Control',
                'no-store'
            )
            ->withHeader(
                'WWW-Authenticate',
                sprintf('Bearer realm="%s", error="invalid_token"', $realm)
            );
    }
}
