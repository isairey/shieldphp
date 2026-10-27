
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

final class PermissionMiddleware implements MiddlewareInterface
{
    /**
     * Atributo donde AuthMiddleware almacena el usuario autenticado.
     */
    private const USER_ATTRIBUTE = 'shieldphp.user';

    /**
     * Permiso requerido para acceder a la ruta.
     */
    private readonly string $permission;

    public function __construct(
        private readonly AccessControl $accessControl,
        private readonly ResponseFactoryInterface $responseFactory,
        string $permission
    ) {
        $permission = trim($permission);

        if (
            preg_match(
                '/\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}\z/',
                $permission
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'El identificador del permiso no es válido.'
            );
        }

        $this->permission = $permission;
    }

    /**
     * Comprueba que el usuario tenga el permiso requerido.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $user = $request->getAttribute(self::USER_ATTRIBUTE);

        // Si no hay usuario autenticado, no permitir el acceso.
        if (!$user instanceof UserIdentity) {
            return $this->unauthorized();
        }

        // Las cuentas desactivadas no pueden acceder.
        if (!$user->isActive()) {
            return $this->unauthorized();
        }

        // Comprobar el permiso mediante AccessControl.
        if (!$this->accessControl->can($user, $this->permission)) {
            return $this->forbidden();
        }

        // El usuario está autenticado y tiene el permiso necesario.
        return $handler->handle($request);
    }

    /**
     * Devuelve una respuesta 401 cuando no existe
     * un usuario autenticado en la petición.
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
     * Devuelve una respuesta 403 cuando el usuario
     * está autenticado, pero carece del permiso.
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
                    'message' => 'No tienes permiso para realizar esta operación.',
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $response;
    }
}
