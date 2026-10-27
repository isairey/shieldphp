
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Middleware;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Políticas de protección contra la inclusión en marcos.
     *
     * @var list<string>
     */
    private const ALLOWED_FRAME_OPTIONS = [
        'DENY',
        'SAMEORIGIN',
    ];

    /**
     * Políticas permitidas para Referrer-Policy.
     *
     * @var list<string>
     */
    private const ALLOWED_REFERRER_POLICIES = [
        'no-referrer',
        'no-referrer-when-downgrade',
        'origin',
        'origin-when-cross-origin',
        'same-origin',
        'strict-origin',
        'strict-origin-when-cross-origin',
        'unsafe-url',
    ];

    public function __construct(
        private readonly string $frameOptions = 'DENY',
        private readonly string $referrerPolicy =
            'strict-origin-when-cross-origin',
        private readonly ?string $contentSecurityPolicy = null,
        private readonly string $permissionsPolicy =
            'camera=(), microphone=(), geolocation=()',
        private readonly bool $enableHsts = false,
        private readonly int $hstsMaxAge = 31536000,
        private readonly bool $includeSubdomains = false,
        private readonly bool $preload = false
    ) {
        if (
            !in_array(
                $this->frameOptions,
                self::ALLOWED_FRAME_OPTIONS,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'frameOptions debe ser DENY o SAMEORIGIN.'
            );
        }

        if (
            !in_array(
                $this->referrerPolicy,
                self::ALLOWED_REFERRER_POLICIES,
                true
            )
        ) {
            throw new InvalidArgumentException(
                'La política Referrer-Policy no es válida.'
            );
        }

        $this->validateHeaderValue(
            $this->permissionsPolicy,
            'permissionsPolicy',
            2048
        );

        if ($this->contentSecurityPolicy !== null) {
            $this->validateHeaderValue(
                $this->contentSecurityPolicy,
                'contentSecurityPolicy',
                4096
            );
        }

        if (
            $this->hstsMaxAge < 0
            || $this->hstsMaxAge > 63072000
        ) {
            throw new InvalidArgumentException(
                'hstsMaxAge debe estar entre 0 y 63072000 segundos.'
            );
        }

        if ($this->preload) {
            if (
                !$this->enableHsts
                || !$this->includeSubdomains
                || $this->hstsMaxAge < 31536000
            ) {
                throw new InvalidArgumentException(
                    'HSTS preload requiere HSTS habilitado, '
                    . 'includeSubdomains=true y una duración mínima de un año.'
                );
            }
        }
    }

    /**
     * Añade los encabezados de seguridad a la respuesta HTTP.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);

        // Impide interpretar una respuesta con un tipo MIME diferente
        // del declarado por Content-Type.
        $response = $this->setDefaultHeader(
            $response,
            'X-Content-Type-Options',
            'nosniff'
        );

        // Controla si el navegador puede mostrar la página en un iframe.
        $response = $this->setDefaultHeader(
            $response,
            'X-Frame-Options',
            $this->frameOptions
        );

        // Limita la información de referencia enviada por el navegador.
        $response = $this->setDefaultHeader(
            $response,
            'Referrer-Policy',
            $this->referrerPolicy
        );

        // Desactiva funciones del navegador que la aplicación no necesita.
        $response = $this->setDefaultHeader(
            $response,
            'Permissions-Policy',
            $this->permissionsPolicy
        );

        // Solo se aplica si se configuró una política para la aplicación.
        if ($this->contentSecurityPolicy !== null) {
            $response = $this->setDefaultHeader(
                $response,
                'Content-Security-Policy',
                $this->contentSecurityPolicy
            );
        }

        // HSTS debe activarse explícitamente cuando el sitio utiliza HTTPS.
        if ($this->enableHsts) {
            $hsts = 'max-age=' . $this->hstsMaxAge;

            if ($this->includeSubdomains) {
                $hsts .= '; includeSubDomains';
            }

            if ($this->preload) {
                $hsts .= '; preload';
            }

            $response = $this->setDefaultHeader(
                $response,
                'Strict-Transport-Security',
                $hsts
            );
        }

        // Evita almacenar en caché las respuestas 401 y 403.
        if (
            in_array($response->getStatusCode(), [401, 403], true)
            && !$response->hasHeader('Cache-Control')
        ) {
            $response = $response->withHeader(
                'Cache-Control',
                'no-store'
            );
        }

        return $response;
    }

    /**
     * No reemplaza un encabezado que ya haya establecido la aplicación.
     */
    private function setDefaultHeader(
        ResponseInterface $response,
        string $name,
        string $value
    ): ResponseInterface {
        if ($response->hasHeader($name)) {
            return $response;
        }

        return $response->withHeader($name, $value);
    }

    /**
     * Evita valores de encabezado vacíos, excesivos o con saltos de línea.
     */
    private function validateHeaderValue(
        string $value,
        string $name,
        int $maxLength
    ): void {
        if (
            $value === ''
            || strlen($value) > $maxLength
            || preg_match('/[\r\n\0]/', $value) === 1
        ) {
            throw new InvalidArgumentException(
                sprintf('El valor de %s no es válido.', $name)
            );
        }
    }
}
