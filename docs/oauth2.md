# OAuth 2.0 — ShieldPHP

<p align="center">
  <strong>Autorización delegada e integración con proveedores externos para PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/OAuth-2.0-4285F4?style=for-the-badge" alt="OAuth 2.0">
  <img src="https://img.shields.io/badge/PKCE-Supported-purple?style=for-the-badge" alt="PKCE">
  <img src="https://img.shields.io/badge/Architecture-Modular-blue?style=for-the-badge" alt="Arquitectura modular">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

El módulo **OAuth 2.0** de ShieldPHP proporciona componentes para implementar flujos de autorización delegada e integrar aplicaciones PHP con proveedores de identidad y servicios externos.

Su objetivo es ofrecer una arquitectura modular que permita gestionar procesos de autorización sin acoplar la aplicación a un proveedor específico.

ShieldPHP contempla:

- Autorización mediante OAuth 2.0.
- Flujo Authorization Code.
- Generación de URL de autorización.
- Gestión de parámetros `state`.
- Protección mediante PKCE.
- Intercambio de códigos de autorización por tokens.
- Administración de respuestas de tokens.
- Obtención de perfiles de usuario.
- Integración con proveedores externos.
- Validación de dominios y conexiones HTTPS.
- Gestión de callbacks.

El módulo está diseñado para funcionar con proveedores independientes mediante contratos comunes.

### OAuth 2.0 y autenticación

Es importante distinguir dos conceptos:

**OAuth 2.0:** permite que una aplicación obtenga autorización para acceder a recursos de un usuario.

**OpenID Connect:** añade una capa de autenticación sobre OAuth 2.0 para verificar la identidad del usuario.

Por tanto, OAuth 2.0 por sí solo no garantiza que el perfil obtenido corresponda a una identidad autenticada.

Para iniciar sesión con proveedores como Google se recomienda utilizar OpenID Connect y validar criptográficamente los ID Tokens.

## 2. Arquitectura

El módulo OAuth utiliza diferentes componentes para separar las responsabilidades del proceso.

| Componente | Responsabilidad |
|---|---|
| `OAuthManager` | Coordinar los procesos OAuth. |
| `OAuthProviderInterface` | Definir el contrato de proveedores. |
| `OAuthClient` | Ejecutar solicitudes HTTP. |
| `AuthorizationUrlBuilder` | Construir direcciones de autorización. |
| `OAuthStateManager` | Generar y validar estados OAuth. |
| `PKCEManager` | Administrar desafíos y verificadores PKCE. |
| `TokenResponse` | Representar respuestas del servidor de autorización. |
| `CallbackHandler` | Procesar respuestas de autorización. |

Namespace principal:

```php
ShieldPHP\Core\OAuth\
```

### Diagrama general

```text
                  APLICACIÓN PHP
                        |
                        v
                  OAuthManager
                        |
              ┌─────────┴─────────┐
              |                   |
              v                   v
       OAuthStateManager      PKCEManager
              |                   |
              └─────────┬─────────┘
                        |
                        v
              AuthorizationUrlBuilder
                        |
                        v
                OAuthProviderInterface
                        |
              ┌─────────┴─────────┐
              |                   |
              v                   v
          GoogleProvider     FacebookProvider
              |                   |
              └─────────┬─────────┘
                        |
                        v
                   OAuthClient
                        |
                        v
                SERVIDOR EXTERNO
                        |
                        v
                 AUTHORIZATION CODE
                        |
                        v
                  CALLBACK HANDLER
                        |
                        v
                  TOKEN RESPONSE
                        |
                        v
                PERFIL / RECURSOS
```

## 3. Requisitos

Para trabajar con el módulo OAuth necesitas:

- PHP 8.2 o superior.
- Composer 2.
- ShieldPHP Core.
- Extensión PHP JSON.
- Extensión PHP Sodium.
- Interfaces PSR-7.
- Interfaces PSR-17.
- Interfaces PSR-18.
- Conexión HTTPS.
- Credenciales de los proveedores utilizados.

Las integraciones pueden requerir dependencias adicionales dependiendo del framework y del cliente HTTP utilizado.

## 4. Contrato OAuthProviderInterface

