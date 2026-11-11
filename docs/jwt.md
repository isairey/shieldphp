# JWT — ShieldPHP

<p align="center">
  <strong>Administración segura de JSON Web Tokens para aplicaciones PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/JWT-RFC%207519-blue?style=for-the-badge" alt="JSON Web Token">
  <img src="https://img.shields.io/badge/Algorithm-HMAC-purple?style=for-the-badge" alt="HMAC">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

El módulo **JWT** de ShieldPHP proporciona los componentes necesarios para generar, firmar, decodificar, validar y revocar JSON Web Tokens.

Su objetivo es facilitar la implementación de autenticación basada en tokens en aplicaciones PHP, API REST y servicios que necesitan intercambiar información de identidad de manera segura.

JWT permite representar información mediante un formato compacto que puede transmitirse entre diferentes componentes de una aplicación.

El módulo contempla:

- Generación de tokens firmados.
- Administración de declaraciones JWT.
- Validación de firmas.
- Configuración de algoritmos criptográficos.
- Control de expiración.
- Configuración de emisor y audiencia.
- Administración de identificadores únicos.
- Revocación de tokens.
- Administración de listas de tokens revocados.
- Soporte para tokens de acceso y actualización.

ShieldPHP utiliza Firebase PHP-JWT para las operaciones de procesamiento de JWT.

## 2. ¿Qué es JWT?

JSON Web Token es un estándar que define una estructura compacta para representar declaraciones entre dos partes.

Un JWT normalmente tiene tres componentes:

```text
HEADER.PAYLOAD.SIGNATURE
```

### Header

Contiene información sobre el tipo de token y el algoritmo utilizado.

Ejemplo:

```json
{
    "typ": "JWT",
    "alg": "HS256"
}
```

### Payload

Contiene las declaraciones del token.

Ejemplo:

```json
{
    "iss": "shieldphp",
    "sub": "user-123",
    "aud": "shieldphp-api",
    "iat": 1794348000,
    "exp": 1794348900,
    "jti": "unique-token-id"
}
```

### Signature

La firma permite comprobar que el token no haya sido modificado y que haya sido generado mediante la clave correspondiente.

Un JWT firmado no está cifrado automáticamente.

Por lo tanto, no debes incluir contraseñas, secretos, claves privadas ni información confidencial dentro del payload.

## 3. Arquitectura del módulo

El módulo JWT utiliza diferentes componentes para separar responsabilidades.

| Componente | Responsabilidad |
|---|---|
| `JWTConfig` | Configuración de firma y expiración. |
| `JWTClaims` | Representación de las declaraciones. |
| `JWTManager` | Emisión, decodificación y validación. |
| `TokenValidator` | Validación adicional y comprobación de revocación. |
| `TokenBlacklist` | Almacenamiento en memoria de tokens revocados. |
| `TokenPair` | Representación de un conjunto de tokens. |
| `TokenRevocationStoreInterface` | Contrato para almacenar revocaciones. |

Namespace principal:

```php
ShieldPHP\Core\JWT\
```

### Diagrama de funcionamiento

```text
                 APLICACIÓN PHP
                       |
                       v
                  JWTConfig
                       |
                       v
                  JWTManager
                       |
                ┌──────┴──────┐
                |             |
                v             v
             JWTClaims     Signature
                |             |
                └──────┬──────┘
                       |
                       v
                  Access Token
                       |
                       v
                 Solicitud HTTP
                       |
                       v
                 TokenValidator
                       |
                ┌──────┴──────┐
                |             |
                v             v
          Validación       Revocación
          JWTManager       TokenStore
                |             |
                └──────┬──────┘
                       |
                 ┌─────┴─────┐
                 |           |
                 v           v
               Válido     Inválido
                 |           |
                 v           v
              Acceso      Rechazado
```

## 4. JWTConfig

`JWTConfig` centraliza los parámetros utilizados para configurar la generación y validación de tokens.

**Namespace:**

```php
ShieldPHP\Core\JWT\JWTConfig
```

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

