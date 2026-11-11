# Google Login — ShieldPHP

<p align="center">
  <strong>Autenticación mediante Google OAuth 2.0 y OpenID Connect para PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/OAuth-2.0-4285F4?style=for-the-badge" alt="OAuth 2.0">
  <img src="https://img.shields.io/badge/OpenID-Connect-F5A623?style=for-the-badge" alt="OpenID Connect">
  <img src="https://img.shields.io/badge/Provider-Google-34A853?style=for-the-badge&logo=google&logoColor=white" alt="Google">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

**Google Login** es una integración de ShieldPHP que permite a los usuarios autenticarse en aplicaciones PHP utilizando su cuenta de Google.

La integración utiliza OAuth 2.0 Authorization Code y OpenID Connect para iniciar el proceso de autenticación, obtener un token de acceso y recuperar información de identidad del usuario.

Su objetivo es proporcionar una implementación reutilizable para aplicaciones que necesitan autenticación social sin administrar directamente las credenciales de Google de sus usuarios.

### Características principales

- Inicio de sesión mediante Google.
- Redirección al proceso de autorización.
- Gestión del estado OAuth.
- Soporte para PKCE cuando el flujo y la implementación lo admitan.
- Intercambio de códigos de autorización por tokens.
- Obtención de perfiles de usuario.
- Identificación de usuarios mediante un identificador externo estable.
- Integración con el sistema de identidad de ShieldPHP.
- Separación entre identidad externa y cuenta local.

## 2. OAuth 2.0 y OpenID Connect

Google Login utiliza dos estándares relacionados, pero con responsabilidades diferentes.

| Tecnología | Responsabilidad |
|---|---|
| OAuth 2.0 | Autorizar el acceso a recursos y obtener tokens. |
| OpenID Connect | Proporcionar información de identidad autenticada. |

OAuth 2.0 permite obtener un `access_token` para acceder a recursos autorizados.

OpenID Connect añade el concepto de `id_token`, que contiene declaraciones sobre la identidad del usuario.

Para un inicio de sesión, la solicitud debe incluir los scopes:

```text
openid email profile
```

- `openid`: solicita autenticación mediante OpenID Connect.
- `email`: solicita información del correo electrónico.
- `profile`: solicita información básica del perfil.

Estos scopes permiten solicitar únicamente la información necesaria para identificar al usuario.

## 3. Arquitectura

La integración utiliza los componentes OAuth de ShieldPHP Core.

| Componente | Responsabilidad |
|---|---|
| `GoogleProvider` | Implementa las operaciones específicas de Google. |
| `OAuthManager` | Coordina el proceso OAuth. |
| `OAuthClient` | Ejecuta las solicitudes HTTP. |
| `OAuthStateManager` | Genera y valida el estado OAuth. |
| `PKCEManager` | Administra verificadores PKCE. |
| `TokenResponse` | Representa la respuesta del endpoint de tokens. |
| `CallbackHandler` | Coordina el procesamiento del callback. |

Namespace principal:

```php
ShieldPHP\Core\OAuth\Providers\GoogleProvider
```

### Diagrama del flujo

```text
                  USUARIO
                     |
                     v
               Aplicación PHP
                     |
                     v
                OAuthManager
                     |
                     v
                GoogleProvider
                     |
                     v
             Google Authorization
                     |
                     v
               Cuenta de Google
                     |
                Autorización
                     |
                     v
              Callback OAuth
                     |
                     v
              Validar STATE
                     |
                     v
            Intercambiar CODE
                     |
                     v
               Access Token
                     |
                     v
             Google UserInfo
                     |
                     v
              Perfil externo
                     |
                     v
           Validar identidad
                     |
                     v
            Identidad local
```

## 4. Requisitos

Para utilizar Google Login necesitas:

