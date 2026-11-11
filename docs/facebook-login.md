# Facebook Login — ShieldPHP

<p align="center">
  <strong>Autenticación mediante Facebook OAuth 2.0 para aplicaciones PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/OAuth-2.0-4285F4?style=for-the-badge" alt="OAuth 2.0">
  <img src="https://img.shields.io/badge/Provider-Facebook-1877F2?style=for-the-badge&logo=facebook&logoColor=white" alt="Facebook">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

**Facebook Login** es una integración de ShieldPHP que permite a los usuarios autenticarse en aplicaciones PHP utilizando su cuenta de Facebook.

La implementación utiliza OAuth 2.0 para delegar el proceso de autenticación a Meta y obtener información del perfil del usuario después de recibir la autorización correspondiente.

El objetivo es proporcionar una integración reutilizable que permita:

- Iniciar sesión mediante Facebook.
- Redirigir al usuario al proceso de autorización de Meta.
- Gestionar parámetros de seguridad OAuth.
- Intercambiar códigos de autorización por tokens de acceso.
- Obtener información del perfil.
- Identificar usuarios provenientes de Facebook.
- Integrar identidades externas con el sistema de autenticación de ShieldPHP.

La integración utiliza el módulo OAuth del paquete ShieldPHP Core.

## 2. Arquitectura

El flujo de Facebook Login utiliza los siguientes componentes:

| Componente | Responsabilidad |
|---|---|
| `FacebookProvider` | Implementación del proveedor OAuth de Facebook. |
| `OAuthManager` | Coordina el proceso de autenticación OAuth. |
| `OAuthClient` | Realiza solicitudes HTTP al proveedor. |
| `OAuthStateManager` | Administra y valida el parámetro `state`. |
| `PKCEManager` | Administra verificadores y desafíos PKCE cuando el flujo los admite. |
| `TokenResponse` | Representa la respuesta del servidor de autorización. |
| `CallbackHandler` | Coordina el procesamiento del callback. |

Namespace principal del proveedor:

```php
ShieldPHP\Core\OAuth\Providers\FacebookProvider
```

### Diagrama del proceso

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
          FacebookProvider
                   |
                   v
          Facebook Login
             (Meta)
                   |
             Autorización
                   |
                   v
          Callback de la app
                   |
                   v
           Validación STATE
                   |
                   v
         Intercambio del CODE
                   |
                   v
           Access Token
                   |
                   v
          Facebook Graph API
                   |
                   v
            Perfil Facebook
                   |
                   v
           ShieldPHP Core
                   |
                   v
          Identidad local