| Parámetro | Tipo | Descripción |
|---|---|---|
| `$signingKey` | string | Clave binaria utilizada para firmar. |
| `$issuer` | string | Emisor de los tokens. |
| `$audience` | string | Audiencia autorizada. |
| `$algorithm` | string | Algoritmo de firma. |
| `$accessTTL` | int | Tiempo de vida del token de acceso. |
| `$refreshTTL` | int | Tiempo de vida del token de actualización. |
| `$leeway` | int | Margen de tolerancia temporal. |

### Valores predeterminados

| Configuración | Valor |
|---|---:|
| Algoritmo | HS256 |
| Access Token TTL | 900 segundos |
| Refresh Token TTL | 604800 segundos |
| Leeway | 0 segundos |

### Algoritmos contemplados

ShieldPHP contempla los siguientes algoritmos HMAC:

| Algoritmo | Longitud mínima de clave |
|---|---:|
| HS256 | 32 bytes |
| HS384 | 48 bytes |
| HS512 | 64 bytes |

### Restricciones de configuración

La implementación contempla las siguientes validaciones:

- Clave de firma con longitud suficiente para el algoritmo seleccionado.
- Duración del token de acceso superior a cero y no mayor de 86400 segundos.
- Duración del token de actualización superior a la del token de acceso.
- Duración del token de actualización no mayor de 365 días.
- Margen de tolerancia entre 0 y 300 segundos.

Una configuración inválida produce una excepción `InvalidArgumentException`.

### Ejemplo

```php
use ShieldPHP\Core\JWT\JWTConfig;

$signingKey = random_bytes(32);

$config = new JWTConfig(
    signingKey: $signingKey,
    issuer: 'shieldphp',
    audience: 'shieldphp-api',
    algorithm: 'HS256',
    accessTTL: 900,
    refreshTTL: 604800,
    leeway: 0
);
```

Este ejemplo genera una clave temporal. Para una aplicación real debes utilizar una clave persistente, protegida y compartida únicamente entre los componentes autorizados que deban firmar o verificar estos tokens.

## 5. JWTClaims

`JWTClaims` representa la información que se incorpora al payload de un JWT.

**Namespace:**

```php
ShieldPHP\Core\JWT\JWTClaims
```

Permite separar las declaraciones del token de la lógica encargada de firmarlo.

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

### Declaraciones estándar

| Declaración | Nombre | Descripción |
|---|---|---|
| `iss` | Issuer | Emisor del token. |
| `sub` | Subject | Identificador del sujeto. |
| `aud` | Audience | Destinatario del token. |
| `iat` | Issued At | Fecha de emisión. |
| `exp` | Expiration Time | Fecha de expiración. |
| `nbf` | Not Before | Momento a partir del cual es válido. |
| `jti` | JWT ID | Identificador único del token. |

### Ejemplo

```php
use ShieldPHP\Core\JWT\JWTClaims;

$now = time();

$claims = new JWTClaims(
    issuer: 'shieldphp',
    subject: 'user-123',
    audience: 'shieldphp-api',
    issuedAt: $now,
    expiresAt: $now + 900,
    notBefore: $now,
    jti: bin2hex(random_bytes(16)),
    customClaims: [
        'email' => 'usuario@example.com',
        'roles' => ['user']
    ]
);
```

### Declaraciones personalizadas

ShieldPHP permite incorporar información adicional mediante `customClaims`.

Ejemplo:

```php
$customClaims = [
    'email' => 'usuario@example.com',
    'roles' => ['admin'],
    'tenant_id' => 'tenant-001'
];
```

Las declaraciones reservadas no pueden sobrescribirse mediante declaraciones personalizadas.

No se recomienda almacenar información confidencial ni datos que puedan comprometer la seguridad de la aplicación.

## 6. JWTManager

`JWTManager` es el componente encargado de administrar la creación y validación de JWT.

**Namespace:**

```php
ShieldPHP\Core\JWT\JWTManager
```

### Constructor

```php
public function __construct(JWTConfig $config);
```

Recibe una instancia de `JWTConfig` con los parámetros necesarios.

