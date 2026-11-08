
<?php

declare(strict_types=1);

/**
 * ShieldPHP - Laravel Integration
 *
 * Configuración principal de la integración con Laravel.
 *
 * Publicación sugerida:
 *
 * php artisan vendor:publish --tag=shieldphp-config
 *
 * Archivo publicado:
 *
 * config/quickauth.php
 *
 * IMPORTANTE:
 * - No guardar secretos directamente en este archivo.
 * - Utilizar variables de entorno.
 * - No utilizar este archivo para modificar directamente
 *   la configuración global de Laravel.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    |
    | Configuración general de ShieldPHP dentro de Laravel.
    |
    */

    'app' => [

        'name' => env(
            'SHIELDPHP_APP_NAME',
            env('APP_NAME', 'ShieldPHP')
        ),

        'environment' => env(
            'SHIELDPHP_ENV',
            env('APP_ENV', 'production')
        ),

        'debug' => env(
            'SHIELDPHP_DEBUG',
            env('APP_DEBUG', false)
        ),

        'base_url' => env(
            'SHIELDPHP_BASE_URL',
            env('APP_URL', 'http://localhost')
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | ShieldPHP Core
    |--------------------------------------------------------------------------
    |
    | Configuración compatible conceptualmente con ShieldConfig.
    |
    */

    'shield' => [

        'app_name' => env(
            'SHIELDPHP_APP_NAME',
            env('APP_NAME', 'ShieldPHP')
        ),

        'access_token_ttl' => (int) env(
            'SHIELDPHP_ACCESS_TOKEN_TTL',
            900
        ),

        'refresh_token_ttl' => (int) env(
            'SHIELDPHP_REFRESH_TOKEN_TTL',
            604800
        ),

        'secure_cookies' => env(
            'SHIELDPHP_SECURE_COOKIES',
            true
        ),

        'same_site' => env(
            'SHIELDPHP_SAME_SITE',
            'Lax'
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Authentication
    |--------------------------------------------------------------------------
    |
    | Integración con el sistema de autenticación de Laravel.
    |
    | El guard y el provider se resuelven desde config/auth.php.
    |
    */

    'auth' => [

        'guard' => env(
            'SHIELDPHP_AUTH_GUARD',
            'web'
        ),

        'provider' => env(
            'SHIELDPHP_AUTH_PROVIDER',
            'users'
        ),

        'default_guard' => env(
            'SHIELDPHP_DEFAULT_GUARD',
            'web'
        ),

        'sync_user' => env(
            'SHIELDPHP_SYNC_USER',
            false
        ),

        'regenerate_session_on_login' => true,

        'invalidate_session_on_logout' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | JWT
    |--------------------------------------------------------------------------
    |
    | Configuración de tokens JSON Web Token.
    |
    | signing_key debe ser una clave secreta con suficiente
    | entropía y longitud para el algoritmo seleccionado.
    |
    */

    'jwt' => [

        'enabled' => env(
            'SHIELDPHP_JWT_ENABLED',
            true
        ),

        'algorithm' => env(
            'SHIELDPHP_JWT_ALGORITHM',
            'HS256'
        ),

        'signing_key' => env(
            'SHIELDPHP_JWT_SIGNING_KEY',
            ''
        ),

        'issuer' => env(
            'SHIELDPHP_JWT_ISSUER',
            env('APP_URL', 'http://localhost')
        ),

        'audience' => env(
            'SHIELDPHP_JWT_AUDIENCE',
            'shieldphp-api'
        ),

        'access_ttl' => (int) env(
            'SHIELDPHP_ACCESS_TOKEN_TTL',
            900
        ),

        'refresh_ttl' => (int) env(
            'SHIELDPHP_REFRESH_TOKEN_TTL',
            604800
        ),

        'leeway' => (int) env(
            'SHIELDPHP_JWT_LEEWAY',
            0
        ),

        'require_jti' => true,

        'validate_issuer' => true,

        'validate_audience' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0
    |--------------------------------------------------------------------------
    |
    | Configuración centralizada de proveedores externos.
    |
    */

    'oauth' => [

        'enabled' => env(
            'SHIELDPHP_OAUTH_ENABLED',
            true
        ),

        'state_ttl' => (int) env(
            'SHIELDPHP_OAUTH_STATE_TTL',
            600
        ),

        'pkce_ttl' => (int) env(
            'SHIELDPHP_OAUTH_PKCE_TTL',
            300
        ),

        'use_pkce' => true,

        'pkce_method' => 'S256',

        'validate_state' => true,

        'single_use_state' => true,

        /*
         * Todas las URI deben estar registradas explícitamente.
         */

        'redirect_uris' => array_values(
            array_filter([
                env('SHIELDPHP_GOOGLE_REDIRECT_URI'),
                env('SHIELDPHP_FACEBOOK_REDIRECT_URI'),
            ])
        ),

        /*
         * Hosts autorizados para peticiones HTTP OAuth.
         */

        'allowed_hosts' => [
            'oauth2.googleapis.com',
            'openidconnect.googleapis.com',
            'graph.facebook.com',
            'www.facebook.com',
        ],

        /*
         * Google OAuth 2.0 / OpenID Connect.
         */

        'providers' => [

            'google' => [

                'enabled' => env(
                    'SHIELDPHP_GOOGLE_ENABLED',
                    false
                ),

                'client_id' => env(
                    'SHIELDPHP_GOOGLE_CLIENT_ID',
                    ''
                ),

                'client_secret' => env(
                    'SHIELDPHP_GOOGLE_CLIENT_SECRET',
                    ''
                ),

                'redirect_uri' => env(
                    'SHIELDPHP_GOOGLE_REDIRECT_URI',
                    ''
                ),

                'scopes' => [
                    'openid',
                    'email',
                    'profile',
                ],

            ],

            /*
             * Facebook Login.
             */

            'facebook' => [

                'enabled' => env(
                    'SHIELDPHP_FACEBOOK_ENABLED',
                    false
                ),

                'client_id' => env(
                    'SHIELDPHP_FACEBOOK_CLIENT_ID',
                    ''
                ),

                'client_secret' => env(
                    'SHIELDPHP_FACEBOOK_CLIENT_SECRET',
                    ''
                ),

                'redirect_uri' => env(
                    'SHIELDPHP_FACEBOOK_REDIRECT_URI',
                    ''
                ),

                'scopes' => [
                    'email',
                    'public_profile',
                ],

            ],

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Session
    |--------------------------------------------------------------------------
    |
    | Laravel conserva la responsabilidad del almacenamiento
    | y del ciclo de vida de la sesión.
    |
    | ShieldPHP puede integrarse utilizando los mecanismos
    | de sesión proporcionados por Laravel.
    |
    */

    'session' => [

        'use_laravel_session' => true,

        'regenerate_on_login' => true,

        'invalidate_on_logout' => true,

        'regenerate_csrf_token' => true,

        'cookie' => [

            'name' => env(
                'SHIELDPHP_SESSION_COOKIE',
                env('SESSION_COOKIE', 'laravel_session')
            ),

            'secure' => env(
                'SHIELDPHP_SECURE_COOKIES',
                true
            ),

            'http_only' => true,

            'same_site' => env(
                'SHIELDPHP_SAME_SITE',
                'Lax'
            ),

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cookies
    |--------------------------------------------------------------------------
    */

    'cookies' => [

        'secure' => env(
            'SHIELDPHP_SECURE_COOKIES',
            true
        ),

        'http_only' => true,

        'same_site' => env(
            'SHIELDPHP_SAME_SITE',
            'Lax'
        ),

        'path' => '/',

        'domain' => env(
            'SHIELDPHP_COOKIE_DOMAIN',
            null
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | Password
    |--------------------------------------------------------------------------
    |
    | Políticas de contraseñas utilizadas por los componentes
    | de ShieldPHP.
    |
    */

    'password' => [

        'algorithm' => PASSWORD_DEFAULT,

        'minimum_length' => (int) env(
            'SHIELDPHP_PASSWORD_MIN_LENGTH',
            12
        ),

        'maximum_length' => (int) env(
            'SHIELDPHP_PASSWORD_MAX_LENGTH',
            1024
        ),

        'check_rehash' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Las claves deben obtenerse de un gestor de secretos.
    |
    | encryption_key debe estar en Base64 cuando se utilice
    | una integración que espere una clave codificada.
    |
    */

    'encryption' => [

        'enabled' => env(
            'SHIELDPHP_ENCRYPTION_ENABLED',
            true
        ),

        'algorithm' => 'sodium_crypto_secretbox',

        'key' => env(
            'SHIELDPHP_ENCRYPTION_KEY',
            ''
        ),

        'key_id' => env(
            'SHIELDPHP_ENCRYPTION_KEY_ID',
            'primary'
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | Key Management
    |--------------------------------------------------------------------------
    */

    'keys' => [

        'active_key_id' => env(
            'SHIELDPHP_ACTIVE_KEY_ID',
            'primary'
        ),

        'rotation_enabled' => env(
            'SHIELDPHP_KEY_ROTATION_ENABLED',
            false
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | Gestión de roles y permisos.
    |
    */

    'authorization' => [

        'enabled' => true,

        'default_role' => env(
            'SHIELDPHP_DEFAULT_ROLE',
            'USER'
        ),

        'cache_permissions' => true,

        'permission_cache_ttl' => (int) env(
            'SHIELDPHP_PERMISSION_CACHE_TTL',
            3600
        ),

    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Configuración de middleware para integraciones Laravel.
    |
    */

    'middleware' => [

        'enabled' => true,

        'auth' => 'auth',

        'guest' => 'guest',

        'role' => 'role',

        'permission' => 'permission',

        'jwt' => 'shield.jwt',

        'csrf' => 'web',

    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Configuración de rutas que podrá registrar el paquete.
    |
    */

    'routes' => [

        'enabled' => env(
            'SHIELDPHP_ROUTES_ENABLED',
            true
        ),

        'prefix' => env(
            'SHIELDPHP_ROUTE_PREFIX',
            'shield'
        ),

        'middleware' => [
            'web',
        ],

        'api_prefix' => env(
            'SHIELDPHP_API_PREFIX',
            'api/shield'
        ),

        'api_middleware' => [
            'api',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */

    'csrf' => [

        'enabled' => true,

        'use_laravel_csrf' => true,

        'token_length' => 64,

        'regenerate_on_login' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [

        'enabled' => true,

        'login' => [

            'attempts' => (int) env(
                'SHIELDPHP_LOGIN_MAX_ATTEMPTS',
                5
            ),

            'decay_seconds' => (int) env(
                'SHIELDPHP_LOGIN_DECAY_SECONDS',
                60
            ),

        ],

        'oauth' => [

            'attempts' => (int) env(
                'SHIELDPHP_OAUTH_MAX_ATTEMPTS',
                10
            ),

            'decay_seconds' => (int) env(
                'SHIELDPHP_OAUTH_DECAY_SECONDS',
                60
            ),

        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Token Revocation
    |--------------------------------------------------------------------------
    */

    'token_revocation' => [

        'enabled' => true,

        'store' => env(
            'SHIELDPHP_TOKEN_REVOCATION_STORE',
            env('CACHE_STORE', 'database')
        ),

        'check_on_validation' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [

        'enabled' => env(
            'SHIELDPHP_LOGGING_ENABLED',
            true
        ),

        'channel' => env(
            'SHIELDPHP_LOG_CHANNEL',
            env('LOG_CHANNEL', 'stack')
        ),

        'level' => env(
            'SHIELDPHP_LOG_LEVEL',
            'warning'
        ),

        'log_authentication_events' => true,

        'log_oauth_events' => true,

        'log_sensitive_data' => false,

        'log_access_tokens' => false,

        'log_refresh_tokens' => false,

        'log_passwords' => false,

    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'security' => [

        'force_https' => env(
            'SHIELDPHP_FORCE_HTTPS',
            true
        ),

        'prevent_session_fixation' => true,

        'validate_redirect_uri' => true,

        'reject_untrusted_redirects' => true,

        'require_verified_email' => env(
            'SHIELDPHP_REQUIRE_VERIFIED_EMAIL',
            false
        ),

        'prevent_user_enumeration' => true,

    ],

];
