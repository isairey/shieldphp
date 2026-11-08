
<?php

declare(strict_types=1);

namespace ShieldPHP\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use ShieldPHP\Core\Config\ShieldConfig;
use ShieldPHP\Core\JWT\JWTConfig;

/**
 * ShieldPHP - Laravel Service Provider.
 *
 * Registra e integra los servicios de ShieldPHP Core
 * dentro del contenedor de dependencias de Laravel.
 *
 * Responsabilidades:
 *
 * - Registrar la configuración del paquete.
 * - Publicar quickauth.php.
 * - Registrar servicios de ShieldPHP.
 * - Integrar JWTConfig y ShieldConfig.
 * - Registrar alias de middleware disponibles.
 * - Registrar rutas web y API.
 * - Respetar la configuración de Laravel.
 */
final class QuickAuthServiceProvider extends ServiceProvider
{
    /**
     * Nombre de la configuración del paquete.
     */
    private const CONFIG_KEY = 'quickauth';

    /**
     * Ruta del archivo de configuración.
     */
    private const CONFIG_FILE = 'config/quickauth.php';

    /**
     * Etiqueta para publicar la configuración.
     */
    private const CONFIG_TAG = 'shieldphp-config';

    /**
     * Registrar servicios en el contenedor de Laravel.
     */
    public function register(): void
    {
        $this->registerConfiguration();

        $this->registerCoreServices();
    }

    /**
     * Registrar configuración predeterminada del paquete.
     */
    private function registerConfiguration(): void
    {
        $configPath = $this->packagePath(
            self::CONFIG_FILE
        );

        if (is_file($configPath)) {
            $this->mergeConfigFrom(
                $configPath,
                self::CONFIG_KEY
            );
        }
    }

    /**
     * Registrar los servicios principales de ShieldPHP Core.
     */
    private function registerCoreServices(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ShieldConfig
        |--------------------------------------------------------------------------
        |
        | Configuración principal del núcleo ShieldPHP.
        |
        */

        $this->app->singleton(
            ShieldConfig::class,
            function ($app): ShieldConfig {
                $settings = $app['config']->get(
                    'quickauth.shield',
                    []
                );

                if (!is_array($settings)) {
                    throw new RuntimeException(
                        'ShieldPHP configuration must be an array.'
                    );
                }

                return new ShieldConfig($settings);
            }
        );

        $this->app->alias(
            ShieldConfig::class,
            'shieldphp.config'
        );

        /*
        |--------------------------------------------------------------------------
        | JWTConfig
        |--------------------------------------------------------------------------
        |
        | La instancia se construye de forma diferida.
        | La clave solamente se necesita cuando se resuelve
        | el servicio JWT.
        |
        */

        $this->app->singleton(
            JWTConfig::class,
            function ($app): JWTConfig {
                $settings = $app['config']->get(
                    'quickauth.jwt',
                    []
                );

                if (!is_array($settings)) {
                    throw new RuntimeException(
                        'ShieldPHP JWT configuration must be an array.'
                    );
                }

                if (!($settings['enabled'] ?? true)) {
                    throw new RuntimeException(
                        'ShieldPHP JWT is disabled in the configuration.'
                    );
                }

                $signingKey = $settings['signing_key'] ?? '';

                if (
                    !is_string($signingKey)
                    || $signingKey === ''
                ) {
                    throw new RuntimeException(
                        'SHIELDPHP_JWT_SIGNING_KEY must be configured.'
                    );
                }

                $issuer = $settings['issuer'] ?? '';

                if (
                    !is_string($issuer)
                    || $issuer === ''
                ) {
                    throw new RuntimeException(
                        'A valid JWT issuer must be configured.'
                    );
                }

                $audience = $settings['audience'] ?? 'shieldphp-api';

                if (
                    !is_string($audience)
                    && !is_array($audience)
                ) {
                    throw new RuntimeException(
                        'JWT audience must be a string or an array.'
                    );
                }

                return new JWTConfig(
                    $signingKey,
                    $issuer,
                    $audience,
                    (string) ($settings['algorithm'] ?? 'HS256'),
                    (int) ($settings['access_ttl'] ?? 900),
                    (int) ($settings['refresh_ttl'] ?? 604800),
                    (int) ($settings['leeway'] ?? 0)
                );
            }
        );

        $this->app->alias(
            JWTConfig::class,
            'shieldphp.jwt.config'
        );
    }

