# ShieldPHP — Integración con Slim Framework

<p align="center">
  <strong>Autenticación JWT y protección de rutas para Slim Framework 4.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Slim-4.x-719E40?style=for-the-badge" alt="Slim Framework 4">
  <img src="https://img.shields.io/badge/PSR--15-Compatible-blue?style=for-the-badge" alt="PSR-15">
  <img src="https://img.shields.io/badge/Estado-En%20desarrollo-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## Acerca de

**ShieldPHP Slim** es una integración diseñada para conectar los mecanismos de autenticación y seguridad de ShieldPHP Core con aplicaciones desarrolladas mediante Slim Framework 4.

Su objetivo es permitir que los desarrolladores protejan sus aplicaciones y API HTTP utilizando componentes reutilizables de ShieldPHP.

La integración utiliza middleware basado en PSR-15 para interceptar las solicitudes, validar los tokens JWT y proporcionar información del usuario autenticado a los controladores.

### Objetivos

- Integrar ShieldPHP Core con Slim Framework.
- Proporcionar autenticación mediante JWT.
- Proteger rutas y grupos de rutas.
- Validar tokens de acceso.
- Facilitar el acceso a las declaraciones del usuario autenticado.
- Mantener compatibilidad con estándares PSR.
- Separar la autenticación de la autorización.

## Características

### Autenticación JWT

El middleware `QuickAuthMiddleware` proporciona:

- Extracción de tokens desde el encabezado `Authorization`.
- Autenticación mediante esquema Bearer.
- Validación de tokens JWT.
- Verificación de firmas y expiración mediante ShieldPHP Core.
- Comprobación de revocación cuando está configurada en Core.
- Respuestas HTTP 401 para solicitudes no autorizadas.
- Respuestas JSON estructuradas.
- Protección de información interna durante los errores de autenticación.

### Integración con Slim

- Middleware compatible con PSR-15.
- Protección de rutas individuales.
- Protección de grupos de rutas.
- Inyección de dependencias.
- Compatibilidad con implementaciones PSR-7 y PSR-17.

### Información del usuario

Después de una autenticación correcta, el middleware agrega atributos a la solicitud HTTP:

| Atributo | Descripción |
|---|---|
| `shieldphp.claims` | Declaraciones del token JWT. |
| `shieldphp.user_id` | Identificador del usuario obtenido de `sub`. |

---

## Arquitectura

La integración utiliza una separación de responsabilidades entre Slim Framework y ShieldPHP Core.

```text
                    CLIENTE HTTP
                         |
                         |
                 Authorization: Bearer
                         |
                         v
              ┌──────────────────────┐
              │    Slim Framework    │
              │                      │
              │    Routing System   │
              └──────────┬───────────┘
                         |
                         v
              ┌──────────────────────┐
              │ QuickAuthMiddleware  │
              │                      │
              │  Extracción del JWT  │
              │  Validación Bearer   │
              └──────────┬───────────┘
                         |
                         v
              ┌──────────────────────┐
              │   ShieldPHP Core     │
              │                      │
              │    JWTManager        │
              │    TokenValidator    │
              └──────────┬───────────┘
                         |
                  ┌──────┴──────┐
                  |             |
                  v             v
              Token válido   Token inválido
                  |             |
                  v             v
             Controlador     HTTP 401
                  |
                  v
             Respuesta API
```

## Estructura del proyecto

```text
shieldphp/
│
├── packages/
│   └── php-core/
│       └── src/
│           ├── Auth/
│           ├── Authorization/
│           ├── Config/
│           ├── Contracts/
│           ├── Crypto/
│           ├── Exceptions/
│           ├── JWT/
│           ├── Middleware/
│           ├── OAuth/
│           ├── Session/
│           └── Validation/
│
├── slim/
│   ├── QuickAuthMiddleware.php
│   └── README.md
│
├── composer.json
└── phpunit.xml
```

## Requisitos

Para utilizar esta integración necesitas:

- PHP 8.2 o superior.
- Composer 2.
- Slim Framework 4.
- ShieldPHP Core.
- Extensión PHP Sodium.
- Extensión PHP JSON.
- Interfaces PSR-7, PSR-15 y PSR-17.
- Una implementación de mensajes HTTP PSR-7.

## Instalación

### 1. Instalar Slim Framework

Desde el directorio de tu aplicación:

```bash
composer require slim/slim:^4
```

### 2. Instalar una implementación PSR-7

Por ejemplo, Slim PSR-7:

```bash
composer require slim/psr7
```

### 3. Instalar las interfaces necesarias

```bash
composer require psr/http-server-middleware:^1.0
composer require psr/http-server-handler:^1.0
composer require psr/http-factory:^1.1
```

### 4. Incorporar ShieldPHP

Durante el desarrollo dentro del monorepositorio, agrega el autoload de la integración al `composer.json` de la raíz:

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

Conserva las demás configuraciones y dependencias que ya existan en el archivo.

Actualiza el autoload:

```bash
composer dump-autoload
```

