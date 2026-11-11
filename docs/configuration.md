# Configuration — ShieldPHP

<p align="center">
  <strong>Administración, validación y seguridad de configuración para PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Configuration-Management-blue?style=for-the-badge" alt="Configuration Management">
  <img src="https://img.shields.io/badge/Architecture-Modular-purple?style=for-the-badge" alt="Arquitectura modular">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

El módulo **Configuration** de ShieldPHP proporciona los componentes necesarios para administrar y validar los parámetros de configuración utilizados por los diferentes módulos de la biblioteca.

Su objetivo es centralizar las opciones de funcionamiento del sistema, facilitar la personalización de sus componentes y mantener separada la configuración de la lógica de negocio.

La configuración de ShieldPHP contempla diferentes áreas:

- Configuración general de la aplicación.
- Parámetros de autenticación.
- Configuración JWT.
- Opciones criptográficas.
- Administración de sesiones.
- Seguridad de cookies.
- Integraciones OAuth.
- Políticas de contraseñas.
- Protección contra solicitudes excesivas.
- Configuración de registros y diagnóstico.

El módulo busca proporcionar una configuración consistente, predecible y validada antes de utilizar los componentes correspondientes.

## 2. Arquitectura

El módulo Configuration contempla dos componentes principales:

| Componente | Responsabilidad |
|---|---|
| `ShieldConfig` | Almacenar y consultar parámetros de configuración. |
| `ConfigValidator` | Validar la configuración recibida. |

Namespace principal:

```php
ShieldPHP\Core\Config\
```

### Flujo de configuración

```text
              CONFIGURACIÓN DE LA APLICACIÓN
                           |
                           |
                           v
                  ┌──────────────────┐
                  │  Configuration   │
                  │                  │
                  │  Array / Options │
                  └────────┬─────────┘
                           |
                           v
                  ┌──────────────────┐
                  │  ConfigValidator │
                  │                  │
                  │ Validar opciones │
                  └────────┬─────────┘
                           |
                    ┌──────┴──────┐
                    |             |
                    v             v
                Válida         Inválida
                    |             |
                    v             v
              ShieldConfig   ConfigurationException
                    |
                    v
              ShieldPHP Core
                    |
          ┌─────────┼──────────┐
          |         |          |
          v         v          v
         Auth      JWT       Crypto
```

## 3. ShieldConfig

`ShieldConfig` es el componente principal de administración de configuración.

**Namespace:**

```php
ShieldPHP\Core\Config\ShieldConfig
```

Su responsabilidad es recibir, almacenar y proporcionar acceso a los parámetros de configuración de ShieldPHP.

### Constructor

```php
public function __construct(array $configuration);
```

El constructor recibe un arreglo asociativo con las opciones que utilizará la aplicación.

### Valores predeterminados conocidos

La configuración inicial contempla:

| Parámetro | Valor predeterminado | Descripción |
|---|---|---|
| `app_name` | `ShieldPHP` | Nombre de la aplicación. |
| `access_token_ttl` | `900` | Duración del token de acceso en segundos. |
| `refresh_token_ttl` | `604800` | Duración del token de actualización en segundos. |
| `secure_cookies` | `true` | Configuración de seguridad de cookies. |
| `same_site` | `Lax` | Política SameSite de cookies. |

Los valores indicados corresponden a la configuración inicial conocida del componente.

### Métodos disponibles

#### get()

Obtiene el valor de una opción de configuración.

```php
public function get(
    string $key,
    mixed $default = null
): mixed;
```

Ejemplo:

```php
$appName = $config->get('app_name');

$accessTokenTTL = $config->get(
    'access_token_ttl',
    900
);
```

#### has()

Comprueba si existe una opción de configuración.

```php
public function has(string $key): bool;
```

Ejemplo:

```php
if ($config->has('app_name')) {
    echo 'La configuración existe';
}
```

#### all()

Obtiene el arreglo completo de configuración.

```php
public function all(): array;
```

Ejemplo:

