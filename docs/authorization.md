# Authorization — ShieldPHP

<p align="center">
  <strong>Sistema de autorización, roles y control de acceso para PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Security-Authorization-blue?style=for-the-badge" alt="Authorization">
  <img src="https://img.shields.io/badge/Architecture-Modular-purple?style=for-the-badge" alt="Arquitectura modular">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

El módulo **Authorization** de ShieldPHP proporciona los componentes necesarios para administrar el acceso de los usuarios a recursos, funcionalidades y operaciones dentro de una aplicación PHP.

Su objetivo es centralizar las decisiones de autorización y permitir que las aplicaciones definan qué operaciones puede realizar cada identidad autenticada.

A diferencia del módulo Authentication, que determina quién es el usuario, Authorization determina qué está autorizado a hacer.

El módulo contempla mecanismos para:

- Administrar roles.
- Definir permisos.
- Asignar privilegios.
- Consultar roles asociados a una identidad.
- Evaluar condiciones de acceso.
- Proteger recursos y operaciones.
- Separar las reglas de autorización de la lógica de negocio.

La arquitectura busca facilitar la implementación de controles de acceso reutilizables y extensibles.

## 2. Diferencia entre autenticación y autorización

Estos dos conceptos trabajan juntos, pero tienen responsabilidades diferentes.

| Concepto | Responsabilidad | Ejemplo |
|---|---|---|
| Authentication | Identificar al usuario. | Iniciar sesión con correo y contraseña. |
| Authorization | Determinar sus privilegios. | Permitir que un administrador elimine usuarios. |

### Flujo general

```text
                 SOLICITUD DEL USUARIO
                          |
                          v
                  AUTENTICACIÓN
                          |
                  ¿Identidad válida?
                     /         \
                   No           Sí
                   |             |
                   v             v
                 HTTP 401    AUTORIZACIÓN
                                  |
                          ¿Tiene permiso?
                             /       \
                           No         Sí
                           |           |
                           v           v
                        HTTP 403   EJECUTAR
                                   OPERACIÓN
```

**Principio fundamental:** una identidad autenticada no debe considerarse automáticamente autorizada para acceder a todos los recursos de la aplicación.

## 3. Arquitectura del módulo

El módulo Authorization se organiza alrededor de cinco componentes principales.

| Componente | Responsabilidad |
|---|---|
| `AccessControl` | Coordinar los controles de acceso. |
| `PermissionManager` | Administrar permisos. |
| `Permission` | Representar un permiso. |
| `RoleManager` | Administrar roles. |
| `Role` | Representar un rol. |

Namespace principal:

```php
ShieldPHP\Core\Authorization\
```

### Diagrama de componentes

```text
                  USER IDENTITY
                        |
                        |
                        v
                ┌──────────────────┐
                │   AccessControl  │
                │                  │
                │ Control de acceso│
                └────────┬─────────┘
                         |
              ┌──────────┴──────────┐
              |                     |
              v                     v
     ┌──────────────────┐  ┌──────────────────┐
     │  RoleManager     │  │ PermissionManager│
     │                  │  │                  │
     │ Gestión de roles │  │ Gestión permisos │
     └────────┬─────────┘  └────────┬─────────┘
              |                     |
              v                     v
        ┌──────────┐          ┌────────────┐
        │   Role   │          │ Permission │
        │          │          │            │
        └──────────┘          └────────────┘
```

## 4. Modelo de autorización

ShieldPHP contempla una organización de privilegios basada en tres elementos principales:

### 4.1 Identidad

La identidad representa al usuario que ha sido autenticado.

El módulo Authentication proporciona `UserIdentity`, que permite consultar información como:

- Identificador.
- Correo electrónico.
- Estado de la cuenta.
- Roles asociados.

### 4.2 Roles

Un rol representa un conjunto de privilegios asociados a una identidad.

Ejemplos:

| Rol | Descripción |
|---|---|
| `admin` | Administración general del sistema. |
| `moderator` | Moderación de contenidos. |
| `editor` | Administración de publicaciones. |
| `user` | Acceso a funcionalidades de usuario. |
| `guest` | Acceso limitado a recursos públicos. |

Los roles permiten agrupar privilegios para simplificar su asignación y mantenimiento.

### 4.3 Permisos

Un permiso representa una operación específica que puede realizar una identidad.

Ejemplos:

| Permiso | Descripción |
|---|---|
| `users.read` | Consultar usuarios. |
| `users.create` | Registrar usuarios. |
| `users.update` | Actualizar usuarios. |
| `users.delete` | Eliminar usuarios. |
| `posts.create` | Crear publicaciones. |
| `posts.update` | Modificar publicaciones. |
| `posts.delete` | Eliminar publicaciones. |
| `reports.view` | Consultar informes. |
| `settings.manage` | Administrar configuraciones. |

