# Sessions — ShieldPHP

<p align="center">
  <strong>Administración segura de sesiones y cookies para aplicaciones PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Sessions-PHP%20Sessions-blue?style=for-the-badge" alt="PHP Sessions">
  <img src="https://img.shields.io/badge/Security-Session%20Management-purple?style=for-the-badge" alt="Session Management">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

El módulo **Sessions** de ShieldPHP proporciona componentes para administrar sesiones de usuario y cookies dentro de aplicaciones PHP.

Su objetivo es centralizar el ciclo de vida de las sesiones, facilitar el almacenamiento temporal de información y establecer opciones de protección frente a ataques relacionados con la gestión de sesiones.

El módulo contempla:

- Inicio y cierre de sesiones.
- Consulta del estado de una sesión.
- Almacenamiento y recuperación de información.
- Eliminación de datos almacenados.
- Regeneración de identificadores.
- Destrucción de sesiones.
- Administración de cookies.
- Configuración de atributos de seguridad.
- Control de duración y expiración.
- Integración con aplicaciones autenticadas.

La implementación se apoya en los mecanismos nativos de sesiones de PHP y busca facilitar su utilización mediante componentes reutilizables.

## 2. Arquitectura

El módulo Sessions contempla tres componentes principales:

| Componente | Responsabilidad |
|---|---|
| `SessionConfig` | Configuración de las sesiones. |
| `SessionManager` | Administración del ciclo de vida de las sesiones. |
| `CookieManager` | Administración de cookies. |

Namespace principal:

```php
ShieldPHP\Core\Session\
```

### Diagrama de componentes

```text
                 APLICACIÓN PHP
                       |
                       v
                SESSION MANAGER
                       |
             ┌─────────┴─────────┐
             |                   |
             v                   v
        SessionConfig        CookieManager
             |                   |
             v                   v
       Configuración          Cookies HTTP
             |
             v
       PHP SESSION SYSTEM
             |
             v
       $_SESSION / SESSION ID
             |
             v
       Datos temporales
```

## 3. Conceptos fundamentales

Antes de utilizar el módulo es importante distinguir entre sesiones y cookies.

### Sesiones

Una sesión permite almacenar información temporal asociada a un usuario.

Los datos se conservan en el servidor o en el almacenamiento de sesiones configurado.

Ejemplos:

- Identificador del usuario autenticado.
- Preferencias temporales.
- Estado de autenticación.
- Información de navegación.
- Datos temporales de una operación.

### Cookies

Las cookies son pequeños valores que el servidor puede enviar al navegador para que este los devuelva en solicitudes posteriores.

Ejemplos:

- Identificador de sesión.
- Preferencias de idioma.
- Configuraciones de interfaz.
- Tokens específicos de una aplicación.

### Diferencias

| Característica | Sesión | Cookie |
|---|---|---|
| Ubicación habitual | Servidor | Navegador |
| Capacidad | Depende del almacenamiento | Limitada por el navegador |
| Confidencialidad | Depende de la protección del servidor | Debe considerarse información accesible al cliente |
| Transporte | Mediante un identificador de sesión | Se envía en encabezados HTTP |
| Uso principal | Estado temporal | Datos pequeños asociados al navegador |

Una sesión PHP normalmente utiliza una cookie para transportar su identificador, pero los datos de la sesión permanecen en el almacenamiento del servidor.

## 4. SessionConfig

`SessionConfig` es el componente encargado de representar las opciones de configuración de las sesiones.

**Namespace:**

```php
ShieldPHP\Core\Session\SessionConfig
```

Su objetivo es mantener centralizados los parámetros que controlan el comportamiento de las sesiones PHP.

### Configuraciones contempladas

