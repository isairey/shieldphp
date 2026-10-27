
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Middleware;

use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ShieldPHP\Core\Auth\UserIdentity;
use ShieldPHP\Core\Contracts\ClockInterface;

final class RateLimitMiddleware implements MiddlewareInterface
{
    private const USER_ATTRIBUTE = 'shieldphp.user';

    /**
     * Contadores indexados por cliente y ámbito.
     *
     * @var array<string, array{window: int, count: int}>
     */
    private array $buckets = [];

    private int $processedRequests = 0;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly int $maxRequests = 60,
        private readonly int $windowSeconds = 60,
        private readonly string $scope = 'default',
        private readonly ?ClockInterface $clock = null
    ) {
        if ($this->maxRequests < 1 || $this->maxRequests > 1000000) {
            throw new InvalidArgumentException(
                'maxRequests debe estar entre 1 y 1000000.'
            );
        }

        if ($this->windowSeconds < 1 || $this->windowSeconds > 86400) {
            throw new InvalidArgumentException(
                'windowSeconds debe estar entre 1 y 86400.'
            );
        }

        if (
            trim($this->scope) === ''
            || strlen($this->scope) > 128
        ) {
            throw new InvalidArgumentException(
                'scope debe ser una cadena no vacía de hasta 128 bytes.'
            );
        }
    }

    /**
     * Comprueba el límite antes de ejecutar el siguiente middleware.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $now = $this->now();
        $window = intdiv($now, $this->windowSeconds);
        $resetAt = ($window + 1) * $this->windowSeconds;

        $clientIdentifier = $this->resolveClientIdentifier($request);

        // Evitar almacenar la dirección IP o el ID en la clave interna.
        $bucketKey = hash(
            'sha256',
            $this->scope . "\0" . $clientIdentifier
        );

        $bucket = $this->buckets[$bucketKey] ?? null;

        if ($bucket === null || $bucket['window'] !== $window) {
            $bucket = [
                'window' => $window,
                'count' => 0,
            ];
        }

        // Limpiar ocasionalmente los contadores de ventanas anteriores.
        $this->processedRequests++;

        if ($this->processedRequests % 100 === 0) {
            $this->removeExpiredBuckets($now);
        }

        if ($bucket['count'] >= $this->maxRequests) {
            $this->buckets[$bucketKey] = $bucket;

            return $this->tooManyRequests(
                retryAfter: max(1, $resetAt - $now),
                resetAt: $resetAt
            );
        }

        $bucket['count']++;

        $this->buckets[$bucketKey] = $bucket;

        $remaining = max(
            0,
            $this->maxRequests - $bucket['count']
        );

        // Continuar solo si el cliente no ha superado su límite.
        $response = $handler->handle($request);

        return $this->addRateLimitHeaders(
            response: $response,
            remaining: $remaining,
            resetAt: $resetAt
        );
    }

    /**
     * Identifica al cliente autenticado o utiliza la IP remota.
     *
     * No confía en X-Forwarded-For porque un cliente puede falsificarlo.
     */
    private function resolveClientIdentifier(
        ServerRequestInterface $request
    ): string {
        $user = $request->getAttribute(self::USER_ATTRIBUTE);

        if ($user instanceof UserIdentity) {
            return 'user:' . $user->getId();
        }

        $serverParams = $request->getServerParams();
        $remoteAddress = $serverParams['REMOTE_ADDR'] ?? null;

        if (
            is_string($remoteAddress)
            && filter_var($remoteAddress, FILTER_VALIDATE_IP) !== false
        ) {
            return 'ip:' . $remoteAddress;
        }

        // Si no hay una identidad fiable, no se permite el acceso
        // a un contador individual diferente para cada petición.
        return 'anonymous:unknown';
    }

    /**
     * Agrega los encabezados informativos a la respuesta.
     */
    private function addRateLimitHeaders(
        ResponseInterface $response,
        int $remaining,
        int $resetAt
    ): ResponseInterface {
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining)
            ->withHeader('X-RateLimit-Reset', (string) $resetAt);
    }

    /**
     * Genera la respuesta HTTP 429.
     */
    private function tooManyRequests(
        int $retryAfter,
        int $resetAt
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse(429);

        $response = $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Retry-After', (string) $retryAfter);

        $response = $this->addRateLimitHeaders(
            response: $response,
            remaining: 0,
            resetAt: $resetAt
        );

        $response->getBody()->write(
            json_encode(
                [
                    'error' => 'rate_limit_exceeded',
                    'message' => 'Has superado el límite de solicitudes.',
                    'retry_after' => $retryAfter,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        return $response;
    }

    /**
     * Elimina los contadores de ventanas que ya terminaron.
     */
    private function removeExpiredBuckets(int $now): void
    {
        foreach ($this->buckets as $key => $bucket) {
            $windowEnd = ($bucket['window'] + 1) * $this->windowSeconds;

            if ($windowEnd <= $now) {
                unset($this->buckets[$key]);
            }
        }
    }

    /**
     * Devuelve la hora actual en segundos Unix.
     */
    private function now(): int
    {
        $date = $this->clock?->now() ?? new DateTimeImmutable();

        return $date->getTimestamp();
    }
}
