# Referencia de la API — ShieldPHP

> Documentación técnica de las clases, interfaces, métodos y componentes principales de ShieldPHP Core.

## 1. Introducción

ShieldPHP proporciona una colección de componentes modulares para implementar mecanismos de autenticación, autorización y seguridad en aplicaciones PHP.

Esta referencia documenta las interfaces públicas y los contratos previstos para los siguientes módulos:

- Auth — Autenticación.
- Authorization — Autorización.
- Config — Administración de configuración.
- Crypto — Operaciones criptográficas.
- JWT — Administración de tokens.
- OAuth — Autenticación mediante proveedores externos.
- Session — Administración de sesiones.
- Validation — Validación de datos.
- Exceptions — Excepciones del framework.

**Estado de la documentación:** referencia inicial de la API. Algunas interfaces y componentes continúan en desarrollo y sus contratos deben verificarse contra la implementación correspondiente.

---

## 2. Información general

### Requisitos

| Requisito | Versión |
|---|---|
| PHP | 8.2 o superior |
| Composer | 2.x |
| libsodium | Extensión habilitada |
| JSON | Extensión habilitada |
| Firebase PHP-JWT | 7.2.1 o compatible |
| PSR HTTP Client | 1.x |
| PSR HTTP Message | 2.x |
| PSR HTTP Factory | 1.x |

### Namespace principal

```php
ShieldPHP\Core\
```

### Autoloading PSR-4

La configuración de Composer del paquete Core establece:

```json
{
    "autoload": {
        "psr-4": {
            "ShieldPHP\\Core\\": "src/"
        }
    }
}
```

Ejemplo de importación:

```php
use ShieldPHP\Core\Auth\AuthManager;
use ShieldPHP\Core\JWT\JWTManager;
use ShieldPHP\Core\Crypto\Encryption;
```

---

# 3. Módulo Auth

El módulo `Auth` administra la autenticación de usuarios y la verificación de credenciales.

## 3.1 AuthManager

**Namespace:**

```php
ShieldPHP\Core\Auth\AuthManager
```

**Responsabilidad:**

Administrar el proceso de autenticación mediante un proveedor de usuarios y un componente de hash de contraseñas.

### Constructor

```php
public function __construct(
    UserProviderInterface $userProvider,
    PasswordHasher $passwordHasher
);
```

### Método attempt()

```php
public function attempt(
    string $email,
    string $password
): AuthResult;
```

| Parámetro | Tipo | Descripción |
|---|---|---|
| `$email` | string | Correo electrónico del usuario. |
| `$password` | string | Contraseña proporcionada. |

**Retorno:** `AuthResult`.

El proceso contempla la normalización del correo electrónico, la comprobación de credenciales, la verificación del estado de la cuenta y respuestas genéricas ante fallos de autenticación.

## 3.2 AuthResult

**Namespace:**

```php
ShieldPHP\Core\Auth\AuthResult
```

Representa el resultado de un intento de autenticación.

### Métodos

```php
AuthResult::success(UserIdentity $user): self;

AuthResult::failure(): self;

isSuccessful(): bool;

user(): ?UserIdentity;
```

| Método | Descripción |
|---|---|
| `success()` | Construye un resultado exitoso. |
| `failure()` | Construye un resultado fallido. |
| `isSuccessful()` | Indica si la autenticación fue exitosa. |
| `user()` | Obtiene la identidad autenticada, cuando existe. |

## 3.3 PasswordHasher

**Namespace:**

```php
ShieldPHP\Core\Auth\PasswordHasher
```

Gestiona el hash y la verificación de contraseñas mediante las funciones nativas de PHP.

### Métodos

```php
hash(string $password): string;

verify(string $password, string $hash): bool;

needsRehash(string $hash): bool;
```

| Método | Descripción |
|---|---|
| `hash()` | Genera un hash seguro. |
| `verify()` | Comprueba una contraseña contra un hash. |
| `needsRehash()` | Determina si el hash debe actualizarse. |

La implementación utiliza `PASSWORD_DEFAULT` y contempla un límite de entrada de 4096 bytes.

**Ejemplo:**

```php
use ShieldPHP\Core\Auth\PasswordHasher;

$hasher = new PasswordHasher();

$password = 'MiPasswordSeguro2026!';

$hash = $hasher->hash($password);

if ($hasher->verify($password, $hash)) {
    echo 'Contraseña válida';
}
```

## 3.4 PasswordPolicy

