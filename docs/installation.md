# Instalación — ShieldPHP

<p align="center">
  <strong>Guía de instalación y configuración del ecosistema ShieldPHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Composer-2.x-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer 2">
  <img src="https://img.shields.io/badge/Installation-Guide-blue?style=for-the-badge" alt="Guía de instalación">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

**ShieldPHP** es una biblioteca modular de seguridad para PHP que proporciona componentes para autenticación, autorización, administración de sesiones, criptografía, JWT y OAuth 2.0.

Esta guía explica cómo preparar el entorno de desarrollo, instalar las dependencias y comenzar a utilizar los componentes del proyecto.

Existen dos modalidades de trabajo:

| Modalidad | Descripción |
|---|---|
| Monorepositorio | Permite desarrollar y probar todo el ecosistema ShieldPHP. |
| PHP Core | Permite trabajar directamente con el paquete principal de la biblioteca. |

Actualmente ShieldPHP se encuentra en desarrollo. La instalación documentada está orientada principalmente al trabajo con el código fuente del repositorio.

## 2. Requisitos del sistema

Antes de instalar ShieldPHP, comprueba que tu entorno tenga los siguientes componentes.

### Requisitos principales

| Tecnología | Versión requerida |
|---|---|
| PHP | 8.2 o superior |
| Composer | 2.x |
| JSON | Extensión habilitada |
| Sodium | Extensión habilitada |
| Git | Versión reciente |
| Sistema operativo | Windows, Linux o macOS |

### Extensiones adicionales

Algunos componentes y herramientas requieren extensiones adicionales de PHP.

| Extensión | Utilización |
|---|---|
| `json` | Procesamiento de información JSON. |
| `sodium` | Operaciones criptográficas. |
| `mbstring` | Procesamiento de cadenas multibyte cuando corresponda. |
| `openssl` | Funciones criptográficas y conexiones seguras. |
| `dom` | Algunas herramientas de pruebas. |
| `xml` | Configuración y ejecución de herramientas de desarrollo. |

### Comprobar PHP

```bash
php -v
```

Se recomienda PHP 8.2 o superior.

### Comprobar Composer

```bash
composer --version
```

### Comprobar extensiones

```bash
php -m
```

Comprueba que aparezcan al menos:

```text
json
sodium
```

También puedes verificar Sodium directamente:

```bash
php -r "var_dump(extension_loaded('sodium'));"
```

El resultado esperado es:

```text
bool(true)
```

## 3. Obtener el repositorio

Puedes obtener el código fuente de ShieldPHP desde GitHub.

Repositorio:

https://github.com/isairey/shieldphp

### Clonar el repositorio

Abre una terminal y ejecuta:

```bash
git clone https://github.com/isairey/shieldphp.git

cd shieldphp
```

### Trabajar con la rama de desarrollo

Para utilizar la rama de desarrollo del proyecto:

```bash
git switch Dev
```

También puedes consultar las ramas disponibles:

```bash
git branch -a
```

### Actualizar el repositorio

Para obtener los cambios más recientes de la rama de desarrollo:

```bash
git fetch origin

git pull --ff-only origin Dev
```

Se recomienda mantener los cambios locales guardados antes de sincronizar el repositorio.

## 4. Estructura del proyecto

ShieldPHP utiliza una arquitectura de monorepositorio para organizar sus paquetes, integraciones, ejemplos y documentación.

```text
shieldphp/
│
├── .github/
│   ├── ISSUE_TEMPLATE/
│   └── workflows/
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
│       │   ├── Integration/
│       │   └── Security/
│       │
│       ├── composer.json
│       ├── phpstan.neon
│       └── phpunit.xml
│
├── integrations/
│   └── laravel/
│
├── slim/
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
├── SECURITY.md
├── composer.json
├── phpunit.xml
└── README.md
```

La estructura puede evolucionar conforme se incorporen nuevos paquetes e integraciones.

## 5. Instalación del monorepositorio

