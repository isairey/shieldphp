# Authentication — ShieldPHP

<p align="center">
  <strong>Sistema de autenticación y gestión de credenciales para PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Security-Authentication-blue?style=for-the-badge" alt="Authentication">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

El módulo **Authentication** de ShieldPHP proporciona los componentes fundamentales para implementar procesos de autenticación de usuarios en aplicaciones PHP.

Su objetivo es centralizar la verificación de credenciales, la administración de identidades y las políticas de contraseñas mediante componentes reutilizables y desacoplados.

El módulo permite:

- Autenticar usuarios mediante correo electrónico y contraseña.
- Verificar contraseñas almacenadas mediante hashes seguros.
- Aplicar políticas de seguridad para contraseñas.
- Representar usuarios autenticados mediante identidades.
- Obtener resultados estructurados de autenticación.
- Reducir el riesgo de enumeración de usuarios mediante respuestas genéricas.

La autenticación constituye la primera etapa de protección de una aplicación. La autorización y los permisos deben gestionarse mediante los componentes correspondientes de ShieldPHP.

## 2. Arquitectura

El módulo está organizado en componentes independientes que colaboran durante el proceso de autenticación.

```text
                  CLIENTE / APLICACIÓN
                           |
                           |
                    Credenciales
                           |
                           v
                ┌─────────────────────┐
                │     AuthManager     │
                │                     │
                │   attempt()         │
                └──────────┬──────────┘
                           |
                 ┌─────────┴─────────┐
                 |                   |
                 v                   v
        ┌────────────────┐  ┌────────────────┐
        │ UserProvider   │  │ PasswordHasher │
        │                │  │                │
        │ Busca usuario  │  │ Verifica hash  │
        └────────┬───────┘  └────────┬───────┘
                 |                   |
                 └─────────┬─────────┘
                           |
                           v
                 ┌─────────────────────┐
                 │     AuthResult      │
                 │                     │
                 │ Resultado del login │
                 └──────────┬──────────┘
                            |
                   ┌────────┴────────┐
                   |                 |
                   v                 v
                Éxito              Fallo
                   |                 |
                   v                 v
             UserIdentity       Respuesta genérica
```

## 3. Componentes principales

El módulo Authentication contempla los siguientes componentes:

| Componente | Responsabilidad |
|---|---|
| `AuthManager` | Coordina el proceso de autenticación. |
| `AuthResult` | Representa el resultado de la autenticación. |
| `PasswordHasher` | Genera y verifica hashes de contraseñas. |
| `PasswordPolicy` | Define y valida requisitos de contraseñas. |
| `UserIdentity` | Representa la identidad de un usuario. |
| `UserProviderInterface` | Abstrae el acceso a los usuarios. |

Namespace principal:

```php
ShieldPHP\Core\Auth\
```

Los contratos se encuentran bajo:

```php
ShieldPHP\Core\Contracts\
```

## 4. AuthManager

`AuthManager` es el componente principal del sistema de autenticación.

Su responsabilidad es coordinar la búsqueda del usuario y la verificación de sus credenciales mediante las dependencias correspondientes.

### Constructor

```php
public function __construct(
    UserProviderInterface $userProvider,
    PasswordHasher $passwordHasher
);
```

**Parámetros:**

| Parámetro | Tipo | Descripción |
|---|---|---|
| `$userProvider` | UserProviderInterface | Proveedor encargado de obtener usuarios. |
| `$passwordHasher` | PasswordHasher | Componente de verificación de contraseñas. |

### Método attempt()

```php
public function attempt(
    string $email,
    string $password
): AuthResult;
```

Intenta autenticar al usuario utilizando sus credenciales.

**Parámetros:**

- `$email`: correo electrónico proporcionado.
- `$password`: contraseña proporcionada.

**Retorno:**

Un objeto `AuthResult` que representa el resultado del proceso.

### Comportamiento