- PHP 8.2 o superior.
- Composer 2.
- ShieldPHP Core.
- Extensión PHP Sodium.
- Extensión PHP JSON.
- Una cuenta de Google.
- Un proyecto de Google Cloud.
- Credenciales OAuth 2.0 de tipo aplicación web.
- Una URI de retorno registrada.
- Conexión HTTPS en producción.

## 5. Crear un proyecto en Google Cloud

Antes de utilizar Google Login debes registrar la aplicación.

### Paso 1. Acceder a Google Cloud

Ingresa a:

https://console.cloud.google.com/

Inicia sesión con tu cuenta de Google.

### Paso 2. Crear un proyecto

1. Abre el selector de proyectos.
2. Selecciona crear un proyecto.
3. Introduce el nombre de tu aplicación.
4. Selecciona la organización correspondiente, cuando aplique.
5. Confirma la creación.

### Paso 3. Configurar la pantalla de consentimiento

Accede a la sección Google Auth Platform y configura la pantalla de consentimiento OAuth.

Completa la información solicitada:

- Nombre de la aplicación.
- Correo electrónico de soporte.
- Información de contacto del desarrollador.
- Audiencia de la aplicación.
- Dominios autorizados, cuando corresponda.
- Política de privacidad y condiciones de uso, si son requeridas.

Durante el desarrollo puedes utilizar el modo de prueba y agregar las cuentas autorizadas correspondientes.

Las aplicaciones públicas pueden necesitar completar procesos de verificación dependiendo de los scopes y permisos solicitados.

### Paso 4. Configurar los scopes

Para el inicio de sesión básico utiliza:

```text
openid
email
profile
```

Evita solicitar permisos adicionales que no sean necesarios para la funcionalidad de tu aplicación.

### Paso 5. Crear credenciales OAuth

En Google Auth Platform:

1. Accede a la sección de clientes.
2. Selecciona crear cliente OAuth.
3. Selecciona el tipo de aplicación web.
4. Introduce un nombre identificable.
5. Configura los orígenes JavaScript autorizados cuando corresponda.
6. Registra las URI de redirección autorizadas.
7. Guarda la configuración.

### Paso 6. Obtener credenciales

Google proporciona dos valores:

| Credencial | Descripción |
|---|---|
| Client ID | Identificador público del cliente OAuth. |
| Client Secret | Secreto privado del cliente. |

Ejemplo ilustrativo:

```text
CLIENT ID:
123456789012-example.apps.googleusercontent.com

CLIENT SECRET:
GOCSPX-************************
```

El Client Secret debe mantenerse exclusivamente en el servidor.

## 6. URI de redirección

La URI de redirección es la dirección a la que Google envía al usuario después de completar el proceso de autorización.

Ejemplo local:

```text
http://localhost:8000/oauth/google/callback
```

Ejemplo en producción:

```text
https://midominio.com/oauth/google/callback
```

La URI utilizada durante el intercambio del código debe coincidir con la URI registrada para el cliente OAuth.

Google requiere HTTPS para URI de producción, con excepciones para direcciones localhost.

No utilices direcciones de retorno arbitrarias ni redirecciones abiertas.

## 7. Configuración de ShieldPHP

ShieldPHP contempla las siguientes variables en `.env.example`:

```dotenv
# ==========================================
# GOOGLE OAUTH
# ==========================================

SHIELDPHP_OAUTH_GOOGLE_ENABLED=false

SHIELDPHP_OAUTH_GOOGLE_CLIENT_ID=
SHIELDPHP_OAUTH_GOOGLE_CLIENT_SECRET=

SHIELDPHP_OAUTH_GOOGLE_REDIRECT_URI=http://localhost:8000/oauth/google/callback

# ==========================================
# OAUTH SECURITY
# ==========================================

SHIELDPHP_OAUTH_STATE_TTL=600
SHIELDPHP_OAUTH_PKCE_ENABLED=true
```

### Descripción de variables