Los nombres anteriores son convenciones recomendadas para las aplicaciones consumidoras; no representan un catálogo incorporado obligatoriamente por ShieldPHP.

## 5. AccessControl

`AccessControl` es el componente central para organizar las decisiones de autorización.

**Namespace:**

```php
ShieldPHP\Core\Authorization\AccessControl
```

### Responsabilidades

- Coordinar las comprobaciones de acceso.
- Evaluar los privilegios de una identidad.
- Separar las reglas de autorización de los controladores.
- Facilitar la protección de recursos.
- Evitar que las operaciones sensibles se ejecuten sin autorización.

### Flujo conceptual

1. La aplicación recibe una solicitud.
2. Se identifica al usuario autenticado.
3. Se determina qué recurso intenta utilizar.
4. Se consulta la política de autorización.
5. Se concede o deniega el acceso.
6. La aplicación ejecuta la operación únicamente cuando está autorizada.

### Ejemplo conceptual

```php
// Identidad obtenida después de autenticar al usuario.
$user = $authenticatedUser;

// Permiso requerido por la operación.
$requiredPermission = 'users.delete';

// La aplicación debe consultar el mecanismo
// de autorización configurado en ShieldPHP.

$authorized = $accessControl->/* comprobación */;

if (!$authorized) {
    throw new RuntimeException(
        'No tienes permiso para realizar esta operación.'
    );
}
```

> Este ejemplo representa el flujo de autorización, no una firma definitiva de método. La API pública de `AccessControl` debe verificarse contra su implementación antes de utilizar un nombre de método concreto.

## 6. PermissionManager

`PermissionManager` administra los permisos disponibles dentro del sistema.

**Namespace:**

```php
ShieldPHP\Core\Authorization\PermissionManager
```

### Responsabilidades

- Gestionar permisos.
- Organizar las capacidades disponibles.
- Facilitar la consulta de privilegios.
- Participar en las decisiones de autorización.
- Mantener separada la administración de permisos de la lógica de negocio.

### Catálogo de permisos recomendado

Para una aplicación administrativa puede utilizarse una estructura como esta:

```text
users/
    users.read
    users.create
    users.update
    users.delete

roles/
    roles.read
    roles.create
    roles.update
    roles.delete

permissions/
    permissions.read
    permissions.assign

reports/
    reports.view
    reports.export

settings/
    settings.read
    settings.manage
```

Esta organización permite mantener una nomenclatura coherente conforme crece la aplicación.

### Recomendaciones

- Utilizar nombres descriptivos.
- Mantener permisos específicos.
- Evitar permisos excesivamente generales.
- No confundir roles con permisos.
- Evitar conceder privilegios innecesarios.
- Documentar los permisos que utiliza cada módulo.

## 7. Permission

`Permission` representa un permiso individual dentro del sistema.

**Namespace:**

```php
ShieldPHP\Core\Authorization\Permission
```

### Concepto

Un permiso describe una capacidad que puede concederse a una identidad o asociarse a un rol.

Ejemplo:

```text
Nombre: users.delete
Descripción: Permite eliminar usuarios.
Recurso: users
Acción: delete
```

### Ejemplos de permisos

| Recurso | Acción | Permiso |
|---|---|---|
| Usuarios | Consultar | `users.read` |
| Usuarios | Crear | `users.create` |
| Usuarios | Modificar | `users.update` |
| Usuarios | Eliminar | `users.delete` |
| Productos | Consultar | `products.read` |
| Productos | Crear | `products.create` |
| Productos | Modificar | `products.update` |
| Productos | Eliminar | `products.delete` |

La estructura y los métodos concretos de la clase `Permission` deben contrastarse con su implementación real.

## 8. RoleManager

`RoleManager` se encarga de administrar los roles del sistema.

**Namespace:**

```php
ShieldPHP\Core\Authorization\RoleManager
```

### Responsabilidades

- Gestionar los roles disponibles.
- Organizar los privilegios asociados.
- Facilitar la administración de roles.
- Participar en la asignación de permisos.
- Mantener la separación entre identidades y privilegios.

### Ejemplo de organización

Una aplicación puede establecer la siguiente distribución:

| Rol | Permisos |
|---|---|
| `admin` | Todos los permisos administrativos necesarios. |
| `moderator` | Consultar y moderar publicaciones. |
| `editor` | Crear y modificar publicaciones. |
| `user` | Consultar y administrar sus propios recursos. |
| `guest` | Acceder únicamente a recursos públicos. |

Esta tabla representa una política de ejemplo. Los permisos efectivos deben configurarse explícitamente en la aplicación.

### Principio de mínimo privilegio

Cada rol debe tener únicamente los permisos necesarios para realizar sus funciones.

