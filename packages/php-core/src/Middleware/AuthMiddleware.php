
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShieldPHP\Core\Auth\UserIdentity;
use ShieldPHP\Core\Contracts\UserProviderInterface;
use ShieldPHP\Core\Exceptions\InvalidTokenException;
use ShieldPHP\Core\JWT\TokenValidator;
use Throwable;

final class AuthMiddleware implements MiddlewareInterface
{
    private const USER_ATTRIBUTE = 'shieldphp.user';
    private const CLAIMS_ATTRIBUTE = 'shieldphp.jwt_claims';
    private const MAX_TOKEN_LENGTH = 16384;

    public function __construct(
        private readonly TokenValidator $tokenValidator,
        private readonly UserProviderInterface $userProvider,
        private readonly ResponseFactoryInterface $responseFactory
    ) {
    }

    /**
     * Valida el JWT y autentica al usuario de la petición.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authorization = trim(
            $request->getHeaderLine('Authorization')
        );

        // Exigir el esquema Bearer.
        if (
            preg_match(
                '/\ABearer\s+([^\s,]+)\z/i',
                $authorization,
                $matches
            ) !== 1
        ) {
            return $this->unauthorized();
        }

        $token = $matches[1];

        if (strlen($token) > self::MAX_TOKEN_LENGTH) {
            return $this->unauthorized();
        }

        try {
            // Verificar firma, expiración y claims del JWT.
            $claims = $this->tokenValidator->validate($token);
        } catch (InvalidTokenException) {
            return $this->unauthorized();
        }

        // El subject identifica al usuario.
        if (
            !isset($claims->sub)
            || !is_string($claims->sub)
            || trim($claims->sub) === ''
        ) {
            return $this->unauthorized();
        }

        // Recuperar la identidad desde el proveedor configurado.
        $user = $this->userProvider->findById($claims->sub);

        if (!$user instanceof UserIdentity || !$user->isActive()) {
            return $this->unauthorized();
        }

        // Guardar la identidad y los claims en la petición.
        $authenticatedRequest = $request
            ->withAttribute(self::USER_ATTRIBUTE, $user)
            ->withAttribute(self::CLAIMS_ATTRIBUTE, $claims);

        return $handler->handle($authenticatedRequest);
    }

    /**
     * Genera una respuesta 401 sin revelar el motivo específico
     * del fallo de autenticación.
     */
    private function unauthorized(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(401);

        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('WWW-Authenticate', 'Bearer');

        $response->getBody()->write(
            json_encode(
                [
                    'error' => 'unauthorized',
                    'message' => 'Autenticación requerida o token no válido.',
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $response;
    }
}