| Variable | Descripción |
|---|---|
| `SHIELDPHP_OAUTH_GOOGLE_ENABLED` | Activa o desactiva la integración. |
| `SHIELDPHP_OAUTH_GOOGLE_CLIENT_ID` | Identificador del cliente OAuth. |
| `SHIELDPHP_OAUTH_GOOGLE_CLIENT_SECRET` | Secreto del cliente OAuth. |
| `SHIELDPHP_OAUTH_GOOGLE_REDIRECT_URI` | URI de retorno. |
| `SHIELDPHP_OAUTH_STATE_TTL` | Tiempo de vida del estado OAuth. |
| `SHIELDPHP_OAUTH_PKCE_ENABLED` | Opción prevista para utilizar PKCE. |

Estas variables constituyen la plantilla de configuración. La aplicación debe cargarlas y conectarlas con las dependencias de `GoogleProvider` y `OAuthManager`.

## 8. Endpoints de Google

El proveedor utiliza los endpoints oficiales de Google.

| Operación | Endpoint |
|---|---|
| Autorización | `https://accounts.google.com/o/oauth2/v2/auth` |
| Intercambio de tokens | `https://oauth2.googleapis.com/token` |
| Información del usuario | `https://openidconnect.googleapis.com/v1/userinfo` |
| Metadatos OpenID Connect | `https://accounts.google.com/.well-known/openid-configuration` |
| Claves públicas JWKS | URL indicada en `jwks_uri` de los metadatos. |

Los endpoints no deben construirse a partir de datos proporcionados por el usuario.

## 9. GoogleProvider

`GoogleProvider` es el componente encargado de comunicarse con Google mediante OAuth 2.0.

**Namespace:**

```php
ShieldPHP\Core\OAuth\Providers\GoogleProvider
```

### Responsabilidades

- Identificar el proveedor como `google`.
- Construir la URL de autorización.
- Intercambiar el código de autorización por tokens.
- Consultar el perfil del usuario.
- Procesar los datos de identidad.
- Representar la respuesta mediante los contratos del Core.

### Métodos del contrato OAuth

```php
getName(): string;

getAuthorizationUrl(
    string $state,
    string $redirectUri,
    ?string $challenge = null
): string;

exchangeCodeForToken(
    string $code,
    string $redirectUri,
    ?string $verifier = null
): TokenResponse;

fetchUserProfile(
    TokenResponse $token
): array;
```

La clase implementa el contrato de proveedores OAuth de ShieldPHP.

## 10. Flujo de autenticación

### Etapa 1. Solicitud de inicio de sesión

El usuario selecciona la opción:

**Iniciar sesión con Google.**

La aplicación inicia el proceso mediante `OAuthManager`.

### Etapa 2. Generación del estado

ShieldPHP genera un valor aleatorio `state` y lo almacena temporalmente.

Este valor permite vincular el callback con la solicitud iniciada por el usuario y ayuda a prevenir ataques CSRF.

### Etapa 3. Redirección a Google

El navegador se dirige al endpoint:

```text
https://accounts.google.com/o/oauth2/v2/auth
```

Parámetros principales:

| Parámetro | Descripción |
|---|---|
| `client_id` | Identificador del cliente OAuth. |
| `redirect_uri` | URI de retorno. |
| `response_type` | `code`. |
| `scope` | `openid email profile`. |
| `state` | Valor de protección CSRF. |
| `code_challenge` | Desafío PKCE, cuando se utiliza. |
| `code_challenge_method` | Método de generación del desafío. |

Cuando se utiliza PKCE, el intercambio posterior debe incluir el verificador correspondiente.

### Etapa 4. Autorización del usuario

Google presenta la pantalla de selección de cuenta y consentimiento.

El usuario puede autorizar o cancelar la solicitud.

### Etapa 5. Callback

Google devuelve al usuario a la URI registrada.

Ejemplo:

```text
https://midominio.com/oauth/google/callback?code=AUTHORIZATION_CODE&state=STATE_VALUE
```

Cuando ocurre un error, Google puede devolver parámetros como:

```text
error=access_denied
```

La aplicación debe procesar tanto respuestas exitosas como errores.

### Etapa 6. Validación de state

Antes de procesar el código, ShieldPHP debe comprobar que el estado recibido corresponde al que se generó inicialmente.

Se debe rechazar:

- Estado ausente.
- Estado incorrecto.
- Estado expirado.
- Estado previamente consumido.

### Etapa 7. Intercambio del código

El servidor intercambia el código mediante una solicitud POST al endpoint:

```text
https://oauth2.googleapis.com/token
```

Parámetros habituales:

```text
client_id
client_secret
code
grant_type=authorization_code
redirect_uri
code_verifier
```

`code_verifier` se incluye cuando se utilizó PKCE.

El intercambio debe ejecutarse en el servidor. Nunca se debe exponer el Client Secret al navegador.

### Etapa 8. Recepción de tokens

Google puede devolver una respuesta similar a:

```json
{
    "access_token": "ACCESS_TOKEN",
    "expires_in": 3599,
    "scope": "openid email profile",
    "token_type": "Bearer",
    "id_token": "IDENTITY_TOKEN"
}
```

Esta respuesta es ilustrativa. Los campos disponibles dependen de la solicitud y de la respuesta real del proveedor.

ShieldPHP utiliza `TokenResponse` para representar los datos del intercambio.

### Etapa 9. Obtención del perfil

El proveedor puede consultar la información del usuario mediante:

```text
https://openidconnect.googleapis.com/v1/userinfo
```

La solicitud utiliza el token de acceso:

```http
GET /v1/userinfo HTTP/1.1
Host: openidconnect.googleapis.com
Authorization: Bearer ACCESS_TOKEN
```

Ejemplo de respuesta:

```json
{
    "sub": "10769150350006150715113082367",
    "name": "Usuario de ejemplo",
    "given_name": "Usuario",
    "family_name": "Ejemplo",
    "picture": "https://example.com/profile.jpg",
    "email": "usuario@example.com",
    "email_verified": true
}
```

Los campos son ilustrativos y pueden variar según la información disponible.

### Etapa 10. Crear o recuperar la identidad local

La aplicación utiliza el campo `sub` para identificar la cuenta de Google.

La relación recomendada es:

```text
provider = google
provider_user_id = GOOGLE_SUB
```

No debes utilizar el correo electrónico como identificador principal de una cuenta de Google.

El identificador `sub` es estable para la cuenta y no depende de los cambios que pueda experimentar su dirección de correo electrónico.

## 11. Integración con OAuthManager

ShieldPHP contempla `OAuthManager` como punto central de coordinación de proveedores.

### Registrar GoogleProvider

Ejemplo conceptual:

```php
use ShieldPHP\Core\OAuth\OAuthManager;
use ShieldPHP\Core\OAuth\Providers\GoogleProvider;

$oauthManager = $container->get(
    OAuthManager::class
);

$googleProvider = $container->get(
    GoogleProvider::class
);

$oauthManager->registerProvider(
    'google',
    $googleProvider
);
```

La instancia de `GoogleProvider` debe construirse previamente con sus dependencias HTTP y credenciales.

### Obtener la URL de autorización

```php
$authorizationUrl = $oauthManager->getAuthorizationUrl(
    'google'
);
```

La aplicación debe redirigir al navegador a esa URL.

### Procesar el callback

```php
$result = $oauthManager->handleCallback(
    'google',
    $_GET
);
```

El procesamiento debe contemplar:

- Validación de `state`.
- Rechazo de respuestas de error.
- Validación del código recibido.
- Intercambio del código por tokens.
- Obtención del perfil cuando corresponda.

El resultado debe procesarse de acuerdo con el contrato real de retorno de `OAuthManager`.

## 12. Validación del ID Token

Una parte fundamental de OpenID Connect es la validación del `id_token`.

