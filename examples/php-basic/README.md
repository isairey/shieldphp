 
# ShieldPHP — Basic PHP Example

<p align="center">
  <strong>Una implementación práctica de autenticación segura con ShieldPHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/ShieldPHP-Authentication-3457D5?style=for-the-badge" alt="ShieldPHP">
  <img src="https://img.shields.io/badge/Architecture-MVC-64748B?style=for-the-badge" alt="Architecture">
  <img src="https://img.shields.io/badge/API-REST-059669?style=for-the-badge" alt="REST API">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Security-CSRF%20Protection-16A34A?style=for-the-badge" alt="CSRF Protection">
  <img src="https://img.shields.io/badge/Sessions-HttpOnly-orange?style=for-the-badge" alt="Secure Sessions">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="MIT">
</p>

---

## Overview

**ShieldPHP Basic** es una aplicación de ejemplo desarrollada en PHP que demuestra cómo integrar ShieldPHP en una aplicación web tradicional.

Su objetivo es proporcionar una implementación sencilla y funcional para comprender cómo utilizar los servicios de autenticación, gestionar sesiones, proteger formularios y construir endpoints REST.

Este ejemplo permite explorar la integración de ShieldPHP sin necesidad de desarrollar desde cero toda la infraestructura de autenticación.

### Características principales

- Autenticación mediante `AuthManager`.
- Inicio y cierre de sesión.
- Gestión de sesiones PHP.
- Protección contra CSRF.
- Regeneración del identificador de sesión.
- Cookies HttpOnly y SameSite.
- Validación de entradas.
- Respuestas JSON.
- API REST para operaciones de autenticación.
- Cabeceras HTTP de seguridad.
- Mensajes de error sin exposición de información sensible.

## Preview

La interfaz proporciona una pantalla de inicio de sesión con un diseño minimalista y una vista de usuario autenticado.

La aplicación contempla dos estados principales:

| Estado | Funcionalidad |
|---|---|
| Invitado | Formulario de autenticación. |
| Autenticado | Información del usuario y opción de cerrar sesión. |

## Tech Stack

| Tecnología | Uso |
|---|---|
| PHP 8.2+ | Lenguaje principal. |
| ShieldPHP Core | Motor de autenticación. |
| Composer | Dependencias y autoload. |
| PHP Sessions | Persistencia de autenticación. |
| JSON | Comunicación con la API. |
| HTML5 | Estructura de interfaz. |
| CSS3 | Diseño visual. |
| PHPUnit | Pruebas automatizadas del paquete. |

## Project Structure

```text
php-basic/
│
├── bootstrap.php
│
├── public/
│   ├── index.php
│   └── api.php
│
├── routes/
│   └── api.php
│
└── README.md
```

### Directory description

**bootstrap.php**

Inicializa la aplicación y prepara sus dependencias. Debe devolver un arreglo con los servicios necesarios para ejecutar el ejemplo.

**public/index.php**

Controlador principal de la aplicación web. Gestiona el formulario de autenticación, las sesiones y la presentación de la interfaz.

**public/api.php**

Punto de entrada HTTP para las peticiones REST.

**routes/api.php**

Define los endpoints para autenticación, consulta de usuario, CSRF y cierre de sesión.

## Requirements

Antes de ejecutar el proyecto, necesitas:

- PHP 8.2 o superior.
- Composer 2.x.
- ShieldPHP Core instalado.
- Extensiones PHP:
  - `json`
  - `openssl`
  - `sodium`
  - `filter`

Comprueba tu instalación:

```bash
php -v
composer --version
php -m
```

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/isairey/ShieldPHP.git
cd ShieldPHP
```

> Sustituye la URL por la ubicación real de tu repositorio si utilizas otro nombre o dirección.

### 2. Install dependencies

Desde la raíz del proyecto:

```bash
composer install
```

Si el paquete Core tiene su propio `composer.json`, instala también sus dependencias:

```bash
cd packages/php-core
composer install
```

### 3. Configure the application

Asegúrate de tener la configuración de ShieldPHP y las variables de entorno necesarias.

El ejemplo de configuración se encuentra en:

```text
config/quickauth.example
```

Crea una copia local:

```bash
cp config/quickauth.example config/quickauth.php
```

En Windows PowerShell:

```powershell
Copy-Item config/quickauth.example config/quickauth.php
```

No subas secretos ni archivos `.env` con credenciales reales al repositorio.

### 4. Configure bootstrap.php

El archivo `bootstrap.php` debe construir las dependencias de ShieldPHP y devolver el servicio de autenticación.

Contrato esperado:

```php
<?php