> La integración todavía no cuenta con un paquete Composer independiente publicado. La instalación anterior corresponde al entorno de desarrollo del monorepositorio.

---

## Configuración

Para utilizar `QuickAuthMiddleware` necesitas instancias previamente configuradas de:

- `JWTManager`
- `TokenValidator`
- `ResponseFactoryInterface`

El middleware recibe estas dependencias mediante su constructor.

### Ejemplo de configuración

```php
<?php

declare(strict_types=1);

use ShieldPHP\Core\JWT\JWTManager;
use ShieldPHP\Core\JWT\TokenValidator;
use ShieldPHP\Slim\QuickAuthMiddleware;
use Slim\Psr7\Factory\ResponseFactory;

// Estas instancias deben construirse con la
// configuración correspondiente de ShieldPHP Core.

$jwtManager = $container->get(JWTManager::class);

$tokenValidator = $container->get(
    TokenValidator::class
);

$responseFactory = new ResponseFactory();

$quickAuthMiddleware = new QuickAuthMiddleware(
    $tokenValidator,
    $jwtManager,
    $responseFactory
);
```

### Constructor

```php
new QuickAuthMiddleware(
    TokenValidator $tokenValidator,
    JWTManager $jwtManager,
    ResponseFactoryInterface $responseFactory,
    string $realm = 'ShieldPHP API'
);
```

| Parámetro | Descripción |
|---|---|
| `$tokenValidator` | Valida el JWT y su estado de revocación. |
| `$jwtManager` | Decodifica las declaraciones del JWT. |
| `$responseFactory` | Construye respuestas HTTP. |
| `$realm` | Nombre del dominio de autenticación HTTP. |

---

## Uso básico

### Crear una aplicación Slim

```php
<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();

$app->addErrorMiddleware(
    false,
    true,
    true
);

// Registrar rutas de la aplicación.

$app->run();
```

### Proteger una ruta individual

```php
$app->get('/api/profile', function ($request, $response) {

    $userId = $request->getAttribute(
        \ShieldPHP\Slim\QuickAuthMiddleware::USER_ID_ATTRIBUTE
    );

    $claims = $request->getAttribute(
        \ShieldPHP\Slim\QuickAuthMiddleware::CLAIMS_ATTRIBUTE
    );

    $response->getBody()->write(json_encode([
        'success' => true,
        'user_id' => $userId,
        'email' => $claims->email ?? null
    ]));

    return $response->withHeader(
        'Content-Type',
        'application/json'
    );

})->add($quickAuthMiddleware);
```

### Proteger un grupo de rutas

Una de las principales ventajas del middleware es que permite proteger varias rutas mediante un único registro.

```php
use Slim\Routing\RouteCollectorProxy;

$app->group('/api/private', function (
    RouteCollectorProxy $group
) {

    $group->get('/profile', function ($request, $response) {

        $userId = $request->getAttribute(
            \ShieldPHP\Slim\QuickAuthMiddleware::USER_ID_ATTRIBUTE
        );

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Acceso autorizado',
            'user_id' => $userId
        ]));

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    });

    $group->get('/dashboard', function ($request, $response) {

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Panel privado'
        ]));

        return $response->withHeader(
            'Content-Type',
            'application/json'
        );
    });

})->add($quickAuthMiddleware);
```

Todas las rutas del grupo quedan sujetas a la autenticación del middleware.

## Enviar el token JWT

Las solicitudes protegidas deben incluir el encabezado:

```http
Authorization: Bearer TU_TOKEN_JWT
```

Ejemplo con cURL:

```bash
curl -X GET http://localhost:8080/api/private/profile \
  -H "Authorization: Bearer TU_TOKEN_JWT" \
  -H "Accept: application/json"
```

Ejemplo con JavaScript:

```javascript
const response = await fetch(
    'http://localhost:8080/api/private/profile',
    {
        method: 'GET',
        headers: {
            Authorization: `Bearer ${accessToken}`,
            Accept: 'application/json'
        }
    }
);

const data = await response.json();

console.log(data);
```

---

## Respuestas HTTP

### Token válido

Cuando el token es válido, el middleware permite que la solicitud continúe hacia el siguiente componente de la aplicación.

El controlador será responsable de generar la respuesta final.

Ejemplo:

```json
{
    "success": true,
    "message": "Acceso autorizado",
    "user_id": "123"
}
```

### Token ausente o inválido

Cuando no se proporciona un token, el formato es incorrecto o falla su validación, el middleware responde con HTTP 401.

```json
{
    "success": false,
    "error": "unauthorized",
    "message": "Se requiere un token de acceso válido."
}
```