```php
$configuration = $config->all();

foreach ($configuration as $key => $value) {
    echo $key . ': ' . (is_scalar($value) ? (string) $value : '[valor complejo]');
}
```

No se recomienda imprimir configuraciones completas cuando puedan contener claves, contraseñas o secretos.

## 4. ConfigValidator

`ConfigValidator` es responsable de comprobar que los parámetros proporcionados cumplan con las reglas de configuración establecidas.

**Namespace:**

```php
ShieldPHP\Core\Config\ConfigValidator
```

### Método validate()

```php
public static function validate(array $configuration): void;
```

Valida un arreglo de configuración.

Si encuentra parámetros inválidos, comunica el problema mediante `ConfigurationException`.

### Ejemplo

```php
use ShieldPHP\Core\Config\ConfigValidator;

$configuration = [
    'app_name' => 'Mi aplicación',
    'access_token_ttl' => 900,
    'refresh_token_ttl' => 604800,
    'secure_cookies' => true,
    'same_site' => 'Lax'
];

ConfigValidator::validate($configuration);
```

### Consideraciones de validación

Una configuración debe comprobar aspectos como:

- Tipos de datos esperados.
- Valores obligatorios.
- Rangos numéricos.
- Valores permitidos.
- Dependencias entre parámetros.
- Formatos de claves criptográficas.
- Parámetros incompatibles entre sí.

Las reglas específicas dependerán de la implementación de `ConfigValidator`.

## 5. Configuración básica

Para utilizar ShieldConfig se puede proporcionar un arreglo de opciones al constructor.

### Ejemplo completo

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use ShieldPHP\Core\Config\ShieldConfig;

$config = new ShieldConfig([
    'app_name' => 'Mi aplicación',
    'access_token_ttl' => 900,
    'refresh_token_ttl' => 604800,
    'secure_cookies' => true,
    'same_site' => 'Lax'
]);

echo $config->get('app_name');

echo $config->get('access_token_ttl');

echo $config->get('refresh_token_ttl');
```

El objeto `ShieldConfig` permite centralizar los parámetros para que los componentes puedan obtener sus valores sin depender directamente de la estructura de configuración de la aplicación.

## 6. Variables de entorno

ShieldPHP cuenta con una plantilla de variables de entorno en:

```text
.env.example
```

Su propósito es proporcionar una referencia para configurar los diferentes componentes del ecosistema.

Las variables se organizan por categorías.

### 6.1 Aplicación

```dotenv
APP_NAME="ShieldPHP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC
```

Estas variables describen el entorno y la información general de la aplicación consumidora.

### 6.2 Configuración general

```dotenv
SHIELDPHP_APP_NAME="ShieldPHP"
SHIELDPHP_ENV=local
SHIELDPHP_DEBUG=true
```

Identifican el nombre y el entorno de ejecución de ShieldPHP.

### 6.3 JWT

```dotenv
SHIELDPHP_JWT_ENABLED=true

SHIELDPHP_JWT_SIGNING_KEY_BASE64=

SHIELDPHP_JWT_ALGORITHM=HS256
SHIELDPHP_JWT_ISSUER="shieldphp"
SHIELDPHP_JWT_AUDIENCE="shieldphp-api"

SHIELDPHP_JWT_ACCESS_TTL=900
SHIELDPHP_JWT_REFRESH_TTL=604800
SHIELDPHP_JWT_LEEWAY=0
```

Estas variables permiten describir:

- Activación del mecanismo JWT.
- Clave de firma.
- Algoritmo criptográfico.
- Emisor.
- Audiencia.
- Duración de los tokens.
- Margen de tolerancia temporal.

Los valores deben convertirse y validarse antes de construir `JWTConfig`.

### 6.4 Cifrado

```dotenv
SHIELDPHP_ENCRYPTION_ENABLED=true

SHIELDPHP_ENCRYPTION_KEY_BASE64=