declare(strict_types=1);

use ShieldPHP\Core\Auth\AuthManager;

return [
    'auth_manager' => $authManager,
    'logout_service' => $logoutService ?? null,
];
```

Antes de utilizar este ejemplo, `$authManager` debe estar correctamente inicializado con:

- Un `UserProviderInterface`.
- Una instancia de `PasswordHasher`.
- Los repositorios o servicios necesarios para consultar usuarios.

El servicio de autenticación debe utilizar usuarios de prueba o un almacenamiento configurado específicamente para desarrollo.

## Running the application

Desde la raíz del repositorio:

```bash
php -S localhost:8000 -t examples/php-basic/public
```

Si `examples` está dentro de `shieldphp/`, utiliza:

```bash
php -S localhost:8000 -t shieldphp/examples/php-basic/public
```

Abre:

```text
http://localhost:8000
```

## Authentication Flow

El proceso de autenticación sigue esta secuencia:

```text
              USER
                |
                v
       Login Form (index.php)
                |
                v
         Validate Inputs
                |
                v
         Verify CSRF Token
                |
                v
        AuthManager::attempt()
                |
          +-----+-----+
          |           |
          v           v
       SUCCESS      FAILURE
          |           |
          v           v
    Regenerate ID   Generic Error
          |
          v
    Store User ID
          |
          v
    Authenticated
          |
          v
     Protected UI
```

### Login

El usuario proporciona su correo electrónico y contraseña.

ShieldPHP verifica las credenciales mediante `AuthManager`.

Cuando la autenticación es exitosa:

1. Se regenera el identificador de sesión.
2. Se almacena el identificador del usuario.
3. Se guarda el correo electrónico.
4. Se registra la fecha de autenticación.
5. Se renueva el token CSRF.

### Logout

El cierre de sesión elimina los datos de autenticación y destruye la sesión PHP.

Si se utilizan JWT, su revocación debe integrarse mediante el almacenamiento correspondiente.

## REST API

ShieldPHP Basic incluye endpoints REST para administrar la autenticación.

### Available endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api.php/api/health` | Estado de la API. |
| GET | `/api.php/api/auth/csrf` | Obtener token CSRF. |
| GET | `/api.php/api/auth/me` | Consultar usuario actual. |
| POST | `/api.php/api/auth/login` | Iniciar sesión. |
| POST | `/api.php/api/auth/logout` | Cerrar sesión. |

### Health Check

Request:

```http
GET /api.php/api/health
```

Response:

```json
{
  "success": true,
  "application": "ShieldPHP",
  "example": "php-basic",
  "status": "healthy"
}
```

### Get CSRF Token

Request:

```http
GET /api.php/api/auth/csrf
```

Response:

```json
{
  "success": true,
  "data": {
    "csrf_token": "generated-csrf-token"
  }
}
```

### Get Authenticated User

Request:

```http
GET /api.php/api/auth/me
```

Response cuando existe una sesión autenticada:

```json
{
  "success": true,
  "authenticated": true,
  "data": {
    "id": "user-123",
    "email": "user@example.com",
    "authenticated_at": 1790000000
  }
}
```

### Login

Request:

```http
POST /api.php/api/auth/login
Content-Type: application/json
X-CSRF-Token: generated-csrf-token
```

Body:

```json
{
  "email": "user@example.com",
  "password": "your-password"
}
```

Response:

```json
{
  "success": true,
  "message": "Authentication successful.",
  "authenticated": true,
  "data": {
    "id": "user-123",
    "email": "user@example.com"
  },
  "csrf_token": "new-csrf-token"
}
```

### Logout

Request:

```http
POST /api.php/api/auth/logout
Content-Type: application/json
X-CSRF-Token: generated-csrf-token
```

Body:

```json
{}
```

Response:

```json
{
  "success": true,
  "message": "Session closed successfully.",
  "authenticated": false
}
```

## JavaScript Integration

Example de consumo de la API desde JavaScript:

```javascript
const API_BASE = '/api.php/api';

async function getCsrfToken() {
    const response = await fetch(
        `${API_BASE}/auth/csrf`,
        {
            credentials: 'same-origin'
        }
    );

    if (!response.ok) {
        throw new Error('Unable to obtain CSRF token.');
    }

    const result = await response.json();

    return result.data.csrf_token;
}

async function login(email, password) {
    const csrfToken = await getCsrfToken();

    const response = await fetch(
        `${API_BASE}/auth/login`,
        {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                email,
                password
            })
        }
    );

    const result = await response.json();

    if (!response.ok) {
        throw new Error(
            result.message || 'Authentication failed.'
        );
    }

    return result;
}

async function getCurrentUser() {
    const response = await fetch(
        `${API_BASE}/auth/me`,
        {
            credentials: 'same-origin'
        }
    );

    return response.json();
}

async function logout(csrfToken) {
    const response = await fetch(
        `${API_BASE}/auth/logout`,
        {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({})
        }
    );

    return response.json();
}
```

