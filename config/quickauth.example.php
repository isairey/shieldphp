
<?php

declare(strict_types=1);

/**
 * ShieldPHP - Configuration Example
 *
 * Archivo de configuración de ejemplo.
 *
 * Copia este archivo a:
 *
 * config/quickauth.php
 *
 * Configura las variables de entorno antes de utilizar
 * los servicios de autenticación.
 *
 * IMPORTANTE:
 * No guardes credenciales reales en este archivo.
 */

$env = static function (
    string $name,
    mixed $default = null
): mixed {
    $value = getenv($name);

    return $value !== false ? $value : $default;
};

$envBool = static function (
    string $name,
    bool $default
) use ($env): bool {
    $value = $env($name);

    if ($value === null) {
        return $default;
    }

    $parsed = filter_var(
        $value,
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );

    return $parsed ?? $default;
};

$envInt = static function (
    string $name,
    int $default
) use ($env): int {
    $value = $env($name);

    if ($value === null || $value === '') {
        return $default;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_INT);

    return $parsed !== false ? $parsed : $default;
};

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
*/

$appName = (string) $env(
    'SHIELDPHP_APP_NAME',
    'ShieldPHP Application'
);

$environment = (string) $env(
    'SHIELDPHP_ENV',
    'production'
);

$debug = $envBool(
    'SHIELDPHP_DEBUG',
    false
);

/*
|--------------------------------------------------------------------------
| Cookie configuration
|--------------------------------------------------------------------------
*/

$secureCookies = $envBool(
    'SHIELDPHP_SECURE_COOKIES',
    true
);

$sameSite = (string) $env(
    'SHIELDPHP_SAME_SITE',
    'Lax'
);

if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
    throw new InvalidArgumentException(
        'SHIELDPHP_SAME_SITE must be Lax, Strict or None.'
    );
}

if ($sameSite === 'None' && !$secureCookies) {
    throw new InvalidArgumentException(
        'SameSite=None requires secure cookies.'
    );
}

/*
|--------------------------------------------------------------------------
| OAuth redirect URIs
|--------------------------------------------------------------------------
*/

$googleRedirectUri = (string) $env(
    'SHIELDPHP_GOOGLE_REDIRECT_URI',
    'https://example.com/auth/google/callback'
);