El proceso contempla:

1. Normalización del correo electrónico.
2. Consulta de la identidad mediante el proveedor de usuarios.
3. Verificación de la contraseña.
4. Comprobación del estado activo de la cuenta.
5. Generación de un resultado de autenticación.

Cuando las credenciales no son válidas o la cuenta se encuentra inactiva, el proceso devuelve un resultado fallido genérico.

También contempla una verificación de contraseña ficticia cuando no se encuentra el usuario, como medida para reducir diferencias temporales que puedan facilitar la enumeración de cuentas.

### Ejemplo de utilización

```php
<?php

declare(strict_types=1);

use ShieldPHP\Core\Auth\AuthManager;
use ShieldPHP\Core\Auth\PasswordHasher;

// El proveedor debe implementar UserProviderInterface.
$userProvider = $container->get(UserProviderInterface::class);

$passwordHasher = new PasswordHasher();

$authManager = new AuthManager(
    $userProvider,
    $passwordHasher
);

$result = $authManager->attempt(
    'usuario@example.com',
    'MiPasswordSeguro2026!'
);

if ($result->isSuccessful()) {
    $user = $result->user();

    echo 'Autenticación exitosa';

    echo $user->getEmail();
} else {
    echo 'Credenciales incorrectas';
}
```

En una aplicación real, el proveedor de usuarios debe estar configurado mediante inyección de dependencias y obtener las identidades desde una fuente confiable.

## 5. AuthResult

`AuthResult` representa el resultado de una operación de autenticación.

Permite separar el resultado del proceso de autenticación de la lógica de presentación o respuesta HTTP de la aplicación.

### Métodos disponibles

```php
public static function success(UserIdentity $user): self;

public static function failure(): self;

public function isSuccessful(): bool;

public function user(): ?UserIdentity;
```

### Descripción

| Método | Descripción |
|---|---|
| `success()` | Construye un resultado satisfactorio. |
| `failure()` | Construye un resultado fallido. |
| `isSuccessful()` | Indica si la autenticación fue exitosa. |
| `user()` | Obtiene la identidad autenticada o `null`. |

### Ejemplo

```php
$result = $authManager->attempt(
    'usuario@example.com',
    'MiPasswordSeguro2026!'
);

if (!$result->isSuccessful()) {
    // No se revelan detalles sobre el motivo del rechazo.
    throw new RuntimeException(
        'No fue posible autenticar al usuario.'
    );
}

$user = $result->user();

if ($user === null) {
    throw new RuntimeException(
        'No se obtuvo una identidad autenticada.'
    );
}

echo 'Usuario: ' . $user->getEmail();
```

## 6. PasswordHasher

`PasswordHasher` administra las operaciones de hash y verificación de contraseñas.

Utiliza las funciones nativas de PHP y el algoritmo `PASSWORD_DEFAULT`.

### Métodos

```php
public function hash(string $password): string;

public function verify(
    string $password,
    string $hash
): bool;

public function needsRehash(string $hash): bool;
```

### hash()

Genera un hash seguro de una contraseña.

```php
$passwordHasher->hash(
    'MiPasswordSeguro2026!'
);
```

**Retorno:** cadena que contiene el hash generado.

Las contraseñas nunca deben almacenarse directamente en texto plano.

### verify()

Verifica una contraseña contra un hash previamente generado.

```php
$isValid = $passwordHasher->verify(
    'MiPasswordSeguro2026!',
    $storedHash
);

if ($isValid) {
    echo 'Contraseña correcta';
}
```

La comparación se realiza mediante las funciones seguras de verificación de contraseñas de PHP.

### needsRehash()

Determina si un hash necesita actualizarse conforme a la configuración actual del algoritmo.

```php
if ($passwordHasher->needsRehash($storedHash)) {
    $newHash = $passwordHasher->hash($plainPassword);

    // Actualizar el hash mediante el repositorio de usuarios.
}
```