Un ID Token es un JWT firmado que representa una declaración de identidad emitida por Google.

**No es suficiente decodificar el token y confiar en su contenido.**

Antes de aceptar una identidad basada en un ID Token recibido de un cliente o de otro componente, el servidor debe validar su autenticidad.

### Declaraciones importantes

| Claim | Descripción |
|---|---|
| `iss` | Emisor del token. |
| `sub` | Identificador estable de la cuenta. |
| `aud` | Cliente al que está destinado. |
| `exp` | Fecha de expiración. |
| `iat` | Fecha de emisión. |
| `email` | Correo del usuario. |
| `email_verified` | Indica si el correo fue verificado. |
| `nonce` | Valor para vincular la respuesta con la solicitud, cuando se utilizó. |
| `hd` | Dominio de Google Workspace, cuando está presente. |

### Validaciones obligatorias

1. Verificar criptográficamente la firma mediante las claves públicas de Google.
2. Comprobar que `iss` sea un emisor admitido.
3. Comprobar que `aud` coincida con el Client ID esperado.
4. Verificar que el token no haya expirado.
5. Comprobar `nonce` cuando se haya enviado.
6. Verificar los demás requisitos de la aplicación, como `azp` cuando corresponda.

Las claves de firma deben obtenerse a partir del documento oficial de descubrimiento OpenID Connect y su `jwks_uri`.

El endpoint `tokeninfo` de Google puede ser útil para depuración, pero no debe sustituir la validación normal de producción.

### Identificador de usuario

Utiliza siempre:

```text
sub
```

No utilices `email` como clave primaria de la identidad externa.

### Verificación de correo

Si la aplicación necesita tratar el correo como dirección verificada, debe comprobar:

```json
{
    "email": "usuario@example.com",
    "email_verified": true
}
```

La aplicación debe respetar el valor real de `email_verified` y no asumir que cualquier correo recibido está verificado.

## 13. Relación entre Google Login y ShieldPHP Authentication

Después de obtener un perfil válido, la aplicación puede recuperar o registrar la identidad local.

La separación recomendada es:

| Componente | Responsabilidad |
|---|---|
| `GoogleProvider` | Comunicación con Google. |
| `OAuthManager` | Coordinación del flujo OAuth. |
| `TokenResponse` | Representación de la respuesta de tokens. |
| Verificador OIDC | Validación de la identidad externa. |
| `UserProviderInterface` | Recuperación de usuarios locales. |
| `UserIdentity` | Representación de la identidad local. |
| `AuthManager` | Autenticación mediante credenciales locales. |

Google Login no crea automáticamente una cuenta local ni garantiza que exista una sesión local.

La aplicación debe definir cómo vincular la identidad externa con su sistema de usuarios.

### Política recomendada de vinculación

- Buscar primero por proveedor e identificador `sub`.
- No vincular automáticamente cuentas únicamente porque los correos coincidan.
- Comprobar la propiedad de la cuenta cuando se realicen vinculaciones.
- Permitir que un usuario vincule o desvincule proveedores mediante un proceso autenticado.
- Mantener un registro de las identidades externas asociadas.

## 14. Manejo de errores

| Error | Causa posible | Acción recomendada |
|---|---|---|
| `access_denied` | El usuario canceló el consentimiento. | Informar y permitir reintentar. |
| `invalid_request` | Parámetros OAuth incorrectos. | Revisar la solicitud. |
| `redirect_uri_mismatch` | URI distinta de la registrada. | Corregir la configuración. |
| `invalid_grant` | Código expirado, inválido o reutilizado. | Iniciar un nuevo flujo. |
| `invalid_client` | Credenciales del cliente incorrectas. | Revisar Client ID y Client Secret. |
| `invalid_scope` | Scopes no válidos. | Revisar los permisos solicitados. |
| Estado incorrecto | Posible solicitud falsificada. | Rechazar el callback. |
| ID Token inválido | Firma o declaraciones incorrectas. | Rechazar la identidad. |
| Perfil incompleto | Información no disponible. | Manejar campos opcionales. |