### Métodos principales

```php
public function issue(JWTClaims $claims): string;

public function decode(string $token): stdClass;

public function validate(string $token): bool;
```

### issue()

Genera y firma un JWT utilizando las declaraciones proporcionadas.

```php
$token = $jwtManager->issue($claims);
```

**Retorno:** cadena que contiene el JWT firmado.

### decode()

Valida criptográficamente y decodifica el token.

```php
$decoded = $jwtManager->decode($token);
```

**Retorno:** objeto `stdClass` con las declaraciones del JWT.

La operación puede producir una excepción cuando el token no cumple las condiciones de validación.

### validate()

Comprueba si un JWT es válido utilizando la configuración correspondiente.

```php
$isValid = $jwtManager->validate($token);

if ($isValid) {
    echo 'Token válido';
}
```

La validación criptográfica y temporal del token debe realizarse antes de confiar en su payload.

## 7. Generar un token JWT

El siguiente ejemplo muestra el proceso de configuración, creación de declaraciones y emisión de un token.

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use ShieldPHP\Core\JWT\JWTConfig;
use ShieldPHP\Core\JWT\JWTClaims;
use ShieldPHP\Core\JWT\JWTManager;

$signingKey = random_bytes(32);

$config = new JWTConfig(
    signingKey: $signingKey,
    issuer: 'shieldphp',
    audience: 'shieldphp-api',
    algorithm: 'HS256',
    accessTTL: 900,
    refreshTTL: 604800
);

$jwtManager = new JWTManager($config);

$now = time();

$claims = new JWTClaims(
    issuer: 'shieldphp',
    subject: 'user-123',
    audience: 'shieldphp-api',
    issuedAt: $now,
    expiresAt: $now + 900,
    notBefore: $now,
    jti: bin2hex(random_bytes(16)),
    customClaims: [
        'email' => 'usuario@example.com'
    ]
);

$token = $jwtManager->issue($claims);

echo $token;
```

En producción, el identificador `jti` debe ser único y la clave debe conservarse entre ejecuciones.

## 8. Decodificar un token

Una vez recibido el token, ShieldPHP permite recuperar sus declaraciones mediante `JWTManager`.

```php
$decoded = $jwtManager->decode($token);

$userId = $decoded->sub ?? null;

$issuer = $decoded->iss ?? null;

$audience = $decoded->aud ?? null;

$expiresAt = $decoded->exp ?? null;
```

El resultado corresponde a un objeto `stdClass`.

**Importante:** no debes utilizar una simple decodificación Base64 como sustituto de la validación criptográfica. Un token no es confiable hasta que su firma y declaraciones han sido verificadas correctamente.

## 9. TokenValidator

`TokenValidator` proporciona una capa adicional de validación para los tokens.

**Namespace:**

```php
ShieldPHP\Core\JWT\TokenValidator
```

Su responsabilidad es coordinar la validación del token y comprobar si su identificador ha sido revocado mediante el almacenamiento configurado.

### Constructor

```php
public function __construct(
    JWTManager $jwtManager,
    ?TokenRevocationStoreInterface $revocationStore = null
);
```

### Métodos

```php
public function validate(string $token): bool;

