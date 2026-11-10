# ShieldPHP

<p align="center">
  <img src="docs/assets/shieldphp-logo.png" width="220" alt="ShieldPHP Logo">
</p>

<p align="center">
  <strong>Framework modular de autenticación, autorización y seguridad para PHP.</strong>
</p>

<p align="center">
  Construye aplicaciones PHP más seguras mediante componentes reutilizables, criptografía moderna y mecanismos de protección diseñados para integrarse en diferentes arquitecturas.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Composer-2%2B-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer">
  <img src="https://img.shields.io/badge/PHPUnit-11.5-366488?style=for-the-badge&logo=php&logoColor=white" alt="PHPUnit 11.5">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## Acerca del proyecto

**ShieldPHP** es una biblioteca modular de seguridad para PHP cuyo objetivo es proporcionar herramientas para gestionar la autenticación, autorización, sesiones, tokens, cifrado y autenticación mediante proveedores externos.

El proyecto está diseñado para que los desarrolladores puedan incorporar componentes de seguridad en sus aplicaciones sin tener que implementar desde cero cada mecanismo.

ShieldPHP busca ofrecer:

- Arquitectura modular y extensible.
- Separación de responsabilidades.
- Autenticación y autorización centralizadas.
- Protección mediante mecanismos criptográficos modernos.
- Integración con servicios OAuth 2.0.
- Componentes reutilizables mediante Composer.
- Pruebas unitarias, de integración y seguridad.

Actualmente se encuentra en desarrollo, con una base inicial del paquete Core y diferentes integraciones en construcción.

## Características principales

### 1. Autenticación

Componentes para administrar la identidad de los usuarios y verificar sus credenciales.

- Autenticación mediante correo electrónico y contraseña.
- Verificación segura de contraseñas.
- Políticas de seguridad para contraseñas.
- Administración de identidades.
- Resultados estructurados de autenticación.
- Protección frente a intentos de enumeración de usuarios.

### 2. Autorización

Sistema de control de acceso para administrar los privilegios de los usuarios.

- Administración de roles.
- Administración de permisos.
- Asignación de roles.
- Verificación de permisos.
- Control de acceso a recursos.
- Separación entre autenticación y autorización.

### 3. JWT

Administración de tokens JSON Web Token.

- Generación de tokens.
- Validación de firmas.
- Administración de declaraciones JWT.
- Tokens de acceso.
- Tokens de actualización.
- Configuración de expiración.
- Revocación de tokens.
- Lista negra de tokens.

### 4. OAuth 2.0

Base para integrar autenticación mediante proveedores externos.

- Flujo de autorización OAuth 2.0.
- Gestión del parámetro `state`.
- Protección mediante PKCE.
- Intercambio de códigos de autorización.
- Obtención de perfiles de usuario.
- Cliente HTTP con restricciones HTTPS y de dominios permitidos.

**Proveedores contemplados:**

- Google
- Facebook

### 5. Criptografía

Herramientas para proteger información sensible.

- Cifrado y descifrado de datos.
- Derivación de claves.
- Administración de claves criptográficas.
- Generación de valores aleatorios seguros.
- Firmas digitales.
- Validación de firmas.

Las operaciones criptográficas del Core utilizan libsodium.

### 6. Sesiones y cookies

Componentes para administrar sesiones de usuario.

- Administración de sesiones.
- Regeneración de identificadores.
- Destrucción de sesiones.
- Administración de cookies.
- Configuración de atributos de seguridad.
- Opciones de protección contra ataques relacionados con sesiones.

### 7. Validación

Herramientas para validar entradas y configuraciones.

- Validación de correos electrónicos.
- Validación de cadenas.
- Validación de números.
- Validación de URL.
- Validación de URI de redirección.
- Validación de configuraciones.

---

## Arquitectura

ShieldPHP utiliza una arquitectura modular basada en paquetes independientes.