SHIELDPHP_ENCRYPTION_CIPHER=xchacha20poly1305
```

Estas variables representan las opciones previstas para el cifrado de información sensible.

La implementación conocida de `Encryption` utiliza `sodium_crypto_secretbox`, cuyo formato corresponde a XSalsa20-Poly1305. Por tanto, el valor `xchacha20poly1305` de la plantilla no debe interpretarse como un algoritmo ya implementado por esa clase.

La selección del algoritmo debe coincidir siempre con la implementación criptográfica real.

### 6.5 Administración de claves

```dotenv
SHIELDPHP_KEY_ACTIVE_ID=primary

SHIELDPHP_KEY_ROTATION_ENABLED=false
```

Permiten describir la clave activa y la estrategia prevista para rotación de claves.

La rotación requiere que los componentes de almacenamiento y recuperación de claves estén correctamente implementados.

### 6.6 Contraseñas

```dotenv
SHIELDPHP_PASSWORD_MIN_LENGTH=12
SHIELDPHP_PASSWORD_MAX_LENGTH=1024

SHIELDPHP_PASSWORD_REQUIRE_UPPERCASE=true
SHIELDPHP_PASSWORD_REQUIRE_LOWERCASE=true
SHIELDPHP_PASSWORD_REQUIRE_NUMBER=true
SHIELDPHP_PASSWORD_REQUIRE_SYMBOL=true
```

Estas variables representan las políticas previstas para `PasswordPolicy`.

Los requisitos adicionales deben estar conectados explícitamente a su implementación para que tengan efecto.

### 6.7 Sesiones

```dotenv
SHIELDPHP_SESSION_ENABLED=true
SHIELDPHP_SESSION_NAME=SHIELDPHP_SESSION
SHIELDPHP_SESSION_LIFETIME=7200

SHIELDPHP_SESSION_COOKIE_PATH=/
SHIELDPHP_SESSION_COOKIE_DOMAIN=

SHIELDPHP_SESSION_COOKIE_SECURE=true
SHIELDPHP_SESSION_COOKIE_HTTPONLY=true
SHIELDPHP_SESSION_COOKIE_SAMESITE=Lax

SHIELDPHP_SESSION_USE_STRICT_MODE=true
SHIELDPHP_SESSION_USE_ONLY_COOKIES=true
SHIELDPHP_SESSION_USE_TRANS_SID=false
```

Estas variables representan las opciones previstas para configurar sesiones y sus mecanismos de protección.

### 6.8 Cookies

```dotenv
SHIELDPHP_COOKIE_SECURE=true
SHIELDPHP_COOKIE_HTTPONLY=true
SHIELDPHP_COOKIE_SAMESITE=Lax
```

Permiten describir los atributos de seguridad de las cookies.

### 6.9 OAuth

Google:

```dotenv
SHIELDPHP_OAUTH_GOOGLE_ENABLED=false

SHIELDPHP_OAUTH_GOOGLE_CLIENT_ID=
SHIELDPHP_OAUTH_GOOGLE_CLIENT_SECRET=

SHIELDPHP_OAUTH_GOOGLE_REDIRECT_URI=http://localhost:8000/oauth/google/callback
```

Facebook:

```dotenv
SHIELDPHP_OAUTH_FACEBOOK_ENABLED=false

SHIELDPHP_OAUTH_FACEBOOK_CLIENT_ID=
SHIELDPHP_OAUTH_FACEBOOK_CLIENT_SECRET=

SHIELDPHP_OAUTH_FACEBOOK_REDIRECT_URI=http://localhost:8000/oauth/facebook/callback
```

Configuración adicional:

```dotenv
SHIELDPHP_OAUTH_STATE_TTL=600
SHIELDPHP_OAUTH_PKCE_ENABLED=true
```

Las credenciales deben permanecer privadas y las URI de redirección deben validarse mediante una lista de direcciones autorizadas.

### 6.10 Limitación de solicitudes

```dotenv
SHIELDPHP_RATE_LIMIT_ENABLED=true

SHIELDPHP_RATE_LIMIT_MAX_ATTEMPTS=60
SHIELDPHP_RATE_LIMIT_DECAY_SECONDS=60

