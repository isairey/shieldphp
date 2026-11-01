# 🛡️ ShieldPHP

<p align="center">
  <img src="https://img.shields.io/badge/SHIELDPHP-PHP%20SECURITY%20TOOLKIT-2563EB?style=for-the-badge&logo=php&logoColor=white" width="220" alt="ShieldPHP">
</p>

<p align="center">
  **Framework de autenticación, autorización y seguridad para aplicaciones PHP.**
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-0.1.0-blue?style=for-the-badge" alt="Version 0.1.0">
  <img src="https://img.shields.io/badge/Architecture-Modular-success?style=for-the-badge" alt="Modular Architecture">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

---

## 📖 Descripción

**ShieldPHP** es una librería modular de seguridad desarrollada en PHP que proporciona herramientas para implementar autenticación, autorización, gestión de sesiones, cifrado, manejo de tokens y autenticación mediante proveedores OAuth 2.0.

Su objetivo es facilitar la implementación de mecanismos de seguridad reutilizables, manteniendo una arquitectura desacoplada, extensible y compatible con los estándares modernos de desarrollo PHP.

ShieldPHP está diseñada para integrarse en diferentes tipos de aplicaciones:

- Aplicaciones web desarrolladas en PHP.
- APIs REST.
- Sistemas empresariales.
- Aplicaciones con frontend Vue.js.
- Sistemas que utilizan autenticación mediante JWT.
- Aplicaciones que requieren inicio de sesión social.

## 🎯 Objetivos del proyecto

- Proporcionar un sistema centralizado de autenticación.
- Implementar mecanismos seguros de almacenamiento de contraseñas.
- Gestionar usuarios, roles y permisos.
- Facilitar la autenticación mediante JWT.
- Incorporar mecanismos de cifrado y firmas digitales.
- Implementar autenticación OAuth 2.0 con protección PKCE.
- Administrar sesiones y cookies de forma segura.
- Ofrecer middleware reutilizable para aplicaciones HTTP.
- Facilitar la validación de entradas y direcciones de redirección.
- Proporcionar pruebas unitarias, de integración y de seguridad.

## ✨ Características principales

### 🔐 Authentication

Sistema de autenticación de usuarios.

- Inicio de sesión mediante correo y contraseña.
- Verificación segura de contraseñas.
- Hashing utilizando las funciones nativas de PHP.
- Políticas configurables de contraseñas.
- Identidad de usuario.
- Validación de cuentas activas.
- Resultados de autenticación uniformes.

### 🔑 JWT Authentication

Gestión de JSON Web Tokens.

- Generación de JWT.
- Validación de firmas.
- Claims personalizados.
- Configuración de issuer y audience.
- Control de expiración.
- Validación de not-before.
- Identificadores únicos de tokens (`jti`).
- Lista de revocación de tokens.
- Access tokens y refresh tokens.

Algoritmos contemplados en la configuración:

- HS256
- HS384
- HS512

### 🌐 OAuth 2.0

Autenticación mediante proveedores externos.

Proveedores implementados:

- Google OAuth 2.0.
- Facebook OAuth 2.0.

Características:

- Construcción de URLs de autorización.
- Validación del parámetro `state`.
- Protección contra ataques de repetición.
- PKCE con SHA-256.
- Intercambio de códigos de autorización.
- Obtención y normalización de perfiles.
- Validación de URI de redirección.

### 🔒 Cryptography

Herramientas criptográficas.

- Cifrado autenticado mediante libsodium.
- Derivación de claves mediante Argon2id.
- Administración de claves.
- Generación de números aleatorios criptográficamente seguros.
- Generación de UUID.
- Firmas digitales Ed25519.

### 👤 Authorization

Control de acceso basado en roles y permisos.

- Administración de roles.
- Administración de permisos.
- Asignación de permisos.
- Revocación de permisos.
- Verificación de roles.
- Verificación de permisos.
- Control de acceso basado en usuarios.

### 🍪 Session Management

Administración de sesiones.

- Creación de sesiones.
- Regeneración de identificadores.
- Destrucción de sesiones.
- Tiempo de expiración.
- Tiempo de inactividad.
- Duración máxima de sesión.
- Administración segura de cookies.
- Configuración de atributos HttpOnly, Secure y SameSite.

### 🛡️ Middleware

Middleware compatible con PSR-15.

- Autenticación mediante JWT.
- Protección CSRF.
- Control de permisos.
- Control de roles.
- Limitación de solicitudes (Rate Limiting).
- Cabeceras HTTP de seguridad.

### ✅ Validation

Validación de datos de entrada.

- Validación de correos electrónicos.
- Validación de cadenas.
- Validación de números.
- Validación de booleanos.
- Validación de URLs.
- Validación de opciones permitidas.
- Validación de URI de redirección.

