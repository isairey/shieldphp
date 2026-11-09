 
# ShieldPHP — Laravel Integration

<p align="center">
  <strong>Secure authentication integration for Laravel applications.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/ShieldPHP-Integration-3457D5?style=for-the-badge" alt="ShieldPHP">
  <img src="https://img.shields.io/badge/Laravel-Integration-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Architecture-Modular-64748B?style=for-the-badge" alt="Modular architecture">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/JWT-Authentication-16A34A?style=for-the-badge" alt="JWT">
  <img src="https://img.shields.io/badge/OAuth-2.0-059669?style=for-the-badge" alt="OAuth 2.0">
  <img src="https://img.shields.io/badge/Security-CSRF%20%7C%20PKCE-orange?style=for-the-badge" alt="Security">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="MIT">
</p>

---

## Overview

**ShieldPHP Laravel Integration** is an adapter that connects ShieldPHP Core with Laravel applications.

Its purpose is to make the authentication and security capabilities of ShieldPHP available through Laravel's service container, service providers, middleware, configuration system and routing infrastructure.

The integration follows a modular architecture: ShieldPHP Core remains independent of Laravel, while this package provides the framework-specific implementation.

### Main objectives

- Integrate ShieldPHP Core into Laravel.
- Register services through Laravel's dependency injection container.
- Publish and manage package configuration.
- Provide a foundation for JWT authentication.
- Integrate OAuth 2.0 providers.
- Support role-based and permission-based authorization.
- Reuse Laravel's native session management.
- Integrate with Laravel middleware and routing.
- Maintain separation between framework-independent and framework-specific code.

## Features

The integration is designed around the following capabilities:

| Feature | Description |
|---|---|
| Service Provider | Registers ShieldPHP services in Laravel. |
| Configuration | Publishes `config/quickauth.php`. |
| Dependency Injection | Exposes Core configuration through the container. |
| JWT | Integrates the Core JWT configuration. |
| OAuth 2.0 | Foundation for external authentication providers. |
| Middleware | Provides registration points for authentication and authorization middleware. |
| Sessions | Reuses Laravel's session infrastructure. |
| CSRF | Integrates with Laravel's native CSRF protection. |
| Authorization | Foundation for roles and permissions. |
| Logging | Uses Laravel's logging configuration. |
| Rate Limiting | Configuration for authentication request limits. |

The actual availability of each capability depends on its corresponding adapter and middleware implementation.

## Architecture

ShieldPHP uses a layered architecture to keep the authentication engine independent of Laravel.

```text
              LARAVEL APPLICATION
                       |
                       v
             SHIELDPHP LARAVEL
                       |
          +------------+------------+
          |            |            |
          v            v            v
    Service Provider Middleware   Routes
          |            |            |
          +------------+------------+
                       |
                       v
                SERVICE CONTAINER
                       |
                       v
                 SHIELDPHP CORE
                       |
          +------------+------------+
          |            |            |
          v            v            v
       AUTH           JWT          OAUTH
          |            |            |
          v            v            v
    UserProvider   JWTManager   OAuthManager
          |            |            |
          +------------+------------+
                       |
                       v
                APPLICATION SERVICES
```

### Design principles

**Framework independence**

ShieldPHP Core does not need Laravel to execute its authentication logic.

**Dependency injection**

Laravel manages the lifecycle of registered services through its container.

**Separation of responsibilities**

The integration handles Laravel-specific behavior, while Core handles reusable authentication and security logic.

**Extensibility**

Additional guards, middleware, providers and integrations can be added without coupling the Core package to the framework.

## Requirements

Before installing the integration, make sure you have:

- PHP 8.2 or newer.
- Composer 2.x.
- A Laravel application.
- ShieldPHP Core installed.
- Required PHP extensions:
  - `json`
  - `openssl`
  - `sodium`

Check the environment:

```bash
php -v
composer --version
php artisan --version
php -m
```

The precise Laravel and Illuminate component version constraints must match the versions declared in this integration's `composer.json`.

## Project Structure

