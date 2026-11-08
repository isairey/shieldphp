
# ShieldPHP — Google OAuth Example

<p align="center">
  <strong>Autenticación con Google OAuth 2.0 y OpenID Connect utilizando ShieldPHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/ShieldPHP-OAuth-3457D5?style=for-the-badge" alt="ShieldPHP OAuth">
  <img src="https://img.shields.io/badge/Google-Login-4285F4?style=for-the-badge&logo=google&logoColor=white" alt="Google Login">
  <img src="https://img.shields.io/badge/OpenID-Connect-64748B?style=for-the-badge" alt="OpenID Connect">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/OAuth-2.0-059669?style=for-the-badge" alt="OAuth 2.0">
  <img src="https://img.shields.io/badge/PKCE-S256-16A34A?style=for-the-badge" alt="PKCE S256">
  <img src="https://img.shields.io/badge/CSRF-Protected-16A34A?style=for-the-badge" alt="CSRF Protection">
  <img src="https://img.shields.io/badge/Status-Example-orange?style=for-the-badge" alt="Example">
</p>

---

## Descripción

**ShieldPHP Google OAuth Example** es una aplicación de ejemplo desarrollada en PHP que demuestra cómo integrar la autenticación de Google utilizando la infraestructura OAuth 2.0 y OpenID Connect de ShieldPHP Core.

El propósito es mostrar cómo una aplicación PHP puede permitir que sus usuarios inicien sesión mediante sus cuentas de Google sin tener que implementar manualmente todo el flujo de autorización.

La integración utiliza `GoogleProvider` y `OAuthManager` para centralizar las operaciones relacionadas con la autenticación externa.

### Características

- Inicio de sesión con Google.
- Integración con OAuth 2.0.
- Compatibilidad con OpenID Connect.
- Generación de URLs de autorización.
- Gestión segura del parámetro `state`.
- Prevención de reutilización del estado OAuth.
- Protección PKCE mediante S256.
- Intercambio del código de autorización por tokens.
- Recuperación del perfil de Google.
- Obtención de información del correo electrónico.
- Identificación del usuario mediante `sub`.
- Tratamiento de correos electrónicos no verificados.
- Gestión de errores OAuth.
- Separación entre identidad externa y usuario local.

## Arquitectura

El ejemplo utiliza los componentes de autenticación OAuth de ShieldPHP Core.

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
                 GOOGLE PROVIDER
                      |
                      v
             GENERATE AUTHORIZATION URL
                      |
                      v
                GOOGLE LOGIN
                      |
                      v
               USER AUTHORIZATION
                      |
                      v
                GOOGLE CALLBACK
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
                GOOGLE USERINFO
                      |
                      v
              NORMALIZED USER PROFILE
                      |
                      v
              LOCAL USER AUTHENTICATION
```

## Requisitos

Antes de ejecutar el ejemplo necesitas:

- PHP 8.2 o superior.
- Composer 2.x.
- ShieldPHP Core instalado.
- Extensiones PHP:
  - `json`
  - `openssl`
  - `sodium`
- Una cuenta de Google.
- Un proyecto configurado en Google Cloud.
- Credenciales OAuth 2.0 de tipo Web application.

Comprueba tu instalación:

```bash
php -v
composer --version
php -m
```

## Estructura del proyecto

```text
oauth-google/
│
├── config/
│   └── google.example.php
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

### Responsabilidades

| Archivo | Función |
|---|---|
| `bootstrap.php` | Inicialización de ShieldPHP y sus dependencias. |
| `config/google.example.php` | Configuración de Google OAuth. |
| `public/index.php` | Página principal de la aplicación. |
| `public/login.php` | Inicio del proceso de autenticación. |
| `public/callback.php` | Procesamiento del callback OAuth. |
| `public/logout.php` | Cierre de la sesión local. |
| `.env.example` | Variables de entorno necesarias. |

## 1. Crear un proyecto en Google Cloud

Para utilizar Google Login necesitas registrar una aplicación en Google Cloud.

Accede a:

https://console.cloud.google.com/

### Pasos de configuración

1. Crea un proyecto de Google Cloud.
2. Configura la pantalla de consentimiento OAuth.
3. Completa la información de la aplicación.
4. Configura los usuarios de prueba cuando la aplicación esté en modo Testing.
5. Accede a la sección de clientes OAuth.
6. Crea un cliente OAuth 2.0.
7. Selecciona el tipo **Web application**.
8. Registra las URI de redirección autorizadas.

Al finalizar, Google proporcionará:

- Client ID.
- Client Secret.