## 🏗️ Arquitectura del proyecto

ShieldPHP utiliza una arquitectura modular basada en separación de responsabilidades y contratos mediante interfaces.

```text
ShieldPHP/
│
├── packages/
│   │
│   ├── php-core/
│   │   │
│   │   ├── src/
│   │   │   ├── Auth/
│   │   │   ├── Authorization/
│   │   │   ├── Config/
│   │   │   ├── Contracts/
│   │   │   ├── Crypto/
│   │   │   ├── Exceptions/
│   │   │   ├── JWT/
│   │   │   ├── Middleware/
│   │   │   ├── OAuth/
│   │   │   │   └── Providers/
│   │   │   ├── Session/
│   │   │   ├── Validation/
│   │   │   │
│   │   │   ├── Shield.php
│   │   │   └── QuickAuth.php
│   │   │
│   │   ├── tests/
│   │   │   ├── Unit/
│   │   │   ├── Integration/
│   │   │   ├── Security/
│   │   │   └── bootstrap.php
│   │   │
│   │   ├── composer.json
│   │   └── phpunit.xml
│   │
│   └── vue/
│
├── integrations/
│
├── examples/
│
├── docs/
│
├── config/
│
├── README.md
├── LICENSE
└── .gitignore
```

### Organización de los módulos

| Directorio | Responsabilidad |
|---|---|
| `Auth/` | Autenticación y gestión de usuarios. |
| `Authorization/` | Roles, permisos y control de acceso. |
| `Config/` | Configuración y validación de opciones. |
| `Contracts/` | Interfaces y contratos. |
| `Crypto/` | Cifrado, firmas y administración de claves. |
| `Exceptions/` | Excepciones del sistema. |
| `JWT/` | Tokens de acceso y revocación. |
| `Middleware/` | Protección de solicitudes HTTP. |
| `OAuth/` | Integración con proveedores externos. |
| `Session/` | Administración de sesiones y cookies. |
| `Validation/` | Validación de datos. |

## ⚙️ Requisitos

| Tecnología | Requisito |
|---|---|
| PHP | 8.2 o superior |
| Composer | Administrador de dependencias |
| PHPUnit | Pruebas automatizadas |
| libsodium | Operaciones criptográficas |
| Firebase JWT | Manejo de JWT |
| PSR | Interfaces HTTP estandarizadas |

Dependencias principales utilizadas:

- `firebase/php-jwt`
- `psr/http-client`
- `psr/http-factory`
- `psr/http-message`
- `psr/http-server-middleware`
- `psr/http-server-handler`
- `guzzlehttp/guzzle`
- `guzzlehttp/psr7`

## 🚀 Instalación

Clona el repositorio:

```bash
git clone https://github.com/isairey/shieldphp.git
```

Entra al núcleo PHP:

```bash
cd ShieldPHP/packages/php-core
```

Instala las dependencias:

```bash
composer install
```

Comprueba la versión de PHP:

```bash
php -v
```

Verifica las dependencias:

```bash
composer validate
```

> Sustituye `<REPOSITORY_URL>` por la dirección real del repositorio.

## 💻 Uso básico

ShieldPHP dispone de una clase principal para centralizar la creación de sus componentes.

### Inicialización

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ShieldPHP\Core\Shield;

$shield = new Shield();

echo 'ShieldPHP version: ' . $shield->version();
```

### Hashing de contraseñas

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ShieldPHP\Core\Shield;

$shield = new Shield();

$passwordHasher = $shield->createPasswordHasher();

$password = 'MySecurePassword123!';

// Generar hash
$hash = $passwordHasher->hash($password);

// Verificar contraseña
$isValid = $passwordHasher->verify(
    $password,
    $hash
);

if ($isValid) {
    echo 'Password verified successfully.';
}
```

### Política de contraseñas

```php
<?php

use ShieldPHP\Core\Auth\PasswordPolicy;

$passwordPolicy = new PasswordPolicy(
    12,
    1024
);

$password = 'MySecurePassword123!';

if ($passwordPolicy->isValid($password)) {
    echo 'Valid password.';
} else {
    echo 'Invalid password.';
}
```

### Gestión de roles y permisos

```php
<?php

use ShieldPHP\Core\Auth\UserIdentity;
use ShieldPHP\Core\Authorization\Permission;
use ShieldPHP\Core\Authorization\Role;

$permission = new Permission(
    'users.read',
    'Read users'
);

$role = new Role(
    'admin',
    'Administrator'
);

$role->grantPermission($permission);

if ($role->hasPermission('users.read')) {
    echo 'Access granted.';
}
```