SHIELDPHP_RATE_LIMIT_LOGIN_ATTEMPTS=5
SHIELDPHP_RATE_LIMIT_LOGIN_DECAY_SECONDS=900
```

Estas variables representan los límites previstos para solicitudes generales y operaciones de autenticación.

Su funcionamiento requiere integrar un mecanismo de limitación de solicitudes y un almacenamiento adecuado.

### 6.11 Protección CSRF

```dotenv
SHIELDPHP_CSRF_ENABLED=true
SHIELDPHP_CSRF_TOKEN_NAME=_csrf
SHIELDPHP_CSRF_HEADER=X-CSRF-TOKEN
```

Describen las opciones previstas para proteger operaciones que utilizan credenciales de sesión y cookies.

### 6.12 Registro de eventos

```dotenv
SHIELDPHP_LOG_ENABLED=true
SHIELDPHP_LOG_LEVEL=debug
SHIELDPHP_LOG_CHANNEL=default
```

Permiten establecer el nivel y canal de registro de eventos, siempre que exista un adaptador de logging configurado.

## 7. Cargar variables de entorno

PHP no carga automáticamente los archivos `.env`.

La aplicación debe utilizar un cargador de variables o recibirlas directamente desde el entorno de ejecución.

Por ejemplo, si las variables ya están disponibles mediante el entorno del sistema:

```php
$appName = getenv('SHIELDPHP_APP_NAME') ?: 'ShieldPHP';

$accessTokenTTL = (int) (
    getenv('SHIELDPHP_JWT_ACCESS_TTL') ?: 900
);

$refreshTokenTTL = (int) (
    getenv('SHIELDPHP_JWT_REFRESH_TTL') ?: 604800
);

$secureCookies = filter_var(
    getenv('SHIELDPHP_COOKIE_SECURE') ?: 'true',
    FILTER_VALIDATE_BOOLEAN
);
```

### Construcción de ShieldConfig

```php
use ShieldPHP\Core\Config\ShieldConfig;

$config = new ShieldConfig([
    'app_name' => $appName,
    'access_token_ttl' => $accessTokenTTL,
    'refresh_token_ttl' => $refreshTokenTTL,
    'secure_cookies' => $secureCookies,
    'same_site' => getenv('SHIELDPHP_COOKIE_SAMESITE') ?: 'Lax'
]);
```

Este ejemplo carga las variables desde el proceso PHP y las convierte a los tipos esperados por la configuración.

Las variables de entorno adicionales necesitan sus propios adaptadores para llegar a los módulos correspondientes.

## 8. Configuración de JWT

El módulo JWT tiene su propia clase de configuración: `JWTConfig`.

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

### Ejemplo

```php
use ShieldPHP\Core\JWT\JWTConfig;

$encodedKey = getenv(
    'SHIELDPHP_JWT_SIGNING_KEY_BASE64'
);

if (!is_string($encodedKey) || $encodedKey === '') {
    throw new RuntimeException(
        'No se ha configurado la clave de firma JWT.'
    );
}

$signingKey = base64_decode($encodedKey, true);

if ($signingKey === false) {
    throw new RuntimeException(
        'La clave JWT no contiene Base64 válido.'
    );
}