Por ejemplo, un editor que puede modificar publicaciones no necesita recibir automáticamente permisos para eliminar usuarios o cambiar configuraciones globales.

## 9. Role

`Role` representa un rol dentro del sistema de autorización.

**Namespace:**

```php
ShieldPHP\Core\Authorization\Role
```

### Concepto

Un rol agrupa privilegios que pueden utilizarse para determinar qué operaciones están permitidas a una identidad.

Ejemplo conceptual:

```text
Rol: editor

Permisos:
    posts.read
    posts.create
    posts.update
```

La asignación de roles debe realizarse mediante un mecanismo controlado para evitar que un usuario pueda incrementar sus privilegios por cuenta propia.

## 10. Relación entre roles y permisos

La relación conceptual entre los componentes puede representarse de la siguiente manera:

```text
            USER
             |
             |
          ROLE
             |
       ┌─────┼─────┐
       |     |     |
       v     v     v
    PERM A PERM B PERM C
       |     |     |
       v     v     v
   RESOURCE RESOURCE RESOURCE
```

Por ejemplo:

Un usuario con el rol `editor` puede tener acceso a:

```text
posts.read
posts.create
posts.update
```

Pero no necesariamente a:

```text
posts.delete
users.delete
settings.manage
```

Este modelo facilita la administración de privilegios sin tener que asignar individualmente cada permiso a todos los usuarios.

## 11. Ejemplo de autorización en una aplicación

El siguiente ejemplo muestra cómo debería utilizarse el resultado de una autorización dentro de un controlador.

```php
<?php

declare(strict_types=1);

function deleteUser(
    string $userId,
    $authenticatedUser,
    $accessControl
) {
    // Verificar que el usuario esté autenticado.
    if ($authenticatedUser === null) {
        throw new RuntimeException(
            'Se requiere autenticación.'
        );
    }

    // Determinar el permiso necesario.
    $permission = 'users.delete';

    // Consultar el mecanismo de autorización.
    // La llamada concreta dependerá de la API
    // implementada por AccessControl.

    $authorized = /* comprobar permiso */;

    if (!$authorized) {
        throw new RuntimeException(
            'No tienes permiso para eliminar usuarios.'
        );
    }

    // Ejecutar la operación únicamente después
    // de una decisión de autorización satisfactoria.

    // $userRepository->delete($userId);
}
```

En aplicaciones HTTP es recomendable traducir los resultados a códigos apropiados:

| Situación | Código HTTP |
|---|---:|
| Usuario no autenticado | 401 |
| Usuario autenticado sin permisos | 403 |
| Recurso no encontrado | 404 |
| Operación autorizada | Según el resultado de la operación |

La comprobación de autorización debe realizarse en el servidor, no solamente en la interfaz de usuario.

## 12. Integración con Authentication

El módulo Authorization trabaja junto al módulo Authentication.

La integración sigue esta separación:

| Componente | Responsabilidad |
|---|---|
| `AuthManager` | Verificar las credenciales. |
| `AuthResult` | Representar el resultado de autenticación. |
| `UserIdentity` | Representar la identidad autenticada. |
| `AccessControl` | Evaluar el acceso. |
| `RoleManager` | Administrar roles. |
| `PermissionManager` | Administrar permisos. |

`UserIdentity` proporciona, entre otros métodos:

```php
$user->getRoles();

$user->hasRole('admin');
```

Estos métodos permiten consultar los roles disponibles en la identidad, pero comprobar un rol no sustituye todas las comprobaciones de permisos necesarias.

Una aplicación debe evaluar también el recurso, la operación y las condiciones de acceso correspondientes.

## 13. Integración con JWT

Authorization puede utilizarse junto al módulo JWT.

El token permite transportar declaraciones de identidad y otros datos necesarios para una solicitud autenticada.

Sin embargo, los roles y permisos incluidos en un token pueden quedar desactualizados cuando cambian los privilegios de un usuario.

Por esta razón, una aplicación debe definir una estrategia para mantener las decisiones de autorización actualizadas.

### Recomendaciones

- Utilizar expiraciones razonables.
- Mantener mecanismos de revocación.
- No confiar exclusivamente en roles antiguos incluidos en tokens de larga duración.
- Consultar una fuente confiable cuando se necesite comprobar permisos actuales.
- Invalidar o renovar credenciales después de cambios críticos de privilegios.

## 14. Control de acceso a recursos propios

Además de comprobar roles y permisos, una aplicación debe verificar la propiedad de los recursos.

Por ejemplo, el permiso `posts.update` no significa necesariamente que un usuario pueda modificar cualquier publicación.

Puede establecerse una política como:

```text
Administrador:
    Puede modificar todas las publicaciones.

Editor:
    Puede modificar publicaciones autorizadas.

Usuario:
    Puede modificar únicamente sus propias publicaciones.

Invitado:
    No puede modificar publicaciones.
```