```

## 3. Requisitos

Para implementar Facebook Login necesitas:

- PHP 8.2 o superior.
- Composer 2.
- ShieldPHP Core.
- Extensión PHP Sodium.
- Extensión PHP JSON.
- Una cuenta de Meta for Developers.
- Una aplicación registrada en Meta.
- Credenciales App ID y App Secret.
- Una URI de retorno OAuth configurada.
- Conexión HTTPS en producción.

La aplicación debe tener habilitado el caso de uso o producto de Facebook Login correspondiente a su modalidad de integración.

## 4. Crear una aplicación en Meta

Antes de utilizar Facebook Login necesitas registrar tu aplicación.

### Paso 1. Acceder a Meta for Developers

Ingresa a:

https://developers.facebook.com/

Inicia sesión con tu cuenta de Facebook.

### Paso 2. Crear una aplicación

En el panel de desarrolladores:

1. Accede a la sección de aplicaciones.
2. Selecciona crear una nueva aplicación.
3. Introduce el nombre de tu aplicación.
4. Proporciona un correo electrónico de contacto.
5. Selecciona el caso de uso relacionado con autenticación e inicio de sesión.
6. Completa la configuración solicitada por Meta.

La organización del panel y los nombres de los casos de uso pueden cambiar con el tiempo.

### Paso 3. Configurar Facebook Login

Agrega o habilita Facebook Login para tu aplicación.

Configura la plataforma web cuando corresponda y establece:

- Dominio de la aplicación.
- URL del sitio web.
- URI de retorno OAuth.
- Opciones de inicio de sesión mediante OAuth.

### Paso 4. Obtener las credenciales

Desde la configuración básica de la aplicación podrás consultar:

| Credencial | Descripción |
|---|---|
| App ID | Identificador público de la aplicación. |
| App Secret | Secreto privado utilizado por el servidor. |

Ejemplo:

```text
APP ID: 123456789012345
APP SECRET: ************************
```

El ejemplo es ilustrativo. Nunca publiques el App Secret en GitHub ni lo incluyas en el frontend.

### Paso 5. Configurar URI de retorno

En los ajustes de Facebook Login, registra la dirección exacta a la que Meta devolverá al usuario después de la autorización.

Ejemplo local:

```text
http://localhost:8000/oauth/facebook/callback
```

Ejemplo de producción:

```text
https://midominio.com/oauth/facebook/callback
```

La dirección utilizada en la solicitud OAuth debe coincidir exactamente con una URI autorizada.

### Paso 6. Configurar permisos

Para el inicio de sesión básico se utilizan los permisos:

| Permiso | Descripción |
|---|---|
| `public_profile` | Acceso a información pública básica del perfil. |
| `email` | Solicitud de acceso al correo electrónico disponible. |

El correo electrónico puede no estar disponible para todos los usuarios. La aplicación debe contemplar perfiles sin correo electrónico.

Los permisos y su disponibilidad dependen de la configuración de Meta, las autorizaciones concedidas y los requisitos de revisión aplicables.

## 5. Configuración de ShieldPHP

ShieldPHP contempla las siguientes variables en `.env.example`:

```dotenv
# ==========================================
# FACEBOOK OAUTH
# ==========================================

SHIELDPHP_OAUTH_FACEBOOK_ENABLED=false

SHIELDPHP_OAUTH_FACEBOOK_CLIENT_ID=
SHIELDPHP_OAUTH_FACEBOOK_CLIENT_SECRET=

SHIELDPHP_OAUTH_FACEBOOK_REDIRECT_URI=http://localhost:8000/oauth/facebook/callback

# ==========================================
# OAUTH SECURITY
# ==========================================