| Parámetro | Descripción |
|---|---|
| `session_name` | Nombre de la sesión. |
| `cookie_lifetime` | Duración de la cookie de sesión. |
| `cookie_path` | Ruta para la cookie. |
| `cookie_domain` | Dominio de la cookie. |
| `cookie_secure` | Restringe la cookie a conexiones seguras. |
| `cookie_httponly` | Impide el acceso directo desde JavaScript. |
| `cookie_samesite` | Define la política SameSite. |
| `use_strict_mode` | Activa el modo estricto de sesiones PHP. |
| `use_only_cookies` | Evita utilizar identificadores de sesión en URL. |
| `use_trans_sid` | Controla el uso de identificadores de sesión transparentes. |

### Valores de configuración recomendados

```php
$sessionConfiguration = [
    'session_name' => 'SHIELDPHP_SESSION',
    'cookie_lifetime' => 0,
    'cookie_path' => '/',
    'cookie_domain' => '',
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'use_trans_sid' => false
];
```

Estos valores son una propuesta de configuración inicial. Deben adaptarse al dominio, HTTPS y necesidades de la aplicación.

### Ejemplo de inicialización

La interfaz inicial contempla un constructor que recibe un arreglo de configuración:

```php
use ShieldPHP\Core\Session\SessionConfig;

$sessionConfig = new SessionConfig([
    'session_name' => 'SHIELDPHP_SESSION',
    'cookie_lifetime' => 0,
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'use_trans_sid' => false
]);
```

## 5. SessionManager

`SessionManager` es el componente principal para administrar las sesiones.

**Namespace:**

```php
ShieldPHP\Core\Session\SessionManager
```

Su responsabilidad es proporcionar una interfaz centralizada para trabajar con las sesiones de PHP.

### Responsabilidades

- Iniciar sesiones.
- Consultar su estado.
- Almacenar datos.
- Recuperar valores.
- Comprobar la existencia de claves.
- Eliminar información.
- Limpiar datos de una sesión.
- Regenerar identificadores.
- Destruir sesiones.

### Operaciones contempladas

La siguiente es la interfaz de uso prevista para el componente:

```php
start(): void;

isActive(): bool;

set(string $key, mixed $value): void;

get(string $key, mixed $default = null): mixed;

has(string $key): bool;

remove(string $key): void;

clear(): void;

destroy(): void;

regenerateId(bool $deleteOldSession = true): void;
```

Las firmas definitivas deben contrastarse con la implementación actual de `SessionManager`.

### start()

Inicia una sesión PHP cuando no existe una sesión activa.

Uso previsto:

```php
$sessionManager->start();
```

La sesión debe iniciarse antes de enviar contenido al navegador, especialmente cuando sea necesario establecer encabezados HTTP o cookies.

### isActive()

Comprueba si existe una sesión activa.

```php
if ($sessionManager->isActive()) {
    echo 'Existe una sesión activa.';
}
```

### set()

Almacena información dentro de la sesión.

```php
$sessionManager->set(
    'user_id',
    'user-123'
);
```

### get()

Recupera un valor almacenado.

```php
$userId = $sessionManager->get(
    'user_id',
    null
);
```

El segundo argumento representa el valor predeterminado cuando la clave no existe.

### has()

Comprueba si una clave está presente.

```php
if ($sessionManager->has('user_id')) {
    echo 'Existe información del usuario.';
}
```

### remove()

Elimina un valor específico.

```php
$sessionManager->remove('temporary_data');
```

### clear()

Elimina los datos almacenados dentro de la sesión sin necesariamente destruir la sesión PHP.

```php
$sessionManager->clear();
```

### regenerateId()

Regenera el identificador de sesión.

```php
$sessionManager->regenerateId();
```

Esta operación es especialmente importante después de autenticar a un usuario o modificar sus privilegios.

### destroy()

Finaliza la sesión y elimina los datos asociados según el comportamiento de la implementación.

```php
$sessionManager->destroy();
```

## 6. CookieManager

`CookieManager` permite administrar cookies mediante un componente separado de la lógica de sesiones.