```text
integrations/
└── laravel/
    │
    ├── config/
    │   └── quickauth.php
    │
    ├── src/
    │   ├── QuickAuthServiceProvider.php
    │   │
    │   ├── Auth/
    │   │
    │   ├── Guards/
    │   │
    │   ├── Middleware/
    │   │
    │   ├── Facades/
    │   │
    │   ├── Providers/
    │   │
    │   └── Exceptions/
    │
    ├── routes/
    │   ├── web.php
    │   └── api.php
    │
    ├── tests/
    │   ├── Unit/
    │   ├── Integration/
    │   └── Feature/
    │
    ├── composer.json
    └── README.md
```

### Directory responsibilities

| Directory or file | Responsibility |
|---|---|
| `config/` | Package configuration. |
| `src/QuickAuthServiceProvider.php` | Registers the integration. |
| `src/Auth/` | Laravel authentication adapters. |
| `src/Guards/` | Custom authentication guards. |
| `src/Middleware/` | Authentication and authorization middleware. |
| `src/Facades/` | Optional Laravel facade interfaces. |
| `src/Providers/` | Laravel-specific service providers and adapters. |
| `src/Exceptions/` | Integration-specific exceptions. |
| `routes/` | Optional web and API routes. |
| `tests/` | Integration tests. |

## Installation

### 1. Install ShieldPHP Core

Make sure ShieldPHP Core is available through Composer.

From the monorepo root:

```bash
composer install
```

If Core is maintained as a separate Composer package, verify that its package name and autoload configuration are registered correctly.

### 2. Add the Laravel integration

For local development inside the ShieldPHP monorepo, you can register the integration as a Composer path repository.

In the Laravel application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../shieldphp/integrations/laravel",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "shieldphp/laravel": "@dev"
    }
}
```

Adjust the repository path and package name to match the actual location and `name` declared in the integration's `composer.json`.

Install the package:

```bash
composer update
```

### 3. Register the Service Provider

The package should support Laravel package discovery through Composer.

Expected provider declaration:

```json
{
    "extra": {
        "laravel": {
            "providers": [
                "ShieldPHP\\Laravel\\QuickAuthServiceProvider"
            ]
        }
    }
}
```

If automatic discovery is not enabled, register the provider manually.

For Laravel 11 and later, add it to `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,

    ShieldPHP\Laravel\QuickAuthServiceProvider::class,
];
```

For Laravel 10, register it in `config/app.php` under `providers`.

## Configuration

ShieldPHP Laravel uses a dedicated configuration file.

```text
integrations/laravel/config/quickauth.php
```

The configuration centralizes:

- Application settings.
- Authentication guards and providers.
- JWT.
- OAuth 2.0.
- Sessions and cookies.
- Password policies.
- Encryption.
- Key management.
- Authorization.
- Middleware.
- Routes.
- CSRF.
- Rate limiting.
- Token revocation.
- Logging.
- Security options.

### Publish the configuration

Once the Service Provider is registered:

```bash
php artisan vendor:publish --tag=shieldphp-config
```

The file will be published to:

```text
config/quickauth.php
```

Clear Laravel's configuration cache after making changes:

```bash
php artisan config:clear
php artisan config:cache
```

### Access configuration values

Use Laravel's native `config()` helper:

```php
<?php

use Illuminate\Support\Facades\Config;

// Application name.
$appName = config('quickauth.app.name');

// JWT configuration.
$algorithm = config('quickauth.jwt.algorithm');

$accessTokenTtl = config('quickauth.jwt.access_ttl');

// Google OAuth.
$googleClientId = config(
    'quickauth.oauth.providers.google.client_id'
);

// Session settings.
$secureCookies = config(
    'quickauth.session.cookie.secure'
);

// CSRF.
$csrfEnabled = config('quickauth.csrf.enabled');
```

## Environment Variables

Configure the following variables in your Laravel application's `.env` file.

```dotenv
# ============================================================
# SHIELDPHP - LARAVEL INTEGRATION
# ============================================================

SHIELDPHP_APP_NAME="ShieldPHP Laravel"
SHIELDPHP_ENV=production
SHIELDPHP_DEBUG=false
SHIELDPHP_BASE_URL="${APP_URL}"

# ============================================================
# AUTHENTICATION
# ============================================================

SHIELDPHP_AUTH_GUARD=web
SHIELDPHP_AUTH_PROVIDER=users
SHIELDPHP_DEFAULT_GUARD=web
SHIELDPHP_SYNC_USER=false

# ============================================================
# JWT
# ============================================================

