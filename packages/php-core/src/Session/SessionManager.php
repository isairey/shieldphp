
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Session;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ShieldPHP\Core\Contracts\ClockInterface;
use ShieldPHP\Core\Contracts\SessionStoreInterface;
use ShieldPHP\Core\Exceptions\ShieldException;

final class SessionManager
{
    private const CREATED_AT = '__shieldphp_created_at';
    private const LAST_ACTIVITY = '__shieldphp_last_activity';

    private ?string $sessionId = null;

    /**
     * Datos actuales, incluidos los metadatos internos.
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    private readonly CookieManager $cookieManager;

    public function __construct(
        private readonly SessionStoreInterface $store,
        private readonly SessionConfig $config,
        private readonly ?ClockInterface $clock = null
    ) {
        $this->cookieManager = $this->config->createCookieManager();
    }

    /**
     * Recupera una sesión desde la cookie de la petición.
     *
     * Si la sesión no existe, expiró o incumple sus límites de
     * duración, devuelve un array vacío y deja la sesión inactiva.
     *
     * @return array<string, mixed>
     */
    public function start(ServerRequestInterface $request): array
    {
        $this->sessionId = null;
        $this->data = [];

        $sessionId = $this->cookieManager->getSessionId($request);

        if ($sessionId === null) {
            return [];
        }

        $storedData = $this->store->get($sessionId);

        if ($storedData === null) {
            return [];
        }

        $createdAt = $storedData[self::CREATED_AT] ?? null;
        $lastActivity = $storedData[self::LAST_ACTIVITY] ?? null;
        $now = $this->now()->getTimestamp();

        if (
            !is_int($createdAt)
            || !is_int($lastActivity)
            || $createdAt <= 0
            || $lastActivity < $createdAt
            || $createdAt > $now
            || $lastActivity > $now
        ) {
            $this->store->delete($sessionId);

            return [];
        }

        $idleExpiresAt = $lastActivity
            + $this->config->getIdleTimeoutSeconds();

        $absoluteExpiresAt = $createdAt
            + $this->config->getAbsoluteTimeoutSeconds();

        if ($now >= $idleExpiresAt || $now >= $absoluteExpiresAt) {
            $this->store->delete($sessionId);

            return [];
        }

        $this->sessionId = $sessionId;
        $this->data = $storedData;

        // Actualiza la actividad y la expiración persistida.
        $this->data[self::LAST_ACTIVITY] = $now;
        $this->persist();

        return $this->all();
    }

    /**
     * Crea una sesión con un identificador aleatorio nuevo.
     *
     * @param array<string, mixed> $data Datos iniciales de la sesión.
     */
    public function create(array $data = []): string
    {
        $this->validateUserData($data);

        // Invalidar una sesión previamente cargada.
        if ($this->sessionId !== null) {
            $this->store->delete($this->sessionId);
        }

        $now = $this->now()->getTimestamp();

        $this->sessionId = $this->generateSessionId();

        $this->data = $data;
        $this->data[self::CREATED_AT] = $now;
        $this->data[self::LAST_ACTIVITY] = $now;

        $this->persist();

        return $this->sessionId;
    }

    /**
     * Regenera el identificador y conserva los datos de la sesión.
     *
     * Usa resetAbsoluteTimeout=true cuando la regeneración
     * representa el inicio de una nueva sesión autenticada.
     */
    public function regenerateId(
        bool $resetAbsoluteTimeout = false
    ): string {
        if ($this->sessionId === null) {
            throw new LogicException(
                'No hay una sesión activa que pueda regenerarse.'
            );
        }

        $oldSessionId = $this->sessionId;
        $now = $this->now()->getTimestamp();

        if ($resetAbsoluteTimeout) {
            $this->data[self::CREATED_AT] = $now;
        }

        $this->sessionId = $this->generateSessionId();
        $this->data[self::LAST_ACTIVITY] = $now;

        // Guardar primero el nuevo ID antes de eliminar el anterior.
        $this->persist();
        $this->store->delete($oldSessionId);

        return $this->sessionId;
    }

    /**
     * Regenera el ID al iniciar sesión si está configurado.
     */
    public function regenerateIdOnLogin(): ?string
    {
        if (!$this->config->shouldRegenerateIdOnLogin()) {
            return $this->sessionId;
        }

        if ($this->sessionId === null) {
            throw new LogicException(
                'Debe existir una sesión antes de regenerar su ID.'
            );
        }

        return $this->regenerateId(resetAbsoluteTimeout: true);
    }

    /**
     * Recupera un valor de la sesión.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertUserKey($key);

        return $this->data[$key] ?? $default;
    }

    /**
     * Comprueba si existe una clave de datos de usuario.
     */
    public function has(string $key): bool
    {
        $this->assertUserKey($key);

        return array_key_exists($key, $this->data);
    }