$jwtConfig = new JWTConfig(
    signingKey: $signingKey,
    issuer: getenv('SHIELDPHP_JWT_ISSUER') ?: 'shieldphp',
    audience: getenv('SHIELDPHP_JWT_AUDIENCE') ?: 'shieldphp-api',
    algorithm: getenv('SHIELDPHP_JWT_ALGORITHM') ?: 'HS256',
    accessTTL: (int) (getenv('SHIELDPHP_JWT_ACCESS_TTL') ?: 900),
    refreshTTL: (int) (getenv('SHIELDPHP_JWT_REFRESH_TTL') ?: 604800),
    leeway: (int) (getenv('SHIELDPHP_JWT_LEEWAY') ?: 0)
);
```

La configuración valida las longitudes mínimas de las claves según el algoritmo seleccionado.

| Algoritmo | Longitud mínima |
|---|---:|
| HS256 | 32 bytes |
| HS384 | 48 bytes |
| HS512 | 64 bytes |

También contempla:

- Duración máxima del token de acceso: 86400 segundos.
- Expiración del token de actualización superior a la del token de acceso.
- Duración máxima del token de actualización: 365 días.
- Margen de tolerancia: entre 0 y 300 segundos.

Las claves deben decodificarse de Base64 a bytes antes de entregarlas a `JWTConfig`.

## 9. Configuración de cifrado

El módulo Crypto utiliza claves binarias para realizar las operaciones criptográficas.

La implementación conocida de `Encryption` requiere una clave de 32 bytes.

### Ejemplo de carga de clave

```php
$encodedEncryptionKey = getenv(
    'SHIELDPHP_ENCRYPTION_KEY_BASE64'
);

if (
    !is_string($encodedEncryptionKey) ||
    $encodedEncryptionKey === ''
) {
    throw new RuntimeException(
        'No se ha configurado la clave de cifrado.'
    );
}

$encryptionKey = base64_decode(
    $encodedEncryptionKey,
    true
);

if (
    $encryptionKey === false ||
    strlen($encryptionKey) !== 32
) {
    throw new RuntimeException(
        'La clave de cifrado debe contener exactamente 32 bytes.'
    );
}
```

La clave obtenida puede entregarse a los componentes criptográficos que utilizan ese formato.

No se debe reutilizar una misma clave para JWT y cifrado.

## 10. Generación de claves

Para generar claves aleatorias seguras desde PHP:

### Clave para HS256

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

### Clave de cifrado

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Los dos valores deben ser diferentes.

### Ejemplo de configuración

```dotenv
SHIELDPHP_JWT_SIGNING_KEY_BASE64=CLAVE_JWT_GENERADA
SHIELDPHP_ENCRYPTION_KEY_BASE64=CLAVE_CIFRADO_GENERADA
```

Nunca utilices estos valores de ejemplo como claves reales.

## 11. Entornos de ejecución

Se recomienda separar la configuración según el entorno de ejecución.

### Desarrollo

```dotenv
APP_ENV=local
APP_DEBUG=true

SHIELDPHP_ENV=local
SHIELDPHP_DEBUG=true
SHIELDPHP_TESTING=false
```

El entorno de desarrollo permite habilitar herramientas de diagnóstico, evitando exponer información sensible en los registros.

### Pruebas

```dotenv
APP_ENV=testing
APP_DEBUG=true

SHIELDPHP_ENV=testing
SHIELDPHP_DEBUG=true
SHIELDPHP_TESTING=true
```

En pruebas se recomienda utilizar claves, usuarios y datos de prueba independientes.

### Producción

```dotenv
APP_ENV=production
APP_DEBUG=false

SHIELDPHP_ENV=production
SHIELDPHP_DEBUG=false
SHIELDPHP_TESTING=false

SHIELDPHP_COOKIE_SECURE=true
SHIELDPHP_SESSION_COOKIE_SECURE=true
```

En producción deben utilizarse HTTPS, secretos administrados de manera segura y configuraciones de cookies apropiadas para el entorno.

## 12. Manejo de errores de configuración

ShieldPHP contempla `ConfigurationException` para comunicar errores relacionados con configuraciones inválidas.

**Namespace:**

```php
ShieldPHP\Core\Exceptions\ConfigurationException
```

### Ejemplo

```php
use ShieldPHP\Core\Config\ConfigValidator;
use ShieldPHP\Core\Exceptions\ConfigurationException;