SHIELDPHP_JWT_ENABLED=true
SHIELDPHP_JWT_ALGORITHM=HS256
SHIELDPHP_JWT_SIGNING_KEY=
SHIELDPHP_JWT_ISSUER="${APP_URL}"
SHIELDPHP_JWT_AUDIENCE=shieldphp-api
SHIELDPHP_ACCESS_TOKEN_TTL=900
SHIELDPHP_REFRESH_TOKEN_TTL=604800
SHIELDPHP_JWT_LEEWAY=0

# ============================================================
# OAUTH
# ============================================================

SHIELDPHP_OAUTH_ENABLED=true
SHIELDPHP_OAUTH_STATE_TTL=600
SHIELDPHP_OAUTH_PKCE_TTL=300

# GOOGLE
SHIELDPHP_GOOGLE_ENABLED=false
SHIELDPHP_GOOGLE_CLIENT_ID=
SHIELDPHP_GOOGLE_CLIENT_SECRET=
SHIELDPHP_GOOGLE_REDIRECT_URI=

# FACEBOOK
SHIELDPHP_FACEBOOK_ENABLED=false
SHIELDPHP_FACEBOOK_CLIENT_ID=
SHIELDPHP_FACEBOOK_CLIENT_SECRET=
SHIELDPHP_FACEBOOK_REDIRECT_URI=

# ============================================================
# SESSION AND COOKIES
# ============================================================

SHIELDPHP_SECURE_COOKIES=true
SHIELDPHP_SAME_SITE=Lax
SHIELDPHP_SESSION_COOKIE="${SESSION_COOKIE}"
SHIELDPHP_COOKIE_DOMAIN=

# ============================================================
# PASSWORD
# ============================================================

SHIELDPHP_PASSWORD_MIN_LENGTH=12
SHIELDPHP_PASSWORD_MAX_LENGTH=1024

# ============================================================
# ENCRYPTION
# ============================================================

SHIELDPHP_ENCRYPTION_ENABLED=true
SHIELDPHP_ENCRYPTION_KEY=
SHIELDPHP_ENCRYPTION_KEY_ID=primary

SHIELDPHP_ACTIVE_KEY_ID=primary
SHIELDPHP_KEY_ROTATION_ENABLED=false

# ============================================================
# AUTHORIZATION
# ============================================================

SHIELDPHP_DEFAULT_ROLE=USER
SHIELDPHP_PERMISSION_CACHE_TTL=3600

# ============================================================
# ROUTES
# ============================================================

SHIELDPHP_ROUTES_ENABLED=true
SHIELDPHP_ROUTE_PREFIX=shield
SHIELDPHP_API_PREFIX=api/shield

# ============================================================
# RATE LIMITING
# ============================================================

SHIELDPHP_LOGIN_MAX_ATTEMPTS=5
SHIELDPHP_LOGIN_DECAY_SECONDS=60

SHIELDPHP_OAUTH_MAX_ATTEMPTS=10
SHIELDPHP_OAUTH_DECAY_SECONDS=60

# ============================================================
# TOKEN REVOCATION
# ============================================================

SHIELDPHP_TOKEN_REVOCATION_STORE=database

# ============================================================
# SECURITY
# ============================================================

SHIELDPHP_FORCE_HTTPS=true
SHIELDPHP_REQUIRE_VERIFIED_EMAIL=false

# ============================================================
# LOGGING
# ============================================================

SHIELDPHP_LOGGING_ENABLED=true
SHIELDPHP_LOG_CHANNEL=stack
SHIELDPHP_LOG_LEVEL=warning
```

Never commit real secrets to version control.

## Service Provider

The main integration entry point is:

```text
src/QuickAuthServiceProvider.php
```

Namespace:

```php
ShieldPHP\Laravel\QuickAuthServiceProvider
```

The Service Provider extends Laravel's `ServiceProvider` and handles two main lifecycle stages.

### Register

The `register()` method:

- Loads the package configuration.
- Merges default settings.
- Registers `ShieldConfig`.
- Registers `JWTConfig`.
- Creates service container aliases.

Example:

```php
use ShieldPHP\Core\Config\ShieldConfig;
use ShieldPHP\Core\JWT\JWTConfig;

$shieldConfig = app(ShieldConfig::class);