Esto permite actualizar gradualmente los hashes cuando cambien los algoritmos o parámetros predeterminados.

### Límites de entrada

La implementación de `PasswordHasher` contempla un límite de entrada de 4096 bytes.

Este límite es distinto de la longitud máxima en caracteres definida por `PasswordPolicy`.

## 7. PasswordPolicy

`PasswordPolicy` establece los requisitos mínimos que deben cumplir las contraseñas de una aplicación.

Su objetivo es evitar contraseñas demasiado cortas o que incumplan las reglas de seguridad configuradas.

### Valores predeterminados

| Parámetro | Valor |
|---|---:|
| Longitud mínima | 12 caracteres |
| Longitud máxima | 1024 caracteres |

### Métodos

```php
public function validate(string $password): array;

public function isValid(string $password): bool;

public function assertValid(string $password): void;
```

### validate()

Evalúa una contraseña y devuelve los errores de validación encontrados.

```php
$errors = $passwordPolicy->validate(
    'contraseña'
);
```

### isValid()

Comprueba si una contraseña cumple las reglas establecidas.

```php
if ($passwordPolicy->isValid($password)) {
    echo 'La contraseña cumple la política';
}
```

### assertValid()

Exige que la contraseña cumpla las políticas definidas y comunica el incumplimiento mediante una excepción.

```php
$passwordPolicy->assertValid($password);
```

### Recomendaciones

Una política de contraseñas debe considerar:

- Una longitud mínima adecuada.
- Una longitud máxima compatible con el mecanismo de hash.
- Compatibilidad con frases de contraseña.
- Protección contra contraseñas conocidas como comprometidas.
- Limitación de intentos de autenticación.
- Procesamiento correcto de caracteres Unicode.

No es recomendable imponer reglas de composición excesivamente restrictivas sin una justificación de seguridad.

## 8. UserIdentity

`UserIdentity` representa a un usuario autenticado dentro de ShieldPHP.

Permite consultar su identificador, correo electrónico, estado y roles.

### Métodos

```php
public function getId(): string;

public function getEmail(): string;

public function getPasswordHash(): string;

public function isActive(): bool;

public function getRoles(): array;

public function hasRole(string $role): bool;
```

### Descripción

| Método | Descripción |
|---|---|
| `getId()` | Obtiene el identificador del usuario. |
| `getEmail()` | Obtiene el correo electrónico. |
| `getPasswordHash()` | Obtiene el hash de contraseña. |
| `isActive()` | Consulta el estado de la cuenta. |
| `getRoles()` | Obtiene los roles asociados. |
| `hasRole()` | Comprueba si tiene un rol determinado. |

### Ejemplo

```php
$user = $result->user();

if ($user !== null) {

    $id = $user->getId();

    $email = $user->getEmail();

    $active = $user->isActive();

    $roles = $user->getRoles();

    if ($user->hasRole('admin')) {
        echo 'Usuario administrador';
    }
}
```

**Importante:** una identidad autenticada no significa automáticamente que el usuario tenga autorización para todas las operaciones.

Los permisos deben comprobarse mediante el módulo Authorization.

## 9. UserProviderInterface

`UserProviderInterface` representa el contrato que permite desacoplar la autenticación del sistema de almacenamiento de usuarios.

Su responsabilidad es proporcionar las identidades que necesita `AuthManager`.

Esto permite que una aplicación utilice diferentes fuentes de usuarios, por ejemplo:

- MySQL.
- PostgreSQL.
- SQLite.
- MongoDB.
- Servicios externos.
- Sistemas de identidad propios.

La implementación concreta debe respetar los métodos y tipos definidos en el contrato real de ShieldPHP.

La autenticación no debe depender directamente de una base de datos específica.

## 10. Flujo de autenticación

El flujo habitual de autenticación sigue esta secuencia:

### Paso 1. Recepción de credenciales