Estas credenciales deben mantenerse exclusivamente en el servidor.

Documentación oficial:

https://developers.google.com/identity/protocols/oauth2

## 2. Configurar la URI de redirección

Para desarrollo local puedes utilizar:

```text
http://localhost:8000/callback.php
```

En producción:

```text
https://example.com/auth/google/callback
```

La URI debe estar registrada en Google Cloud y debe coincidir exactamente con la utilizada por ShieldPHP durante el intercambio del código de autorización.

No aceptes direcciones de callback arbitrarias proporcionadas por el usuario.

## 3. Instalar dependencias

Desde la raíz del repositorio:

```bash
composer install
```

Si ShieldPHP Core mantiene su propio archivo Composer:

```bash
cd packages/php-core
composer install
```

Verifica que el autoloader de Composer pueda resolver las clases de ShieldPHP.

## 4. Configurar variables de entorno

Crea un archivo `.env` utilizando `.env.example` como referencia.

```dotenv
# ============================================================
# SHIELDPHP GOOGLE OAUTH
# ============================================================

SHIELDPHP_ENV=development
SHIELDPHP_DEBUG=false

# Google OAuth credentials
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

# OAuth callback
GOOGLE_REDIRECT_URI=http://localhost:8000/callback.php

# OpenID Connect scopes
GOOGLE_SCOPES=openid,email,profile

# OAuth security
OAUTH_STATE_TTL=600
OAUTH_PKCE_TTL=300

# Application
APP_BASE_URL=http://localhost:8000
APP_SESSION_NAME=SHIELDPHP_GOOGLE
```

No guardes credenciales reales en el repositorio.

## 5. Configurar GoogleProvider

ShieldPHP incluye `GoogleProvider`, encargado de gestionar las operaciones específicas de Google.

El proveedor se integra con el cliente HTTP de ShieldPHP y se registra en `OAuthManager`.

Ejemplo de inicialización:

```php
<?php

declare(strict_types=1);

use ShieldPHP\Core\OAuth\OAuthManager;
use ShieldPHP\Core\OAuth\OAuthClient;
use ShieldPHP\Core\OAuth\Providers\GoogleProvider;

// Dependencias HTTP inicializadas por la aplicación.
$oauthClient = new OAuthClient(
    $httpClient,
    $requestFactory,
    $streamFactory,
    [
        'oauth2.googleapis.com',
        'openidconnect.googleapis.com',
    ]
);

// Crear proveedor Google.
$googleProvider = new GoogleProvider(
    $oauthClient,
    getenv('GOOGLE_CLIENT_ID'),
    getenv('GOOGLE_CLIENT_SECRET')
);

// Registrar proveedor.
$oauthManager->registerProvider($googleProvider);
```

El cliente HTTP debe validar HTTPS, restringir los dominios de destino y evitar redirecciones HTTP automáticas hacia hosts no autorizados.

## 6. Configurar OAuthManager

`OAuthManager` centraliza el proceso OAuth y utiliza los administradores de estado y PKCE.

Ejemplo conceptual de inicialización:

```php
<?php

declare(strict_types=1);

use ShieldPHP\Core\OAuth\OAuthManager;
use ShieldPHP\Core\OAuth\OAuthStateManager;
use ShieldPHP\Core\OAuth\PKCEManager;

$oauthManager = new OAuthManager(
    $oauthStateManager,
    $pkceManager,
    getenv('GOOGLE_REDIRECT_URI')
);

$oauthManager->registerProvider($googleProvider);
```

Las dependencias `$oauthStateManager` y `$pkceManager` deben utilizar almacenamientos compatibles con sus respectivas interfaces.

Para producción, utiliza almacenamientos compartidos y persistentes cuando la aplicación trabaje con múltiples procesos o servidores.

## 7. Iniciar sesión con Google

El inicio de autenticación se realiza mediante `OAuthManager`.

Ejemplo para `public/login.php`:

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