$jwtConfig = app(JWTConfig::class);
```

### Boot

The `boot()` method:

- Publishes the package configuration.
- Registers available middleware aliases.
- Loads optional web routes.
- Loads optional API routes.

The provider only registers middleware classes that are available through Composer autoloading.

## Authentication Integration

Laravel provides its own authentication system based on guards and user providers.

ShieldPHP provides a framework-independent authentication engine.

The integration should connect both systems through adapters instead of replacing Laravel's authentication infrastructure.

### Authentication architecture

```text
             LARAVEL REQUEST
                    |
                    v
              AUTH GUARD
                    |
                    v
           LARAVEL AUTH ADAPTER
                    |
                    v
              SHIELDPHP CORE
                    |
                    v
               AUTH MANAGER
                    |
                    v
             USER PROVIDER
                    |
                    v
             USER IDENTITY
```

### Configuration

```php
'auth' => [
    'guard' => 'web',
    'provider' => 'users',
    'default_guard' => 'web',
    'sync_user' => false,
    'regenerate_session_on_login' => true,
    'invalidate_session_on_logout' => true,
],
```

Laravel remains responsible for resolving the configured guard and user provider.

The custom guard and adapter classes must be implemented before this configuration becomes operational.

## JWT Authentication

ShieldPHP Core provides JWT functionality through its JWT configuration and manager.

The Laravel integration can use this functionality to protect API endpoints and authenticate stateless requests.

### Configuration

```php
'jwt' => [
    'enabled' => true,
    'algorithm' => 'HS256',
    'signing_key' => env('SHIELDPHP_JWT_SIGNING_KEY'),
    'issuer' => env('SHIELDPHP_JWT_ISSUER'),
    'audience' => 'shieldphp-api',
    'access_ttl' => 900,
    'refresh_ttl' => 604800,
    'leeway' => 0,
    'require_jti' => true,
    'validate_issuer' => true,
    'validate_audience' => true,
],
```

### Resolve JWT configuration

```php
use ShieldPHP\Core\JWT\JWTConfig;