SHIELDPHP_OAUTH_STATE_TTL=600
SHIELDPHP_OAUTH_PKCE_ENABLED=true
```

### Descripción de variables

| Variable | Descripción |
|---|---|
| `SHIELDPHP_OAUTH_FACEBOOK_ENABLED` | Activa o desactiva la integración. |
| `SHIELDPHP_OAUTH_FACEBOOK_CLIENT_ID` | App ID de Meta. |
| `SHIELDPHP_OAUTH_FACEBOOK_CLIENT_SECRET` | App Secret de Meta. |
| `SHIELDPHP_OAUTH_FACEBOOK_REDIRECT_URI` | URI de retorno autorizada. |
| `SHIELDPHP_OAUTH_STATE_TTL` | Tiempo de vida del estado OAuth. |
| `SHIELDPHP_OAUTH_PKCE_ENABLED` | Opción prevista para utilizar PKCE. |

La activación de estas variables no configura automáticamente el proveedor. La aplicación debe cargar los valores y construir sus dependencias.

## 6. Instalar y preparar dependencias

Desde el directorio del proyecto:

```bash
composer install
composer dump-autoload
```

El paquete Core utiliza las interfaces PSR y los componentes OAuth correspondientes.

La integración de Facebook requiere un cliente HTTP compatible con las interfaces utilizadas por `OAuthClient`.

## 7. FacebookProvider

`FacebookProvider` es el componente encargado de comunicarse con Facebook y ejecutar las operaciones propias del proveedor.

**Namespace:**

```php
ShieldPHP\Core\OAuth\Providers\FacebookProvider
```

La clase implementa el contrato OAuth utilizado por ShieldPHP.

### Responsabilidades

- Identificar el proveedor mediante su nombre.
- Construir la URL de autorización.
- Intercambiar códigos de autorización.
- Obtener tokens de acceso.
- Consultar información del usuario.
- Transformar los datos recibidos en respuestas compatibles con ShieldPHP.

### Métodos del contrato

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

La implementación de Facebook adapta estos métodos al protocolo del proveedor.

## 8. Flujo OAuth 2.0

ShieldPHP utiliza como referencia el flujo Authorization Code.

### Etapa 1. Solicitar autenticación

El usuario selecciona el botón de inicio de sesión con Facebook.

La aplicación inicia el flujo OAuth mediante `OAuthManager`.

### Etapa 2. Generar estado OAuth

Se genera un valor aleatorio `state` y se almacena de forma temporal.

Este parámetro permite comprobar que la respuesta recibida corresponde a una solicitud iniciada por la aplicación.

### Etapa 3. Redirigir a Facebook

El navegador se dirige al endpoint de autorización:

```text
https://www.facebook.com/v{API_VERSION}/dialog/oauth
```

Parámetros habituales:

| Parámetro | Descripción |
|---|---|
| `client_id` | App ID. |
| `redirect_uri` | URI de retorno. |
| `response_type` | `code`. |
| `scope` | Permisos solicitados. |
| `state` | Valor de protección contra CSRF. |

Los permisos solicitados para el ejemplo son:

```text
public_profile,email
```

La versión de Graph API debe configurarse centralmente y mantenerse dentro de las versiones admitidas por Meta.

### Etapa 4. Autorización del usuario

Facebook presenta al usuario una pantalla para autorizar los permisos solicitados.

El usuario puede aceptar o cancelar el proceso.

### Etapa 5. Retorno al callback

Si la autorización se completa, Meta redirige al usuario a la URI registrada y proporciona un código de autorización.

Ejemplo conceptual:

```text
https://midominio.com/oauth/facebook/callback?code=AUTHORIZATION_CODE&state=RANDOM_STATE
```

Si el usuario cancela o Meta devuelve un error, la aplicación debe procesar el parámetro de error y finalizar el proceso de forma segura.

### Etapa 6. Validación del estado

ShieldPHP debe consumir el `state` almacenado y comprobar su correspondencia con la solicitud original.

Si el estado no coincide, ha expirado o ya fue consumido, el proceso debe rechazarse.

### Etapa 7. Intercambio del código

El servidor utiliza el código de autorización, las credenciales de la aplicación y la URI de retorno para solicitar un token de acceso.

Endpoint:

```text
https://graph.facebook.com/v{API_VERSION}/oauth/access_token
```

Parámetros del intercambio:

```text
client_id
client_secret
redirect_uri
code
```

El intercambio debe realizarse en el servidor. El App Secret nunca debe enviarse al navegador.

### Etapa 8. Obtener el perfil

Después de obtener el token, ShieldPHP puede consultar el perfil del usuario mediante Graph API.

Endpoint conceptual:

```text
https://graph.facebook.com/v{API_VERSION}/me
```

Campos solicitados:

```text
id,name,email
```

La respuesta puede tener una estructura similar a:

```json
{
    "id": "123456789012345",
    "name": "Usuario de ejemplo",
    "email": "usuario@example.com"
}
```

Esta respuesta es ilustrativa. Facebook puede omitir campos que no estén disponibles o no hayan sido autorizados.

### Etapa 9. Crear o recuperar la identidad local

La aplicación utiliza el identificador recibido de Facebook para buscar la identidad asociada al proveedor.

Se recomienda identificar la cuenta mediante la combinación:

```text
provider = facebook
provider_user_id = FACEBOOK_USER_ID
```

Si no existe una identidad asociada, la aplicación puede iniciar su propio proceso de registro.

No debe vincular cuentas automáticamente utilizando únicamente la coincidencia de correos electrónicos.

## 9. Integración con OAuthManager

ShieldPHP contempla `OAuthManager` como punto central para registrar proveedores e iniciar procesos de autorización.

### Registrar el proveedor

Ejemplo conceptual:

```php
use ShieldPHP\Core\OAuth\OAuthManager;