    /**
     * Ejecutar operaciones de inicialización del paquete.
     */
    public function boot(): void
    {
        $this->publishConfiguration();

        $this->registerMiddlewareAliases();

        $this->registerRoutes();
    }

    /**
     * Publicar archivos de configuración.
     */
    private function publishConfiguration(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $configPath = $this->packagePath(
            self::CONFIG_FILE
        );

        if (!is_file($configPath)) {
            return;
        }

        $this->publishes([
            $configPath => config_path('quickauth.php'),
        ], self::CONFIG_TAG);
    }

    /**
     * Registrar los alias de middleware de ShieldPHP.
     *
     * Los middleware se registran únicamente cuando sus
     * clases están disponibles mediante Composer.
     *
     * Los middleware auth y guest se mantienen bajo
     * la administración nativa de Laravel.
     */
    private function registerMiddlewareAliases(): void
    {
        if (!$this->app->bound('router')) {
            return;
        }

        $router = $this->app['router'];

        $configuredAliases = config(
            'quickauth.middleware',
            []
        );

        $configuredAliases = is_array($configuredAliases)
            ? $configuredAliases
            : [];

        $middleware = [
            $configuredAliases['jwt'] ?? 'shield.jwt'
                => 'ShieldPHP\\Laravel\\Middleware\\JwtMiddleware',

            $configuredAliases['role'] ?? 'shield.role'
                => 'ShieldPHP\\Laravel\\Middleware\\RoleMiddleware',

            $configuredAliases['permission'] ?? 'shield.permission'
                => 'ShieldPHP\\Laravel\\Middleware\\PermissionMiddleware',
        ];

        foreach ($middleware as $alias => $class) {
            if (
                is_string($alias)
                && $alias !== ''
                && class_exists($class)
            ) {
                $router->aliasMiddleware(
                    $alias,
                    $class
                );
            }
        }
    }

    /**
     * Registrar las rutas web y API del paquete.
     */
    private function registerRoutes(): void
    {
        if (!config('quickauth.routes.enabled', true)) {
            return;
        }

        $routesDirectory = $this->packagePath('routes');

        /*
        |--------------------------------------------------------------------------
        | Web routes
        |--------------------------------------------------------------------------
        */

        $webRoutes = $routesDirectory . '/web.php';

        if (is_file($webRoutes)) {
            $webPrefix = (string) config(
                'quickauth.routes.prefix',
                'shield'
            );

            $webMiddleware = config(
                'quickauth.routes.middleware',
                ['web']
            );

            Route::middleware((array) $webMiddleware)
                ->prefix(trim($webPrefix, '/'))
                ->group($webRoutes);
        }

        /*
        |--------------------------------------------------------------------------
        | API routes
        |--------------------------------------------------------------------------
        */

        $apiRoutes = $routesDirectory . '/api.php';

        if (is_file($apiRoutes)) {
            $apiPrefix = (string) config(
                'quickauth.routes.api_prefix',
                'api/shield'
            );

            $apiMiddleware = config(
                'quickauth.routes.api_middleware',
                ['api']
            );

            Route::middleware((array) $apiMiddleware)
                ->prefix(trim($apiPrefix, '/'))
                ->group($apiRoutes);
        }
    }

    /**
     * Obtener la ruta absoluta de un recurso del paquete.
     */
    private function packagePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__);

        if ($path === '') {
            return $basePath;
        }

        return $basePath . DIRECTORY_SEPARATOR . $path;
    }
}