La aplicación recibe el correo electrónico y la contraseña del usuario.

```php
$email = $request->getParsedBody()['email'] ?? '';

$password = $request->getParsedBody()['password'] ?? '';
```

Los datos deben validarse antes de procesarlos.

### Paso 2. Ejecución de AuthManager

```php
$result = $authManager->attempt(
    $email,
    $password
);
```

### Paso 3. Evaluación del resultado

```php
if (!$result->isSuccessful()) {
    // Respuesta genérica de autenticación fallida.
}
```

### Paso 4. Recuperación de identidad

```php
$user = $result->user();
```

### Paso 5. Continuación del proceso

Después de una autenticación satisfactoria, la aplicación puede iniciar una sesión o emitir un token mediante el módulo JWT.

La emisión de tokens y la persistencia de sesiones son responsabilidades de componentes independientes.

## 11. Integración con JWT

El módulo Authentication puede utilizarse junto al módulo JWT para implementar autenticación basada en tokens.

La separación de responsabilidades es la siguiente:

| Módulo | Responsabilidad |
|---|---|
| Auth | Verificar las credenciales. |
| UserIdentity | Representar al usuario autenticado. |
| JWT | Emitir y validar tokens. |
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
JWTManager
   |
   v
Access Token
```

La implementación de emisión de tokens debe utilizar las declaraciones, configuración y mecanismos de firma definidos por el módulo JWT.

## 12. Integración con aplicaciones HTTP

ShieldPHP puede integrarse con distintos frameworks PHP.

Entre las integraciones contempladas se encuentran:

### Slim Framework

La integración Slim utiliza `QuickAuthMiddleware` para proteger rutas mediante JWT.

Directorio:

```text
slim/
```

### Laravel

La integración con Laravel contempla el registro de servicios y middleware específicos.

Directorio:

```text
integrations/laravel/
```

Ambas integraciones se encuentran en desarrollo y requieren completar sus adaptadores antes de considerarse listas para producción.

## 13. Manejo de errores

Los errores de autenticación deben manejarse sin revelar información sensible.

ShieldPHP contempla respuestas genéricas ante situaciones como:

- Usuario inexistente.
- Contraseña incorrecta.
- Cuenta inactiva.

La aplicación debe evitar mensajes que permitan distinguir entre estos escenarios.

### Ejemplo de respuesta HTTP

```json
{
    "success": false,
    "error": "invalid_credentials",
    "message": "No fue posible autenticar al usuario."
}
```

Esta es una respuesta de referencia para aplicaciones HTTP; no representa necesariamente una respuesta generada directamente por `AuthManager`.

## 14. Buenas prácticas de seguridad

Para utilizar correctamente el módulo Authentication se recomienda:

### Contraseñas

- Utilizar `PasswordHasher` para generar y verificar hashes.
- No almacenar contraseñas en texto plano.
- No utilizar cifrado reversible para almacenar contraseñas.
- Aplicar políticas de contraseñas apropiadas.
- Reemplazar hashes antiguos mediante `needsRehash()`.

### Credenciales

- Validar las entradas recibidas.
- Utilizar respuestas genéricas ante fallos.
- No registrar contraseñas ni hashes en logs.
- Proteger los endpoints de autenticación con limitación de solicitudes.
- Utilizar HTTPS.

### Cuentas

- Comprobar el estado de las cuentas.
- Implementar verificación de correo cuando corresponda.
- Incorporar mecanismos de recuperación de contraseña.
- Registrar eventos relevantes de seguridad.
- Evitar bloqueos permanentes que puedan facilitar ataques de denegación de servicio.

### Tokens y sesiones

- Emitir tokens únicamente después de una autenticación satisfactoria.
- Configurar expiraciones adecuadas.
- Utilizar mecanismos seguros de almacenamiento.
- Revocar credenciales cuando exista un compromiso de seguridad.
- Separar la autenticación de los permisos de acceso.

## 15. Pruebas

El módulo Authentication dispone de una estructura de pruebas unitarias dentro de:

```text
packages/php-core/tests/Unit/Auth/
```

Los componentes principales cuentan con pruebas destinadas a comprobar:

| Componente | Cobertura prevista |
|---|---|
| AuthManager | Credenciales válidas, inválidas y cuentas inactivas. |
| AuthResult | Resultados satisfactorios y fallidos. |
| PasswordHasher | Generación, verificación y actualización de hashes. |
| PasswordPolicy | Longitud y cumplimiento de requisitos. |
| UserIdentity | Identidad, estado y roles. |

### Ejecutar las pruebas

Desde la raíz del paquete Core:

```bash
cd packages/php-core