**Namespace:**

```php
ShieldPHP\Core\Session\CookieManager
```

### Responsabilidades

- Crear cookies.
- Consultar cookies.
- Verificar su existencia.
- Eliminar cookies.
- Limpiar cookies administradas por la aplicación.

### Operaciones contempladas

La API inicial propuesta contempla:

```php
set(string $name, string $value, array $options = []): void;

get(string $name, mixed $default = null): mixed;

has(string $name): bool;

delete(string $name, array $options = []): void;

clear(): void;
```

Las firmas y opciones concretas deben verificarse contra la implementación instalada.

### Crear una cookie

Ejemplo de uso previsto:

```php
$cookieManager->set(
    'language',
    'es',
    [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]
);
```

### Consultar una cookie

```php
$language = $cookieManager->get(
    'language',
    'es'
);
```

### Comprobar una cookie

```php
if ($cookieManager->has('language')) {
    echo 'Existe una preferencia de idioma.';
}
```

### Eliminar una cookie

```php
$cookieManager->delete(
    'language',
    [
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]
);
```

Para eliminar correctamente una cookie deben coincidir los atributos relevantes de dominio y ruta con los utilizados cuando fue creada.

**Importante:** las cookies se envían mediante encabezados HTTP. Deben configurarse antes de enviar contenido al navegador.

## 7. Protección de identificadores de sesión

Los identificadores de sesión representan credenciales temporales que pueden permitir el acceso al estado de un usuario.

Por esta razón, deben protegerse de manera especial.

### Fijación de sesión

La fijación de sesión ocurre cuando un atacante consigue que una víctima utilice un identificador de sesión conocido o controlado por el atacante.

La protección recomendada consiste en:

1. Habilitar el modo estricto de sesiones.
2. Regenerar el identificador después de autenticar al usuario.
3. No aceptar identificadores arbitrarios.
4. Evitar transmitir identificadores de sesión mediante URL.
5. Destruir sesiones cuando corresponda.

### Regeneración del identificador

Después de autenticar correctamente a un usuario:

```php
$sessionManager->regenerateId();
```

La regeneración reduce el riesgo de fijación de sesión.

También debe aplicarse cuando se realizan cambios importantes en el nivel de privilegios.

La operación debe implementarse de manera que no permita reutilizar una sesión anterior ni provoque pérdida accidental de datos legítimos.

## 8. Protección de cookies

ShieldPHP contempla atributos de seguridad para las cookies utilizadas por las aplicaciones.

### Secure

Restringe el envío de la cookie a conexiones HTTPS.

```text
Secure = true
```

Se recomienda habilitarlo en producción.

### HttpOnly

Impide que JavaScript acceda directamente a la cookie mediante las API habituales del navegador.

```text
HttpOnly = true
```

Ayuda a reducir la exposición de cookies ante determinados ataques XSS, pero no sustituye una protección adecuada contra XSS.

### SameSite

Controla el envío de cookies en solicitudes entre sitios.

| Valor | Comportamiento |
|---|---|
| `Strict` | Restringe ampliamente el envío entre sitios. |
| `Lax` | Permite determinados escenarios de navegación entre sitios. |
| `None` | Permite envío entre sitios y requiere `Secure`. |

El valor debe elegirse según la arquitectura de autenticación y los flujos de navegación de la aplicación.

### Ejemplo recomendado

```dotenv
SHIELDPHP_SESSION_COOKIE_SECURE=true
SHIELDPHP_SESSION_COOKIE_HTTPONLY=true
SHIELDPHP_SESSION_COOKIE_SAMESITE=Lax
```

## 9. Configuración mediante variables de entorno

ShieldPHP contempla las siguientes variables en `.env.example`:

```dotenv
# ==========================================
# SESSION MANAGEMENT
# ==========================================

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

# ==========================================
# COOKIE SECURITY
# ==========================================

SHIELDPHP_COOKIE_SECURE=true
SHIELDPHP_COOKIE_HTTPONLY=true
SHIELDPHP_COOKIE_SAMESITE=Lax
```