$jwtConfig = app(JWTConfig::class);
```

### Generate a signing key

For HS256, generate a cryptographically secure random value:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Store the generated value in:

```dotenv
SHIELDPHP_JWT_SIGNING_KEY=your-generated-secret
```

The service provider passes this value directly to `JWTConfig`. It must satisfy the minimum key length required by the selected algorithm.

## OAuth 2.0 Integration

ShieldPHP supports an extensible provider architecture for external authentication.

The Laravel adapter is designed to reuse the OAuth infrastructure in ShieldPHP Core.

### Supported providers in Core

| Provider | Integration |
|---|---|
| Google | `GoogleProvider` |
| Facebook | `FacebookProvider` |

### Google configuration

```php
'google' => [
    'enabled' => true,
    'client_id' => env('SHIELDPHP_GOOGLE_CLIENT_ID'),
    'client_secret' => env('SHIELDPHP_GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('SHIELDPHP_GOOGLE_REDIRECT_URI'),
    'scopes' => [
        'openid',
        'email',
        'profile',
    ],
],
```

### Facebook configuration

```php
'facebook' => [
    'enabled' => true,
    'client_id' => env('SHIELDPHP_FACEBOOK_CLIENT_ID'),
    'client_secret' => env('SHIELDPHP_FACEBOOK_CLIENT_SECRET'),
    'redirect_uri' => env('SHIELDPHP_FACEBOOK_REDIRECT_URI'),
    'scopes' => [
        'email',
        'public_profile',
    ],
],
```

### OAuth security

The Core OAuth implementation is designed around:

- Secure state generation.
- Single-use state validation.
- PKCE S256.
- HTTPS-only provider communication.
- Authorized HTTP hosts.
- Callback URI validation.
- Authentication error handling.

The Laravel integration must provide persistent or shared storage adapters when the application runs across multiple workers or servers.

## Sessions and Cookies

Laravel owns the session lifecycle in this integration.

ShieldPHP should not start a second PHP session or independently manage Laravel's session cookie.

### Configuration

```php
'session' => [
    'use_laravel_session' => true,
    'regenerate_on_login' => true,
    'invalidate_on_logout' => true,
    'regenerate_csrf_token' => true,
],
```

### Cookie configuration

```php
'cookies' => [
    'secure' => true,
    'http_only' => true,
    'same_site' => 'Lax',
    'path' => '/',
],
```

Use HTTPS in production and configure cookie attributes consistently with the Laravel application.

## Middleware

The integration defines configuration points for registering authentication and authorization middleware.

Expected aliases:

| Alias | Purpose |
|---|---|
| `shield.jwt` | JWT authentication. |
| `shield.role` | Role authorization. |
| `shield.permission` | Permission authorization. |

The `QuickAuthServiceProvider` registers these aliases when the corresponding middleware classes exist.

### Example usage

Once implemented and registered, middleware could be used in Laravel routes:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Authenticated user.',
        ]);
    });
});

Route::middleware(['shield.jwt'])->group(function () {
    Route::get('/api/profile', function () {
        return response()->json([
            'message' => 'JWT authenticated endpoint.',
        ]);
    });
});

Route::middleware(['shield.role:ADMIN'])->group(function () {
    Route::get('/admin', function () {
        return response()->json([
            'message' => 'Authorized administrator.',
        ]);
    });
});
```

These examples describe the intended middleware contract. The corresponding middleware implementations and parameter handling must be present before using them.

## Authorization

ShieldPHP Core provides role and permission management.

The Laravel integration can expose these capabilities through middleware, policies or authorization adapters.

Example configuration:

```php
'authorization' => [
    'enabled' => true,
    'default_role' => 'USER',
    'cache_permissions' => true,
    'permission_cache_ttl' => 3600,
],
```

Example role structure:

```text
USER
├── profile.read
└── profile.update

ADMIN
├── profile.read
├── profile.update
├── users.read
├── users.create
└── users.delete
```

Authorization must always be evaluated on the server. Hiding interface elements does not replace permission checks.

## CSRF Protection

The integration is designed to use Laravel's native CSRF middleware for session-based web requests.

```php
'csrf' => [
    'enabled' => true,
    'use_laravel_csrf' => true,
    'token_length' => 64,
    'regenerate_on_login' => true,
],
```

Do not disable Laravel's CSRF protection for state-changing browser requests simply because ShieldPHP is installed.

For stateless APIs, use an appropriate authentication and request-protection strategy instead of assuming that browser session CSRF rules automatically apply.

## Rate Limiting

The configuration provides settings for limiting authentication and OAuth requests.

```php
'rate_limit' => [
    'enabled' => true,

    'login' => [
        'attempts' => 5,
        'decay_seconds' => 60,
    ],

    'oauth' => [
        'attempts' => 10,
        'decay_seconds' => 60,
    ],
],
```

These values must be connected to Laravel's rate limiter or another shared throttling implementation.

For distributed deployments, use a shared cache store rather than process-local counters.

## Encryption and Key Management

ShieldPHP Core supports authenticated encryption using libsodium.

Example configuration:

```php
'encryption' => [
    'enabled' => true,
    'algorithm' => 'sodium_crypto_secretbox',
    'key' => env('SHIELDPHP_ENCRYPTION_KEY'),
    'key_id' => env('SHIELDPHP_ENCRYPTION_KEY_ID', 'primary'),
],
```

The encryption adapter must convert the configured key into the exact representation expected by the Core encryption service.

For a 32-byte binary key represented in Base64, validate and decode the value before passing it to the encryption constructor.

Do not reuse JWT signing keys as encryption keys.

Use a secret manager or protected environment configuration to store encryption material.

## Token Revocation

JWT revocation requires a storage implementation compatible with `TokenRevocationStoreInterface`.

The configuration provides a location for selecting the underlying storage:

```php
'token_revocation' => [
    'enabled' => true,
    'store' => 'database',
    'check_on_validation' => true,
],
```

Suitable production options include Redis or a persistent database adapter.

An in-memory store is appropriate for automated tests, not for applications that use multiple workers or need revocation to survive restarts.

## Logging

ShieldPHP should integrate with Laravel's logging infrastructure.

```php
'logging' => [
    'enabled' => true,
    'channel' => 'stack',
    'level' => 'warning',
    'log_authentication_events' => true,
    'log_oauth_events' => true,
    'log_sensitive_data' => false,
],
```

Never log:

- Passwords.
- JWT signing keys.
- OAuth client secrets.
- Access tokens.
- Refresh tokens.
- Authorization codes.
- Encryption keys.

## Routes

The Service Provider supports optional route registration.

Example configuration:

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'shield',
    'middleware' => [
        'web',
    ],
    'api_prefix' => 'api/shield',
    'api_middleware' => [
        'api',
    ],
],
```

Expected route files:

```text
integrations/laravel/routes/
├── web.php
└── api.php
```

The route files should contain routes relative to the configured prefixes.

## Testing

The integration should include unit tests, integration tests and feature tests.

Recommended organization:

```text
tests/
├── Unit/
│   ├── QuickAuthServiceProviderTest.php
│   ├── ConfigurationTest.php
│   ├── Middleware/
│   └── Guards/
│
├── Integration/
│   ├── AuthenticationIntegrationTest.php
│   ├── JwtIntegrationTest.php
│   └── OAuthIntegrationTest.php
│
└── Feature/
    ├── LoginTest.php
    ├── LogoutTest.php
    ├── JwtMiddlewareTest.php
    └── AuthorizationTest.php