composer test
```

Para ejecutar exclusivamente las pruebas de autenticación:

```bash
vendor/bin/phpunit tests/Unit/Auth
```

### Calidad del código

```bash
composer analyse
```

Las pruebas deben ejecutarse contra las implementaciones reales y mantenerse actualizadas cuando cambie el comportamiento de cualquiera de los componentes.

## 16. Estructura del módulo

```text
packages/php-core/
│
├── src/
│   ├── Auth/
│   │   ├── AuthManager.php
│   │   ├── AuthResult.php
│   │   ├── PasswordHasher.php
│   │   ├── PasswordPolicy.php
│   │   └── UserIdentity.php
│   │
│   └── Contracts/
│       └── UserProviderInterface.php
│
└── tests/
    └── Unit/
        └── Auth/
            ├── AuthManagerTest.php
            ├── AuthResultTest.php
            ├── LoginServiceTest.php
            ├── LogoutServiceTest.php
            ├── PasswordHasherTest.php
            ├── PasswordPolicyTest.php
            └── UserIdentityTest.php
```

La estructura muestra los componentes principales documentados y no implica que todos los procesos de autenticación complementarios estén completamente implementados.

## 17. Funcionalidades y estado actual

| Funcionalidad | Estado |
|---|---|
| AuthManager | Base implementada |
| AuthResult | Base implementada |
| PasswordHasher | Implementación inicial |
| PasswordPolicy | Implementación inicial |
| UserIdentity | Implementación inicial |
| UserProviderInterface | Contrato previsto |
| Autenticación mediante credenciales | Base implementada |
| Protección contra enumeración | Contemplada |
| Emisión de JWT | Gestionada por módulo independiente |
| Recuperación de contraseñas | Pendiente de integración |
| Autenticación multifactor | Pendiente |
| Verificación de correo | Pendiente de integración |
| Limitación de intentos | Requiere integración con middleware o adaptador |

## 18. Consideraciones de desarrollo

El módulo Authentication está diseñado para ser independiente de los mecanismos concretos de almacenamiento y transporte.

Esto permite integrarlo en aplicaciones tradicionales, API REST y sistemas basados en diferentes frameworks PHP.

Las funcionalidades adicionales, como recuperación de contraseñas, autenticación multifactor y verificación de correo, deben incorporarse mediante componentes especializados y no deben considerarse automáticamente disponibles por utilizar `AuthManager`.

---

## 19. Recursos relacionados

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authorization.md` — Gestión de roles y permisos.
- `docs/jwt.md` — Administración de tokens.
- `docs/configuration.md` — Configuración de ShieldPHP.
- `SECURITY.md` — Política de seguridad.
- `CHANGELOG.md` — Registro de cambios.

### Referencias externas

- [PHP Password Hashing](https://www.php.net/manual/en/book.password.php)
- [PHP password_hash()](https://www.php.net/manual/en/function.password-hash.php)
- [PHP password_verify()](https://www.php.net/manual/en/function.password-verify.php)
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)

---

<p align="center">
  <strong>ShieldPHP Authentication</strong>
  <br>
  Identidad, credenciales y autenticación segura para PHP.
</p>