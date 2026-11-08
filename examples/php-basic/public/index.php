
<?php

declare(strict_types=1);

/**
 * ShieldPHP - Basic PHP Example
 *
 * Front Controller
 *
 * Funcionalidades:
 * - Autenticación mediante AuthManager.
 * - Persistencia de autenticación mediante sesiones PHP.
 * - Protección CSRF.
 * - Cierre seguro de sesión.
 * - Validación de entradas.
 * - Protección de cabeceras HTTP.
 *
 * Este ejemplo utiliza una sesión PHP para mantener
 * la identidad autenticada entre peticiones.
 */

use ShieldPHP\Core\Auth\AuthManager;

/*
|--------------------------------------------------------------------------
| Configuración inicial
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);

ini_set('display_errors', '0');

date_default_timezone_set('UTC');

$rootPath = dirname(__DIR__, 3);

/*
|--------------------------------------------------------------------------
| Autoload de Composer
|--------------------------------------------------------------------------
*/

$autoloadCandidates = [
    $rootPath . '/vendor/autoload.php',
    $rootPath . '/packages/php-core/vendor/autoload.php',
];

$autoloadLoaded = false;

foreach ($autoloadCandidates as $autoloadFile) {
    if (is_file($autoloadFile)) {
        require_once $autoloadFile;

        $autoloadLoaded = true;

        break;
    }
}

if (!$autoloadLoaded) {
    http_response_code(500);

    exit(
        'ShieldPHP initialization failed: Composer autoload not found.'
    );
}

/*
|--------------------------------------------------------------------------
| Cabeceras de seguridad HTTP
|--------------------------------------------------------------------------
*/

header('X-Content-Type-Options: nosniff');

header('X-Frame-Options: DENY');

header('Referrer-Policy: strict-origin-when-cross-origin');

header('Cache-Control: no-store, no-cache, must-revalidate');

header('Pragma: no-cache');

header(
    "Content-Security-Policy: default-src 'self'; "
    . "style-src 'self' 'unsafe-inline'; "
    . "form-action 'self'; "
    . "base-uri 'none'; "
    . "frame-ancestors 'none'"
);

/*
|--------------------------------------------------------------------------
| Cargar servicios de ShieldPHP
|--------------------------------------------------------------------------
|
| bootstrap.php debe devolver un arreglo con:
|
| [
|     'auth_manager' => AuthManager,
|     'logout_service' => LogoutService (opcional),
| ]
|
*/

$bootstrapFile = dirname(__DIR__) . '/bootstrap.php';

if (!is_file($bootstrapFile)) {
    http_response_code(500);

    exit(
        'ShieldPHP initialization failed: bootstrap.php not found.'
    );
}

try {
    $services = require $bootstrapFile;

    if (!is_array($services)) {
        throw new RuntimeException(
            'The application bootstrap must return an array.'
        );
    }

    $authManager = $services['auth_manager'] ?? null;

    if (!$authManager instanceof AuthManager) {
        throw new RuntimeException(
            'AuthManager is missing from the application bootstrap.'
        );
    }

    $logoutService = $services['logout_service'] ?? null;

} catch (Throwable $exception) {
    error_log(
        '[ShieldPHP] Bootstrap error: ' . $exception->getMessage()
    );

    http_response_code(500);

    exit('Unable to initialize ShieldPHP.');
}

/*
|--------------------------------------------------------------------------
| Configuración de cookies y sesiones
|--------------------------------------------------------------------------
*/

$isHttps = isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== ''
    && strtolower((string) $_SERVER['HTTPS']) !== 'off';

if (session_status() === PHP_SESSION_NONE) {
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
        http_response_code(500);

        exit('Unable to start the application session.');
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    http_response_code(500);

    exit('Session is not available.');
}

/*
|--------------------------------------------------------------------------
| Funciones auxiliares
|--------------------------------------------------------------------------
*/

/**
 * Escapa valores para mostrarlos en HTML.
 */
function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Obtiene un valor POST exclusivamente si es una cadena.
 */
function postString(
    string $key,
    string $default = ''
): string {
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? $value : $default;
}

/**
 * Genera o recupera el token CSRF de la sesión.
 */