// Las dependencias y credenciales del proveedor
// deben construirse previamente.

$oauthManager = $container->get(
    OAuthManager::class
);

$facebookProvider = $container->get(
    FacebookProvider::class
);

$oauthManager->registerProvider(
    'facebook',
    $facebookProvider
);
```

La creación concreta de `FacebookProvider` depende de su constructor y de la configuración HTTP implementada en el Core.

### Obtener la URL de autorización

```php
$authorizationUrl = $oauthManager->getAuthorizationUrl(
    'facebook'
);
```

La aplicación debe redirigir el navegador a esa URL.

Ejemplo PHP:

```php
header('Location: ' . $authorizationUrl, true, 302);

exit;
```

En aplicaciones con Slim o Laravel se recomienda utilizar los mecanismos de respuesta HTTP del framework.

### Procesar el callback

Cuando Meta devuelve al usuario a la aplicación:

```php
$callbackResult = $oauthManager->handleCallback(
    'facebook',
    $_GET
);
```

Este proceso debe comprobar el estado, procesar el código y coordinar el intercambio de tokens y la obtención del perfil según las responsabilidades de los componentes OAuth.

El resultado debe manejarse de acuerdo con el contrato real de retorno de `OAuthManager`.

## 10. Obtención de información del usuario

El perfil de Facebook puede proporcionar información que ayude a registrar una identidad local.

### Campos contemplados

| Campo | Descripción |
|---|---|
| `id` | Identificador del usuario en Facebook. |
| `name` | Nombre del perfil. |
| `email` | Correo electrónico, cuando está disponible. |

### Recomendaciones

El identificador de Facebook debe utilizarse como identificador externo de la identidad.

El correo electrónico debe tratarse como información opcional, no como una prueba suficiente para vincular cuentas.

La aplicación debe contemplar los siguientes casos:

- El usuario no tiene un correo disponible.
- El usuario no concede el permiso `email`.
- El proveedor no devuelve el campo solicitado.
- El correo ya está asociado a otra cuenta local.
- El usuario ya tiene una identidad vinculada a Facebook.

### Modelo de identidad recomendado

```json
{
    "provider": "facebook",
    "provider_user_id": "123456789012345",
    "name": "Usuario de ejemplo",
    "email": "usuario@example.com"
}
```

Este modelo es ilustrativo y no representa una entidad implementada obligatoriamente por ShieldPHP Core.

## 11. Manejo de errores

El proceso de Facebook Login puede fallar por distintas causas.

| Error | Posible causa | Acción recomendada |
|---|---|---|
| `access_denied` | El usuario canceló el inicio de sesión. | Permitir volver a intentarlo. |
| `invalid_request` | Parámetros de autorización incorrectos. | Revisar la solicitud OAuth. |
| `redirect_uri_mismatch` | URI no registrada o diferente. | Verificar la configuración en Meta. |
| `invalid_grant` | Código inválido, expirado o reutilizado. | Iniciar un nuevo proceso OAuth. |
| Error de permisos | Permisos no concedidos o no disponibles. | Revisar la configuración del caso de uso. |
| Error de aplicación | Credenciales incorrectas o aplicación mal configurada. | Verificar App ID y App Secret. |
| Perfil incompleto | Campos no disponibles. | Manejar valores opcionales. |
| Token inválido | Token expirado, revocado o no válido. | Rechazar la identidad y solicitar una nueva autorización. |

Los mensajes internos de Meta deben registrarse cuidadosamente y nunca deben exponer credenciales o tokens.

### Respuesta de error para la aplicación

```json
{
    "success": false,
    "error": "oauth_authentication_failed",
    "message": "No fue posible completar la autenticación con Facebook."
}
```

Esta es una respuesta de referencia para la aplicación consumidora.

## 12. Seguridad

La integración debe seguir las recomendaciones de seguridad de OAuth 2.0 y las prácticas establecidas por ShieldPHP.

### Protección del estado OAuth

- Generar valores aleatorios seguros.
- Almacenar el estado de forma temporal.
- Vincularlo con la sesión o transacción correspondiente.
- Validarlo durante el callback.
- Consumirlo una sola vez.
- Rechazar valores expirados o incorrectos.

### Protección de credenciales

- Mantener el App Secret exclusivamente en el servidor.
- No incluir credenciales en JavaScript.
- No almacenar secretos dentro del repositorio.
- Utilizar variables de entorno o un administrador de secretos.
- Evitar registrar tokens completos.

### Protección de tokens

- No confiar en tokens recibidos desde parámetros no verificados.
- Validar que el token pertenezca a la aplicación correspondiente cuando se utilicen los mecanismos de inspección de Meta.
- Respetar su expiración.
- No aceptar perfiles obtenidos sin una respuesta autenticada del proveedor.
- Evitar enviar tokens mediante URL cuando exista una alternativa segura.

### Protección de callbacks

- Utilizar URI HTTPS en producción.
- Validar estrictamente las URI de retorno.
- Comprobar el parámetro `state`.
- Rechazar códigos reutilizados.
- No aceptar redirecciones arbitrarias después del inicio de sesión.

### Uso de PKCE

OAuth 2.0 PKCE proporciona protección adicional para el intercambio de códigos en flujos compatibles.

ShieldPHP dispone de un componente `PKCEManager`, pero su presencia no demuestra por sí sola que el proveedor Facebook implemente o admita PKCE en la versión configurada.

Debe verificarse la compatibilidad en la documentación actual de Meta y en la implementación real de `FacebookProvider` antes de habilitarlo.

## 13. Integración con Authentication

Después de obtener una identidad válida de Facebook, la aplicación puede relacionarla con el módulo Authentication.

La separación de responsabilidades recomendada es:

| Componente | Responsabilidad |
|---|---|
| FacebookProvider | Comunicación con Facebook. |
| OAuthManager | Coordinación OAuth. |
| TokenResponse | Representación del token externo. |
| UserProviderInterface | Recuperación de identidades locales. |
| AuthManager | Autenticación mediante credenciales locales. |
| UserIdentity | Representación de la identidad local. |

Facebook Login no debe considerarse automáticamente equivalente a una sesión local de ShieldPHP.

La aplicación necesita establecer explícitamente cómo crear, recuperar y mantener la identidad autenticada.

## 14. Configuración de producción

Antes de habilitar Facebook Login en producción se recomienda:

1. Configurar el dominio definitivo de la aplicación.
2. Registrar la URI HTTPS de callback.
3. Verificar las credenciales de Meta.
4. Revisar los permisos solicitados.
5. Completar los requisitos de revisión aplicables.
6. Configurar el estado de publicación de la aplicación.
7. Proteger el App Secret.
8. Validar los tokens y perfiles.
9. Configurar el manejo de errores.
10. Probar el proceso completo con cuentas autorizadas y cuentas externas de prueba.

Las aplicaciones en modo de desarrollo pueden limitar el acceso a personas que tengan un rol habilitado dentro de la aplicación de Meta.

Para habilitar el acceso público deben cumplirse los requisitos de publicación y permisos correspondientes.

## 15. Pruebas

ShieldPHP contempla una estructura de pruebas para el proveedor Facebook:

```text
packages/php-core/tests/Unit/OAuth/Providers/
```

Archivo:

```text
FacebookProviderTest.php
```

### Escenarios de prueba recomendados

| Escenario | Objetivo |
|---|---|
| Nombre del proveedor | Verificar que se identifique como `facebook`. |
| URL de autorización | Comprobar los parámetros OAuth. |
| Estado | Comprobar el parámetro `state`. |
| Intercambio de código | Validar la respuesta del endpoint de tokens. |
| Token de acceso | Comprobar el procesamiento de la respuesta. |
| Perfil de usuario | Verificar la obtención de datos. |
| Perfil incompleto | Manejar correo electrónico ausente. |
| Error HTTP | Controlar fallos del proveedor. |
| Token inválido | Rechazar credenciales no válidas. |
| Seguridad | Comprobar restricciones HTTPS y de dominios. |

### Ejecutar pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

Para ejecutar específicamente las pruebas de Facebook:

```bash
vendor/bin/phpunit tests/Unit/OAuth/Providers/FacebookProviderTest.php
```

Se recomienda utilizar respuestas HTTP simuladas para las pruebas unitarias y evitar depender de cuentas reales de Facebook.

## 16. Estructura del módulo

```text
shieldphp/
│
├── packages/
│   └── php-core/
│       ├── src/
│       │   ├── OAuth/
│       │   │   ├── Providers/
│       │   │   │   └── FacebookProvider.php
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
│                       └── FacebookProviderTest.php
│
├── examples/
│   └── oauth-facebook/
│       └── README.md
│
└── docs/
    └── facebook-login.md
