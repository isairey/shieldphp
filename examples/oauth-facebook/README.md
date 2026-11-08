 
# ShieldPHP — Facebook OAuth Example

<p align="center">
  <strong>Facebook Login integration using ShieldPHP Core.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/ShieldPHP-OAuth-3457D5?style=for-the-badge" alt="ShieldPHP OAuth">
  <img src="https://img.shields.io/badge/Facebook-Login-1877F2?style=for-the-badge&logo=facebook&logoColor=white" alt="Facebook Login">
  <img src="https://img.shields.io/badge/OAuth-2.0-64748B?style=for-the-badge" alt="OAuth 2.0">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PKCE-S256-16A34A?style=for-the-badge" alt="PKCE S256">
  <img src="https://img.shields.io/badge/Security-CSRF%20Protection-16A34A?style=for-the-badge" alt="CSRF">
  <img src="https://img.shields.io/badge/Status-Example-orange?style=for-the-badge" alt="Example">
</p>

---

## Overview

**ShieldPHP Facebook OAuth Example** demonstrates how to integrate Facebook Login into a PHP application using the OAuth 2.0 infrastructure provided by ShieldPHP Core.

The example demonstrates the complete authorization workflow:

1. Initialize the Facebook OAuth provider.
2. Register the provider in `OAuthManager`.
3. Generate an authorization URL.
4. Redirect the user to Facebook.
5. Validate the OAuth `state`.
6. Retrieve and consume the PKCE verifier.
7. Exchange the authorization code for tokens.
8. Retrieve the Facebook user profile.
9. Process the authenticated identity.

The purpose of this example is to provide a reusable reference for developers integrating social authentication into their PHP applications.

## Features

- Facebook OAuth 2.0 integration.
- Centralized provider management through `OAuthManager`.
- Authorization URL generation.
- OAuth state validation.
- Single-use authorization states.
- PKCE with S256 challenge generation.
- Secure authorization code exchange.
- Access token handling.
- Facebook profile retrieval.
- User identity normalization.
- Optional email handling.
- Configurable callback URI.
- HTTP client abstraction.
- Authentication error handling.

## Architecture

The integration uses the OAuth infrastructure provided by ShieldPHP Core.

```text
                  USER
                    |
                    v
             PHP APPLICATION
                    |
                    v
               OAUTH MANAGER
                    |
                    v
             FACEBOOK PROVIDER
                    |
                    v
           GENERATE AUTHORIZATION URL
                    |
                    v
              FACEBOOK LOGIN
                    |
                    v
              USER CONSENT
                    |
                    v
             FACEBOOK CALLBACK
                    |
                    v
              VALIDATE STATE
                    |
                    v
               VALIDATE PKCE
                    |
                    v
           EXCHANGE AUTHORIZATION CODE
                    |
                    v
              ACCESS TOKEN
                    |
                    v
              USER PROFILE
                    |
                    v
             APPLICATION USER
```

## Requirements

Before running the example, make sure you have:

- PHP 8.2 or newer.
- Composer 2.x.
- ShieldPHP Core installed.
- Required PHP extensions:
  - `json`
  - `openssl`
  - `sodium`
- A Facebook developer application.
- A configured OAuth callback URL.
- HTTPS for non-local development and production environments.

Check your PHP installation:

```bash
php -v
composer --version
php -m
```

## Project Structure

```text
oauth-facebook/
│
├── config/
│   └── facebook.example.php
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── callback.php
│   └── logout.php
│
├── bootstrap.php
├── .env.example
├── .gitignore
└── README.md
```

### Directory Responsibilities

| File | Responsibility |
|---|---|
| `bootstrap.php` | Initializes ShieldPHP and OAuth dependencies. |
| `config/facebook.example.php` | Example Facebook configuration. |
| `public/index.php` | Main application page. |
| `public/login.php` | Starts the Facebook authorization flow. |
| `public/callback.php` | Processes the OAuth callback. |
| `public/logout.php` | Ends the local authenticated session. |
| `.env.example` | Documents required environment variables. |

## 1. Create a Facebook Application

To use Facebook Login, create an application in the Meta developer platform.

Open:

https://developers.facebook.com/

Configure the application and enable the appropriate Facebook Login product.

You will need:

- App ID.
- App Secret.
- Valid OAuth redirect URI.
- Permissions required by your application.

Keep your App Secret private. It must never be exposed in frontend JavaScript, HTML or public repositories.

## 2. Configure OAuth Redirect URI