```text
shieldphp/
│
├── packages/
│   └── php-core/
│       ├── src/
│       │   ├── Auth/
│       │   ├── Authorization/
│       │   ├── Config/
│       │   ├── Contracts/
│       │   ├── Crypto/
│       │   ├── Exceptions/
│       │   ├── JWT/
│       │   ├── Middleware/
│       │   ├── OAuth/
│       │   │   └── Providers/
│       │   ├── Session/
│       │   └── Validation/
│       │
│       ├── tests/
│       │   ├── Unit/
│       │   │   ├── Auth/
│       │   │   ├── Authorization/
│       │   │   ├── Config/
│       │   │   ├── Crypto/
│       │   │   ├── Exceptions/
│       │   │   ├── JWT/
│       │   │   ├── OAuth/
│       │   │   ├── Session/
│       │   │   ├── Support/
│       │   │   └── Validation/
│       │   │
│       │   ├── Integration/
│       │   └── Security/
│       │
│       ├── composer.json
│       ├── phpstan.neon
│       └── phpunit.xml
│
├── integrations/
│   └── laravel/
│       ├── config/
│       ├── src/
│       └── README.md
│
├── examples/
│   ├── php-basic/
│   ├── oauth-google/
│   └── oauth-facebook/
│
├── docs/
│
├── .editorconfig
├── .env.example
├── .gitattributes
├── .gitignore
├── CHANGELOG.md
├── composer.json
└── phpunit.xml
```

*La estructura representa la organización prevista y actual del monorepositorio; algunos adaptadores y módulos todavía están en desarrollo.*

## Tecnologías utilizadas

| Tecnología | Aplicación |
|---|---|
| PHP 8.2+ | Lenguaje principal |
| Composer | Administración de dependencias |
| PHPUnit 11.5 | Pruebas automatizadas |
| PHPStan | Análisis estático |
| PHP-CS-Fixer | Formato del código |
| libsodium | Operaciones criptográficas |
| Firebase PHP-JWT | Administración de JWT |
| PSR-7 | Mensajes HTTP |
| PSR-17 | Fábricas HTTP |
| PSR-18 | Cliente HTTP |

## Requisitos

Antes de instalar ShieldPHP necesitas:

- PHP 8.2 o superior.
- Composer 2.
- Extensión PHP `sodium`.
- Extensión PHP `json`.
- Extensiones y dependencias adicionales según los módulos que utilices.

Puedes comprobar tu instalación con:

```bash
php -v
composer --version
php -m
```

Verifica que `sodium` y `json` aparezcan entre las extensiones habilitadas.

---

## Instalación

### Opción 1. Instalar el monorepositorio

Clona el repositorio y accede a su directorio raíz:

```bash
git clone https://github.com/isairey/shieldphp.git
cd shieldphp
```

Instala las dependencias:

```bash
composer validate
composer install
composer dump-autoload
```

Esta modalidad permite trabajar con los paquetes y ejemplos desde un mismo entorno.

### Opción 2. Instalar ShieldPHP Core

También puedes trabajar directamente desde el paquete independiente:

```bash
cd packages/php-core
```

Instala las dependencias:

```bash
composer validate
composer install
composer dump-autoload
```

El paquete Core utiliza su propio autoloading PSR-4:

```json
{
    "autoload": {
        "psr-4": {
            "ShieldPHP\\Core\\": "src/"
        }
    }
}
```

> La publicación e instalación pública mediante Packagist dependerá de que el paquete se publique oficialmente.

---

## Uso básico

ShieldPHP está organizado para que sus componentes puedan utilizarse de manera independiente.

Por ejemplo, el componente de hash de contraseñas puede utilizarse de la siguiente manera:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use ShieldPHP\Core\Auth\PasswordHasher;

$passwordHasher = new PasswordHasher();

$password = 'MiPasswordSeguro2026!';

// Generar hash
$passwordHash = $passwordHasher->hash($password);

// Verificar contraseña
$isValid = $passwordHasher->verify(
    $password,
    $passwordHash
);