> Los ejemplos muestran el uso de componentes individuales. La integración completa de autenticación con base de datos requiere implementar `UserProviderInterface` y conectar los almacenes persistentes correspondientes.

## 🔧 Configuración

ShieldPHP permite configurar el comportamiento general de la librería.

```php
<?php

use ShieldPHP\Core\Shield;

$shield = new Shield([
    'app_name' => 'My Application',
    'access_token_ttl' => 900,
    'refresh_token_ttl' => 604800,
    'secure_cookies' => true,
    'same_site' => 'Lax',
]);
```

| Parámetro | Descripción |
|---|---|
| `app_name` | Nombre de la aplicación. |
| `access_token_ttl` | Tiempo de vida del access token en segundos. |
| `refresh_token_ttl` | Tiempo de vida del refresh token en segundos. |
| `secure_cookies` | Configura el atributo Secure de las cookies. |
| `same_site` | Política SameSite de las cookies. |

Los secretos, claves de cifrado y credenciales OAuth deben suministrarse mediante variables de entorno o mecanismos seguros de configuración. No deben incluirse directamente en el repositorio.

## 🧪 Testing

ShieldPHP utiliza PHPUnit para realizar pruebas automatizadas.

Las pruebas se organizan en tres niveles:

### Unit Tests

Verifican el comportamiento individual de las clases.

```bash
vendor/bin/phpunit tests/Unit
```

### Integration Tests

Verifican la interacción entre diferentes componentes.

```bash
vendor/bin/phpunit tests/Integration
```

### Security Tests

Verifican el comportamiento de los mecanismos de seguridad frente a entradas maliciosas y situaciones adversas.

```bash
vendor/bin/phpunit tests/Security
```

### Suite completa

```bash
composer test
```

### Análisis estático

```bash
composer analyse
```

## 🔐 Consideraciones de seguridad

ShieldPHP busca incorporar buenas prácticas de seguridad desde su arquitectura.

Entre sus mecanismos se encuentran:

- Hashing de contraseñas con funciones nativas de PHP.
- Cifrado autenticado con libsodium.
- Derivación de claves mediante Argon2id.
- Firmas JWT verificadas mediante algoritmos permitidos.
- Validación de issuer, audience y expiración.
- Protección OAuth mediante `state` y PKCE.
- Validación estricta de URI de redirección.
- Protección contra repetición de estados OAuth.
- Cookies con atributos de seguridad.
- Middleware de autenticación, CSRF y control de acceso.

**Estado de seguridad:** ShieldPHP se encuentra en desarrollo. Las implementaciones en memoria son auxiliares y no deben utilizarse como almacenamiento de producción. Antes de desplegar una aplicación, es necesario configurar almacenes persistentes y compartidos, revisar los flujos completos de autenticación, implementar la rotación de claves y someter el sistema a una auditoría de seguridad.

No se debe considerar que la existencia de pruebas automatizadas equivale a una auditoría o certificación de seguridad.

## 🗺️ Roadmap

| Funcionalidad | Estado |
|---|---|
| Core PHP | En desarrollo |
| Autenticación | Implementado inicialmente |
| Password Hashing | Implementado inicialmente |
| JWT | Implementado inicialmente |
| OAuth Google | Implementado inicialmente |
| OAuth Facebook | Implementado inicialmente |
| PKCE | Implementado inicialmente |
| Sesiones | Implementado inicialmente |
| Roles y permisos | Implementado inicialmente |
| Middleware PSR-15 | Implementado inicialmente |
| Validación | Implementado inicialmente |
| Pruebas automatizadas | En desarrollo |
| Vue SDK | En desarrollo |
| Adaptadores de bases de datos | Pendiente |
| Documentación completa | En desarrollo |
| Auditoría de seguridad | Pendiente |
| Release estable 1.0.0 | Pendiente |

## 🤝 Contribuciones

Las contribuciones son bienvenidas.

Para colaborar:

1. Haz un fork del repositorio.
2. Crea una rama para tu funcionalidad.
3. Implementa los cambios siguiendo la arquitectura existente.
4. Añade las pruebas correspondientes.
5. Ejecuta la suite de pruebas.
6. Envía un Pull Request con una descripción de los cambios.

Las contribuciones deben mantener la compatibilidad con PHP 8.2 o superior y respetar los estándares de código del proyecto.

## 📄 Licencia

ShieldPHP se distribuye bajo la licencia MIT.

Consulta el archivo `LICENSE` para conocer los términos completos de uso y distribución.

## 👨‍💻 Autor

**ShieldPHP Development**

Proyecto independiente enfocado en el desarrollo de herramientas de autenticación, autorización y seguridad para el ecosistema PHP.

<p align="center">
  **ShieldPHP**
  <br>
  <i>Security. Simplicity. Control.</i>
</p>