Choose the callback URL used by the application.

Example:

```text
http://localhost:8000/callback.php
```

For production:

```text
https://example.com/auth/facebook/callback
```

The callback URI must match the URI configured in the Facebook developer application and the URI registered in ShieldPHP.

Avoid dynamically accepting arbitrary redirect destinations supplied by users.

## 3. Install Dependencies

From the repository root:

```bash
composer install
```

If ShieldPHP Core has its own Composer configuration:

```bash
cd packages/php-core
composer install
```

Make sure the application can load the Core package through Composer autoloading.

## 4. Environment Configuration

Create an `.env` file using `.env.example` as a template.

```dotenv
# ============================================================
# SHIELDPHP FACEBOOK OAUTH
# ============================================================

SHIELDPHP_ENV=development
SHIELDPHP_DEBUG=false

# Facebook application credentials
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=

# OAuth callback
FACEBOOK_REDIRECT_URI=http://localhost:8000/callback.php

# OAuth configuration
FACEBOOK_SCOPES=email,public_profile

# OAuth security
OAUTH_STATE_TTL=600
OAUTH_PKCE_TTL=300

# Application
APP_BASE_URL=http://localhost:8000
APP_SESSION_NAME=SHIELDPHP_FACEBOOK
```

Do not commit the real `.env` file.

## 5. Configure FacebookProvider

ShieldPHP includes a Facebook provider that implements the OAuth provider contract.

Its responsibility is to handle the Facebook-specific authorization and profile operations while leaving general OAuth state management and PKCE handling to the shared infrastructure.

Example initialization:

```php
<?php

declare(strict_types=1);

use ShieldPHP\Core\OAuth\OAuthManager;
use ShieldPHP\Core\OAuth\OAuthClient;
use ShieldPHP\Core\OAuth\Providers\FacebookProvider;

// These dependencies must be initialized by the application.
$oauthClient = new OAuthClient(
    $httpClient,
    $requestFactory,
    $streamFactory,
    [
        'graph.facebook.com',
    ]
);

$facebookProvider = new FacebookProvider(
    $oauthClient,
    getenv('FACEBOOK_CLIENT_ID'),
    getenv('FACEBOOK_CLIENT_SECRET')
);

// Register the provider in OAuthManager.
$oauthManager->registerProvider($facebookProvider);
```

The HTTP client and PSR-7 factories must be configured according to the dependencies installed by ShieldPHP Core.

The provider's HTTP client must restrict outgoing requests to the official hosts required by the integration.

## 6. Start Facebook Authentication

Use `OAuthManager` to generate the authorization URL.

```php
<?php

declare(strict_types=1);

$authorizationUrl = $oauthManager->getAuthorizationUrl(
    'facebook'
);

header(
    'Location: ' . $authorizationUrl,
    true,
    302
);

exit;
```

ShieldPHP handles the generation and storage of the OAuth state and PKCE verifier through its configured managers.

The application should not construct the authorization URL manually when using the provider integration.

## 7. Handle the OAuth Callback

After the user completes authorization, Facebook redirects the browser to the configured callback URI.

The callback handler should delegate processing to `OAuthManager`.

```php
<?php

declare(strict_types=1);

try {
    $profile = $oauthManager->handleCallback(
        'facebook',
        $_GET
    );

    /*
     * The profile is now available to the application.
     *
     * Create or locate the corresponding local user.
     * Establish your application's authenticated session.
     */

    $facebookId = $profile['id'] ?? null;

    $email = $profile['email'] ?? null;

    $name = $profile['name'] ?? null;

    if (!is_string($facebookId) || $facebookId === '') {
        throw new RuntimeException(
            'Facebook did not return a valid user identifier.'
        );
    }

    // Continue with local user registration or login.

} catch (\Throwable $exception) {
    error_log(
        '[ShieldPHP Facebook] OAuth callback failed: '
        . $exception->getMessage()
    );

    http_response_code(401);

    exit('Facebook authentication could not be completed.');
}
```

### Callback Security

The callback flow must ensure:

- The received `state` matches the previously stored state.
- The state is consumed only once.
- The PKCE verifier belongs to the original authorization request.
- The authorization code is exchanged only after successful validation.
- Provider errors are handled without exposing sensitive data.
- The application creates a local authenticated session only after successful identity validation.

## 8. Facebook User Profile

The provider retrieves and normalizes the available profile information.

Example profile:

```json
{
  "id": "123456789012345",
  "name": "Example User",
  "first_name": "Example",
  "last_name": "User",
  "email": "user@example.com",
  "picture": "https://example.com/profile-picture.jpg"
}
```