### Descripción

| Variable | Función |
|---|---|
| `SHIELDPHP_SESSION_ENABLED` | Activación prevista del módulo. |
| `SHIELDPHP_SESSION_NAME` | Nombre de la sesión. |
| `SHIELDPHP_SESSION_LIFETIME` | Duración prevista de la sesión. |
| `SHIELDPHP_SESSION_COOKIE_PATH` | Ruta de la cookie. |
| `SHIELDPHP_SESSION_COOKIE_DOMAIN` | Dominio de la cookie. |
| `SHIELDPHP_SESSION_COOKIE_SECURE` | Uso de HTTPS. |
| `SHIELDPHP_SESSION_COOKIE_HTTPONLY` | Protección frente al acceso mediante JavaScript. |
| `SHIELDPHP_SESSION_COOKIE_SAMESITE` | Política entre sitios. |
| `SHIELDPHP_SESSION_USE_STRICT_MODE` | Modo estricto de PHP. |
| `SHIELDPHP_SESSION_USE_ONLY_COOKIES` | Uso exclusivo de cookies para el identificador. |
| `SHIELDPHP_SESSION_USE_TRANS_SID` | Uso de identificadores transparentes. |

La duración de la sesión almacenada en el servidor y la duración de la cookie del navegador son conceptos diferentes.

La expiración de una sesión debe controlarse mediante mecanismos del servidor y no depender exclusivamente de la duración de una cookie.

La plantilla de entorno no carga automáticamente estas variables. La aplicación debe convertirlas en opciones que puedan utilizar los componentes.

## 10. Configuración de PHP

Las sesiones de ShieldPHP utilizan el sistema de sesiones de PHP.

Entre las opciones que pueden configurarse se encuentran:

| Directiva PHP | Función |
|---|---|
| `session.name` | Nombre del identificador. |
| `session.cookie_lifetime` | Duración de la cookie de sesión. |
| `session.cookie_path` | Ruta de la cookie. |
| `session.cookie_domain` | Dominio. |
| `session.cookie_secure` | Restricción HTTPS. |
| `session.cookie_httponly` | Restricción de acceso JavaScript. |
| `session.cookie_samesite` | Política SameSite. |
| `session.use_strict_mode` | Modo estricto. |
| `session.use_only_cookies` | Evita identificadores en URL. |
| `session.use_trans_sid` | Controla la propagación transparente del identificador. |

Estas opciones deben establecerse antes de iniciar la sesión.

Ejemplo de configuración nativa:

```php
<?php

declare(strict_types=1);

session_name('SHIELDPHP_SESSION');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
```

Este ejemplo muestra la configuración nativa de PHP y no debe ejecutarse adicionalmente si `SessionManager` ya administra las mismas opciones.

## 11. Ejemplo de autenticación mediante sesiones

El módulo Sessions puede utilizarse junto a Authentication para mantener el estado de un usuario autenticado.

### Flujo conceptual

```text
             SOLICITUD DE LOGIN
                    |
                    v
                AuthManager
                    |
                    v
                AuthResult
                    |
                    v
              ¿Autenticación válida?
                    |
             ┌──────┴──────┐
             |             |
             v             v
            NO             SÍ
             |             |
             v             v
         Rechazar      SessionManager
                           |
                           v
                     Regenerar ID
                           |
                           v
                     Guardar user_id
                           |
                           v
                     Sesión activa
```

### Ejemplo de uso

La siguiente secuencia muestra el uso previsto de las API de autenticación y sesión:

```php
<?php

declare(strict_types=1);

use ShieldPHP\Core\Auth\AuthManager;
use ShieldPHP\Core\Session\SessionManager;

$result = $authManager->attempt(
    $email,
    $password
);

if (!$result->isSuccessful()) {
    throw new RuntimeException(
        'No fue posible autenticar al usuario.'
    );
}

$user = $result->user();

if ($user === null) {
    throw new RuntimeException(
        'No se obtuvo una identidad válida.'
    );
}

// Iniciar sesión.
$sessionManager->start();

// Regenerar el identificador después de autenticar.
$sessionManager->regenerateId();

// Almacenar únicamente la información necesaria.
$sessionManager->set(
    'user_id',
    $user->getId()
);

$sessionManager->set(
    'authenticated',
    true
);
```

Este ejemplo supone las operaciones de `SessionManager` descritas anteriormente. La compatibilidad debe verificarse contra la implementación real del proyecto.

**Recomendación:** evita almacenar hashes de contraseñas, secretos o perfiles completos innecesarios dentro de la sesión.

## 12. Cierre de sesión

El cierre de sesión debe invalidar el estado autenticado de la aplicación.

### Operaciones recomendadas

1. Identificar la sesión actual.
2. Eliminar los datos de autenticación.
3. Destruir la sesión cuando corresponda.
4. Invalidar o eliminar las cookies pertinentes.
5. Revocar refresh tokens u otras credenciales persistentes cuando la aplicación los utilice.

### Ejemplo

```php
// Finalizar la sesión actual.
$sessionManager->destroy();
```

Cuando se utiliza autenticación basada en JWT, destruir la sesión del navegador no invalida automáticamente los tokens que ya fueron emitidos.

La aplicación debe utilizar también los mecanismos de revocación correspondientes.

## 13. Integración con JWT

Las sesiones y los JWT representan modelos diferentes para mantener la autenticación.

| Característica | Sesiones | JWT |
|---|---|---|
| Almacenamiento | Estado mantenido por el servidor. | Declaraciones transportadas en el token. |
| Identificación | Cookie con identificador de sesión. | Token Bearer u otro mecanismo de transporte. |
| Revocación | Mediante invalidación de sesión. | Mediante revocación o expiración. |
| Estado | Generalmente centralizado. | Puede ser autónomo, según su implementación. |
| Aplicaciones habituales | Aplicaciones web. | API y servicios distribuidos. |

No se debe asumir que una sesión y un JWT son intercambiables.

Una aplicación puede utilizar ambos mecanismos, pero debe definir claramente sus responsabilidades y su ciclo de vida.

## 14. Protección CSRF

Las aplicaciones que utilizan cookies de sesión deben implementar controles CSRF para proteger las operaciones sensibles.

Medidas recomendadas:

- Generar tokens CSRF impredecibles.
- Asociarlos con la sesión correspondiente.
- Validarlos en operaciones de modificación.
- Utilizar cookies `SameSite` apropiadas.
- Validar el origen cuando corresponda.
- No modificar datos mediante solicitudes GET.
- Rechazar solicitudes sin el mecanismo de protección requerido.

La configuración prevista contempla:

```dotenv
SHIELDPHP_CSRF_ENABLED=true
SHIELDPHP_CSRF_TOKEN_NAME=_csrf
SHIELDPHP_CSRF_HEADER=X-CSRF-TOKEN
```

La presencia de estas variables no implica que exista un mecanismo CSRF universal completamente integrado en el módulo Sessions.

## 15. Almacenamiento de sesiones

PHP permite utilizar diferentes mecanismos para almacenar el estado de las sesiones.

ShieldPHP debe mantener la posibilidad de utilizar almacenamiento propio mediante los mecanismos de sesiones disponibles en PHP.

Entre las alternativas se encuentran:

| Almacenamiento | Características |
|---|---|
| Archivos | Adecuado para instalaciones sencillas. |
| Redis | Almacenamiento compartido y de acceso rápido. |
| Base de datos | Persistencia mediante registros. |
| Servicio personalizado | Integración con infraestructura propia. |