Los proveedores deben seguir un contrato común para que ShieldPHP pueda utilizarlos independientemente de sus endpoints y particularidades.

**Namespace:**

```php
ShieldPHP\Core\Contracts\OAuthProviderInterface
```

También se contempla la interfaz:

```php
ShieldPHP\Core\OAuth\OAuthProviderInterface
```

Esta última extiende el contrato ubicado en `Contracts`.

### Métodos principales

```php
interface OAuthProviderInterface
{
    public function getName(): string;

    public function getAuthorizationUrl(
        string $state,
        string $redirectUri,
        ?string $challenge = null
    ): string;

    public function exchangeCodeForToken(
        string $code,
        string $redirectUri,
        ?string $verifier = null
    ): TokenResponse;

    public function fetchUserProfile(
        TokenResponse $token
    ): array;
}
```

### getName()

Devuelve el identificador del proveedor.

Ejemplos:

```text
google
facebook
```

### getAuthorizationUrl()

Construye la dirección a la que debe redirigirse el usuario para iniciar el proceso de autorización.

Recibe:

- El estado OAuth.
- La URI de retorno.
- El desafío PKCE, cuando corresponda.

### exchangeCodeForToken()

Intercambia el código de autorización recibido en el callback por una respuesta de tokens.

Recibe:

- El código de autorización.
- La URI de retorno.
- El verificador PKCE cuando corresponda.

### fetchUserProfile()

Permite obtener información del usuario utilizando el token de acceso recibido.

El formato del perfil depende del proveedor y de los permisos concedidos.

## 5. OAuthManager

`OAuthManager` funciona como punto central para administrar los proveedores registrados y coordinar los procesos de autorización.

**Namespace:**

```php
ShieldPHP\Core\OAuth\OAuthManager
```

### Responsabilidades

- Registrar proveedores OAuth.
- Identificar proveedores mediante un nombre.
- Iniciar procesos de autorización.
- Obtener direcciones de autorización.
- Coordinar el procesamiento de callbacks.
- Integrar los mecanismos de estado y PKCE.

### Métodos contemplados

```php
registerProvider(
    string $name,
    OAuthProviderInterface $provider
): void;

getAuthorizationUrl(
    string $providerName
): string;

handleCallback(
    string $providerName,
    array $query
);
```

El contrato de retorno de `handleCallback()` y los parámetros exactos de construcción deben confirmarse contra la implementación concreta del manager.

### Registro de proveedores

Ejemplo conceptual:

```php
use ShieldPHP\Core\OAuth\OAuthManager;

$oauthManager = $container->get(
    OAuthManager::class
);

$googleProvider = $container->get(
    GoogleProvider::class
);

$facebookProvider = $container->get(
    FacebookProvider::class
);

$oauthManager->registerProvider(
    'google',
    $googleProvider
);

$oauthManager->registerProvider(
    'facebook',
    $facebookProvider
);
```

Cada proveedor debe construirse previamente con sus credenciales, configuración y dependencias HTTP correspondientes.

## 6. AuthorizationUrlBuilder

`AuthorizationUrlBuilder` permite construir URL de autorización con parámetros OAuth.

**Namespace:**

```php
ShieldPHP\Core\OAuth\AuthorizationUrlBuilder
```

### Responsabilidades

- Establecer el endpoint de autorización.
- Incorporar el identificador del cliente.
- Configurar los scopes.
- Establecer la URI de retorno.
- Incorporar el estado OAuth.
- Añadir parámetros PKCE.
- Agregar parámetros adicionales permitidos.

### Parámetros habituales

| Parámetro | Descripción |
|---|---|
| `client_id` | Identificador del cliente OAuth. |
| `redirect_uri` | URI de retorno. |
| `response_type` | Generalmente `code`. |
| `scope` | Permisos solicitados. |
| `state` | Valor de protección CSRF. |
| `code_challenge` | Desafío PKCE. |
| `code_challenge_method` | Método de generación del desafío. |

### Construcción conceptual

```php
$authorizationUrl = $builder->build(
    redirectUri: $redirectUri,
    state: $state,
    challenge: $challenge
);
```

La clase contempla la validación HTTPS del endpoint y la construcción de parámetros de autorización.

