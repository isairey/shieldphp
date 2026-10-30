
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Session;

use ShieldPHP\Core\Exceptions\ConfigurationException;

final class SessionConfig
{
    private const MAX_LIFETIME_SECONDS = 31536000;

    private readonly string $cookieName;
    private readonly string $path;
    private readonly ?string $domain;
    private readonly bool $secure;
    private readonly bool $httpOnly;
    private readonly string $sameSite;
    private readonly ?int $cookieLifetimeSeconds;
    private readonly int $idleTimeoutSeconds;
    private readonly int $absoluteTimeoutSeconds;
    private readonly bool $regenerateIdOnLogin;

    public function __construct(
        string $cookieName = '__Host-shieldphp_session',
        string $path = '/',
        ?string $domain = null,
        bool $secure = true,
        bool $httpOnly = true,
        string $sameSite = 'Lax',
        ?int $cookieLifetimeSeconds = 1800,
        int $idleTimeoutSeconds = 1800,
        int $absoluteTimeoutSeconds = 28800,
        bool $regenerateIdOnLogin = true
    ) {
        $this->validateCookieName($cookieName);
        $this->validatePath($path);
        $this->validateDomain($domain);
        $this->validateSameSite($sameSite, $secure);
        $this->validateLifetimes(
            $cookieLifetimeSeconds,
            $idleTimeoutSeconds,
            $absoluteTimeoutSeconds
        );

        // Las cookies __Host- requieren Secure, Path=/ y ningún Domain.
        if (str_starts_with($cookieName, '__Host-')) {
            if (!$secure || $path !== '/' || $domain !== null) {
                throw new ConfigurationException(
                    'Las cookies __Host- requieren Secure=true, '
                    . 'Path=/ y Domain=null.'
                );
            }
        }

        $this->cookieName = $cookieName;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
        $this->cookieLifetimeSeconds = $cookieLifetimeSeconds;
        $this->idleTimeoutSeconds = $idleTimeoutSeconds;
        $this->absoluteTimeoutSeconds = $absoluteTimeoutSeconds;
        $this->regenerateIdOnLogin = $regenerateIdOnLogin;
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * Devuelve null cuando se desea una cookie de sesión del navegador.
     */
    public function getCookieLifetimeSeconds(): ?int
    {
        return $this->cookieLifetimeSeconds;
    }

    /**
     * Tiempo máximo de inactividad permitido para una sesión.
     */
    public function getIdleTimeoutSeconds(): int
    {
        return $this->idleTimeoutSeconds;
    }

    /**
     * Tiempo máximo absoluto de una sesión.
     */
    public function getAbsoluteTimeoutSeconds(): int
    {
        return $this->absoluteTimeoutSeconds;
    }

    public function shouldRegenerateIdOnLogin(): bool
    {
        return $this->regenerateIdOnLogin;
    }

    /**
     * Crea un CookieManager con las opciones de esta configuración.
     */
    public function createCookieManager(): CookieManager
    {
        return new CookieManager(
            cookieName: $this->cookieName,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httpOnly: $this->httpOnly,
            sameSite: $this->sameSite
        );
    }

    private function validateCookieName(string $name): void
    {
        if (
            preg_match('/\A[A-Za-z0-9_-]{1,128}\z/', $name) !== 1
        ) {
            throw new ConfigurationException(
                'El nombre de la cookie no es válido.'
            );
        }
    }

    private function validatePath(string $path): void
    {
        if (
            $path === ''
            || $path[0] !== '/'
            || preg_match('/[\x00-\x1F\x7F;]/', $path) === 1
        ) {
            throw new ConfigurationException(
                'La ruta de la cookie no es válida.'
            );
        }
    }

    private function validateDomain(?string $domain): void
    {
        if ($domain === null) {
            return;
        }

        if (
            preg_match(
                '/\A(?=.{1,253}\z)[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)*\z/',
                $domain
            ) !== 1
        ) {
            throw new ConfigurationException(
                'El dominio de la cookie no es válido.'
            );
        }
    }

    private function validateSameSite(
        string $sameSite,
        bool $secure
    ): void {
        if (!in_array($sameSite, ['Strict', 'Lax', 'None'], true)) {
            throw new ConfigurationException(
                'sameSite debe ser Strict, Lax o None.'
            );
        }

        if ($sameSite === 'None' && !$secure) {
            throw new ConfigurationException(
                'SameSite=None requiere Secure=true.'
            );
        }
    }

    private function validateLifetimes(
        ?int $cookieLifetime,
        int $idleTimeout,
        int $absoluteTimeout
    ): void {
        if (
            $cookieLifetime !== null
            && (
                $cookieLifetime < 1
                || $cookieLifetime > self::MAX_LIFETIME_SECONDS
            )
        ) {
            throw new ConfigurationException(
                'La duración de la cookie debe estar entre 1 segundo '
                . 'y 365 días, o ser null para una cookie de sesión.'
            );
        }

        if (
            $idleTimeout < 1
            || $idleTimeout > self::MAX_LIFETIME_SECONDS
        ) {
            throw new ConfigurationException(
                'El tiempo de inactividad debe estar entre 1 segundo y 365 días.'
            );
        }

        if (
            $absoluteTimeout < $idleTimeout
            || $absoluteTimeout > self::MAX_LIFETIME_SECONDS
        ) {
            throw new ConfigurationException(
                'El tiempo máximo absoluto debe ser igual o superior '
                . 'al tiempo de inactividad y no superar 365 días.'
            );
        }
    }
}