$facebookRedirectUri = (string) $env(
    'SHIELDPHP_FACEBOOK_REDIRECT_URI',
    'https://example.com/auth/facebook/callback'
);

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */

    'app' => [
        'name' => $appName,
        'environment' => $environment,
        'debug' => $debug,
        'base_url' => (string) $env(
            'SHIELDPHP_BASE_URL',
            'https://example.com'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Core ShieldPHP configuration
    |--------------------------------------------------------------------------
    |
    | Compatible with ShieldConfig.
    |
    */

    'shield' => [
        'app_name' => $appName,

        'access_token_ttl' => $envInt(
            'SHIELDPHP_ACCESS_TOKEN_TTL',
            900
        ),

        'refresh_token_ttl' => $envInt(
            'SHIELDPHP_REFRESH_TOKEN_TTL',
            604800
        ),

        'secure_cookies' => $secureCookies,

        'same_site' => $sameSite,
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT
    |--------------------------------------------------------------------------
    */

    'jwt' => [
        'algorithm' => (string) $env(
            'SHIELDPHP_JWT_ALGORITHM',
            'HS256'
        ),

        'signing_key' => (string) $env(
            'SHIELDPHP_JWT_SIGNING_KEY',
            ''
        ),

        'issuer' => (string) $env(
            'SHIELDPHP_JWT_ISSUER',
            'https://example.com'
        ),

        'audience' => (string) $env(
            'SHIELDPHP_JWT_AUDIENCE',
            'shieldphp-api'
        ),

        'access_ttl' => $envInt(
            'SHIELDPHP_ACCESS_TOKEN_TTL',
            900
        ),

        'refresh_ttl' => $envInt(
            'SHIELDPHP_REFRESH_TOKEN_TTL',
            604800
        ),

        'leeway' => $envInt(
            'SHIELDPHP_JWT_LEEWAY',
            0
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0
    |--------------------------------------------------------------------------
    */

    'oauth' => [

        'enabled' => $envBool(
            'SHIELDPHP_OAUTH_ENABLED',
            true
        ),

        'state_ttl' => $envInt(
            'SHIELDPHP_OAUTH_STATE_TTL',
            600
        ),

        'pkce_ttl' => $envInt(
            'SHIELDPHP_OAUTH_PKCE_TTL',
            300
        ),

        /*
         * Las URI deben coincidir exactamente con las registradas
         * en cada proveedor OAuth.
         */

        'redirect_uris' => [
            $googleRedirectUri,
            $facebookRedirectUri,
        ],

        'providers' => [

            /*
            | Google OAuth
            */

            'google' => [
                'enabled' => $envBool(
                    'SHIELDPHP_GOOGLE_ENABLED',
                    false
                ),

                'client_id' => (string) $env(
                    'SHIELDPHP_GOOGLE_CLIENT_ID',
                    ''
                ),

                'client_secret' => (string) $env(
                    'SHIELDPHP_GOOGLE_CLIENT_SECRET',
                    ''
                ),

                'redirect_uri' => $googleRedirectUri,

                'scopes' => [
                    'openid',
                    'email',
                    'profile',
                ],
            ],

            /*
            | Facebook Login
            */

            'facebook' => [
                'enabled' => $envBool(
                    'SHIELDPHP_FACEBOOK_ENABLED',
                    false
                ),

                'client_id' => (string) $env(
                    'SHIELDPHP_FACEBOOK_CLIENT_ID',
                    ''
                ),

                'client_secret' => (string) $env(
                    'SHIELDPHP_FACEBOOK_CLIENT_SECRET',
                    ''
                ),

                'redirect_uri' => $facebookRedirectUri,

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
    */

    'session' => [
        'session_name' => (string) $env(
            'SHIELDPHP_SESSION_NAME',
            'SHIELDPHP_SESSION'
        ),

        'cookie_lifetime' => $envInt(
            'SHIELDPHP_SESSION_LIFETIME',
            0
        ),

        'cookie_path' => '/',

        'cookie_domain' => (string) $env(
            'SHIELDPHP_COOKIE_DOMAIN',
            ''
        ),

        'cookie_secure' => $secureCookies,

        'cookie_httponly' => true,

        'cookie_samesite' => $sameSite,

        'use_strict_mode' => true,

        'use_only_cookies' => true,

        'use_trans_sid' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Password security
    |--------------------------------------------------------------------------
    */

    'password' => [
        'algorithm' => PASSWORD_DEFAULT,

        'minimum_length' => $envInt(
            'SHIELDPHP_PASSWORD_MIN_LENGTH',
            12
        ),

        'maximum_length' => $envInt(
            'SHIELDPHP_PASSWORD_MAX_LENGTH',
            1024
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    */

    'encryption' => [
        'enabled' => true,

        'algorithm' => 'sodium_crypto_secretbox',

        /*
         * Las claves deben obtenerse de un gestor de secretos
         * o variables de entorno.
         *
         * No incluyas claves reales en el repositorio.
         */

        'key' => (string) $env(
            'SHIELDPHP_ENCRYPTION_KEY',
            ''
        ),

        'key_id' => (string) $env(
            'SHIELDPHP_ENCRYPTION_KEY_ID',
            'primary'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'security' => [
        'csrf_protection' => true,

        'strict_transport_security' => true,

        'allow_insecure_http' => false,

        'validate_redirect_uri' => true,

        'prevent_session_fixation' => true,

        'regenerate_session_id' => true,

        'http_only_cookies' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => $envBool(
            'SHIELDPHP_LOGGING_ENABLED',
            true
        ),

        'level' => (string) $env(
            'SHIELDPHP_LOG_LEVEL',
            'warning'
        ),

        'log_sensitive_data' => false,
    ],
];