Esta modalidad es la recomendada para trabajar con todo el ecosistema ShieldPHP.

Desde la raíz del repositorio:

```bash
cd shieldphp
```

### Paso 1. Validar Composer

```bash
composer validate
```

Este comando comprueba la estructura y los metadatos del archivo `composer.json`.

### Paso 2. Instalar dependencias

```bash
composer install
```

Composer instalará las dependencias declaradas en el archivo de configuración raíz.

### Paso 3. Generar el autoload

```bash
composer dump-autoload
```

El autoload permite cargar automáticamente las clases registradas mediante PSR-4.

### Paso 4. Comprobar las dependencias

```bash
composer show
```

Con esto tendrás preparado el entorno principal del monorepositorio.

## 6. Instalación independiente de PHP Core

ShieldPHP Core dispone de su propio archivo `composer.json`.

Esto permite trabajar directamente con el paquete principal sin depender del directorio raíz del monorepositorio.

### Acceder al paquete

```bash
cd packages/php-core
```

### Validar la configuración

```bash
composer validate
```

### Instalar las dependencias

```bash
composer install
```

### Generar el autoload

```bash
composer dump-autoload
```

### Comprobar la instalación

```bash
composer show
```

El paquete utiliza el siguiente namespace:

```php
ShieldPHP\Core\
```

Y su configuración PSR-4:

```json
{
    "autoload": {
        "psr-4": {
            "ShieldPHP\\Core\\": "src/"
        }
    }
}
```

Esta modalidad es útil para desarrollar y probar el Core de manera independiente.

## 7. Configuración de variables de entorno

ShieldPHP incluye una plantilla de configuración:

```text
.env.example
```

Esta contiene variables previstas para diferentes componentes:

- Aplicación.
- Autenticación.
- JWT.
- Cifrado.
- Administración de claves.
- Sesiones.
- Cookies.
- OAuth.
- Control de solicitudes.
- Protección CSRF.
- Registros.

### Crear el archivo .env

Desde la raíz:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### Configuración inicial

```dotenv
APP_NAME="ShieldPHP"
APP_ENV=local
APP_DEBUG=true

SHIELDPHP_APP_NAME="ShieldPHP"
SHIELDPHP_ENV=local
SHIELDPHP_DEBUG=true

SHIELDPHP_JWT_ENABLED=true
SHIELDPHP_JWT_ALGORITHM=HS256

SHIELDPHP_JWT_ISSUER="shieldphp"
SHIELDPHP_JWT_AUDIENCE="shieldphp-api"

SHIELDPHP_JWT_ACCESS_TTL=900
SHIELDPHP_JWT_REFRESH_TTL=604800

SHIELDPHP_ENCRYPTION_ENABLED=true
```

Este bloque es ilustrativo y no sustituye la configuración completa de `.env.example`.

### Importante sobre las variables de entorno

PHP no carga automáticamente archivos `.env`.

Para utilizar estas variables debes emplear un cargador de variables de entorno o configurarlas directamente en el entorno de ejecución.

La presencia de una variable en `.env.example` tampoco implica que todos los módulos de ShieldPHP ya la consuman automáticamente.

## 8. Generación de claves criptográficas

ShieldPHP necesita claves seguras para las operaciones que requieren firma y cifrado.

Las claves deben generarse independientemente.

### Clave para JWT HS256

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

### Clave para Encryption

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Guarda ambos resultados en sus variables correspondientes:

```dotenv
SHIELDPHP_JWT_SIGNING_KEY_BASE64=CLAVE_JWT_GENERADA

SHIELDPHP_ENCRYPTION_KEY_BASE64=CLAVE_CIFRADO_GENERADA
```

**Recomendaciones:**

- No reutilizar la misma clave.
- No publicar secretos en GitHub.
- No almacenar las claves dentro de directorios públicos.
- Mantener copias protegidas de las claves necesarias para recuperar información cifrada.
- No generar nuevas claves en cada ejecución si necesitas descifrar datos anteriores.