if ($isValid) {
    echo 'Contraseña válida';
} else {
    echo 'Credenciales incorrectas';
}
```

Este ejemplo ilustra el uso previsto de la clase `PasswordHasher`. Para utilizarlo desde una aplicación consumidora, instala primero las dependencias del paquete y adapta la ruta del autoload según tu proyecto.

---

## Configuración

ShieldPHP cuenta con una plantilla de variables de entorno:

```text
.env.example
```

Esta contempla variables relacionadas con:

- Nombre y entorno de la aplicación.
- Configuración JWT.
- Claves de cifrado.
- Administración de sesiones.
- Cookies.
- Proveedores OAuth.
- Protección CSRF.
- Limitación de solicitudes.
- Registro de eventos.
- Configuración de base de datos.

Copia la plantilla para preparar tu entorno:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

**Importante:** la plantilla documenta las variables previstas, pero no todas están conectadas automáticamente a los componentes del Core. Una aplicación debe cargar las variables y transformarlas en la configuración que corresponda. ShieldPHP no incluye actualmente un cargador universal de archivos `.env`.

### Generación de claves

Genera claves criptográficas independientes:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Puedes utilizar una clave para JWT con HS256 y otra para el cifrado.

Las claves almacenadas en Base64 deben decodificarse antes de entregarlas a los componentes que esperan claves binarias.

Nunca publiques las claves reales en GitHub.

---

## Pruebas automatizadas

ShieldPHP utiliza PHPUnit para organizar sus pruebas en tres categorías.

| Suite | Objetivo |
|---|---|
| Unit | Validar el comportamiento individual de cada componente. |
| Integration | Comprobar la interacción entre componentes. |
| Security | Evaluar mecanismos y escenarios relacionados con la seguridad. |

### Ejecutar todas las pruebas

Desde la raíz:

```bash
composer test
```

### Ejecutar pruebas por categoría

```bash
vendor/bin/phpunit --testsuite "ShieldPHP - Unit Tests"

vendor/bin/phpunit --testsuite "ShieldPHP - Integration Tests"