public function isValid(string $token): bool;
```

### isValid()

Permite comprobar si un token es válido.

```php
if ($tokenValidator->isValid($token)) {
    echo 'Acceso autorizado';
} else {
    echo 'Token inválido';
}
```

### validate()

Proporciona la operación de validación definida por el componente.

```php
$isValid = $tokenValidator->validate($token);
```

### Responsabilidades

- Validar el token mediante `JWTManager`.
- Comprobar la presencia de un identificador `jti` cuando se requiere revocación.
- Consultar el almacenamiento de tokens revocados.
- Rechazar tokens que no cumplan las condiciones de validez.

El contrato de almacenamiento permite implementar distintos mecanismos de persistencia.

## 10. TokenRevocationStoreInterface

Esta interfaz define el contrato para almacenar y consultar los tokens revocados.

**Namespace:**

```php
ShieldPHP\Core\JWT\TokenRevocationStoreInterface
```

### Contrato

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

### Métodos

| Método | Descripción |
|---|---|
| `revoke()` | Marca un identificador como revocado. |
| `isRevoked()` | Comprueba si un identificador está revocado. |
| `removeExpired()` | Elimina registros cuya expiración ya pasó. |

### Propósito

Una firma válida no significa que un token deba seguir autorizado.

Por ejemplo, un usuario puede cerrar sesión o un administrador puede revocar sus credenciales antes de la expiración del JWT.

La lista de revocación permite rechazar esos tokens incluso cuando su firma sigue siendo correcta.

## 11. TokenBlacklist

`TokenBlacklist` proporciona una implementación en memoria del contrato de revocación.

**Namespace:**

```php
ShieldPHP\Core\JWT\TokenBlacklist
```

### Características

- Almacenamiento temporal de identificadores revocados.
- Consulta de revocaciones.
- Limpieza de registros expirados.
- Administración de entradas.
- Compatibilidad con `TokenRevocationStoreInterface`.

### Operaciones

```php
revoke(string $tokenId, DateTimeImmutable $expiresAt): void;

isRevoked(string $tokenId): bool;

removeExpired(DateTimeImmutable $now): int;

count(): int;
```

### Consideraciones

La implementación en memoria es apropiada para pruebas y escenarios de un solo proceso.

No proporciona persistencia entre reinicios ni sincronización automática entre diferentes servidores.

Para producción se recomienda implementar el contrato utilizando un almacenamiento compartido, por ejemplo:

- Redis.
- PostgreSQL.
- MySQL.
- Otro almacenamiento persistente apropiado.

La implementación debe utilizar `jti` como identificador del token, no almacenar JWT completos innecesariamente.

## 12. Revocación de tokens

El proceso conceptual de revocación es el siguiente:

```text
               ACCESS TOKEN
                    |
                    v
               Obtener JTI
                    |
                    v
             Registrar expiración
                    |
                    v
             TokenBlacklist
                    |
                    v
             Marcar como revocado
                    |
                    v
            Próxima solicitud HTTP
                    |
                    v
              TokenValidator
                    |
                    v
              Revisar JTI
                    |
              ┌─────┴─────┐
              |           |
              v           v
           Revocado    No revocado
              |           |
              v           v
           Rechazar    Continuar
```

### Consideración importante

Para revocar un token necesitas recuperar sus declaraciones de forma segura, obtener su `jti` y registrar la fecha de expiración correspondiente.

La revocación no debe aceptar identificadores arbitrarios suministrados por clientes sin validar primero el contexto del token.

## 13. TokenPair

`TokenPair` representa un conjunto de tokens utilizado durante los procesos de autenticación.

**Namespace:**

```php
ShieldPHP\Core\JWT\TokenPair
```

Permite trabajar con una representación conjunta de tokens de acceso y actualización.

### Responsabilidades

- Representar los tokens del conjunto.
- Proporcionar métodos de consulta.
- Facilitar la serialización.
- Mantener una representación estructurada de los datos.

### Conversión a arreglo

La implementación contempla:

```php
$tokenData = $tokenPair->toArray();
```

La representación utiliza claves en formato `snake_case`.

Por ejemplo, una respuesta conceptual podría contener:

```json
{
    "access_token": "ACCESS_TOKEN",
    "refresh_token": "REFRESH_TOKEN",
    "token_type": "Bearer"
}
```

La estructura exacta y los métodos getters deben consultarse en la implementación de `TokenPair`.

## 14. Access Tokens y Refresh Tokens

ShieldPHP contempla dos tipos de tokens con diferentes responsabilidades.

### Access Token

Se utiliza para autenticar solicitudes a recursos protegidos.

Características recomendadas:

- Duración corta.
- Uso limitado a la audiencia correspondiente.
- Validación en cada solicitud protegida.
- Revocación cuando resulte necesario.

Valor inicial configurado:

```text
900 segundos
```

Equivalente a 15 minutos.

### Refresh Token

Se utiliza para obtener un nuevo token de acceso mediante un flujo de renovación autorizado.

Características recomendadas:

- Duración superior al access token.
- Almacenamiento protegido.
- Rotación cuando corresponda.
- Revocación durante cierre de sesión o compromiso de credenciales.
- Protección contra reutilización cuando se implemente rotación.

Valor inicial configurado:

```text
604800 segundos
```

Equivalente a 7 días.

### Comparación

| Característica | Access Token | Refresh Token |
|---|---|---|
| Objetivo | Acceder a recursos. | Renovar credenciales. |
| Duración inicial | 15 minutos. | 7 días. |
| Uso | Solicitudes protegidas. | Endpoint de renovación. |
| Exposición | Controlada y limitada. | Especialmente restringida. |
| Revocación | Recomendada. | Esencial para controlar sesiones. |

**Importante:** disponer de `JWTManager` y `TokenPair` no significa que el flujo completo de renovación, rotación y detección de reutilización ya esté implementado. Esas operaciones deben desarrollarse y probarse por separado.

## 15. Configuración mediante variables de entorno

ShieldPHP contempla las siguientes variables en `.env.example`:

```dotenv
# ==========================================
# JWT AUTHENTICATION
# ==========================================