Las URI de retorno deben validarse mediante una lista de direcciones permitidas.

## 7. OAuthClient

`OAuthClient` es el cliente HTTP utilizado para comunicarse con los proveedores.

**Namespace:**

```php
ShieldPHP\Core\OAuth\OAuthClient
```

Su objetivo es centralizar las solicitudes HTTP y aplicar restricciones de seguridad a las conexiones externas.

### Constructor

La implementación contempla dependencias PSR:

```php
public function __construct(
    ClientInterface $httpClient,
    RequestFactoryInterface $requestFactory,
    StreamFactoryInterface $streamFactory,
    array $allowedHosts
);
```

### Dependencias

| Dependencia | Responsabilidad |
|---|---|
| `ClientInterface` | Ejecutar solicitudes HTTP. |
| `RequestFactoryInterface` | Crear solicitudes. |
| `StreamFactoryInterface` | Crear cuerpos de solicitudes. |
| `allowedHosts` | Restringir los dominios permitidos. |

### Métodos

```php
getJson(string $url, array $headers = []): array;

postForm(string $url, array $data, array $headers = []): array;
```

### getJson()

Realiza una solicitud HTTP para obtener información JSON.

Ejemplo conceptual:

```php
$response = $oauthClient->getJson(
    'https://api.example.com/userinfo',
    [
        'Authorization' => 'Bearer ACCESS_TOKEN'
    ]
);
```

### postForm()

Permite enviar parámetros mediante un formulario HTTP.

Ejemplo conceptual:

```php
$tokenResponse = $oauthClient->postForm(
    'https://provider.example.com/oauth/token',
    [
        'grant_type' => 'authorization_code',
        'code' => $authorizationCode,
        'redirect_uri' => $redirectUri
    ]
);
```

### Restricciones de seguridad

La implementación contempla:

- Uso de HTTPS.
- Validación de hosts permitidos.
- Restricción de destinos HTTP.
- Deshabilitación de redirecciones automáticas.
- Uso de interfaces PSR.

Estas medidas ayudan a reducir riesgos asociados con solicitudes a destinos no autorizados.

Las restricciones deben aplicarse a todos los endpoints utilizados durante el proceso OAuth.

## 8. OAuthStateManager

`OAuthStateManager` gestiona el parámetro `state`.

**Namespace:**

```php
ShieldPHP\Core\OAuth\OAuthStateManager
```

El estado es un valor aleatorio que permite relacionar el callback recibido con la operación de autorización iniciada por la aplicación.

### Responsabilidades

- Generar estados aleatorios.
- Almacenar su representación de manera segura.
- Asociarles una expiración.
- Consumir estados recibidos.
- Rechazar estados desconocidos.
- Impedir la reutilización.

### Constructor

```php
public function __construct(
    OAuthStateStoreInterface $store,
    int $ttl = 600,
    ?ClockInterface $clock = null
);
```

La dependencia de reloj permite controlar el tiempo durante las operaciones cuando se utiliza la implementación correspondiente.

### Métodos

```php
generateAndStore(): string;

consume(string $state): bool;
```

### Ejemplo

```php
$state = $oauthStateManager->generateAndStore();
```

Después del callback:

```php
if (!$oauthStateManager->consume($receivedState)) {
    throw new RuntimeException(
        'El estado OAuth no es válido.'
    );
}
```

El estado debe estar vinculado de manera segura a la transacción iniciada por el cliente y consumirse una sola vez.

### Tiempo de vida

El valor inicial contemplado es:

```text
600 segundos
```

Equivalente a diez minutos.

## 9. OAuthStateStoreInterface

Esta interfaz define el contrato de almacenamiento para los estados OAuth.

**Métodos:**

```php
interface OAuthStateStoreInterface
{
    public function store(
        string $stateHash,
        DateTimeImmutable $expiresAt
    ): void;

    public function consume(
        string $stateHash,
        DateTimeImmutable $now
    ): bool;

    public function removeExpired(
        DateTimeImmutable $now
    ): int;
}
```

### Métodos

| Método | Responsabilidad |
|---|---|
| `store()` | Almacenar el hash del estado y su expiración. |
| `consume()` | Consumir un estado válido. |
| `removeExpired()` | Eliminar estados expirados. |