**Namespace:**

```php
ShieldPHP\Core\Auth\PasswordPolicy
```

Define las reglas de aceptación de contraseñas.

### Configuración inicial

| Parámetro | Valor |
|---|---:|
| Longitud mínima | 12 caracteres |
| Longitud máxima | 1024 caracteres |

### Métodos

```php
validate(string $password): array;

isValid(string $password): bool;

assertValid(string $password): void;
```

- `validate()` devuelve los errores encontrados.
- `isValid()` indica si la contraseña cumple las políticas.
- `assertValid()` exige el cumplimiento de las reglas y comunica los errores mediante una excepción.

Los requisitos adicionales, como mayúsculas, minúsculas, números y símbolos, dependen de la configuración de la política.

## 3.5 UserIdentity

**Namespace:**

```php
ShieldPHP\Core\Auth\UserIdentity
```

Representa la identidad de un usuario autenticado.

### Acceso a propiedades

```php
getId();

getEmail(): string;

getPasswordHash(): string;

isActive(): bool;

getRoles(): array;

hasRole(string $role): bool;
```

Permite consultar el identificador, correo, estado y roles asociados al usuario.

---

# 4. Módulo Authorization

El módulo `Authorization` administra los roles, permisos y controles de acceso.

### Componentes principales

| Clase | Responsabilidad |
|---|---|
| `AccessControl` | Administración de controles de acceso. |
| `PermissionManager` | Administración de permisos. |
| `Permission` | Representación de un permiso. |
| `RoleManager` | Administración de roles. |
| `Role` | Representación de un rol. |

### Conceptos principales

**Rol:** representa un conjunto de privilegios asignables a un usuario.

**Permiso:** identifica una operación o capacidad que puede concederse.

**Control de acceso:** determina si una identidad está autorizada a realizar una operación.

### Ejemplo conceptual

```php
use ShieldPHP\Core\Auth\UserIdentity;

// Identidad obtenida después de autenticar al usuario.
$user = $authenticatedUser;

if ($user->hasRole('admin')) {
    echo 'Usuario con rol de administrador';
}
```

> Los métodos específicos de asignación, eliminación, comprobación y persistencia deben consultarse en las implementaciones públicas de `AccessControl`, `PermissionManager` y `RoleManager`. No se establece aquí una firma de método que todavía no haya sido verificada.

---

# 5. Módulo Config

Centraliza la configuración de ShieldPHP y valida los parámetros utilizados por los diferentes componentes.

## 5.1 ShieldConfig

**Namespace:**

```php
ShieldPHP\Core\Config\ShieldConfig
```

### Constructor

```php
public function __construct(array $configuration);
```

### Métodos

```php
get(string $key, mixed $default = null): mixed;

has(string $key): bool;

all(): array;
```

| Método | Descripción |
|---|---|
| `get()` | Obtiene un parámetro de configuración. |
| `has()` | Comprueba si existe un parámetro. |
| `all()` | Devuelve la configuración completa. |

### Ejemplo

```php
use ShieldPHP\Core\Config\ShieldConfig;

$config = new ShieldConfig([
    'app_name' => 'Mi aplicación',
    'access_token_ttl' => 900,
    'refresh_token_ttl' => 604800,
    'secure_cookies' => true,
    'same_site' => 'Lax'
]);

$appName = $config->get('app_name');

$ttl = $config->get('access_token_ttl', 900);
```

## 5.2 ConfigValidator

**Namespace:**

```php
ShieldPHP\Core\Config\ConfigValidator
```

Valida los parámetros de configuración.

```php
ConfigValidator::validate(array $configuration): void;
```

Las configuraciones inválidas se comunican mediante `ConfigurationException`.

---

# 6. Módulo Crypto

El módulo `Crypto` proporciona herramientas criptográficas para proteger información sensible.

Utiliza las primitivas criptográficas de libsodium en las operaciones correspondientes.

## 6.1 Encryption

**Namespace:**

```php
ShieldPHP\Core\Crypto\Encryption
```

Responsable del cifrado y descifrado simétrico.

### Operaciones

```php
encrypt(string $plaintext): string;

decrypt(string $ciphertext): string;
```

La implementación utiliza `sodium_crypto_secretbox`.

**Características:**

- Claves binarias de 32 bytes.
- Nonces criptográficamente seguros.
- Autenticación del contenido cifrado.
- Formato versionado `shp:v1:`.
- Codificación Base64 del contenido resultante.