vendor/bin/phpunit --testsuite "ShieldPHP - Security Tests"
```

Desde `packages/php-core`:

```bash
composer test
```

### Generar cobertura de código

Requiere Xdebug o PCOV:

```bash
vendor/bin/phpunit --coverage-html var/coverage
```

---

## Análisis estático

ShieldPHP utiliza PHPStan para identificar errores potenciales y problemas relacionados con los tipos.

La configuración se encuentra en:

```text
packages/php-core/phpstan.neon
```

Ejecuta el análisis desde el paquete Core:

```bash
composer analyse
```

El nivel inicial de análisis configurado es 8.

## Formato del código

El proyecto utiliza PHP-CS-Fixer para mantener un estilo consistente.

```bash
composer format
```

Para comprobar el formato sin modificar los archivos:

```bash
composer format:check
```

---

## Integración con Laravel

ShieldPHP contempla una integración específica con Laravel.

Directorio:

```text
integrations/laravel/
```

Esta integración cuenta con una estructura inicial para:

- Registro de servicios.
- Configuración del paquete.
- Registro de alias de middleware.
- Publicación de configuración.
- Carga de rutas.

La integración todavía requiere completar y validar sus adaptadores, middleware y mecanismos de autenticación antes de considerarse lista para producción.

---

## Seguridad

La seguridad es uno de los principios fundamentales de ShieldPHP.

Se contemplan las siguientes prácticas:

- Utilizar algoritmos criptográficos estándar.
- No almacenar contraseñas en texto plano.
- Validar las firmas y expiraciones de los JWT.
- Mantener protegidas las claves criptográficas.
- Validar el parámetro `state` durante los procesos OAuth.
- Utilizar PKCE cuando corresponda.
- Restringir las conexiones HTTP OAuth a HTTPS y dominios permitidos.
- Aplicar controles de acceso.
- Proteger las sesiones y cookies.
- Validar todas las entradas externas.

### Recomendaciones para producción

1. Desactivar el modo de depuración.
2. Utilizar HTTPS en toda la aplicación.
3. Configurar claves criptográficas seguras.
4. No compartir secretos entre diferentes mecanismos.
5. Configurar adecuadamente la expiración de tokens.
6. Implementar almacenamiento persistente para revocación y estado OAuth.
7. Verificar criptográficamente los ID tokens cuando se utilicen.
8. Configurar límites de solicitudes.
9. No confiar únicamente en las validaciones del frontend.
10. Ejecutar las pruebas y auditorías de seguridad antes del despliegue.

> ShieldPHP se encuentra en desarrollo. Su estructura y componentes iniciales no constituyen por sí solos una certificación de seguridad ni garantizan que todas las integraciones estén listas para producción.

---

## Estado del desarrollo

| Componente | Estado |
|---|---|
| Estructura del monorepositorio | Inicial |
| Autenticación Core | Base implementada |
| Autorización | Base implementada |
| Configuración | Base implementada |
| Criptografía | Base implementada |
| JWT | Base implementada |
| OAuth Google | Implementación inicial |
| OAuth Facebook | Implementación inicial |
| Sesiones y cookies | En desarrollo |
| Validaciones | Implementación inicial |
| PHPUnit | Suite inicial |
| PHPStan | Configuración inicial |
| Integración Laravel | En desarrollo |
| Adaptadores persistentes | Pendientes |
| Documentación | En desarrollo |

## Hoja de ruta

### Fase 1 — Núcleo de seguridad
- [x] Crear la estructura del monorepositorio.
- [x] Establecer el autoloading PSR-4.
- [x] Incorporar componentes iniciales de autenticación.
- [x] Incorporar herramientas criptográficas.
- [x] Establecer la base de JWT y OAuth.
- [ ] Completar y validar todas las API del Core.

### Fase 2 — Pruebas y calidad
- [x] Organizar las pruebas PHPUnit.
- [x] Configurar PHPStan.
- [ ] Completar las pruebas unitarias.
- [ ] Completar las pruebas de integración.
- [ ] Ampliar las pruebas de seguridad.
- [ ] Integrar comprobaciones automatizadas mediante CI.

### Fase 3 — Integraciones
- [x] Crear la estructura de integración con Laravel.
- [x] Preparar ejemplos OAuth.
- [ ] Completar los adaptadores de Laravel.
- [ ] Incorporar almacenamiento persistente.
- [ ] Completar los ejemplos de uso.

### Fase 4 — Distribución
- [ ] Completar la documentación técnica.
- [ ] Preparar la documentación de instalación.
- [ ] Realizar auditorías de seguridad.
- [ ] Publicar ShieldPHP Core en Packagist.
- [ ] Preparar la primera versión estable.

---

## Contribuciones

Las contribuciones son bienvenidas.

Para colaborar:

1. Realiza un fork del repositorio.
2. Crea una rama para tu funcionalidad o corrección.
3. Implementa los cambios siguiendo la arquitectura del proyecto.
4. Agrega o actualiza las pruebas correspondientes.
5. Ejecuta las comprobaciones de calidad.
6. Envía un Pull Request con una descripción clara de los cambios.

Ejemplo:

```bash
git checkout -b feature/nueva-funcionalidad

git add .

git commit -m "feat: agregar nueva funcionalidad"

git push origin feature/nueva-funcionalidad
```

### Convención de commits

ShieldPHP utiliza como referencia Conventional Commits:

| Prefijo | Uso |
|---|---|
| `feat:` | Nueva funcionalidad. |
| `fix:` | Corrección de errores. |
| `docs:` | Cambios en documentación. |
| `test:` | Incorporación o modificación de pruebas. |
| `refactor:` | Mejoras internas del código. |
| `security:` | Mejoras relacionadas con seguridad. |
| `chore:` | Mantenimiento y configuración. |

---

## Licencia

El archivo `composer.json` declara la licencia MIT.

Antes de distribuir oficialmente ShieldPHP, incorpora el archivo `LICENSE` correspondiente y verifica que todos los paquetes y dependencias respeten sus respectivas licencias.

## Autor

**Isai Reyes Peña**

Desarrollador de ShieldPHP.

## Agradecimientos

ShieldPHP se construye con el objetivo de proporcionar herramientas de seguridad reutilizables para el ecosistema PHP, apoyándose en estándares abiertos, buenas prácticas de desarrollo y tecnologías ampliamente utilizadas.

---

<p align="center">
  <strong>ShieldPHP</strong>
  <br>
  Seguridad modular para aplicaciones PHP.
</p>