### Consideraciones

- Proteger los permisos del almacenamiento.
- Evitar que los archivos de sesión sean accesibles públicamente.
- Configurar expiración y limpieza.
- Utilizar almacenamiento compartido cuando existan múltiples servidores.
- Evitar inconsistencias durante escrituras concurrentes.
- No utilizar almacenamiento en memoria local como mecanismo compartido entre servidores.

La integración de adaptadores persistentes específicos todavía debe completarse.

## 16. Manejo de errores

Los problemas relacionados con las sesiones pueden producirse por diferentes motivos:

- No es posible iniciar la sesión.
- Los encabezados HTTP ya fueron enviados.
- La configuración es inválida.
- La sesión ha expirado.
- El identificador no es válido.
- El almacenamiento de sesiones no está disponible.
- No es posible establecer una cookie.

### Recomendaciones

- Evitar exponer identificadores de sesión en los errores.
- No mostrar trazas internas a los usuarios.
- Registrar únicamente información necesaria para diagnóstico.
- Diferenciar problemas de configuración de errores de almacenamiento.
- Evitar continuar con una autenticación si no se puede establecer correctamente el estado de seguridad requerido.

Los errores deben manejarse desde la aplicación y los componentes de excepciones correspondientes, según las implementaciones reales de ShieldPHP.

## 17. Integración con frameworks

ShieldPHP contempla adaptadores para utilizar sus componentes en diferentes entornos PHP.

### Slim Framework

La integración puede utilizarse para construir aplicaciones HTTP protegidas.

Directorio:

```text
slim/
```

La sesión debe gestionarse de forma compatible con el ciclo de vida de las solicitudes PSR y el almacenamiento elegido.

### Laravel

La integración se encuentra en:

```text
integrations/laravel/
```

Laravel dispone de sus propios mecanismos de sesiones. Una integración con ShieldPHP debe evitar iniciar o destruir sesiones de manera duplicada y definir claramente qué componente controla su ciclo de vida.

Ambas integraciones requieren validación antes de considerarse preparadas para producción.

## 18. Buenas prácticas de seguridad

Para utilizar el módulo Sessions correctamente se recomienda:

### Identificadores

- Utilizar identificadores generados por PHP.
- Activar el modo estricto.
- Regenerar el identificador después de autenticar.
- Evitar identificadores de sesión en URL.
- Invalidar sesiones cuando se produzcan eventos de seguridad.

### Cookies

- Activar `Secure` cuando se utiliza HTTPS.
- Activar `HttpOnly`.
- Configurar `SameSite` según el flujo de autenticación.
- Definir una ruta apropiada.
- Restringir el dominio cuando corresponda.
- Utilizar expiraciones adecuadas.

### Datos

- No almacenar contraseñas en las sesiones.
- No guardar secretos innecesarios.
- Almacenar únicamente los datos requeridos.
- Validar los datos recuperados.
- Proteger el almacenamiento de sesiones.

### Operaciones

- Destruir sesiones correctamente.
- Invalidar credenciales cuando corresponda.
- Proteger solicitudes sensibles contra CSRF.
- Evitar confiar únicamente en la presencia de una cookie.
- Comprobar que la identidad y sus privilegios continúen siendo válidos.

## 19. Pruebas

El módulo Sessions dispone de una estructura inicial de pruebas unitarias dentro de:

```text
packages/php-core/tests/Unit/Session/
```

### Archivos principales

| Prueba | Objetivo |
|---|---|
| `SessionConfigTest.php` | Validación de configuración. |
| `SessionManagerTest.php` | Inicio, consulta, limpieza y destrucción de sesiones. |
| `CookieManagerTest.php` | Creación, consulta y eliminación de cookies. |

### Ejecutar todas las pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

### Ejecutar únicamente las pruebas de sesiones

```bash
vendor/bin/phpunit tests/Unit/Session
```

### Análisis estático