La implementación puede utilizar memoria, Redis, bases de datos u otro mecanismo de almacenamiento.

El almacenamiento debe permitir que el estado se recupere desde el mismo contexto de usuario o transacción que inició el proceso OAuth.

## 10. PKCEManager

`PKCEManager` administra los parámetros utilizados por Proof Key for Code Exchange.

**Namespace:**

```php
ShieldPHP\Core\OAuth\PKCEManager
```

PKCE agrega una protección al flujo Authorization Code mediante un valor secreto que debe conservar el cliente.

### Conceptos principales

**Code Verifier:** valor aleatorio generado por el cliente.

**Code Challenge:** valor derivado del verifier que se envía durante la autorización.

**S256:** método recomendado para generar el desafío mediante SHA-256 y codificación Base64URL sin relleno.

### Flujo

```text
              CLIENTE
                 |
                 v
          Generar Verifier
                 |
                 v
          Calcular Challenge
                 |
                 v
        Enviar Challenge al proveedor
                 |
                 v
         Autorizar la solicitud
                 |
                 v
          Recibir Authorization Code
                 |
                 v
       Enviar Code + Verifier
                 |
                 v
          Validación PKCE
                 |
                 v
           Obtener Tokens
```

### Constructor

La implementación inicial contempla un tiempo de vida configurable.

```php
public function __construct(
    PKCEStoreInterface $store,
    int $ttl = 600
);
```

### Métodos

```php
createChallenge(string $state): string;

consumeVerifier(string $state): ?string;
```

### Crear un desafío

```php
$challenge = $pkceManager->createChallenge($state);
```

El componente administra el material necesario para recuperar posteriormente el verificador relacionado con el estado.

### Consumir el verificador

```php
$verifier = $pkceManager->consumeVerifier($state);
```

El verificador debe recuperarse de manera segura y utilizarse únicamente en el intercambio correspondiente.

### Recomendaciones

- Generar valores impredecibles.
- Utilizar `S256`.
- No transmitir el verifier en la solicitud de autorización.
- Asociar el verifier con una única transacción.
- Aplicar expiración.
- Evitar reutilizar verificadores.
- Eliminar los datos temporales una vez finalizado el proceso.

La disponibilidad de `PKCEManager` en ShieldPHP no garantiza que todos los proveedores externos admitan PKCE. La compatibilidad debe verificarse para cada proveedor.

## 11. PKCEStoreInterface

Define el contrato para almacenar los verificadores PKCE.

```php
interface PKCEStoreInterface
{
    public function store(
        string $stateHash,
        string $verifier,
        DateTimeImmutable $expiresAt
    ): void;

    public function consume(
        string $stateHash,
        DateTimeImmutable $now
    ): ?string;
}
```

### Métodos

| Método | Descripción |
|---|---|
| `store()` | Guarda un verificador asociado con un estado. |
| `consume()` | Recupera y consume un verificador válido. |

El almacenamiento debe impedir el uso de verificadores expirados o reutilizados.

## 12. TokenResponse

`TokenResponse` representa las respuestas obtenidas de los endpoints de tokens.

**Namespace:**

```php
ShieldPHP\Core\OAuth\TokenResponse
```

### Información contemplada

| Propiedad | Descripción |
|---|---|
| `accessToken` | Token de acceso. |
| `tokenType` | Tipo de token. |
| `expiresIn` | Tiempo de expiración. |
| `refreshToken` | Token de actualización. |
| `scope` | Permisos autorizados. |
| `idToken` | Token de identidad, cuando corresponde. |
| `receivedAt` | Fecha de recepción. |

### Métodos

```php
TokenResponse::fromArray(array $data): self;

isExpired(): bool;
```

También proporciona métodos de acceso a sus propiedades.

### Crear una respuesta

```php
$tokenResponse = TokenResponse::fromArray([
    'access_token' => 'ACCESS_TOKEN',
    'token_type' => 'Bearer',
    'expires_in' => 3600,
    'refresh_token' => 'REFRESH_TOKEN',
    'scope' => 'profile email',
    'id_token' => null
]);
```

El contenido del ejemplo es ilustrativo.

### Comprobar expiración