### Respuesta de error recomendada

```json
{
    "success": false,
    "error": "oauth_authentication_failed",
    "message": "No fue posible completar la autenticación con Google."
}
```

No expongas al usuario mensajes internos que incluyan tokens, credenciales o detalles de implementación.

## 15. Seguridad

La integración debe seguir las recomendaciones de OAuth 2.0 y OpenID Connect.

### Protección del estado

- Generar valores aleatorios seguros.
- Mantener el estado asociado a la sesión o transacción original.
- Validarlo antes de intercambiar el código.
- Consumirlo una sola vez.
- Aplicar una expiración limitada.

### Protección PKCE

- Generar verificadores criptográficamente seguros.
- Utilizar el método `S256`.
- Almacenar el verificador temporalmente.
- Asociarlo con la solicitud de autorización.
- Utilizar el mismo verificador durante el intercambio del código.

### Protección de credenciales

- Mantener el Client Secret exclusivamente en el servidor.
- No incluir secretos en el frontend.
- No publicar credenciales en GitHub.
- No registrar tokens completos.
- Utilizar variables de entorno o un administrador de secretos.

### Protección de tokens

- Validar los ID Tokens antes de confiar en sus declaraciones.
- Verificar firma, emisor, audiencia y expiración.
- Comprobar `nonce` cuando corresponda.
- Proteger los tokens de acceso y actualización.
- Transmitir tokens únicamente mediante canales seguros.
- Evitar almacenarlos en ubicaciones accesibles desde JavaScript.

### Protección de URI

- Utilizar HTTPS en producción.
- Registrar las URI autorizadas.
- Aplicar coincidencia estricta.
- Evitar redirecciones abiertas.
- No aceptar destinos proporcionados arbitrariamente por el usuario.

## 16. Renovación de tokens

Para aplicaciones que necesitan acceder a recursos de Google durante períodos prolongados, el flujo de servidor puede solicitar acceso sin conexión mediante:

```text
access_type=offline
```

Google puede proporcionar un `refresh_token` cuando se cumplen las condiciones correspondientes.

La renovación debe realizarse exclusivamente desde el servidor.

Para un simple inicio de sesión, no siempre es necesario conservar un token de actualización.

### Recomendaciones

- Solicitar acceso sin conexión únicamente cuando sea necesario.
- Almacenar los refresh tokens de forma protegida.
- Implementar revocación cuando el usuario desvincule su cuenta.
- Manejar respuestas de renovación fallidas.
- No asumir que todas las autorizaciones devuelven un nuevo refresh token.

## 17. Pruebas

ShieldPHP contempla una estructura de pruebas para GoogleProvider:

```text
packages/php-core/tests/Unit/OAuth/Providers/
```

Archivo:

```text
GoogleProviderTest.php
```

### Escenarios recomendados

| Escenario | Objetivo |
|---|---|
| Nombre del proveedor | Verificar que se identifique como `google`. |
| URL de autorización | Comprobar parámetros OAuth y scopes. |
| Estado | Validar el parámetro `state`. |
| PKCE | Comprobar la relación entre challenge y verifier. |
| Intercambio de código | Comprobar la respuesta del endpoint de tokens. |
| Perfil | Validar la obtención de información. |
| Identificador `sub` | Comprobar la identificación externa. |
| Correo verificado | Respetar `email_verified`. |
| Perfil incompleto | Manejar campos opcionales. |
| ID Token | Validar firma y declaraciones. |
| Errores HTTP | Manejar fallos del proveedor. |

### Ejecutar las pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

Ejecutar exclusivamente las pruebas de Google:

```bash
vendor/bin/phpunit tests/Unit/OAuth/Providers/GoogleProviderTest.php
```

Se recomienda simular las respuestas HTTP para evitar depender de cuentas y servicios reales durante las pruebas unitarias.