```

## 17. Estado actual

| Funcionalidad | Estado |
|---|---|
| FacebookProvider | Implementación inicial |
| Contrato OAuth | Base implementada |
| Generación de URL de autorización | Implementación inicial |
| Intercambio de códigos | Implementación inicial |
| Obtención de perfil | Implementación inicial |
| Integración con OAuthManager | En desarrollo |
| Gestión de estado OAuth | Base implementada |
| Integración PKCE | Requiere verificar compatibilidad del proveedor |
| Vinculación con usuarios locales | Pendiente de integración |
| Verificación de identidad externa | Pendiente de completar |
| Pruebas unitarias | Suite inicial |
| Pruebas de integración reales | Pendientes |
| Validación para producción | Pendiente |

## 18. Consideraciones técnicas

Facebook Login utiliza OAuth 2.0 y Graph API.

Los endpoints deben configurarse con una versión de Graph API que esté admitida por Meta.

Las rutas de referencia son:

```text
Autorización:
https://www.facebook.com/v{API_VERSION}/dialog/oauth

Intercambio de tokens:
https://graph.facebook.com/v{API_VERSION}/oauth/access_token

Perfil:
https://graph.facebook.com/v{API_VERSION}/me
```

La versión no debe quedar dispersa en diferentes clases. Se recomienda centralizarla en la configuración del proveedor.

También es importante considerar que:

- El token de Facebook no es un JWT de ShieldPHP.
- El token externo no sustituye el token de acceso de la aplicación.
- El correo electrónico no está garantizado.
- Los identificadores externos deben almacenarse junto al nombre del proveedor.
- Los errores de Meta deben procesarse sin filtrar secretos.
- La identidad externa debe validarse antes de vincularla con una cuenta local.

---

## 19. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authentication.md` — Sistema de autenticación.
- `docs/authorization.md` — Roles y permisos.
- `docs/configuration.md` — Configuración de ShieldPHP.
- `docs/encryption.md` — Herramientas criptográficas.
- `examples/oauth-facebook/README.md` — Ejemplo de integración.
- `SECURITY.md` — Política de seguridad.

### Documentación externa

- [Meta for Developers](https://developers.facebook.com/)
- [Facebook Login](https://developers.facebook.com/documentation/facebook-login/)
- [Guía para construir manualmente un flujo de inicio de sesión](https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow/)
- [Referencia de Graph API](https://developers.facebook.com/docs/graph-api/)
- [Permisos de Facebook Login](https://developers.facebook.com/docs/permissions/)
- [OAuth 2.0 — RFC 6749](https://www.rfc-editor.org/rfc/rfc6749)
- [OAuth 2.0 PKCE — RFC 7636](https://www.rfc-editor.org/rfc/rfc7636)

---

<p align="center">
  <strong>ShieldPHP Facebook Login</strong>
  <br>
  Autenticación social mediante OAuth 2.0 para PHP.
</p>