| Field | Description |
|---|---|
| `id` | Facebook user identifier. |
| `name` | Display name. |
| `first_name` | First name, when provided. |
| `last_name` | Last name, when provided. |
| `email` | Email address, when available. |
| `picture` | Profile picture URL, when available. |

### Important

The email address is optional. Facebook might not return it in every situation.

Do not use the email address as the only identity key. Use the provider identifier together with the provider name to identify the external account.

For example:

```text
provider: facebook
provider_user_id: 123456789012345
```

The application should maintain its own local user identity and link the Facebook account to that identity.

## 9. Logout

Facebook OAuth authentication and your application's local session are separate concerns.

Logging out of the application should terminate its local authenticated session.

Example:

```php
<?php

declare(strict_types=1);

session_start();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]
    );
}

session_destroy();

header('Location: index.php', true, 303);

exit;
```

This only terminates the local application session. It does not necessarily log the user out of Facebook itself.

## 10. Error Handling

The application should handle OAuth failures explicitly.

| Error | Expected behavior |
|---|---|
| Invalid state | Reject the callback. |
| Replayed state | Reject the callback. |
| Missing authorization code | Reject the callback. |
| User denied authorization | Handle provider error. |
| Invalid PKCE verifier | Reject token exchange. |
| Invalid client credentials | Report authentication failure. |
| Token exchange failure | Do not establish a local session. |
| Profile retrieval failure | Do not establish a local session. |
| Missing Facebook user ID | Reject the profile. |
| Missing email | Allow processing with a null email if the application supports it. |

Never log access tokens, refresh tokens, App Secrets or authorization codes.

## 11. Security Considerations

ShieldPHP is designed to centralize security-sensitive OAuth functionality.

This example should preserve the following protections:

### OAuth State

Use an unpredictable, cryptographically secure state value. Validate it against the stored value and consume it once.

### PKCE

Use the S256 challenge method and keep the original verifier on the server.

### HTTPS

Use HTTPS for production callback URLs and protect session cookies with the Secure attribute.

### Client Secrets

Keep Facebook credentials exclusively on the server.

### Redirect URI Validation

Validate the full callback URI against an explicit allowlist. Do not use partial hostname matching.

### Access Tokens

Avoid exposing tokens to the browser or logging them. Store them only when required by the application's functionality and protect them at rest.

### Account Linking

Do not automatically link accounts solely because two providers return the same email address. Apply an explicit account-linking and verification policy.

## 12. Running the Example

From the example directory:

```bash
cd examples/oauth-facebook
```

Start the PHP development server:

```bash
php -S localhost:8000 -t public
```

Open:

```text
http://localhost:8000
```

Make sure the configured Facebook callback URI matches the address being used by your local application.

The PHP development server is intended for local testing, not production deployment.

## 13. Testing

The Facebook provider tests are located in:

```text
packages/php-core/tests/Unit/OAuth/Providers/FacebookProviderTest.php
```

Run the provider tests:

```bash
cd packages/php-core

vendor/bin/phpunit tests/Unit/OAuth/Providers/FacebookProviderTest.php
```

Run the OAuth unit tests:

```bash
vendor/bin/phpunit tests/Unit/OAuth
```

The OAuth integration tests should also cover the full authorization flow, including:

- Authorization URL generation.
- State validation.
- PKCE verification.
- Authorization code exchange.
- Facebook profile retrieval.
- Invalid callback handling.
- State replay prevention.
- Provider error handling.

## 14. Extending the Example

Once Facebook Login is working, you can extend the application with:

- Local user registration.
- Automatic account lookup.
- Social account linking.
- Multiple OAuth providers.
- Session-based authentication.
- JWT issuance after successful login.
- Role and permission assignment.
- User profile synchronization.
- Account disconnection.
- Audit logs for authentication events.

The same OAuth architecture can be reused with other providers supported by ShieldPHP.

## Contributing

Contributions are welcome.

1. Fork the repository.
2. Create a feature branch.
3. Implement your changes.
4. Add or update tests.
5. Execute the test suite.
6. Submit a Pull Request.

## License

This example is part of the ShieldPHP project.

Refer to the repository's root `LICENSE` file for licensing terms.

---

<p align="center">
  <strong>ShieldPHP</strong>
  <br>
  Secure authentication for modern PHP applications.
  <br><br>
  Built with PHP and OAuth 2.0.
</p>