SHIELDPHP_JWT_ENABLED=true

SHIELDPHP_JWT_SIGNING_KEY_BASE64=

SHIELDPHP_JWT_ALGORITHM=HS256

SHIELDPHP_JWT_ISSUER=shieldphp

SHIELDPHP_JWT_AUDIENCE=shieldphp-api

SHIELDPHP_JWT_ACCESS_TTL=900

SHIELDPHP_JWT_REFRESH_TTL=604800

SHIELDPHP_JWT_LEEWAY=0
```

### Descripción

| Variable | Descripción |
|---|---|
| `SHIELDPHP_JWT_ENABLED` | Activación prevista del mecanismo JWT. |
| `SHIELDPHP_JWT_SIGNING_KEY_BASE64` | Clave de firma codificada en Base64. |
| `SHIELDPHP_JWT_ALGORITHM` | Algoritmo de firma. |
| `SHIELDPHP_JWT_ISSUER` | Emisor. |
| `SHIELDPHP_JWT_AUDIENCE` | Audiencia. |
| `SHIELDPHP_JWT_ACCESS_TTL` | Expiración del access token. |
| `SHIELDPHP_JWT_REFRESH_TTL` | Expiración del refresh token. |
| `SHIELDPHP_JWT_LEEWAY` | Tolerancia temporal. |

La aplicación debe cargar y convertir estas variables antes de construir `JWTConfig`.

### Cargar la clave

```php
$encodedKey = getenv(
    'SHIELDPHP_JWT_SIGNING_KEY_BASE64'
);

if (!is_string($encodedKey) || $encodedKey === '') {
    throw new RuntimeException(
        'No se ha configurado la clave JWT.'
    );
}

$signingKey = base64_decode($encodedKey, true);

if ($signingKey === false) {
    throw new RuntimeException(
        'La clave JWT no contiene Base64 válido.'
    );
}
```

La clave obtenida es una cadena binaria que debe cumplir los requisitos del algoritmo configurado.

## 16. Generación de claves

Para HS256 puedes generar una clave de 32 bytes mediante PHP:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Para otros algoritmos HMAC, respeta sus tamaños mínimos:

```bash
# HS256: 32 bytes
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"

# HS384: 48 bytes
php -r "echo base64_encode(random_bytes(48)), PHP_EOL;"