### Ejemplo

```php
use ShieldPHP\Core\Crypto\Encryption;

$encryption = new Encryption($encryptionKey);

$encrypted = $encryption->encrypt(
    'Información confidencial'
);

$decrypted = $encryption->decrypt($encrypted);
```

> `$encryptionKey` debe ser una clave binaria válida de 32 bytes. El nombre y la firma exacta del constructor deben contrastarse con la implementación instalada.

## 6.2 KeyDerivation

**Namespace:**

```php
ShieldPHP\Core\Crypto\KeyDerivation
```

Proporciona operaciones para derivar claves criptográficas a partir de material de entrada.

## 6.3 KeyManager

**Namespace:**

```php
ShieldPHP\Core\Crypto\KeyManager
```

Administra claves criptográficas e identifica la clave activa.

Capacidades previstas:

- Generación de claves.
- Decodificación de claves Base64.
- Obtención de la clave activa.
- Consulta de claves por identificador.
- Configuración mediante variables de entorno.

Las claves utilizadas por los algoritmos deben respetar sus tamaños y formatos correspondientes.

## 6.4 RandomGenerator

**Namespace:**

```php
ShieldPHP\Core\Crypto\RandomGenerator
```

Proporciona generación de valores aleatorios.

Operaciones contempladas:

- Generación de bytes.
- Generación hexadecimal.
- Generación de tokens.
- Generación de enteros aleatorios.
- Generación de UUID.

## 6.5 Signature

**Namespace:**

```php
ShieldPHP\Core\Crypto\Signature
```

Implementa operaciones de firma digital mediante Ed25519.

Capacidades:

- Firmar información.
- Verificar firmas.
- Codificar firmas en Base64.
- Utilizar claves públicas y privadas compatibles con Ed25519.

---

# 7. Módulo JWT

El módulo `JWT` permite emitir, decodificar, validar y revocar tokens.

Utiliza Firebase PHP-JWT como dependencia criptográfica.

## 7.1 JWTConfig

**Namespace:**

```php
ShieldPHP\Core\JWT\JWTConfig
```

Configura el comportamiento de los tokens.

### Constructor

```php
public function __construct(
    string $signingKey,
    string $issuer,
    string $audience,
    string $algorithm = 'HS256',
    int $accessTTL = 900,
    int $refreshTTL = 604800,
    int $leeway = 0
);
```

### Parámetros

| Parámetro | Descripción | Valor predeterminado |
|---|---|---|
| `$signingKey` | Clave de firma. | Obligatorio |
| `$issuer` | Emisor del token. | Obligatorio |
| `$audience` | Audiencia del token. | Obligatorio |
| `$algorithm` | Algoritmo de firma. | HS256 |
| `$accessTTL` | Duración del token de acceso. | 900 segundos |
| `$refreshTTL` | Duración del token de actualización. | 604800 segundos |
| `$leeway` | Margen de tolerancia temporal. | 0 segundos |

### Restricciones conocidas

| Algoritmo | Longitud mínima de clave |
|---|---:|
| HS256 | 32 bytes |
| HS384 | 48 bytes |
| HS512 | 64 bytes |

También se validan los límites de expiración y tolerancia temporal.

## 7.2 JWTClaims

**Namespace:**

```php
ShieldPHP\Core\JWT\JWTClaims
```

Representa las declaraciones de un token.

### Constructor

```php
public function __construct(
    string $issuer,
    string $subject,
    string $audience,
    int $issuedAt,
    int $expiresAt,
    ?int $notBefore = null,
    ?string $jti = null,
    array $customClaims = []
);
```

### Declaraciones

| Campo | Descripción |
|---|---|
| `iss` | Emisor. |
| `sub` | Sujeto o identificador del usuario. |
| `aud` | Audiencia. |
| `iat` | Fecha de emisión. |
| `exp` | Fecha de expiración. |
| `nbf` | Fecha a partir de la cual es válido. |
| `jti` | Identificador único del token. |

Las declaraciones reservadas no pueden sobrescribirse mediante declaraciones personalizadas.

## 7.3 JWTManager

**Namespace:**

```php
ShieldPHP\Core\JWT\JWTManager
```

Administra la creación y decodificación de tokens.

### Constructor

```php
public function __construct(JWTConfig $config);
```

### Métodos

```php
issue(JWTClaims $claims): string;

decode(string $token): stdClass;

validate(string $token): bool;
```