La autorización debe contemplar tanto los permisos generales como las condiciones particulares del recurso.

Este patrón ayuda a prevenir accesos indebidos relacionados con referencias directas a identificadores de objetos.

## 15. Recomendaciones de seguridad

El módulo Authorization debe utilizarse siguiendo estas prácticas:

### Principio de mínimo privilegio

Conceder únicamente los permisos indispensables.

### Denegación por defecto

Si no existe una autorización explícita, la operación debe rechazarse.

### Validación en el servidor

Nunca confiar exclusivamente en los controles de la interfaz de usuario.

### Separación de responsabilidades

Mantener las decisiones de autorización fuera de los controladores siempre que sea posible.

### Protección contra escalada de privilegios

Impedir que usuarios normales asignen roles administrativos o modifiquen sus propios permisos sin autorización.

### Validación de recursos

Comprobar que el usuario tenga acceso al recurso solicitado, no solamente que posea un permiso general.

### Auditoría

Registrar eventos relevantes, como:

- Cambios de roles.
- Asignación de permisos.
- Revocación de privilegios.
- Accesos administrativos.
- Intentos de operaciones no autorizadas.

Los registros no deben contener tokens, contraseñas ni otros secretos.

## 16. Pruebas

El módulo Authorization dispone de una estructura de pruebas unitarias dentro de:

```text
packages/php-core/tests/Unit/Authorization/
```

Los componentes principales cuentan con pruebas orientadas a comprobar:

| Componente | Cobertura prevista |
|---|---|
| `AccessControl` | Evaluación de controles de acceso. |
| `PermissionManager` | Administración y consulta de permisos. |
| `Permission` | Comportamiento de permisos individuales. |
| `RoleManager` | Administración de roles. |
| `Role` | Comportamiento de roles individuales. |

### Ejecutar las pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

Para ejecutar exclusivamente las pruebas del módulo:

```bash
vendor/bin/phpunit tests/Unit/Authorization
```

### Análisis estático

```bash
composer analyse
```

Se recomienda incorporar pruebas de integración que comprueben las relaciones entre identidades, roles, permisos y recursos protegidos.

## 17. Estructura del módulo

```text
packages/php-core/
│
├── src/
│   ├── Authorization/
│   │   ├── AccessControl.php
│   │   ├── PermissionManager.php
│   │   ├── Permission.php
│   │   ├── RoleManager.php
│   │   └── Role.php
│   │
│   └── Auth/
│       └── UserIdentity.php
│
└── tests/
    └── Unit/
        └── Authorization/
            ├── AccessControlTest.php
            ├── PermissionManagerTest.php
            ├── PermissionTest.php
            ├── RoleManagerTest.php
            └── RoleTest.php
```

La estructura refleja los componentes principales documentados y no presupone un mecanismo de persistencia específico.

## 18. Funcionalidades y estado actual

| Funcionalidad | Estado |
|---|---|
| AccessControl | Base implementada |
| PermissionManager | Base implementada |
| Permission | Base implementada |
| RoleManager | Base implementada |
| Role | Base implementada |
| Organización de roles | En desarrollo |
| Administración de permisos | En desarrollo |
| Evaluación de acceso | En desarrollo |
| Integración con UserIdentity | Requiere validación |
| Integración con JWT | Pendiente de completar |
| Políticas de propiedad de recursos | Pendiente |
| Persistencia de roles y permisos | Pendiente de adaptadores |
| Auditoría de autorización | Pendiente |
| Middleware de autorización | Pendiente de integración |
| Pruebas de integración | Pendientes |

## 19. Consideraciones de desarrollo

Authorization debe mantenerse independiente de la infraestructura de almacenamiento y del framework HTTP utilizado.

Esto permitirá utilizar sus mecanismos desde diferentes tipos de aplicaciones:

- Aplicaciones PHP tradicionales.
- API REST.
- Aplicaciones Slim Framework.
- Aplicaciones Laravel.
- Sistemas administrativos.
- Servicios y microservicios PHP.

Los adaptadores de cada entorno deben encargarse de traducir las decisiones de autorización a sus respectivos mecanismos de respuesta.

---

## 20. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authentication.md` — Autenticación de usuarios.
- `docs/configuration.md` — Configuración del Core.
- `docs/jwt.md` — Administración de tokens.
- `SECURITY.md` — Política de seguridad.
- `CHANGELOG.md` — Registro de cambios.

### Referencias externas

- [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html)
- [OWASP Access Control](https://owasp.org/www-community/Access_Control)
- [NIST Computer Security Resource Center](https://csrc.nist.gov/)
- [PHP Documentation](https://www.php.net/docs.php)

---

<p align="center">
  <strong>ShieldPHP Authorization</strong>
  <br>
  Roles, permisos y control de acceso para PHP.
</p>