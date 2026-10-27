
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShieldPHP\Core\Auth\UserIdentity;
use ShieldPHP\Core\Authorization\AccessControl;

final class RoleMiddleware implements MiddlewareInterface
{
    /**
     * Atributo establecido por AuthMiddleware.
     */
    private const USER_ATTRIBUTE = 'shieldphp.user';

    /**
     * Rol necesario para acceder a la ruta.
     */
    private readonly string $requiredRole;

    public function __construct(
        private readonly AccessControl $accessControl,
        private readonly ResponseFactoryInterface $responseFactory,
        string $requiredRole
    ) {
        $requiredRole = trim($requiredRole);

        if (
            preg_match(
                '/\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}\z/',
                $requiredRole
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'El identificador del rol no es válido.'
            );
        }

        $this->requiredRole = $requiredRole;
    }

    /**
     * Comprueba si el usuario tiene el rol requerido.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $user = $request->getAttribute(self::USER_ATTRIBUTE);

        // La autenticación debe ejecutarse antes que este middleware.
        if (!$user instanceof UserIdentity || !$user->isActive()) {
            return $this->unauthorized();
        }

        if (!$this->accessControl->hasRole($user, $this->requiredRole)) {
            return $this->forbidden();
        }

        return $handler->handle($request);
    }

    /**
     * Respuesta para peticiones no autenticadas.
     */
    private function unauthorized(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(401);

        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('WWW-Authenticate', 'Bearer');

        $response->getBody()->write(
            json_encode(
                [
                    'error' => 'unauthorized',
                    'message' => 'Se requiere autenticación.',
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $response;
    }

    /**
     * Respuesta para usuarios autenticados sin el rol requerido.
     */
    private function forbidden(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(403);

        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store');

        $response->getBody()->write(
            json_encode(
                [
                    'error' => 'forbidden',
                    'message' => 'No tienes el rol necesario para acceder a este recurso.',
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $response;
    }
}
