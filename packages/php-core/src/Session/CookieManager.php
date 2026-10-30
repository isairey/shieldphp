
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Session;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CookieManager
{
    private const DEFAULT_COOKIE_NAME = '__Host-shieldphp_session';
    private const MAX_SESSION_ID_LENGTH = 256;
    private const MAX_COOKIE_AGE = 31536000;

    public function __construct(
        private readonly string $cookieName = self::DEFAULT_COOKIE_NAME,
        private readonly string $path = '/',
        private readonly ?string $domain = null,
        private readonly bool $secure = true,
        private readonly bool $httpOnly = true,
        private readonly string $sameSite = 'Lax'
    ) {
        $this->validateConfiguration();
    }

    /**
     * Añade una cookie que contiene el identificador de sesión.
     *
     * Si maxAge es null, se crea una cookie de sesión del navegador.
     */
    public function setSessionCookie(
        ResponseInterface $response,
        string $sessionId,
        ?int $maxAge = null
    ): ResponseInterface {
        $this->validateSessionId($sessionId);

        if (
            $maxAge !== null
            && ($maxAge < 1 || $maxAge > self::MAX_COOKIE_AGE)
        ) {
            throw new InvalidArgumentException(
                'maxAge debe estar entre 1 segundo y 365 días.'
            );
        }

        $cookie = $this->cookieName . '=' . $sessionId;

        $cookie .= '; Path=' . $this->path;

        if ($this->domain !== null) {
            $cookie .= '; Domain=' . $this->domain;
        }

        if ($this->secure) {
            $cookie .= '; Secure';
        }

        if ($this->httpOnly) {
            $cookie .= '; HttpOnly';
        }

        $cookie .= '; SameSite=' . $this->sameSite;

        if ($maxAge !== null) {
            $cookie .= '; Max-Age=' . $maxAge;

            $expires = time() + $maxAge;

            $cookie .= '; Expires='
                . gmdate('D, d M Y H:i:s', $expires)
                . ' GMT';
        }

        // No sobrescribir otras cookies establecidas por la aplicación.
        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    /**
     * Lee el identificador de sesión de las cookies de la petición.
     *
     * Devuelve null si la cookie no existe o tiene un formato inválido.
     */
    public function getSessionId(
        ServerRequestInterface $request
    ): ?string {
        $cookies = $request->getCookieParams();
        $sessionId = $cookies[$this->cookieName] ?? null;

        if (!is_string($sessionId)) {
            return null;
        }

        if (!$this->isValidSessionId($sessionId)) {
            return null;
        }

        return $sessionId;
    }

    /**
     * Expira la cookie de sesión en el navegador.
     */
    public function clearSessionCookie(
        ResponseInterface $response
    ): ResponseInterface {
        $cookie = $this->cookieName . '=';
        $cookie .= '; Path=' . $this->path;

        if ($this->domain !== null) {
            $cookie .= '; Domain=' . $this->domain;
        }

        if ($this->secure) {
            $cookie .= '; Secure';
        }

        if ($this->httpOnly) {
            $cookie .= '; HttpOnly';
        }

        $cookie .= '; SameSite=' . $this->sameSite;
        $cookie .= '; Max-Age=0';
        $cookie .= '; Expires=Thu, 01 Jan 1970 00:00:00 GMT';

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    /**
     * Devuelve el nombre configurado de la cookie.
     */
    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    /**
     * Valida las opciones de seguridad de la cookie.
     */
    private function validateConfiguration(): void
    {
        if (
            preg_match('/\A[A-Za-z0-9_-]{1,128}\z/', $this->cookieName) !== 1
        ) {
            throw new InvalidArgumentException(
                'El nombre de la cookie no es válido.'
            );
        }

        if (
            $this->path === ''
            || $this->path[0] !== '/'
            || preg_match('/[\x00-\x1F\x7F;]/', $this->path) === 1
        ) {
            throw new InvalidArgumentException(
                'La ruta de la cookie no es válida.'
            );
        }

        if ($this->domain !== null) {
            if (
                preg_match(
                    '/\A(?=.{1,253}\z)[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?)*\z/',
                    $this->domain
                ) !== 1
            ) {
                throw new InvalidArgumentException(
                    'El dominio de la cookie no es válido.'
                );
            }
        }

        if (!in_array($this->sameSite, ['Strict', 'Lax', 'None'], true)) {
            throw new InvalidArgumentException(
                'sameSite debe ser Strict, Lax o None.'
            );
        }

        if ($this->sameSite === 'None' && !$this->secure) {
            throw new InvalidArgumentException(
                'SameSite=None requiere Secure=true.'
            );
        }

        // Las cookies __Host- requieren Secure, Path=/ y ningún Domain.
        if (str_starts_with($this->cookieName, '__Host-')) {
            if (
                !$this->secure
                || $this->path !== '/'
                || $this->domain !== null
            ) {
                throw new InvalidArgumentException(
                    'Las cookies __Host- requieren Secure, Path=/ y no permiten Domain.'
                );
            }
        }
    }

    /**
     * Comprueba el formato del identificador de sesión.
     *
     * Se espera un identificador aleatorio codificado en Base64 URL-safe.
     */
    private function validateSessionId(string $sessionId): void
    {
        if (!$this->isValidSessionId($sessionId)) {
            throw new InvalidArgumentException(
                'El identificador de sesión no tiene un formato válido.'
            );
        }
    }

    private function isValidSessionId(string $sessionId): bool
    {
        return strlen($sessionId) >= 32
            && strlen($sessionId) <= self::MAX_SESSION_ID_LENGTH
            && preg_match('/\A[A-Za-z0-9_-]+\z/', $sessionId) === 1;
    }
}