    /**
     * Guarda un valor y persiste inmediatamente la sesión.
     */
    public function set(string $key, mixed $value): void
    {
        $this->assertActive();
        $this->assertUserKey($key);

        $this->data[$key] = $value;

        $this->persist();
    }

    /**
     * Elimina un valor de la sesión.
     */
    public function remove(string $key): void
    {
        $this->assertActive();
        $this->assertUserKey($key);

        unset($this->data[$key]);

        $this->persist();
    }

    /**
     * Obtiene únicamente los datos accesibles de la aplicación.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $data = $this->data;

        unset(
            $data[self::CREATED_AT],
            $data[self::LAST_ACTIVITY]
        );

        return $data;
    }

    /**
     * Persiste los datos y renueva el vencimiento por inactividad.
     */
    public function save(): void
    {
        $this->assertActive();

        $now = $this->now()->getTimestamp();
        $this->data[self::LAST_ACTIVITY] = $now;

        $this->persist();
    }

    /**
     * Indica si hay una sesión cargada o creada.
     */
    public function isStarted(): bool
    {
        return $this->sessionId !== null;
    }

    /**
     * Devuelve el ID de la sesión actual.
     */
    public function getId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Escribe la cookie correspondiente a la sesión actual.
     */
    public function withCookie(ResponseInterface $response): ResponseInterface
    {
        if ($this->sessionId === null) {
            return $this->cookieManager->clearSessionCookie($response);
        }

        $cookieLifetime = $this->config->getCookieLifetimeSeconds();

        if ($cookieLifetime !== null) {
            $expiresAt = $this->calculateExpiresAt();
            $remaining = $expiresAt->getTimestamp()
                - $this->now()->getTimestamp();

            if ($remaining <= 0) {
                $this->destroy();

                return $this->cookieManager->clearSessionCookie($response);
            }

            $cookieLifetime = min($cookieLifetime, $remaining);
        }

        return $this->cookieManager->setSessionCookie(
            response: $response,
            sessionId: $this->sessionId,
            maxAge: $cookieLifetime
        );
    }

    /**
     * Invalida la sesión en el servidor.
     *
     * Para expirar también la cookie de la respuesta HTTP,
     * utiliza destroyAndClearCookie().
     */
    public function destroy(): void
    {
        if ($this->sessionId !== null) {
            $this->store->delete($this->sessionId);
        }

        $this->sessionId = null;
        $this->data = [];
    }

    /**
     * Invalida la sesión y expira su cookie HTTP.
     */
    public function destroyAndClearCookie(
        ResponseInterface $response
    ): ResponseInterface {
        $this->destroy();

        return $this->cookieManager->clearSessionCookie($response);
    }

    /**
     * Guarda los datos con una expiración limitada por inactividad
     * y por la duración absoluta.
     */
    private function persist(): void
    {
        $this->assertActive();

        $expiresAt = $this->calculateExpiresAt();

        $this->store->save(
            $this->sessionId,
            $this->data,
            $expiresAt
        );
    }

    private function calculateExpiresAt(): DateTimeImmutable
    {
        $createdAt = $this->data[self::CREATED_AT] ?? null;
        $lastActivity = $this->data[self::LAST_ACTIVITY] ?? null;

        if (!is_int($createdAt) || !is_int($lastActivity)) {
            throw new LogicException(
                'La sesión no contiene los metadatos de expiración requeridos.'
            );
        }

        $idleExpiresAt = $lastActivity
            + $this->config->getIdleTimeoutSeconds();

        $absoluteExpiresAt = $createdAt
            + $this->config->getAbsoluteTimeoutSeconds();

        return (new DateTimeImmutable())->setTimestamp(
            min($idleExpiresAt, $absoluteExpiresAt)
        );
    }

    private function generateSessionId(): string
    {
        try {
            return rtrim(
                strtr(base64_encode(random_bytes(32)), '+/', '-_'),
                '='
            );
        } catch (\Random\RandomException $exception) {
            throw new ShieldException(
                'No fue posible generar un ID de sesión seguro.',
                0,
                $exception
            );
        }
    }

    private function validateUserData(array $data): void
    {
        foreach (array_keys($data) as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException(
                    'Las claves de sesión deben ser cadenas.'
                );
            }

            $this->assertUserKey($key);
        }
    }

    private function assertUserKey(string $key): void
    {
        if (
            $key === ''
            || strlen($key) > 128
            || preg_match('/[\x00-\x1F\x7F]/', $key) === 1
            || str_starts_with($key, '__shieldphp_')
        ) {
            throw new InvalidArgumentException(
                'La clave de sesión no es válida o está reservada.'
            );
        }
    }

    private function assertActive(): void
    {
        if ($this->sessionId === null) {
            throw new LogicException(
                'No hay una sesión activa. Ejecuta start() o create() primero.'
            );
        }
    }

    private function now(): DateTimeImmutable
    {
        return $this->clock?->now() ?? new DateTimeImmutable();
    }
}