```php
if ($tokenResponse->isExpired()) {
    throw new RuntimeException(
        'El token de acceso ha expirado.'
    );
}
```

La expiración depende del tiempo de recepción y de la información proporcionada por el proveedor.

No todos los proveedores entregan un refresh token en cada intercambio.

## 13. CallbackHandler

`CallbackHandler` es el componente destinado a coordinar el procesamiento de respuestas de autorización.

Su responsabilidad es ayudar a mantener un flujo controlado desde la recepción del callback hasta el intercambio del código.

### Responsabilidades previstas

- Procesar parámetros de retorno.
- Comprobar errores recibidos del proveedor.
- Validar el estado OAuth.
- Recuperar el verificador PKCE cuando corresponda.
- Intercambiar el código por tokens.
- Solicitar información del usuario.
- Entregar el resultado a la aplicación.

### Secuencia de procesamiento

1. Recibir la respuesta OAuth.
2. Comprobar que no exista un error del proveedor.
3. Validar y consumir el estado.
4. Recuperar el verificador PKCE cuando se utilizó.
5. Intercambiar el código.
6. Recuperar el perfil, cuando corresponda.
7. Entregar los datos del proveedor a la aplicación.

El contrato público de construcción y retorno de `CallbackHandler` debe verificarse en su implementación.

## 14. Registrar proveedores

ShieldPHP contempla diferentes proveedores mediante un contrato común.

### Google

```php
use ShieldPHP\Core\OAuth\Providers\GoogleProvider;

$googleProvider = $container->get(
    GoogleProvider::class
);

$oauthManager->registerProvider(
    'google',
    $googleProvider
);
```

Documentación específica:

```text
docs/google-login.md
```

### Facebook

```php
use ShieldPHP\Core\OAuth\Providers\FacebookProvider;

$facebookProvider = $container->get(
    FacebookProvider::class
);

$oauthManager->registerProvider(
    'facebook',
    $facebookProvider
);
```

Documentación específica:

```text
docs/facebook-login.md
```

Los proveedores pueden utilizar configuraciones, endpoints y parámetros diferentes, pero deben mantener el contrato común.

## 15. Flujo Authorization Code

ShieldPHP contempla el flujo Authorization Code como mecanismo general de autorización.

### Paso 1. Iniciar la solicitud

El cliente solicita autenticación o autorización desde la aplicación.

### Paso 2. Generar state y PKCE

La aplicación genera el estado y, cuando el proveedor lo admite, un desafío PKCE.

### Paso 3. Construir la URL

Se obtiene la URL de autorización mediante el proveedor correspondiente.

### Paso 4. Redirigir al proveedor

La aplicación redirige el navegador al endpoint de autorización.

### Paso 5. Recibir el callback

El proveedor devuelve un código de autorización o un error.

### Paso 6. Validar el estado

Se comprueba la correspondencia de la solicitud original.

### Paso 7. Intercambiar el código

El servidor realiza una solicitud al endpoint de tokens.

### Paso 8. Obtener información

La aplicación puede solicitar un perfil o un recurso autorizado, dependiendo de los scopes.

### Paso 9. Finalizar el proceso

La aplicación decide si establece una sesión local, crea una identidad o emite sus propios tokens.

El token externo no debe confundirse automáticamente con un token local de ShieldPHP.

## 16. Configuración mediante variables de entorno

ShieldPHP contempla las siguientes variables en `.env.example`:

```dotenv
# ==========================================
# OAUTH - GOOGLE
# ==========================================

SHIELDPHP_OAUTH_GOOGLE_ENABLED=false
SHIELDPHP_OAUTH_GOOGLE_CLIENT_ID=
SHIELDPHP_OAUTH_GOOGLE_CLIENT_SECRET=
SHIELDPHP_OAUTH_GOOGLE_REDIRECT_URI=http://localhost:8000/oauth/google/callback

# ==========================================
# OAUTH - FACEBOOK
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

### Configuraciones

| Variable | Función |
|---|---|
| `SHIELDPHP_OAUTH_GOOGLE_ENABLED` | Activación de Google. |
| `SHIELDPHP_OAUTH_GOOGLE_CLIENT_ID` | Identificador de Google. |
| `SHIELDPHP_OAUTH_GOOGLE_CLIENT_SECRET` | Secreto de Google. |
| `SHIELDPHP_OAUTH_FACEBOOK_ENABLED` | Activación de Facebook. |
| `SHIELDPHP_OAUTH_FACEBOOK_CLIENT_ID` | Identificador de Facebook. |
| `SHIELDPHP_OAUTH_FACEBOOK_CLIENT_SECRET` | Secreto de Facebook. |
| `SHIELDPHP_OAUTH_STATE_TTL` | Duración del estado. |
| `SHIELDPHP_OAUTH_PKCE_ENABLED` | Opción prevista para PKCE. |

La aplicación debe cargar estas variables y convertirlas en la configuración efectiva de cada proveedor.

No todos los parámetros de la plantilla tienen actualmente un adaptador automático en el Core.

## 17. Manejo de errores

El proceso OAuth puede fallar en distintas etapas.

| Error | Causa posible | Respuesta recomendada |
|---|---|---|
| `access_denied` | El usuario rechazó la autorización. | Permitir reintentar. |
| `invalid_request` | Parámetros incorrectos. | Revisar la solicitud. |
| `invalid_client` | Credenciales incorrectas. | Revisar la configuración. |
| `invalid_grant` | Código inválido o expirado. | Iniciar una nueva autorización. |
| `redirect_uri_mismatch` | URI diferente de la registrada. | Corregir la configuración. |
| `invalid_scope` | Permisos no admitidos. | Revisar los scopes. |
| Estado inválido | Solicitud no correspondiente. | Rechazar el callback. |
| Verificador incorrecto | Fallo de PKCE. | Rechazar el intercambio. |
| Error HTTP | Fallo de comunicación. | Manejar el error sin exponer secretos. |

### Respuesta de referencia

```json
{
    "success": false,
    "error": "oauth_authorization_failed",
    "message": "No fue posible completar la autorización."
}
```

Las aplicaciones deben evitar revelar información interna que facilite el abuso del proceso OAuth.

## 18. Seguridad

La seguridad debe formar parte de todas las etapas del flujo OAuth.

### 18.1 Validación de state

- Generar valores aleatorios seguros.
- Vincularlos con la transacción y el usuario que inició el proceso.
- Consumirlos una sola vez.
- Rechazar estados expirados.
- No confiar en parámetros recibidos sin validación.

### 18.2 PKCE

- Utilizar PKCE en los flujos Authorization Code compatibles.
- Preferir el método `S256`.
- Asociar cada verifier con una sola transacción.
- No exponer el verifier en la solicitud de autorización.
- Impedir la reutilización de verificadores.
- Detectar intentos de degradar la protección PKCE.

### 18.3 URI de retorno

- Registrar las URI autorizadas.
- Utilizar coincidencia exacta.
- Utilizar HTTPS en producción.
- No permitir redirecciones arbitrarias.
- Evitar endpoints que funcionen como redireccionadores abiertos.

### 18.4 Credenciales

- Mantener los secretos exclusivamente en el servidor.
- No publicarlos en GitHub.
- Evitar almacenarlos en el frontend.
- Proteger los archivos de configuración.
- No registrar tokens completos.

### 18.5 Solicitudes HTTP

- Utilizar HTTPS.
- Restringir los dominios de destino.
- Deshabilitar redirecciones automáticas no verificadas.
- Validar las respuestas recibidas.
- Evitar solicitar recursos a dominios proporcionados arbitrariamente por el usuario.

### 18.6 Protección contra ataques de mezcla de proveedores

Cuando una aplicación trabaja con varios servidores de autorización, debe asegurarse de que cada callback corresponde al proveedor y a la transacción iniciados originalmente.

Se recomienda utilizar los mecanismos de identificación del emisor disponibles, como el parámetro `iss` cuando el proveedor lo admita, o endpoints de retorno diferenciados.

### 18.7 Tokens

- Validar el propósito y alcance de cada token.
- Respetar su expiración.
- No incluir datos confidenciales en tokens.
- Proteger los refresh tokens.
- Revocar credenciales cuando corresponda.
- No utilizar el access token como prueba automática de identidad.

## 19. OAuth 2.0 y OpenID Connect

OAuth 2.0 y OpenID Connect deben mantenerse diferenciados dentro de ShieldPHP.

| Característica | OAuth 2.0 | OpenID Connect |
|---|---|---|
| Objetivo | Autorización delegada. | Autenticación de identidad. |
| Token de acceso | Sí. | Puede utilizarse. |
| ID Token | No lo define por sí solo. | Sí. |
| Perfil de identidad | Depende del proveedor o recurso. | Se basa en declaraciones estandarizadas. |
| Scope `openid` | No es propio de OAuth básico. | Indica una solicitud OpenID Connect. |

Para iniciar sesión con Google se utiliza OpenID Connect.

Para Facebook Login se utiliza el flujo de autorización correspondiente y el mecanismo de obtención de perfil de Facebook.

La identidad externa debe verificarse antes de asociarla con un usuario local.

## 20. Almacenamiento persistente

Los componentes temporales de OAuth utilizan contratos de almacenamiento.

| Contrato | Información |
|---|---|
| `OAuthStateStoreInterface` | Estado OAuth y expiración. |
| `PKCEStoreInterface` | Hash de estado, verifier y expiración. |

Para desarrollo pueden utilizarse implementaciones en memoria.

En producción se recomienda disponer de almacenamiento persistente y seguro, compartido entre los procesos que deban completar las transacciones.

Posibles tecnologías:

- Redis.
- PostgreSQL.
- MySQL.
- Almacenamiento compartido equivalente.

El almacenamiento debe evitar que una transacción pueda recuperarse varias veces y debe eliminar los registros temporales expirados.

## 21. Integraciones

El módulo OAuth puede integrarse con diferentes frameworks.

### PHP básico

Ejemplo:

```text
examples/php-basic/
```

### Slim Framework

La integración Slim permite utilizar los componentes de ShieldPHP en rutas y aplicaciones HTTP.

```text
slim/
```

### Laravel

Se contempla una integración específica:

```text
integrations/laravel/
```

Estas integraciones requieren validar y completar sus adaptadores antes de considerarse listas para producción.

## 22. Pruebas

El módulo OAuth dispone de una estructura de pruebas unitarias en:

```text
packages/php-core/tests/Unit/OAuth/
```

### Pruebas principales

| Archivo | Objetivo |
|---|---|
| `AuthorizationUrlBuilderTest.php` | Construcción de URL de autorización. |
| `OAuthClientTest.php` | Solicitudes HTTP y restricciones. |
| `OAuthManagerTest.php` | Coordinación de proveedores. |
| `OAuthStateManagerTest.php` | Gestión de estados. |
| `PKCEManagerTest.php` | Generación y consumo de PKCE. |
| `TokenResponseTest.php` | Procesamiento de tokens. |
| `GoogleProviderTest.php` | Integración Google. |
| `FacebookProviderTest.php` | Integración Facebook. |

### Ejecutar las pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

Ejecutar únicamente las pruebas OAuth:

```bash
vendor/bin/phpunit tests/Unit/OAuth
```

### Análisis estático

```bash
composer analyse
```

Las pruebas deben utilizar respuestas HTTP simuladas para evitar depender de proveedores reales durante la ejecución unitaria.

También deben comprobar:

- Estados inválidos.
- Reutilización de códigos.
- Expiración de estados.
- Verificadores incorrectos.
- URI de retorno no autorizadas.
- Hosts externos no permitidos.
- Tokens incompletos.
- Respuestas HTTP inesperadas.

## 23. Estructura del módulo

```text
shieldphp/
│
├── packages/
│   └── php-core/
│       ├── src/
│       │   ├── OAuth/
│       │   │   ├── Providers/
│       │   │   │   ├── GoogleProvider.php
│       │   │   │   └── FacebookProvider.php
│       │   │   │
│       │   │   ├── AuthorizationUrlBuilder.php
│       │   │   ├── CallbackHandler.php
│       │   │   ├── OAuthClient.php
│       │   │   ├── OAuthManager.php
│       │   │   ├── OAuthProviderInterface.php
│       │   │   ├── OAuthStateManager.php
│       │   │   ├── PKCEManager.php
│       │   │   └── TokenResponse.php
│       │   │
│       │   └── Contracts/
│       │       └── OAuthProviderInterface.php
│       │
│       └── tests/
│           └── Unit/
│               └── OAuth/
│                   ├── Providers/
│                   ├── AuthorizationUrlBuilderTest.php
│                   ├── OAuthClientTest.php
│                   ├── OAuthManagerTest.php
│                   ├── OAuthStateManagerTest.php
│                   ├── PKCEManagerTest.php
│                   └── TokenResponseTest.php
│
├── examples/
│   ├── oauth-google/
│   └── oauth-facebook/
│
└── docs/
    ├── oauth2.md
    ├── google-login.md
    └── facebook-login.md
