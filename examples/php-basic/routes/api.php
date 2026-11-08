
<?php

declare(strict_types=1);

namespace ShieldPHP\Examples\Basic\Routes;

use ShieldPHP\Core\Auth\AuthManager;

/**
 * ShieldPHP Basic - API Routes
 *
 * Endpoints:
 *
 * GET  /api/health
 * GET  /api/auth/csrf
 * GET  /api/auth/me
 * POST /api/auth/login
 * POST /api/auth/logout
 *
 * Este archivo devuelve un dispatcher que recibe:
 * - Servicios de la aplicación.
 * - Método HTTP.
 * - Ruta solicitada.
 *
 * Las respuestas utilizan JSON.
 */

/**
 * Devuelve el dispatcher de rutas de la API.
 */
return static function (
    array $services,
    string $method,
    string $path
): never {

    /*
    |--------------------------------------------------------------------------
    | Respuesta JSON
    |--------------------------------------------------------------------------
    */

    $respond = static function (
        int $status,
        array $data
    ): never {
        http_response_code($status);

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        $json = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE
        );

        if ($json === false) {
            http_response_code(500);
            echo '{"success":false,"message":"Internal server error."}';

            exit;
        }

        echo $json;

        exit;
    };

    /*
    |--------------------------------------------------------------------------
    | Normalización de la solicitud
    |--------------------------------------------------------------------------
    */

    $method = strtoupper($method);

    $path = parse_url($path, PHP_URL_PATH) ?: '/';

    $path = rtrim($path, '/') ?: '/';

    /*
    |--------------------------------------------------------------------------
    | Verificar servicios
    |--------------------------------------------------------------------------
    */

    $authManager = $services['auth_manager'] ?? null;

    if (!$authManager instanceof AuthManager) {
        $respond(500, [
            'success' => false,
            'message' => 'Authentication service is unavailable.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Health check
    |--------------------------------------------------------------------------
    */

    if ($method === 'GET' && $path === '/api/health') {
        $respond(200, [
            'success' => true,
            'application' => 'ShieldPHP',
            'example' => 'php-basic',
            'status' => 'healthy',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Configuración de sesión
    |--------------------------------------------------------------------------
    */

    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = isset($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== ''
            && strtolower((string) $_SERVER['HTTPS']) !== 'off';

        session_name('SHIELDPHP_DEMO');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        if (!session_start()) {
            $respond(500, [
                'success' => false,
                'message' => 'Unable to start session.',
            ]);
        }
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        $respond(500, [
            'success' => false,
            'message' => 'Session is not active.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generación de token CSRF
    |--------------------------------------------------------------------------
    */

    $generateCsrfToken = static function (): string {
        if (
            !isset($_SESSION['_csrf_token'])
            || !is_string($_SESSION['_csrf_token'])
        ) {
            $_SESSION['_csrf_token'] = bin2hex(
                random_bytes(32)
            );
        }

        return $_SESSION['_csrf_token'];
    };

    /*
    |--------------------------------------------------------------------------
    | Validación CSRF
    |--------------------------------------------------------------------------
    */

    $verifyCsrfToken = static function (
        array $data
    ): bool {
        $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? ($data['csrf_token'] ?? null);

        $storedToken = $_SESSION['_csrf_token'] ?? null;

        if (
            !is_string($submittedToken)
            || !is_string($storedToken)
            || $submittedToken === ''
            || $storedToken === ''
        ) {
            return false;
        }

        return hash_equals(
            $storedToken,
            $submittedToken
        );
    };

    /*
    |--------------------------------------------------------------------------
    | Obtener datos JSON
    |--------------------------------------------------------------------------
    */

    $getJsonInput = static function () use ($respond): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (
            !str_starts_with(
                strtolower($contentType),
                'application/json'
            )
        ) {
            $respond(415, [
                'success' => false,
                'message' => 'Content-Type must be application/json.',
            ]);
        }

        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || $rawBody === '') {
            $respond(400, [
                'success' => false,
                'message' => 'Request body is empty.',
            ]);
        }

        // Limitar el tamaño de las solicitudes.
        if (strlen($rawBody) > 16384) {
            $respond(413, [
                'success' => false,
                'message' => 'Request body is too large.',
            ]);
        }

        try {
            $data = json_decode(
                $rawBody,
                true,
                32,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            $respond(400, [
                'success' => false,
                'message' => 'Invalid JSON payload.',
            ]);
        }

        if (!is_array($data)) {
            $respond(400, [
                'success' => false,
                'message' => 'JSON payload must be an object.',
            ]);
        }

        return $data;
    };

    /*
    |--------------------------------------------------------------------------
    | GET /api/auth/csrf
    |--------------------------------------------------------------------------
    |
    | Genera o recupera el token CSRF asociado a la sesión.
    |
    */

    if ($method === 'GET' && $path === '/api/auth/csrf') {
        $respond(200, [
            'success' => true,
            'data' => [
                'csrf_token' => $generateCsrfToken(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/auth/me
    |--------------------------------------------------------------------------
    |
    | Devuelve la identidad autenticada.
    |
    */

    if ($method === 'GET' && $path === '/api/auth/me') {
        $auth = $_SESSION['auth'] ?? null;

        $authenticated = is_array($auth)
            && isset($auth['user_id'], $auth['email'])
            && is_string($auth['user_id'])
            && is_string($auth['email']);

        if (!$authenticated) {
            $respond(200, [
                'success' => true,
                'authenticated' => false,
                'data' => null,
            ]);
        }

        $respond(200, [
            'success' => true,
            'authenticated' => true,
            'data' => [
                'id' => $auth['user_id'],
                'email' => $auth['email'],
                'authenticated_at' => $auth['authenticated_at'] ?? null,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/auth/login
    |--------------------------------------------------------------------------
    */

    if ($method === 'POST' && $path === '/api/auth/login') {
        $data = $getJsonInput();

        if (!$verifyCsrfToken($data)) {
            $respond(403, [
                'success' => false,
                'message' => 'Invalid CSRF token.',
            ]);
        }

        /*
         * Recuperar credenciales.
         */

        $email = $data['email'] ?? null;

        $password = $data['password'] ?? null;

        if (
            !is_string($email)
            || !is_string($password)
        ) {
            $respond(422, [
                'success' => false,
                'message' => 'Email and password are required.',
            ]);
        }

        $email = trim($email);

        if (
            $email === ''
            || strlen($email) > 254
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $respond(422, [
                'success' => false,
                'message' => 'Invalid email address.',
            ]);
        }

        if (
            $password === ''
            || strlen($password) > 4096
        ) {
            $respond(422, [
                'success' => false,
                'message' => 'Invalid credentials.',
            ]);
        }

        /*
         * Autenticación mediante ShieldPHP.
         */

        try {
            $result = $authManager->attempt(
                $email,
                $password
            );

            if (!$result->isSuccessful()) {
                $respond(401, [
                    'success' => false,
                    'message' => 'Email or password is incorrect.',
                ]);
            }

            $identity = $result->user();

            if ($identity === null) {
                throw new \RuntimeException(
                    'Authentication returned an empty identity.'
                );
            }

            /*
             * Regenerar el ID evita la fijación de sesión.
             */

            if (!session_regenerate_id(true)) {
                throw new \RuntimeException(
                    'Unable to regenerate session ID.'
                );
            }

            /*
             * Guardar solamente los datos necesarios.
             */

            $_SESSION['auth'] = [
                'user_id' => $identity->getId(),
                'email' => $identity->getEmail(),
                'authenticated_at' => time(),
            ];

            /*
             * Renovar el token CSRF después de autenticarse.
             */

            $_SESSION['_csrf_token'] = bin2hex(
                random_bytes(32)
            );

            $respond(200, [
                'success' => true,
                'message' => 'Authentication successful.',
                'authenticated' => true,
                'data' => [
                    'id' => $identity->getId(),
                    'email' => $identity->getEmail(),
                ],
                'csrf_token' => $_SESSION['_csrf_token'],
            ]);

        } catch (\Throwable $exception) {
            error_log(
                '[ShieldPHP API] Login error: '
                . $exception->getMessage()
            );

            $respond(500, [
                'success' => false,
                'message' => 'Authentication could not be completed.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/auth/logout
    |--------------------------------------------------------------------------
    */

    if ($method === 'POST' && $path === '/api/auth/logout') {
        $data = $getJsonInput();

        if (!$verifyCsrfToken($data)) {
            $respond(403, [
                'success' => false,
                'message' => 'Invalid CSRF token.',
            ]);
        }

        /*
         * Limpiar los datos de autenticación.
         */

        $_SESSION = [];

        /*
         * Eliminar la cookie de sesión.
         */

        if (ini_get('session.use_cookies')) {
            $cookieParams = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $cookieParams['path'],
                    'domain' => $cookieParams['domain'],
                    'secure' => $cookieParams['secure'],
                    'httponly' => $cookieParams['httponly'],
                    'samesite' => $cookieParams['samesite'],
                ]
            );
        }

        /*
         * Destruir la sesión.
         */

        if (!session_destroy()) {
            $respond(500, [
                'success' => false,
                'message' => 'Unable to destroy session.',
            ]);
        }

        $respond(200, [
            'success' => true,
            'message' => 'Session closed successfully.',
            'authenticated' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Ruta no encontrada
    |--------------------------------------------------------------------------
    */

    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');

        $respond(405, [
            'success' => false,
            'message' => 'HTTP method not allowed.',
        ]);
    }

    $respond(404, [
        'success' => false,
        'message' => 'API endpoint not found.',
    ]);
};