# HS512: 64 bytes
php -r "echo base64_encode(random_bytes(64)), PHP_EOL;"
```

Las claves deben:

- Permanecer fuera del repositorio.
- Ser diferentes entre desarrollo y producción.
- Mantenerse protegidas.
- Ser compartidas únicamente con los servicios autorizados.
- Rotarse mediante un procedimiento que contemple los tokens todavía válidos.

## 17. Integración con Authentication

El módulo JWT puede utilizarse después de autenticar a un usuario mediante `AuthManager`.

La separación de responsabilidades es:

| Módulo | Responsabilidad |
|---|---|
| Authentication | Comprobar las credenciales. |
| `UserIdentity` | Representar al usuario autenticado. |
| JWT | Generar y validar tokens. |
| Authorization | Comprobar roles y permisos. |

### Flujo conceptual

```text
          Usuario
             |
             v
        Credenciales
             |
             v
         AuthManager
             |
             v
          AuthResult
             |
             v
        UserIdentity
             |
             v
        JWTClaims
             |
             v
         JWTManager
             |
             v
        ACCESS TOKEN
             |
             v
       API protegida
```

El token debe emitirse únicamente después de que la identidad haya sido autenticada correctamente.

## 18. Integración con API REST

Los tokens suelen enviarse mediante el encabezado HTTP `Authorization`.

Formato:

```http
Authorization: Bearer ACCESS_TOKEN
```

Ejemplo de solicitud:

```bash
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer TU_JWT" \
  -H "Accept: application/json"
```

El servidor debe:

1. Extraer el token.
2. Validar su formato.
3. Comprobar su firma.
4. Verificar las declaraciones temporales.
5. Validar emisor y audiencia.
6. Comprobar revocación cuando esté configurada.
7. Recuperar la identidad correspondiente.
8. Comprobar los permisos necesarios.

Una solicitud con token inválido debe ser rechazada.

## 19. Integración con Slim Framework

ShieldPHP contempla un middleware para integrar JWT con Slim Framework 4:

```php
ShieldPHP\Slim\QuickAuthMiddleware
```

Archivo:

```text
slim/QuickAuthMiddleware.php
```

El middleware utiliza los componentes `JWTManager` y `TokenValidator` para autenticar solicitudes protegidas.

Consulta:

```text
slim/README.md
```

La integración todavía requiere validación completa de sus dependencias y pruebas antes de considerarse lista para producción.

## 20. Manejo de errores

La validación de JWT puede fallar por diferentes motivos:

- Firma incorrecta.
- Token malformado.
- Token expirado.
- Token todavía no válido.
- Emisor incorrecto.
- Audiencia incorrecta.
- Identificador revocado.
- Algoritmo no permitido.

ShieldPHP contempla excepciones específicas para representar diferentes errores.

| Excepción | Descripción |
|---|---|
| `InvalidTokenException` | Token inválido. |
| `ExpiredTokenException` | Token expirado. |

También se dispone de `ShieldException` como parte de la jerarquía general de excepciones.

### Respuesta HTTP recomendada

Token inválido:

```json
{
    "success": false,
    "error": "invalid_token",
    "message": "El token proporcionado no es válido."
}
```

Token expirado:

```json
{
    "success": false,
    "error": "token_expired",
    "message": "La sesión ha expirado."
}
```

Para API que no necesitan distinguir entre las diferentes causas de rechazo, se recomienda utilizar respuestas genéricas y evitar exponer detalles criptográficos internos.

## 21. Buenas prácticas de seguridad

La utilización de JWT requiere proteger correctamente las claves, declaraciones y tokens.

### Firma

- Utilizar algoritmos criptográficos admitidos.
- Mantener claves suficientemente largas.
- No aceptar algoritmos seleccionados arbitrariamente por el cliente.
- Validar todas las firmas en el servidor.

### Declaraciones

- Configurar correctamente el emisor.
- Restringir la audiencia.
- Establecer fechas de expiración.
- Utilizar identificadores `jti` únicos.
- No incluir contraseñas ni secretos en el payload.

### Almacenamiento

- No publicar claves de firma.
- No registrar tokens completos.
- Utilizar mecanismos de almacenamiento seguros.
- Limitar la exposición del refresh token.
- Evitar almacenar tokens en ubicaciones accesibles desde JavaScript cuando sea posible.

### Revocación

- Utilizar almacenamiento persistente cuando la aplicación tenga varios procesos o servidores.
- Mantener registros hasta que el token expire.
- Invalidar credenciales durante eventos de seguridad.
- Considerar la rotación de refresh tokens.

### Transporte

- Utilizar HTTPS.
- No enviar tokens en parámetros de URL.
- Evitar incluirlos en registros de solicitudes.
- Configurar controles adecuados para aplicaciones web y móviles.

## 22. Pruebas

El módulo JWT cuenta con una estructura de pruebas unitarias en:

```text
packages/php-core/tests/Unit/JWT/
```

Los archivos principales son:

| Prueba | Objetivo |
|---|---|
| `JWTClaimsTest.php` | Declaraciones y restricciones del payload. |
| `JWTConfigTest.php` | Validación de configuración. |
| `JWTManagerTest.php` | Emisión, decodificación y validación. |
| `TokenBlacklistTest.php` | Administración de revocaciones. |
| `TokenPairTest.php` | Representación de conjuntos de tokens. |
| `TokenValidatorTest.php` | Comprobación de validez y revocación. |

### Ejecutar todas las pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

### Ejecutar solamente las pruebas JWT

```bash
vendor/bin/phpunit tests/Unit/JWT
```

### Ejecutar PHPStan

```bash
composer analyse
```

Se recomienda probar especialmente tokens expirados, firmas alteradas, claves incorrectas, declaraciones inválidas y tokens revocados.

## 23. Estructura del módulo

```text
packages/php-core/
│
├── src/
│   ├── JWT/
│   │   ├── JWTConfig.php
│   │   ├── JWTClaims.php
│   │   ├── JWTManager.php
│   │   ├── TokenValidator.php
│   │   ├── TokenBlacklist.php
│   │   ├── TokenPair.php
│   │   └── TokenRevocationStoreInterface.php
│   │
│   └── Exceptions/
│       ├── InvalidTokenException.php
│       └── ExpiredTokenException.php
│
└── tests/
    └── Unit/
        └── JWT/
            ├── JWTClaimsTest.php
            ├── JWTConfigTest.php
            ├── JWTManagerTest.php
            ├── TokenBlacklistTest.php
            ├── TokenPairTest.php
            └── TokenValidatorTest.php