try {
    $authorizationUrl = $oauthManager->getAuthorizationUrl(
        'google'
    );

    header(
        'Location: ' . $authorizationUrl,
        true,
        302
    );

    exit;

} catch (Throwable $exception) {
    error_log(
        '[ShieldPHP Google] Authorization error: '
        . $exception->getMessage()
    );

    http_response_code(500);

    exit('Unable to start Google authentication.');
}
```

ShieldPHP genera el estado OAuth y el challenge PKCE. Sus valores deben conservarse en el servidor para completar correctamente el callback.

## 8. Procesar el callback

Después de que el usuario autoriza la aplicación, Google redirige el navegador a la URI configurada.

El callback debe delegar la validación y el intercambio de código a `OAuthManager`.

Ejemplo para `public/callback.php`:

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use ShieldPHP\Core\Exceptions\AuthenticationException;

try {
    $profile = $oauthManager->handleCallback(
        'google',
        $_GET
    );

    /*
     * Perfil normalizado proporcionado por GoogleProvider.
     */

    $googleUserId = $profile['id'] ?? null;

    $email = $profile['email'] ?? null;

    $name = $profile['name'] ?? null;

    if (
        !is_string($googleUserId)
        || $googleUserId === ''
    ) {
        throw new AuthenticationException(
            'Google user identifier is missing.'
        );
    }

    /*
     * Buscar o registrar el usuario local.
     *
     * La aplicación debe implementar su propio
     * proceso de asociación de cuentas.
     */

    // $user = $userService->findOrCreateGoogleUser($profile);

    /*
     * Iniciar una sesión local después de validar la identidad.
     */

    // session_regenerate_id(true);
    // $_SESSION['auth'] = [...];

    header('Location: index.php', true, 303);
    exit;

} catch (Throwable $exception) {
    error_log(
        '[ShieldPHP Google] Callback error: '
        . $exception->getMessage()
    );

    http_response_code(401);

    exit('Google authentication could not be completed.');
}
```

### ¿Qué valida ShieldPHP?

El proceso debe garantizar:

1. Que el parámetro `state` corresponda a la solicitud original.
2. Que el estado no haya sido consumido anteriormente.
3. Que el verifier PKCE corresponda al challenge original.
4. Que el código de autorización se intercambie únicamente después de validar el callback.
5. Que los errores enviados por Google no se interpreten como autenticaciones exitosas.
6. Que la identidad local se establezca únicamente después de validar el perfil.

## 9. Perfil de Google

Google puede proporcionar información de identidad mediante el endpoint UserInfo de OpenID Connect.

Ejemplo de perfil:

```json
{
  "sub": "109876543210987654321",
  "name": "Example User",
  "given_name": "Example",
  "family_name": "User",
  "email": "user@example.com",
  "email_verified": true,
  "picture": "https://example.com/profile-picture.jpg"
}
```

ShieldPHP normaliza la información para facilitar su utilización en la aplicación.

Ejemplo:

```php
[
    'id' => '109876543210987654321',
    'name' => 'Example User',
    'first_name' => 'Example',
    'last_name' => 'User',
    'email' => 'user@example.com',
    'picture' => 'https://example.com/profile-picture.jpg',
];
```

### Campos disponibles

| Campo | Descripción |
|---|---|
| `id` | Identificador único de Google (`sub`). |
| `name` | Nombre completo. |
| `first_name` | Nombre del usuario. |
| `last_name` | Apellidos. |
| `email` | Dirección de correo verificada, cuando corresponda. |
| `picture` | URL de la fotografía de perfil. |

### Validación del correo

Una dirección de correo no debe considerarse verificada únicamente porque Google haya devuelto una cadena con formato válido.

El proveedor debe tener en cuenta el indicador `email_verified`.

Cuando el correo no esté verificado, ShieldPHP debe tratarlo como no confiable. La política concreta de la aplicación debe determinar si permite continuar con la identidad externa o requiere una verificación adicional.

## 10. OpenID Connect e ID Token

Google utiliza OpenID Connect para proporcionar información de identidad.

Los scopes habituales de este ejemplo son:

```text
openid
email
profile
```

El ID Token es diferente del Access Token:

| Token | Propósito |
|---|---|
| Access Token | Autorizar el acceso a recursos protegidos. |
| ID Token | Proporcionar información de autenticación e identidad. |
| Refresh Token | Obtener nuevos access tokens cuando el proveedor lo permite. |

**Importante:** nunca confíes en un ID Token únicamente porque tiene formato JWT. Debe validarse criptográficamente y comprobar sus claims, incluyendo:

- Firma.
- Emisor (`iss`).
- Audiencia (`aud`).
- Caducidad (`exp`).
- Destinatario de la solicitud (`nonce`), cuando se utiliza.

No decodifiques un ID Token y utilices sus datos como identidad confiable sin completar estas verificaciones.

## 11. Refresh Tokens

Google puede proporcionar un refresh token dependiendo de las condiciones de autorización y configuración de la aplicación.

No debes asumir que todas las respuestas OAuth contienen uno.

Cuando necesites acceso prolongado, debes gestionar de forma segura la renovación de tokens y almacenar los refresh tokens protegidos.