También incluye encabezados de protección:

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json; charset=utf-8
Cache-Control: no-store
WWW-Authenticate: Bearer realm="ShieldPHP API", error="invalid_token"
```

El middleware evita revelar detalles internos sobre el motivo específico del rechazo.

---

## Autorización y permisos

La autenticación y la autorización son responsabilidades diferentes.

`QuickAuthMiddleware` comprueba que la solicitud presenta un token válido, pero no comprueba por sí mismo si el usuario tiene permisos para ejecutar una operación.

ShieldPHP Core contempla componentes para administrar:

- Roles.
- Permisos.
- Acceso a recursos.
- Controles de autorización.

Una aplicación debe realizar estas comprobaciones después de autenticar al usuario.

Por ejemplo:

```php
$claims = $request->getAttribute(
    QuickAuthMiddleware::CLAIMS_ATTRIBUTE
);

$roles = $claims->roles ?? [];

if (!in_array('admin', $roles, true)) {
    // La aplicación debe responder con HTTP 403.
}
```

Esta comprobación es ilustrativa: para aplicaciones reales se recomienda utilizar un componente de autorización y validar los permisos contra una fuente confiable.

---

## Orden de middleware

Slim Framework ejecuta los middleware en orden LIFO (*Last In, First Out*): el último middleware agregado es el primero en ejecutarse.

Por ello, el orden de registro debe planificarse cuidadosamente.

Se recomienda:

1. Configurar el middleware de enrutamiento.
2. Registrar los componentes necesarios para la aplicación.
3. Incorporar el middleware de manejo de errores en el orden adecuado.
4. Aplicar la autenticación a las rutas privadas.

La autenticación debe ejecutarse antes de que el controlador procese una solicitud protegida.

---

## Seguridad

ShieldPHP Slim está diseñado para utilizar los mecanismos de protección de ShieldPHP Core.

Se recomienda:

- Utilizar HTTPS en producción.
- No almacenar claves privadas ni secretos en el repositorio.
- Mantener protegidas las claves de firma JWT.
- Configurar correctamente el emisor y la audiencia de los tokens.
- Establecer tiempos de expiración adecuados.
- Implementar revocación de tokens.
- No confiar en los datos del cliente.
- Separar autenticación de autorización.
- Verificar que el usuario continúe activo cuando la aplicación lo requiera.
- No registrar tokens completos ni credenciales.
- Validar los permisos antes de ejecutar operaciones sensibles.

El middleware no debe utilizarse como sustituto de las medidas de seguridad de la aplicación o de la infraestructura.

---

## Limitaciones actuales

La integración se encuentra en desarrollo.

| Funcionalidad | Estado |
|---|---|
| Middleware PSR-15 | Implementación inicial |
| Extracción de Bearer Token | Implementada |
| Validación JWT mediante Core | Implementación inicial |
| Respuestas HTTP 401 | Implementadas |
| Atributos de usuario | Implementados |
| Protección de rutas individuales | Compatible |
| Protección de grupos de rutas | Compatible |
| Autorización por roles | Pendiente de integración |
| Autorización por permisos | Pendiente de integración |
| Pruebas unitarias específicas | Pendientes |
| Pruebas de integración con Slim | Pendientes |
| Paquete Composer independiente | Pendiente |
| Documentación de producción | En desarrollo |

### Consideraciones técnicas

La implementación de `QuickAuthMiddleware` presupone que ShieldPHP Core dispone de los siguientes métodos:

```php
$tokenValidator->isValid($token);

$jwtManager->decode($token);
```

La compatibilidad debe verificarse contra las clases reales del Core antes de integrar el middleware en una aplicación de producción.

El middleware tampoco consulta automáticamente una base de datos para comprobar si el usuario existe o continúa activo.

---

## Desarrollo y contribuciones

Las contribuciones son bienvenidas.

Para proponer cambios:

1. Crea una rama de trabajo.
2. Implementa la funcionalidad respetando PSR-15 y PSR-7.
3. Incorpora pruebas unitarias y de integración.
4. Ejecuta las comprobaciones del proyecto.
5. Documenta cualquier cambio en el comportamiento.
6. Envía un Pull Request.

Ejemplo:

```bash
git checkout -b feature/slim-auth-improvements

git add .

git commit -m "feat: mejorar autenticación en Slim"

git push origin feature/slim-auth-improvements
```

## Documentación

- [Documentación oficial de Slim Framework 4](https://www.slimframework.com/docs/v4/)
- [Slim Middleware](https://www.slimframework.com/docs/v4/concepts/middleware.html)
- [Slim Routing](https://www.slimframework.com/docs/v4/objects/routing.html)
- [PSR-15: HTTP Server Middleware](https://www.php-fig.org/psr/psr-15/)
- [PSR-7: HTTP Message Interfaces](https://www.php-fig.org/psr/psr-7/)
- [PSR-17: HTTP Factories](https://www.php-fig.org/psr/psr-17/)

---

## Licencia

ShieldPHP utiliza la licencia MIT, declarada en la configuración del proyecto.

Consulta el archivo `LICENSE` de la raíz del repositorio para conocer sus condiciones de distribución.

---

<p align="center">
  <strong>ShieldPHP + Slim Framework</strong>
  <br>
  Autenticación modular para aplicaciones PHP.
  <br><br>
  Desarrollado como parte del ecosistema ShieldPHP.
</p>