## Security Features

ShieldPHP Basic incorpora diferentes mecanismos de protección:

| Security mechanism | Implementation |
|---|---|
| CSRF Protection | Tokens aleatorios y comparación segura. |
| Session Fixation | Regeneración del identificador de sesión. |
| HttpOnly Cookies | Evita el acceso desde JavaScript. |
| SameSite Cookies | Protección adicional contra solicitudes cross-site. |
| Strict Session Mode | Rechazo de identificadores de sesión no inicializados. |
| Input Validation | Validación de credenciales y solicitudes. |
| Output Encoding | Escape de contenido HTML. |
| Cache Control | Evita almacenar respuestas sensibles en caché. |
| Security Headers | CSP, X-Frame-Options y otros encabezados. |

### Important Security Notes

- Utiliza HTTPS en producción.
- Mantén `display_errors` deshabilitado en producción.
- No registres contraseñas, tokens ni secretos en logs.
- No almacenes contraseñas en sesiones.
- Utiliza un almacenamiento persistente para sesiones en aplicaciones distribuidas.
- Mantén las dependencias actualizadas.
- Configura límites de intentos de autenticación para reducir ataques de fuerza bruta.
- No confíes únicamente en los datos guardados en una sesión para autorizar operaciones sensibles.

## Testing

Las pruebas unitarias del paquete se encuentran en:

```text
packages/php-core/tests/Unit/
```

Para ejecutar todas las pruebas:

```bash
cd packages/php-core
composer test
```

Para ejecutar las pruebas de autenticación:

```bash
vendor/bin/phpunit tests/Unit/Auth
```

Para ejecutar las pruebas de sesiones:

```bash
vendor/bin/phpunit tests/Unit/Session
```

Para ejecutar las pruebas de OAuth:

```bash
vendor/bin/phpunit tests/Unit/OAuth
```

## Configuration

Las principales configuraciones que puedes adaptar son:

| Variable | Description |
|---|---|
| `SHIELDPHP_APP_NAME` | Nombre de la aplicación. |
| `SHIELDPHP_ENV` | Entorno de ejecución. |
| `SHIELDPHP_DEBUG` | Modo de depuración. |
| `SHIELDPHP_JWT_SIGNING_KEY` | Clave de firma JWT. |
| `SHIELDPHP_GOOGLE_CLIENT_ID` | Identificador OAuth de Google. |
| `SHIELDPHP_GOOGLE_CLIENT_SECRET` | Secreto OAuth de Google. |
| `SHIELDPHP_FACEBOOK_CLIENT_ID` | Identificador OAuth de Facebook. |
| `SHIELDPHP_FACEBOOK_CLIENT_SECRET` | Secreto OAuth de Facebook. |
| `SHIELDPHP_SECURE_COOKIES` | Habilitar cookies Secure. |
| `SHIELDPHP_SAME_SITE` | Política SameSite. |
| `SHIELDPHP_ENCRYPTION_KEY` | Clave de cifrado. |

## Limitations

Este ejemplo tiene como objetivo demostrar la integración básica de ShieldPHP.

No representa por sí solo una aplicación de producción completamente configurada.

Para ampliar el ejemplo, se recomienda integrar:

- Registro de usuarios.
- Recuperación de contraseñas.
- Verificación de correo electrónico.
- Autenticación multifactor (MFA).
- OAuth con Google y Facebook.
- Gestión de roles y permisos.
- Limitación de intentos de inicio de sesión.
- Almacenamiento persistente de sesiones.
- Autenticación mediante JWT para clientes móviles.

## Contributing

Las contribuciones son bienvenidas.

1. Realiza un fork del repositorio.
2. Crea una rama para tu funcionalidad.
3. Implementa los cambios.
4. Agrega pruebas unitarias.
5. Ejecuta la suite de pruebas.
6. Envía un Pull Request.

## License

Este ejemplo forma parte del proyecto ShieldPHP.

Consulta el archivo `LICENSE` de la raíz del repositorio para conocer los términos de distribución y uso.

---

<p align="center">
  <strong>ShieldPHP</strong>
  <br>
  Secure authentication for modern PHP applications.
</p>