| Método | Descripción |
|---|---|
| `issue()` | Emite un token JWT firmado. |
| `decode()` | Verifica y decodifica el token. |
| `validate()` | Comprueba la validez del token. |

## 7.4 TokenValidator

**Namespace:**

```php
ShieldPHP\Core\JWT\TokenValidator
```

Proporciona validación adicional y comprobación del estado de revocación.

### Constructor

```php
public function __construct(
    JWTManager $jwtManager,
    ?TokenRevocationStoreInterface $revocationStore = null
);
```

### Métodos

```php
validate(string $token): bool;

isValid(string $token): bool;
```

## 7.5 TokenRevocationStoreInterface

Contrato para implementar almacenamiento de tokens revocados.

```php
interface TokenRevocationStoreInterface
{
    public function revoke(
        string $tokenId,
        DateTimeImmutable $expiresAt
    ): void;

    public function isRevoked(string $tokenId): bool;

    public function removeExpired(DateTimeImmutable $now): int;
}
```

Las implementaciones pueden utilizar almacenamiento en memoria, bases de datos o servicios externos.

## 7.6 TokenBlacklist

Implementación en memoria del almacenamiento de revocación.

Operaciones conocidas:

- Registrar un identificador de token revocado.
- Consultar si un token está revocado.
- Eliminar entradas expiradas.
- Consultar el número de entradas almacenadas.

No debe utilizarse como almacenamiento compartido entre varios servidores sin una implementación persistente apropiada.

## 7.7 TokenPair

Representa un conjunto de tokens de acceso y actualización.

Proporciona métodos de acceso a sus valores y una representación mediante `toArray()` con claves en formato `snake_case`.

---

# 8. Módulo OAuth

El módulo `OAuth` proporciona componentes para integrar proveedores de autenticación externos.

## 8.1 OAuthProviderInterface

Contrato para proveedores OAuth.

Namespace:

```php
ShieldPHP\Core\Contracts\OAuthProviderInterface
```

Métodos:

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

fetchUserProfile(TokenResponse $token): array;
```

## 8.2 OAuthClient

**Namespace:**

```php
ShieldPHP\Core\OAuth\OAuthClient
```

Cliente HTTP para realizar solicitudes a proveedores OAuth.

### Responsabilidades

- Ejecutar solicitudes HTTP.
- Enviar parámetros de formularios.
- Obtener respuestas JSON.
- Restringir las conexiones a hosts permitidos.
- Exigir HTTPS.
- Evitar seguir redirecciones HTTP automáticamente.

Utiliza interfaces PSR para la comunicación HTTP.

## 8.3 AuthorizationUrlBuilder

Construye URL de autorización OAuth.

### Operaciones

```php
build(
    string $redirectUri,
    string $state,
    ?string $challenge = null
): string;
```

Contempla parámetros como:

- `client_id`
- `redirect_uri`
- `response_type`
- `scope`
- `state`
- `code_challenge`

Los parámetros específicos dependen del proveedor y de la configuración.

## 8.4 TokenResponse

**Namespace:**

```php
ShieldPHP\Core\OAuth\TokenResponse
```

Representa la respuesta recibida del endpoint de tokens.

Incluye los siguientes datos:

| Propiedad | Descripción |
|---|---|
| `accessToken` | Token de acceso. |
| `tokenType` | Tipo de token. |
| `expiresIn` | Tiempo de expiración. |
| `refreshToken` | Token de actualización, cuando está disponible. |
| `scope` | Permisos concedidos. |
| `idToken` | Token de identidad, cuando está disponible. |
| `receivedAt` | Fecha de recepción. |

Operaciones:

```php
TokenResponse::fromArray(array $data): self;

isExpired(): bool;
```

También proporciona métodos de acceso para consultar sus propiedades.

## 8.5 OAuthStateManager

Administra el parámetro `state` utilizado durante los procesos OAuth.

Operaciones:

```php
generateAndStore(): string;

consume(string $state): bool;
```

El estado se almacena mediante un contrato `OAuthStateStoreInterface` y se utiliza una expiración configurable.

## 8.6 PKCEManager

Administra las claves y desafíos PKCE.

Operaciones:

```php
createChallenge(string $state): string;