```

## 24. Estado actual

| Funcionalidad | Estado |
|---|---|
| JWTConfig | Implementado inicialmente |
| JWTClaims | Base implementada |
| JWTManager | Base implementada |
| Firma HMAC | Implementación inicial |
| Validación de declaraciones | Implementación inicial |
| Validación de expiración | Contemplada |
| Validación de emisor y audiencia | Contemplada |
| TokenValidator | Base implementada |
| TokenBlacklist | Implementación en memoria |
| TokenPair | Base implementada |
| TokenRevocationStoreInterface | Contrato definido |
| Almacenamiento persistente de revocaciones | Pendiente de adaptador |
| Emisión completa de refresh tokens | Requiere integración |
| Rotación de refresh tokens | Pendiente |
| Detección de reutilización de refresh tokens | Pendiente |
| Verificación de algoritmos asimétricos | No contemplada en la configuración actual |
| Pruebas unitarias | Suite inicial |
| Pruebas de integración | Pendientes |

## 25. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Roles y permisos.
- `docs/configuration.md` — Configuración.
- `docs/encryption.md` — Herramientas criptográficas.
- `slim/README.md` — Integración con Slim.
- `SECURITY.md` — Política de seguridad.

### Referencias externas

- [RFC 7519 — JSON Web Token](https://www.rfc-editor.org/rfc/rfc7519)
- [RFC 7515 — JSON Web Signature](https://www.rfc-editor.org/rfc/rfc7515)
- [RFC 8725 — JWT Best Current Practices](https://www.rfc-editor.org/rfc/rfc8725)
- [RFC 6750 — Bearer Token Usage](https://www.rfc-editor.org/rfc/rfc6750)
- [Firebase PHP-JWT](https://github.com/firebase/php-jwt)
- [OWASP JSON Web Token Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/JSON_Web_Token_for_Java_Cheat_Sheet.html)

---

<p align="center">
  <strong>ShieldPHP JWT</strong>
  <br>
  Identidad, tokens y autenticación segura para aplicaciones PHP.
</p>