```bash
composer analyse
```

Se recomienda probar especialmente:

- Regeneración de identificadores.
- Rechazo de identificadores inválidos.
- Sesiones expiradas.
- Destrucción de sesiones.
- Eliminación de cookies con atributos correctos.
- Errores cuando los encabezados ya fueron enviados.
- Comportamiento con diferentes almacenes de sesiones.
- Concurrencia entre solicitudes.

Las pruebas unitarias deben aislar adecuadamente el estado global de PHP y utilizar almacenamiento de prueba cuando corresponda.

## 20. Estructura del módulo

```text
packages/php-core/
│
├── src/
│   └── Session/
│       ├── SessionConfig.php
│       ├── SessionManager.php
│       └── CookieManager.php
│
└── tests/
    └── Unit/
        ├── Session/
        │   ├── SessionConfigTest.php
        │   ├── SessionManagerTest.php
        │   └── CookieManagerTest.php
        │
        └── Support/
            └── InMemorySessionStore.php
```

La estructura representa la organización prevista y las clases de prueba trabajadas. `InMemorySessionStore` es un manejador de sesiones PHP para pruebas, no debe confundirse con una interfaz de almacenamiento propia del Core.

## 21. Estado actual

| Funcionalidad | Estado |
|---|---|
| SessionConfig | Implementación inicial |
| SessionManager | Base en desarrollo |
| CookieManager | Base en desarrollo |
| Inicio de sesiones | Requiere validación |
| Administración de datos | Requiere validación |
| Regeneración de identificadores | Requiere validación |
| Destrucción de sesiones | Requiere validación |
| Atributos de cookies | Contemplados |
| Modo estricto | Configuración prevista |
| Almacenamiento en memoria para pruebas | Disponible |
| Adaptador Redis | Pendiente |
| Adaptador de base de datos | Pendiente |
| Integración con Authentication | Pendiente de validar |
| Integración con Slim | Pendiente de completar |
| Protección CSRF | Pendiente de integrar |
| Pruebas de integración | Pendientes |

## 22. Consideraciones técnicas

El módulo Sessions debe mantener una separación clara entre la administración de sesiones PHP y la administración de cookies HTTP.

También debe considerar que:

- Una cookie de sesión contiene normalmente un identificador, no todos los datos de la sesión.
- `cookie_lifetime = 0` significa que la cookie es de sesión del navegador, pero no garantiza que los datos almacenados en el servidor nunca expiren.
- La duración real de las sesiones depende también de las políticas de expiración y limpieza del almacenamiento.
- La regeneración del identificador debe realizarse después de autenticar y durante cambios importantes de privilegios.
- Destruir una sesión PHP no revoca automáticamente JWT emitidos con anterioridad.
- `HttpOnly` no sustituye la protección contra XSS.
- `SameSite` no sustituye necesariamente un mecanismo CSRF completo.
- El almacenamiento local en archivos puede no ser suficiente para aplicaciones distribuidas.

---

## 23. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Autorización.
- `docs/configuration.md` — Configuración.
- `docs/encryption.md` — Criptografía.
- `docs/jwt.md` — JSON Web Tokens.
- `docs/oauth2.md` — OAuth 2.0.
- `docs/security.md` — Arquitectura de seguridad.
- `SECURITY.md` — Política de reporte de vulnerabilidades.

### Referencias externas

- [PHP Sessions](https://www.php.net/manual/en/book.session.php)
- [PHP Session Security Management](https://www.php.net/manual/en/session.security.ini.php)
- [PHP Session Configuration](https://www.php.net/manual/en/session.configuration.php)
- [PHP setcookie()](https://www.php.net/manual/en/function.setcookie.php)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [OWASP Cross-Site Request Forgery Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)

---

<p align="center">
  <strong>ShieldPHP Sessions</strong>
  <br>
  Administración de sesiones, cookies y protección de identidad para PHP.
</p>