No expongas estos valores al navegador.

## 12. Cierre de sesión

La autenticación de Google y la sesión local de la aplicación son independientes.

Cerrar sesión en ShieldPHP debe terminar la sesión local.

Ejemplo:

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

Esto no cierra necesariamente la sesión del usuario en todos los servicios de Google.

## 13. Manejo de errores

La aplicación debe manejar los fallos del flujo OAuth de forma controlada.

| Situación | Comportamiento esperado |
|---|---|
| `state` incorrecto | Rechazar callback. |
| Estado reutilizado | Rechazar callback. |
| Código ausente | Rechazar callback. |
| Usuario cancela autorización | Procesar error del proveedor. |
| Verifier PKCE incorrecto | Rechazar intercambio. |
| Credenciales inválidas | Rechazar autenticación. |
| Error al obtener tokens | No establecer sesión local. |
| Error al obtener perfil | No establecer sesión local. |
| Identificador `sub` ausente | Rechazar perfil. |
| Correo no verificado | No tratarlo como correo confiable. |

No guardes contraseñas, tokens, códigos de autorización ni secretos en los registros de errores.

## 14. Seguridad

ShieldPHP debe mantener las siguientes protecciones:

### Protección OAuth State

Genera valores impredecibles y consume cada estado una sola vez.

### PKCE S256

Utiliza SHA-256 para el challenge y conserva el verifier exclusivamente en el servidor.

### Redirect URI

Registra y valida exactamente las URI de callback autorizadas.

### HTTPS

Utiliza HTTPS en producción para proteger las comunicaciones y las cookies de sesión.

### Client Secret

Mantén el secreto de Google fuera del código público y del frontend.

### Identidad externa

Utiliza el identificador `sub` como identificador de la cuenta externa. No uses exclusivamente el correo electrónico para asociar usuarios.

### Sesiones

Regenera el identificador de sesión después de autenticar al usuario y utiliza cookies `Secure`, `HttpOnly` y `SameSite`.

## 15. Ejecutar el ejemplo

Desde la carpeta:

```bash
cd examples/oauth-google
```

Inicia el servidor PHP:

```bash
php -S localhost:8000 -t public
```

Abre:

```text
http://localhost:8000
```

Verifica que la URI de callback configurada en Google Cloud coincida con la utilizada por la aplicación.

El servidor integrado de PHP está destinado exclusivamente al desarrollo local.

## 16. Pruebas

Las pruebas del proveedor Google se encuentran en:

```text
packages/php-core/tests/Unit/OAuth/Providers/GoogleProviderTest.php
```

Ejecutar las pruebas unitarias:

```bash
cd packages/php-core

vendor/bin/phpunit tests/Unit/OAuth/Providers/GoogleProviderTest.php
```

Ejecutar las pruebas de OAuth:

```bash
vendor/bin/phpunit tests/Unit/OAuth
```

Ejecutar la prueba de integración:

```bash
vendor/bin/phpunit tests/Integration/OAuth/GoogleOAuthTest.php
```

La prueba de integración debe cubrir el flujo de autorización completo, incluyendo:

- Generación de URL.
- Generación de state y PKCE.
- Intercambio del código.
- Recuperación del perfil.
- Prevención de reutilización del estado.
- Rechazo de callbacks inválidos.
- Gestión de correos no verificados.
- Manejo de proveedores no registrados.

## 17. Extensiones futuras

Este ejemplo puede ampliarse con:

- Registro automático de usuarios.
- Asociación de cuentas sociales.
- Inicio de sesión con múltiples proveedores.
- Autenticación mediante JWT.
- Autenticación multifactor.
- Recuperación de cuentas.
- Verificación adicional de identidad.
- Gestión de roles y permisos.
- Sincronización de perfiles.
- Desconexión de cuentas externas.
- Auditoría de autenticaciones.

## Contribuir

Las contribuciones son bienvenidas.

1. Realiza un fork del repositorio.
2. Crea una rama para tu funcionalidad.
3. Implementa los cambios.
4. Añade pruebas unitarias.
5. Ejecuta la suite de pruebas.
6. Envía un Pull Request.

## Licencia

Este ejemplo forma parte del proyecto ShieldPHP.

Consulta el archivo `LICENSE` de la raíz del repositorio para conocer los términos de uso y distribución.

---

<p align="center">
  <strong>ShieldPHP</strong>
  <br>
  Autenticación segura para aplicaciones PHP modernas.
  <br><br>
  Built with PHP, OAuth 2.0 and OpenID Connect.
</p>