try {
    ConfigValidator::validate($configuration);

} catch (ConfigurationException $exception) {

    // Registrar el incidente sin incluir secretos.
    error_log(
        'Error de configuración en ShieldPHP.'
    );

    throw $exception;
}
```

Los errores de configuración deben detectarse antes de iniciar las operaciones sensibles.

No es recomendable continuar utilizando claves vacías, algoritmos no permitidos o parámetros de expiración inválidos.

## 13. Seguridad de la configuración

La configuración puede contener información crítica para la seguridad de una aplicación.

Por eso se recomienda:

### Protección de secretos

- No almacenar claves reales en Git.
- Mantener los archivos `.env` fuera del control de versiones.
- No compartir secretos entre entornos.
- Utilizar claves criptográficas generadas de forma segura.
- Limitar el acceso a credenciales.
- Evitar imprimir configuraciones sensibles en logs.

### Protección de claves

- Separar claves de firma y cifrado.
- Validar longitudes y formatos.
- Considerar mecanismos de rotación.
- Mantener copias de respaldo protegidas.
- Evitar almacenar claves privadas en directorios públicos.

### Configuración de producción

- Desactivar el modo de depuración.
- Utilizar HTTPS.
- Establecer tiempos de expiración adecuados.
- Revisar las políticas de cookies.
- Aplicar las configuraciones de seguridad correspondientes a cada módulo.
- Validar las configuraciones antes del inicio de la aplicación.

## 14. Organización de archivos

Los archivos de configuración del monorepositorio se organizan de la siguiente manera:

```text
shieldphp/
│
├── .env.example
├── .editorconfig
├── .gitattributes
├── .gitignore
│
├── composer.json
├── phpunit.xml
│
├── packages/
│   └── php-core/
│       ├── src/
│       │   └── Config/
│       │       ├── ShieldConfig.php
│       │       └── ConfigValidator.php
│       │
│       ├── tests/
│       │   └── Unit/
│       │       └── Config/
│       │           ├── ShieldConfigTest.php
│       │           └── ConfigValidatorTest.php
│       │
│       ├── composer.json
│       └── phpstan.neon
│
└── docs/
    └── configuration.md
```

## 15. Estado actual

| Funcionalidad | Estado |
|---|---|
| ShieldConfig | Base implementada |
| ConfigValidator | Base implementada |
| Acceso a parámetros | Implementación inicial |
| Valores predeterminados | Definidos |
| Validación de parámetros | Implementación inicial |
| Configuración JWT | Clase independiente disponible |
| Carga automática de `.env` | No implementada en Core |
| Adaptador universal de variables de entorno | Pendiente |
| Integración completa de claves | En desarrollo |
| Configuración de sesiones | Requiere validación |
| Configuración OAuth | Requiere adaptadores |
| Configuración de rate limiting | Pendiente de integración |
| Configuración CSRF | Pendiente de integración |
| Rotación de claves | Requiere implementación y validación |

La presencia de una variable en `.env.example` no significa que ShieldPHP ya la consuma automáticamente.

## 16. Pruebas

El módulo Configuration dispone de una estructura de pruebas unitarias en:

```text
packages/php-core/tests/Unit/Config/
```

Los archivos principales son:

- `ShieldConfigTest.php`
- `ConfigValidatorTest.php`

### Ejecutar las pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

### Ejecutar únicamente las pruebas de configuración

```bash
vendor/bin/phpunit tests/Unit/Config
```

### Ejecutar PHPStan

```bash
composer analyse
```

Se recomienda comprobar tanto las configuraciones válidas como los escenarios con tipos incorrectos, valores fuera de rango, parámetros ausentes y claves criptográficas inválidas.

## 17. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Autorización.
- `SECURITY.md` — Política de seguridad.
- `.env.example` — Plantilla de variables de entorno.
- `CHANGELOG.md` — Registro de cambios.

### Referencias externas

- [PHP Environment Variables](https://www.php.net/manual/en/function.getenv.php)
- [PHP Configuration](https://www.php.net/manual/en/configuration.php)
- [PHP Sodium](https://www.php.net/manual/en/book.sodium.php)
- [Composer](https://getcomposer.org/doc/)
- [OWASP Secrets Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html)
- [OWASP Configuration Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Configuration_Cheat_Sheet.html)

---

<p align="center">
  <strong>ShieldPHP Configuration</strong>
  <br>
  Configuración centralizada, validada y segura para PHP.
</p>