## 9. Instalar dependencias de desarrollo

ShieldPHP contempla herramientas para pruebas y análisis de calidad.

Entre ellas:

| Herramienta | Función |
|---|---|
| PHPUnit | Pruebas automatizadas. |
| PHPStan | Análisis estático. |
| PHP-CS-Fixer | Formato del código. |
| Composer | Administración de dependencias. |

Desde el directorio correspondiente:

```bash
composer install
```

Comprueba que PHPUnit esté disponible:

```bash
vendor/bin/phpunit --version
```

Comprueba PHPStan:

```bash
vendor/bin/phpstan --version
```

En Windows puedes utilizar las rutas equivalentes dentro de `vendor/bin`.

## 10. Verificar la instalación

Una vez instaladas las dependencias, realiza las siguientes comprobaciones.

### Validar Composer

Desde la raíz:

```bash
composer validate --strict
```

### Ejecutar PHPUnit

```bash
composer test
```

### Ejecutar PHPStan

Desde el paquete Core:

```bash
cd packages/php-core

composer analyse
```

### Comprobar el formato

```bash
composer format:check
```

### Ejecutar todas las comprobaciones

Cuando los scripts estén configurados en el `composer.json` correspondiente:

```bash
composer check
```

Una instalación se considera preparada para desarrollo cuando Composer puede resolver las dependencias, el autoload funciona y las herramientas configuradas pueden ejecutarse correctamente.

## 11. Utilizar ShieldPHP en una aplicación

Durante el desarrollo del monorepositorio, puedes utilizar el autoload de Composer de la raíz.

Ejemplo:

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use ShieldPHP\Core\Auth\PasswordHasher;

$passwordHasher = new PasswordHasher();

$password = 'MiPasswordSeguro2026!';

$hash = $passwordHasher->hash($password);

$isValid = $passwordHasher->verify(
    $password,
    $hash
);

if ($isValid) {
    echo 'ShieldPHP funciona correctamente.';
}
```

Este ejemplo utiliza el componente `PasswordHasher` como comprobación inicial.

La aplicación consumidora debe tener acceso a las dependencias del Core y a su autoload correspondiente.

## 12. Integración con Slim Framework

ShieldPHP contempla una integración con Slim Framework 4 mediante el componente:

```php
ShieldPHP\Slim\QuickAuthMiddleware
```

La integración permite utilizar los mecanismos JWT del Core para proteger rutas y grupos de rutas.

### Requisitos

- PHP 8.2 o superior.
- Slim Framework 4.
- ShieldPHP Core.
- Interfaces PSR-7.
- Interfaces PSR-15.
- Interfaces PSR-17.
- Implementación de fábrica de respuestas HTTP.

### Instalar Slim

Desde la aplicación Slim:

```bash
composer require slim/slim:^4

composer require slim/psr7
```

### Registrar el autoload de desarrollo

En el `composer.json` de la raíz del monorepositorio:

```json
{
    "autoload": {
        "psr-4": {
            "ShieldPHP\\Core\\": "packages/php-core/src/",
            "ShieldPHP\\Slim\\": "slim/"
        }
    }
}
```

Actualiza Composer:

```bash
composer dump-autoload
```

La integración Slim se encuentra en desarrollo y debe verificarse contra las API reales del Core antes de utilizarla en producción.

Consulta:

```text
slim/README.md
```

## 13. Integración con Laravel

ShieldPHP también contempla una integración específica con Laravel.

Directorio:

```text
integrations/laravel/
```

La estructura inicial contempla:

- Registro de servicios.
- Configuración del paquete.
- Publicación de configuraciones.
- Registro de alias de middleware.
- Carga de rutas.

La instalación final requiere completar y validar los adaptadores, middleware y dependencias del paquete.

Consulta la documentación correspondiente:

```text
integrations/laravel/README.md
```

No se recomienda considerar esta integración lista para producción hasta completar sus pruebas de compatibilidad.

## 14. Problemas comunes

### Composer no reconoce PHP

Comprueba la instalación:

```bash
php -v
```

Verifica que PHP esté agregado al PATH del sistema.

### La extensión Sodium no está habilitada

Comprueba:

```bash
php -m
```

Si no aparece `sodium`, habilita la extensión en la configuración de PHP y reinicia el proceso correspondiente.

### Composer no puede instalar dependencias

Ejecuta:

```bash
composer diagnose