## 18. Estructura del módulo

```text
shieldphp/
│
├── packages/
│   └── php-core/
│       ├── src/
│       │   ├── OAuth/
│       │   │   ├── Providers/
│       │   │   │   └── GoogleProvider.php
│       │   │   │
│       │   │   ├── OAuthClient.php
│       │   │   ├── OAuthManager.php
│       │   │   ├── OAuthStateManager.php
│       │   │   ├── PKCEManager.php
│       │   │   ├── TokenResponse.php
│       │   │   └── AuthorizationUrlBuilder.php
│       │   │
│       │   └── Contracts/
│       │       └── OAuthProviderInterface.php
│       │
│       └── tests/
│           └── Unit/
│               └── OAuth/
│                   └── Providers/
│                       └── GoogleProviderTest.php
│
├── examples/
│   └── oauth-google/
│       └── README.md
│
└── docs/
    └── google-login.md
```

## 19. Estado actual

| Funcionalidad | Estado |
|---|---|
| `GoogleProvider` | Implementación inicial |
| Contrato OAuth | Base implementada |
| Generación de URL de autorización | Implementación inicial |
| Intercambio de códigos | Implementación inicial |
| Solicitudes HTTP mediante OAuthClient | Base implementada |
| Obtención del perfil | Implementación inicial |
| Obtención del identificador `sub` | Contemplada |
| Tratamiento de `email_verified` | Contemplado |
| Validación de estado OAuth | Base implementada |
| Integración con OAuthManager | En desarrollo |
| PKCE | Requiere validación del flujo completo |
| Verificación criptográfica de ID Tokens | Pendiente de completar |
| Vinculación con usuarios locales | Pendiente de integración |
| Pruebas unitarias | Suite inicial |
| Pruebas de integración reales | Pendientes |
| Validación de producción | Pendiente |

## 20. Consideraciones técnicas

- El `access_token` permite realizar solicitudes a recursos autorizados.
- El `id_token` representa una declaración de identidad de OpenID Connect.
- Ambos tokens tienen propósitos diferentes.
- El campo `sub` debe ser el identificador principal de la cuenta externa.
- El correo electrónico puede cambiar, por lo que no debe utilizarse como identificador estable.
- El campo `email_verified` debe comprobarse antes de tratar el correo como verificado.
- El ID Token debe validarse criptográficamente antes de confiar en declaraciones que procedan de clientes u otros componentes no confiables.
- La versión de los componentes de ShieldPHP debe comprobarse antes de integrar este flujo en producción.

---

## 21. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Roles y permisos.
- `docs/configuration.md` — Configuración.
- `docs/encryption.md` — Criptografía.
- `docs/facebook-login.md` — Inicio de sesión con Facebook.
- `examples/oauth-google/README.md` — Ejemplo de Google OAuth.
- `SECURITY.md` — Política de seguridad.

### Documentación oficial de Google

- [Google Cloud Console](https://console.cloud.google.com/)
- [OAuth 2.0 para aplicaciones web de servidor](https://developers.google.com/identity/protocols/oauth2/web-server)
- [OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect)
- [Referencia de OpenID Connect](https://developers.google.com/identity/openid-connect/reference)
- [Scopes de OAuth 2.0](https://developers.google.com/identity/protocols/oauth2/scopes)
- [Google OAuth 2.0 Playground](https://developers.google.com/oauthplayground)

### Estándares

- [OAuth 2.0 — RFC 6749](https://www.rfc-editor.org/rfc/rfc6749)
- [OpenID Connect Core 1.0](https://openid.net/specs/openid-connect-core-1_0.html)
- [OAuth 2.0 PKCE — RFC 7636](https://www.rfc-editor.org/rfc/rfc7636)

---

<p align="center">
  <strong>ShieldPHP Google Login</strong>
  <br>
  Autenticación mediante Google OAuth 2.0 y OpenID Connect.
</p>