function getCsrfToken(): string
{
    if (
        !isset($_SESSION['_csrf_token'])
        || !is_string($_SESSION['_csrf_token'])
    ) {
        $_SESSION['_csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Verifica el token CSRF enviado por el formulario.
 */
function verifyCsrfToken(string $submittedToken): bool
{
    $storedToken = $_SESSION['_csrf_token'] ?? null;

    if (
        !is_string($storedToken)
        || $storedToken === ''
        || $submittedToken === ''
    ) {
        return false;
    }

    return hash_equals(
        $storedToken,
        $submittedToken
    );
}

/**
 * Guarda un mensaje para la siguiente petición.
 */
function setFlashMessage(string $message): void
{
    $_SESSION['_flash_message'] = $message;
}

/**
 * Recupera y elimina el mensaje temporal.
 */
function getFlashMessage(): ?string
{
    $message = $_SESSION['_flash_message'] ?? null;

    unset($_SESSION['_flash_message']);

    return is_string($message) ? $message : null;
}

/**
 * Redirige a la página principal.
 */
function redirectToHome(): never
{
    header('Location: index.php', true, 303);

    exit;
}

/*
|--------------------------------------------------------------------------
| Procesamiento de solicitudes
|--------------------------------------------------------------------------
*/

$message = null;

$messageType = 'info';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {
    $action = postString('action');

    $submittedToken = postString('csrf_token');

    /*
    |--------------------------------------------------------------------------
    | Validación CSRF
    |--------------------------------------------------------------------------
    */

    if (!verifyCsrfToken($submittedToken)) {
        http_response_code(403);

        $message = 'La solicitud no pudo ser validada. Recarga la página e inténtalo nuevamente.';

        $messageType = 'error';

    } else {
        /*
        |--------------------------------------------------------------------------
        | Login
        |--------------------------------------------------------------------------
        */

        if ($action === 'login') {
            $email = trim(postString('email'));

            $password = postString('password');

            /*
             * Validaciones básicas.
             */

            if (
                $email === ''
                || strlen($email) > 254
                || !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                $message = 'Introduce una dirección de correo válida.';

                $messageType = 'error';

            } elseif (
                $password === ''
                || strlen($password) > 4096
            ) {
                $message = 'Las credenciales proporcionadas no son válidas.';

                $messageType = 'error';

            } else {
                try {
                    /*
                     * AuthManager valida las credenciales
                     * mediante el proveedor de usuarios.
                     */

                    $result = $authManager->attempt(
                        $email,
                        $password
                    );

                    if ($result->isSuccessful()) {
                        $identity = $result->user();

                        if ($identity === null) {
                            throw new RuntimeException(
                                'Authentication returned an empty identity.'
                            );
                        }

                        /*
                         * Evitar fijación de sesión.
                         */

                        if (!session_regenerate_id(true)) {
                            throw new RuntimeException(
                                'Session ID regeneration failed.'
                            );
                        }

                        /*
                         * Almacenar únicamente los datos necesarios
                         * para identificar al usuario en el ejemplo.
                         *
                         * La autorización debe seguir comprobándose
                         * en el servidor mediante las reglas de acceso.
                         */

                        $_SESSION['auth'] = [
                            'user_id' => $identity->getId(),
                            'email' => $identity->getEmail(),
                            'authenticated_at' => time(),
                        ];

                        // Renovar el token CSRF después del login.
                        $_SESSION['_csrf_token'] = bin2hex(
                            random_bytes(32)
                        );

                        setFlashMessage(
                            'Has iniciado sesión correctamente.'
                        );

                        redirectToHome();

                    } else {
                        // Mensaje genérico para no revelar
                        // si existe una cuenta determinada.
                        $message = 'Correo electrónico o contraseña incorrectos.';

                        $messageType = 'error';
                    }

                } catch (Throwable $exception) {
                    error_log(
                        '[ShieldPHP] Login error: '
                        . $exception->getMessage()
                    );

                    $message = 'No fue posible completar la autenticación.';

                    $messageType = 'error';
                }
            }

        /*
        |--------------------------------------------------------------------------
        | Logout
        |--------------------------------------------------------------------------
        */

        } elseif ($action === 'logout') {
            try {
                /*
                 * Utilizar LogoutService cuando esté disponible.
                 * Puede encargarse de destruir sesiones y revocar tokens.
                 */

                if ($logoutService !== null) {
                    $logoutService->logout();

                } elseif (session_status() === PHP_SESSION_ACTIVE) {
                    $_SESSION = [];

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

                    session_destroy();
                }

                setFlashMessage(
                    'Tu sesión se ha cerrado correctamente.'
                );

                redirectToHome();

            } catch (Throwable $exception) {
                error_log(
                    '[ShieldPHP] Logout error: '
                    . $exception->getMessage()
                );

                $message = 'No fue posible cerrar la sesión correctamente.';

                $messageType = 'error';
            }

        } else {
            http_response_code(400);

            $message = 'La acción solicitada no es válida.';

            $messageType = 'error';
        }
    }

} elseif ($requestMethod !== 'GET') {
    http_response_code(405);

    header('Allow: GET, POST');

    exit('Method not allowed.');
}

/*
|--------------------------------------------------------------------------
| Estado de autenticación
|--------------------------------------------------------------------------
*/

$auth = $_SESSION['auth'] ?? null;

$isAuthenticated = is_array($auth)
    && isset($auth['user_id'], $auth['email'])
    && is_string($auth['user_id'])
    && is_string($auth['email']);

$csrfToken = getCsrfToken();

$flashMessage = getFlashMessage();

if ($flashMessage !== null && $message === null) {
    $message = $flashMessage;

    $messageType = 'success';
}

/*
|--------------------------------------------------------------------------
| Renderizado HTML
|--------------------------------------------------------------------------
*/

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#ffffff"
    >

    <title>ShieldPHP | Basic Example</title>

    <style>
        :root {
            color-scheme: light;
            --background: #f5f7fb;
            --surface: #ffffff;
            --text: #172033;
            --muted: #667085;
            --border: #e5e7eb;
            --primary: #3457d5;
            --primary-hover: #2746b8;
            --success-bg: #ecfdf3;
            --success-text: #067647;
            --error-bg: #fef3f2;
            --error-text: #b42318;
            --radius: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--background);
            color: var(--text);
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .header-content {
            max-width: 1080px;
            margin: auto;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .brand {
            font-size: 21px;
            font-weight: 750;
            letter-spacing: -0.7px;
        }

        .brand span {
            color: var(--primary);
        }

        .header-label {
            color: var(--muted);
            font-size: 13px;
        }

        main {
            width: 100%;
            max-width: 1080px;
            margin: auto;
            padding: 56px 24px;
            flex: 1;
        }

        .hero {
            text-align: center;
            margin-bottom: 36px;
        }

        .hero h1 {
            margin: 0 0 12px;
            font-size: clamp(28px, 5vw, 42px);
            letter-spacing: -1.5px;
        }

        .hero p {
            color: var(--muted);
            max-width: 540px;
            margin: auto;
            line-height: 1.7;
        }

        .card {
            max-width: 440px;
            margin: auto;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: 0 8px 30px rgba(16, 24, 40, 0.04);
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 23px;
            letter-spacing: -0.5px;
        }

        .description {
            margin: 0 0 25px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .field {
            margin-bottom: 19px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 9px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 87, 213, 0.12);
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 9px;
            padding: 13px 16px;
            background: var(--primary);
            color: #ffffff;
            font-size: 15px;
            font-weight: 650;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: var(--primary-hover);
        }

        .alert {
            padding: 13px 15px;
            margin-bottom: 22px;
            border-radius: 9px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        .alert.error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .user-info {
            padding: 20px;
            margin-bottom: 22px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #fafbff;
        }

        .user-info strong {
            display: block;
            margin-bottom: 6px;
            font-size: 17px;
        }

        .user-info p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-top: 17px;
            color: var(--success-text);
            font-size: 13px;
            font-weight: 600;
        }

        .status::before {
            content: "";
            width: 8px;
            height: 8px;
            background: #12b76a;
            border-radius: 50%;
        }

        .security-note {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        footer {
            padding: 22px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        @media (max-width: 560px) {
            main {
                padding: 36px 16px;
            }

            .card {
                padding: 24px 20px;
            }

            .header-content {
                padding: 17px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="header-content">
        <div class="brand">
            Shield<span>PHP</span>
        </div>

        <div class="header-label">
            Basic PHP Example
        </div>
    </div>
</header>

<main>

    <section class="hero">
        <h1>Autenticación simple y segura.</h1>

        <p>
            Ejemplo básico de autenticación utilizando
            ShieldPHP, sesiones seguras y protección CSRF.
        </p>
    </section>

    <section class="card">

        <?php if ($message !== null): ?>
            <div
                class="alert <?= $messageType === 'success' ? 'success' : 'error' ?>"
                role="alert"
            >
                <?= escapeHtml($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($isAuthenticated): ?>

            <h2>¡Bienvenido!</h2>

            <p class="description">
                Tu identidad ha sido autenticada correctamente.
            </p>

            <div class="user-info">
                <strong>Sesión autenticada</strong>

                <p>
                    <?= escapeHtml($auth['email']) ?>
                </p>

                <div class="status">
                    Sesión activa
                </div>
            </div>

            <form method="POST" action="index.php">

                <input
                    type="hidden"
                    name="action"
                    value="logout"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapeHtml($csrfToken) ?>"
                >

                <button type="submit">
                    Cerrar sesión
                </button>

            </form>

        <?php else: ?>

            <h2>Iniciar sesión</h2>

            <p class="description">
                Introduce tus credenciales para acceder a tu cuenta.
            </p>

            <form
                method="POST"
                action="index.php"
                autocomplete="on"
            >

                <input
                    type="hidden"
                    name="action"
                    value="login"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapeHtml($csrfToken) ?>"
                >

                <div class="field">

                    <label for="email">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="usuario@ejemplo.com"
                        autocomplete="username"
                        maxlength="254"
                        required
                    >

                </div>

                <div class="field">

                    <label for="password">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Tu contraseña"
                        autocomplete="current-password"
                        maxlength="4096"
                        required
                    >

                </div>

                <button type="submit">
                    Iniciar sesión
                </button>

            </form>

        <?php endif; ?>

        <div class="security-note">
            <strong>Protegido por ShieldPHP</strong><br>

            Las credenciales se procesan en el servidor.
            Las solicitudes POST utilizan protección CSRF
            y la sesión utiliza cookies HttpOnly y SameSite.
        </div>

    </section>

</main>

<footer>
    ShieldPHP &copy; <?= date('Y') ?>.
    Authentication made simple.
</footer>

</body>
</html>