```

Run the integration tests using PHPUnit:

```bash
vendor/bin/phpunit
```

For a package managed through Orchestra Testbench, use its configured PHPUnit bootstrap and Laravel application test environment.

Tests should verify:

- Service Provider registration.
- Configuration publishing.
- Container bindings.
- JWT configuration resolution.
- Middleware alias registration.
- Route registration.
- Session and CSRF integration.
- Authentication behavior.
- Authorization behavior.
- OAuth callback validation.
- Token revocation.

## Development

From the integration directory:

```bash
cd integrations/laravel
composer validate
composer dump-autoload
```

From the Laravel application:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
```

After publishing configuration:

```bash
php artisan vendor:publish --tag=shieldphp-config
```

## Security Best Practices

For production deployments:

1. Require HTTPS.
2. Keep all secrets outside version control.
3. Use sufficiently strong JWT signing keys.
4. Validate JWT issuer, audience, expiration and signature.
5. Protect session cookies with Secure, HttpOnly and appropriate SameSite settings.
6. Regenerate session identifiers after authentication.
7. Keep CSRF protection enabled for browser-based state-changing requests.
8. Validate OAuth state and PKCE.
9. Validate redirect URIs against an explicit allowlist.
10. Store token revocation records in shared persistent storage.
11. Apply rate limits to authentication endpoints.
12. Avoid exposing sensitive error details to clients.

## Limitations and Implementation Status

This directory is the Laravel integration layer. A configuration option or middleware alias does not, by itself, implement the corresponding behavior.

The current `QuickAuthServiceProvider` provides the foundation for:

- Configuration merging.
- Configuration publishing.
- `ShieldConfig` container registration.
- `JWTConfig` container registration.
- Conditional middleware alias registration.
- Conditional web and API route loading.

The following components require their corresponding implementations and tests before they can be considered operational:

- Laravel authentication guards and user providers.
- JWT middleware.
- Role and permission middleware.
- OAuth service adapters.
- Persistent session and PKCE stores.
- Token revocation storage adapter.
- Web and API route definitions.
- Encryption and key-management adapters.
- Rate-limiting integration.
- Automated Laravel feature tests.

This distinction keeps the documentation accurate and makes the integration easier to develop incrementally.

## Roadmap

- [x] Initial Laravel Service Provider.
- [x] Configuration merging.
- [x] Configuration publishing.
- [x] ShieldConfig container binding.
- [x] JWTConfig container binding.
- [x] Conditional middleware alias registration.
- [x] Optional route loading.
- [ ] Laravel authentication guard.
- [ ] Laravel user provider adapter.
- [ ] JWT authentication middleware.
- [ ] Role and permission middleware.
- [ ] OAuth service integration.
- [ ] Laravel session adapter.
- [ ] Token revocation adapter.
- [ ] Key management integration.
- [ ] Rate limiter integration.
- [ ] Complete unit and feature test suite.

## Contributing

Contributions are welcome.

1. Fork the ShieldPHP repository.
2. Create a feature branch.
3. Implement your changes.
4. Add automated tests.
5. Run the test suite.
6. Submit a Pull Request.

Keep framework-specific code inside `integrations/laravel` and reusable authentication logic inside ShieldPHP Core.

## License

This integration is part of the ShieldPHP project.

Refer to the root `LICENSE` file for the applicable licensing terms.

---

<p align="center">
  <strong>ShieldPHP</strong>
  <br>
  Secure authentication for modern PHP applications.
  <br><br>
  Built with PHP, Laravel and ShieldPHP Core.
</p>