composer validate

composer clear-cache

composer install
```

Consulta los errores de resolución de dependencias antes de modificar las restricciones de versiones.

### Error de autoload

Regenera los archivos:

```bash
composer dump-autoload -o
```

Verifica que los namespaces coincidan con las rutas registradas en `composer.json`.

### PHPUnit no encuentra las pruebas

Comprueba que exista el archivo de configuración correspondiente:

```text
phpunit.xml
```

Ejecuta:

```bash
vendor/bin/phpunit --list-tests
```

Verifica que las suites apunten a los directorios correctos y que el autoload de Composer esté disponible.

### PHPStan no encuentra la configuración

Desde el paquete Core:

```bash
vendor/bin/phpstan analyse --configuration=phpstan.neon
```

Verifica la ubicación del archivo y los directorios incluidos en `paths`.

## 15. Actualización

Para actualizar el repositorio durante el desarrollo:

```bash
git fetch origin

git pull --ff-only origin Dev
```

Después actualiza las dependencias:

```bash
composer install

composer dump-autoload
```

Si utilizas el paquete Core de manera independiente, ejecuta los comandos correspondientes dentro de `packages/php-core`.

Evita utilizar `composer update` de manera rutinaria sin revisar los cambios de dependencias que se introducirán.

## 16. Instalación para producción

Antes de utilizar ShieldPHP en un entorno de producción, es necesario comprobar que los componentes requeridos estén implementados, probados y correctamente configurados.

Se recomienda:

1. Utilizar versiones estables de PHP y Composer.
2. Instalar únicamente dependencias necesarias.
3. Desactivar los mensajes de depuración.
4. Configurar claves criptográficas seguras.
5. Utilizar HTTPS.
6. Proteger credenciales y archivos de entorno.
7. Configurar expiración y revocación de tokens.
8. Validar los procesos OAuth.
9. Revisar las configuraciones de sesiones y cookies.
10. Ejecutar pruebas automatizadas y análisis estático.
11. Revisar las dependencias por vulnerabilidades conocidas.
12. Realizar una evaluación de seguridad antes del despliegue.

Para instalaciones optimizadas de Composer:

```bash
composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader
```

Este comando debe utilizarse únicamente cuando el paquete y la aplicación estén preparados para producción.

**Estado actual:** ShieldPHP se encuentra en desarrollo. Esta guía no representa una certificación de producción ni garantiza que todas las integraciones estén finalizadas.

## 17. Estructura de documentación

La documentación relacionada con la instalación y configuración se encuentra organizada de la siguiente manera:

```text
docs/
│
├── api-reference.md
├── authentication.md
├── authorization.md
├── configuration.md
├── encryption.md
├── facebook-login.md
├── google-login.md
└── installation.md
```

## 18. Recursos externos

- [PHP](https://www.php.net/)
- [Composer](https://getcomposer.org/)
- [PHPUnit](https://phpunit.de/)
- [PHPStan](https://phpstan.org/)
- [libsodium](https://doc.libsodium.org/)
- [Slim Framework](https://www.slimframework.com/)
- [Laravel](https://laravel.com/)
- [GitHub](https://github.com/)
- [GitHub Actions](https://docs.github.com/en/actions)

---

<p align="center">
  <strong>ShieldPHP</strong>
  <br>
  Instalación, configuración y desarrollo de una biblioteca de seguridad modular para PHP.
</p>