```

## 24. Estado actual

| Funcionalidad | Estado |
|---|---|
| OAuthProviderInterface | Base implementada |
| AuthorizationUrlBuilder | Base implementada |
| OAuthClient | Implementación inicial |
| OAuthManager | Implementación inicial |
| OAuthStateManager | Base implementada |
| OAuthStateStoreInterface | Contrato definido |
| PKCEManager | Base implementada |
| PKCEStoreInterface | Contrato definido |
| TokenResponse | Base implementada |
| CallbackHandler | Implementación inicial |
| GoogleProvider | Implementación inicial |
| FacebookProvider | Implementación inicial |
| Almacenamiento en memoria para pruebas | Disponible en estructura de pruebas |
| Almacenamiento persistente de estados | Pendiente de adaptadores |
| Almacenamiento persistente PKCE | Pendiente de adaptadores |
| Verificación de ID Tokens OIDC | Pendiente de completar |
| Renovación de tokens | Requiere integración |
| Protección completa contra ataques de mezcla | Pendiente de verificar |
| Pruebas de integración reales | Pendientes |
| Validación para producción | Pendiente |

## 25. Consideraciones técnicas

OAuth 2.0 es un protocolo de seguridad y sus implementaciones deben revisarse cuidadosamente.

ShieldPHP utiliza como base el flujo Authorization Code, evitando implementar mecanismos antiguos que entregan directamente tokens al navegador como parte de la autorización.

Las aplicaciones deben considerar:

- Utilizar Authorization Code con PKCE cuando sea compatible.
- Evitar el flujo Implicit.
- No utilizar el flujo Resource Owner Password Credentials.
- Validar las URI de retorno.
- Vincular de manera segura las transacciones OAuth.
- Evitar reutilizar códigos de autorización.
- Proteger el acceso a los tokens.
- Identificar correctamente al servidor de autorización.
- Diferenciar la autorización externa de la autenticación local.

El desarrollo debe seguir las recomendaciones de seguridad publicadas por la IETF, particularmente RFC 9700.

---

## 26. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Autorización.
- `docs/configuration.md` — Configuración.
- `docs/encryption.md` — Criptografía.
- `docs/jwt.md` — JSON Web Tokens.
- `docs/google-login.md` — Inicio de sesión con Google.
- `docs/facebook-login.md` — Inicio de sesión con Facebook.
- `SECURITY.md` — Política de seguridad.

### Estándares y documentación oficial

- [RFC 6749 — The OAuth 2.0 Authorization Framework](https://www.rfc-editor.org/rfc/rfc6749)
- [RFC 6750 — OAuth 2.0 Bearer Token Usage](https://www.rfc-editor.org/rfc/rfc6750)
- [RFC 7636 — Proof Key for Code Exchange (PKCE)](https://www.rfc-editor.org/rfc/rfc7636)
- [RFC 8414 — OAuth 2.0 Authorization Server Metadata](https://www.rfc-editor.org/rfc/rfc8414)
- [RFC 9700 — Best Current Practice for OAuth 2.0 Security](https://www.rfc-editor.org/rfc/rfc9700)
- [OpenID Connect Core 1.0](https://openid.net/specs/openid-connect-core-1_0.html)
- [PHP-FIG](https://www.php-fig.org/psr/)
- [Composer](https://getcomposer.org/doc/)

---

<p align="center">
  <strong>ShieldPHP OAuth 2.0</strong>
  <br>
  Integración segura con proveedores de autorización externos para PHP.
</p>