consumeVerifier(string $state): ?string;
```

PKCE permite vincular el proceso de autorización con el intercambio posterior del código.

## 8.7 Proveedores disponibles

ShieldPHP contempla implementaciones iniciales de:

| Proveedor | Clase |
|---|---|
| Google | `GoogleProvider` |
| Facebook | `FacebookProvider` |

Cada proveedor implementa el contrato OAuth y proporciona sus propios endpoints y mecanismos de obtención de perfil.

### Consideraciones de seguridad OAuth

- Validar siempre `state`.
- Utilizar PKCE cuando corresponda.
- Verificar las URI de redirección.
- No confiar automáticamente en perfiles externos.
- Validar criptográficamente los ID tokens antes de confiar en sus declaraciones.

---

# 9. Módulo Session

El módulo `Session` contempla componentes para administrar sesiones y cookies.

### Componentes

| Clase | Responsabilidad |
|---|---|
| `SessionConfig` | Configuración de sesiones. |
| `SessionManager` | Administración del ciclo de vida de sesiones. |
| `CookieManager` | Administración de cookies. |

### Operaciones contempladas

**SessionManager:**

- Iniciar una sesión.
- Consultar si existe una sesión activa.
- Almacenar y recuperar datos.
- Eliminar datos.
- Limpiar una sesión.
- Regenerar el identificador.
- Destruir una sesión.

**CookieManager:**

- Crear cookies.
- Consultar cookies.
- Verificar su existencia.
- Eliminar cookies.
- Limpiar cookies.

**SessionConfig:**

Contempla parámetros para:

- Nombre de sesión.
- Duración.
- Ruta y dominio de cookies.
- Atributos `Secure`, `HttpOnly` y `SameSite`.
- Modo estricto.
- Uso exclusivo de cookies.
- Deshabilitación de identificadores en URL.

> Las firmas definitivas de estos componentes y los detalles de su almacenamiento deben consultarse en sus clases reales. Esta sección describe las responsabilidades previstas, no un contrato definitivo verificado.

---

# 10. Módulo Validation

Proporciona herramientas para validar información y entradas de usuario.

### Componentes principales

| Clase | Responsabilidad |
|---|---|
| `EmailValidator` | Validación de correos electrónicos. |
| `InputValidator` | Validación de diferentes tipos de entrada. |
| `RedirectUriValidator` | Validación de URI de redirección. |

### EmailValidator

Su interfaz prevista contempla:

```php
isValid(string $email): bool;
```

### InputValidator

La interfaz propuesta contempla operaciones para comprobar:

- Cadenas.
- Números enteros.
- Valores booleanos.
- Correos electrónicos.
- Direcciones URL.

Los nombres definitivos de las operaciones deben verificarse en la implementación.

### RedirectUriValidator

Su responsabilidad es validar las URI de redirección mediante una lista de direcciones autorizadas.

La política recomendada es utilizar coincidencia exacta y no aceptar redirecciones arbitrarias.

---

# 11. Módulo Exceptions

ShieldPHP dispone de excepciones específicas para representar errores de diferentes componentes.

### Excepciones principales

| Excepción | Descripción |
|---|---|
| `ShieldException` | Excepción base del framework. |
| `AuthenticationException` | Error de autenticación. |
| `ConfigurationException` | Error de configuración. |
| `EncryptionException` | Error relacionado con cifrado. |
| `ExpiredTokenException` | Token expirado. |
| `InvalidCredentialsException` | Credenciales incorrectas. |
| `InvalidTokenException` | Token inválido. |

### Manejo de excepciones

Ejemplo conceptual:

```php
try {
    $authenticated = $authManager->attempt(
        $email,
        $password
    );

    if (!$authenticated->isSuccessful()) {
        throw new InvalidCredentialsException(
            'No fue posible autenticar al usuario.'
        );
    }

} catch (AuthenticationException $exception) {
    // Registrar el incidente sin exponer información sensible.
}
```

Los mensajes de error destinados a clientes HTTP deben evitar revelar detalles internos de seguridad.

---

# 12. Interfaces y contratos

ShieldPHP utiliza contratos para desacoplar los componentes de sus implementaciones de almacenamiento e infraestructura.

Entre los contratos principales se encuentran:

| Interfaz | Responsabilidad |
|---|---|
| `UserProviderInterface` | Obtener identidades de usuarios. |
| `OAuthProviderInterface` | Definir proveedores OAuth. |
| `OAuthStateStoreInterface` | Almacenar estados OAuth. |
| `PKCEStoreInterface` | Almacenar verificadores PKCE. |
| `TokenRevocationStoreInterface` | Administrar la revocación de JWT. |

Las aplicaciones pueden proporcionar implementaciones propias cuando respeten los métodos y tipos establecidos por cada contrato.

---

# 13. Integraciones

ShieldPHP está diseñado para extenderse mediante adaptadores independientes.

## Slim Framework

Directorio:

```text
slim/
```

Componente inicial:

```php
ShieldPHP\Slim\QuickAuthMiddleware
```

Responsabilidades:

- Extraer tokens Bearer.
- Validar JWT mediante ShieldPHP Core.
- Adjuntar declaraciones del usuario a la solicitud.
- Interceptar solicitudes no autorizadas.
- Generar respuestas HTTP 401.

## Laravel

Directorio:

```text
integrations/laravel/
```

La integración contempla:

- Registro de servicios.
- Configuración del paquete.
- Aliases de middleware.
- Publicación de configuraciones.
- Registro de rutas.

Ambas integraciones continúan en desarrollo y requieren pruebas de compatibilidad y validación de sus adaptadores.

---

# 14. Convenciones de la API

Para mantener una interfaz consistente, ShieldPHP utiliza las siguientes convenciones:

### Tipado

- Declaración estricta de tipos mediante `declare(strict_types=1)`.
- Tipos explícitos para parámetros y retornos.
- Uso de tipos nullable cuando corresponde.
- Uso de interfaces para desacoplar dependencias.

### Nombres

- Clases en `PascalCase`.
- Métodos en `camelCase`.
- Constantes en mayúsculas.
- Namespaces organizados por módulo.

### Errores

- Excepciones específicas para cada componente.
- Validación de entradas.
- Respuestas genéricas cuando revelar detalles pueda comprometer la seguridad.
- Registro interno de errores sin exponer secretos.

### Compatibilidad

- PHP 8.2 o superior.
- Autoloading PSR-4.
- Integración con interfaces PSR cuando corresponde.

---

# 15. Pruebas y calidad

La API de ShieldPHP debe validarse mediante pruebas automatizadas.

### PHPUnit

```bash
composer test
```

### PHPStan

```bash
composer analyse
```

### Formato del código

```bash
composer format
```

### Validación completa

```bash
composer check
```

Estos comandos dependen de los scripts y las configuraciones definidos en el Composer correspondiente.

---

# 16. Seguridad

Los componentes de esta API deben utilizarse siguiendo las recomendaciones del proyecto.

- No almacenar contraseñas en texto plano.
- Proteger las claves criptográficas.
- Verificar las firmas JWT.
- Validar emisor y audiencia.
- Controlar la expiración de tokens.
- Validar `state` durante OAuth.
- Implementar PKCE cuando corresponda.
- Utilizar HTTPS.
- Validar todas las entradas externas.
- Aplicar autorización después de autenticar al usuario.
- Mantener las dependencias actualizadas.

Para más información consulta:

- `SECURITY.md`
- `docs/`
- `CHANGELOG.md`

---

# 17. Versionado

ShieldPHP utiliza como referencia el versionado semántico:

```text
MAJOR.MINOR.PATCH
```

- **MAJOR:** cambios incompatibles.
- **MINOR:** nuevas funcionalidades compatibles.
- **PATCH:** correcciones y mejoras compatibles.

Los cambios de la API pública deben documentarse en `CHANGELOG.md`.

---

# 18. Contribuciones

Las contribuciones para mejorar la API son bienvenidas.

Antes de enviar cambios:

1. Revisa los contratos existentes.
2. Mantén la compatibilidad cuando sea posible.
3. Agrega pruebas para los métodos nuevos o modificados.
4. Actualiza esta documentación.
5. Ejecuta PHPUnit y PHPStan.
6. Describe los cambios en el Pull Request.

---

## 19. Recursos

- [PHP Documentation](https://www.php.net/docs.php)
- [Composer](https://getcomposer.org/doc/)
- [PHP-FIG](https://www.php-fig.org/psr/)
- [JSON Web Token](https://www.rfc-editor.org/rfc/rfc7519)
- [OAuth 2.0](https://www.rfc-editor.org/rfc/rfc6749)
- [OAuth 2.0 PKCE](https://www.rfc-editor.org/rfc/rfc7636)
- [libsodium](https://doc.libsodium.org/)
- [PHPUnit](https://phpunit.de/)
- [PHPStan](https://phpstan.org/)

---

<p align="center">
  <strong>ShieldPHP</strong>
  <br>
  Referencia técnica de la API.
  <br>
  Seguridad modular para aplicaciones PHP.
</p>