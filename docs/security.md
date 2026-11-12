# Security — ShieldPHP

<p align="center">
  <strong>Arquitectura, mecanismos de protección y buenas prácticas de seguridad para PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Security-Application%20Security-blue?style=for-the-badge" alt="Application Security">
  <img src="https://img.shields.io/badge/Cryptography-libsodium-purple?style=for-the-badge" alt="libsodium">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

**Security** es uno de los principios fundamentales de ShieldPHP.

El objetivo del proyecto es proporcionar componentes reutilizables que permitan implementar mecanismos de protección en aplicaciones PHP mediante estándares reconocidos y prácticas de desarrollo seguro.

ShieldPHP contempla diferentes áreas de seguridad:

- Autenticación de usuarios.
- Autorización y control de acceso.
- Protección de contraseñas.
- Administración de sesiones.
- Seguridad de cookies.
- Firma y validación de JWT.
- Autorización OAuth 2.0.
- Protección criptográfica de información.
- Validación de entradas.
- Protección de solicitudes HTTP.
- Administración de claves.
- Gestión de errores y eventos de seguridad.

La seguridad debe considerarse una responsabilidad transversal: cada módulo debe proteger sus propias operaciones y trabajar de manera coordinada con los demás componentes.

> **Estado del proyecto:** ShieldPHP se encuentra en desarrollo. Las medidas descritas en este documento representan la arquitectura de seguridad prevista y las capacidades iniciales del proyecto. No todas están implementadas o auditadas completamente.

## 2. Principios de seguridad

ShieldPHP utiliza los siguientes principios como referencia para su desarrollo.

### 2.1 Seguridad por diseño

Los mecanismos de protección deben formar parte de la arquitectura desde el inicio y no agregarse únicamente cuando aparecen problemas.

### 2.2 Mínimo privilegio

Cada usuario, servicio y componente debe recibir exclusivamente los permisos necesarios para cumplir su función.

### 2.3 Denegación por defecto

Cuando no exista una autorización explícita, el acceso debe rechazarse.

### 2.4 Separación de responsabilidades

La autenticación, autorización, criptografía, administración de sesiones y comunicación HTTP deben mantenerse desacopladas.

### 2.5 Defensa en profundidad

La seguridad no debe depender de una sola medida. Los componentes deben complementarse para reducir la posibilidad de compromiso.

### 2.6 Protección de información sensible

Las contraseñas, claves, tokens y credenciales deben protegerse durante su generación, almacenamiento, transmisión y utilización.

### 2.7 Validación de todas las entradas

Los datos externos deben considerarse no confiables hasta que hayan sido validados.

## 3. Arquitectura de seguridad

ShieldPHP organiza los mecanismos de protección en módulos independientes.

| Módulo | Responsabilidad de seguridad |
|---|---|
| Auth | Verificación de identidad y credenciales. |
| Authorization | Roles, permisos y controles de acceso. |
| Config | Validación de parámetros de seguridad. |
| Crypto | Cifrado, derivación de claves y firmas. |
| JWT | Generación, validación y revocación de tokens. |
| OAuth | Integración segura con proveedores externos. |
| Session | Protección del ciclo de vida de las sesiones. |
| Validation | Validación de entradas y direcciones. |
| Middleware | Aplicación de controles sobre solicitudes HTTP. |
| Exceptions | Manejo controlado de errores. |

### Diagrama general

```text
                       APLICACIÓN PHP
                             |
                             v
                     CAPA DE SEGURIDAD
                             |
           ┌─────────────────┼──────────────────┐
           |                 |                  |
           v                 v                  v
      AUTHENTICATION     AUTHORIZATION        VALIDATION
           |                 |                  |
           v                 v                  v
       Identidad          Permisos          Datos seguros
           |                 |                  |
           └─────────────────┼──────────────────┘
                             |
                             v
                      CAPA DE PROTECCIÓN
                             |
             ┌───────────────┼────────────────┐
             |               |                |
             v               v                v
            JWT            OAuth            Crypto
             |               |                |
             └───────────────┼────────────────┘
                             |
                             v
                     RECURSOS PROTEGIDOS
```

## 4. Modelo de amenazas

Durante el desarrollo de ShieldPHP deben considerarse diferentes amenazas que pueden comprometer aplicaciones y servicios.

| Amenaza | Descripción | Protección recomendada |
|---|---|---|
| Fuerza bruta | Intentos repetidos de adivinar contraseñas. | Limitación de solicitudes y controles de autenticación. |
| Enumeración de usuarios | Descubrimiento de cuentas mediante respuestas diferentes. | Mensajes genéricos y comportamiento consistente. |
| Escalada de privilegios | Obtención de permisos superiores a los autorizados. | Control de acceso y validación de permisos. |
| Secuestro de sesión | Uso no autorizado de una sesión. | Cookies seguras y protección de identificadores. |
| Fijación de sesión | Reutilización de identificadores de sesión controlados. | Regeneración de identificadores después de autenticar. |
| Manipulación JWT | Alteración de declaraciones o firmas. | Validación criptográfica estricta. |
| Repetición de tokens | Reutilización de credenciales comprometidas. | Expiración, revocación y rotación cuando corresponda. |
| CSRF | Ejecución de operaciones mediante solicitudes no deseadas. | Tokens CSRF y controles de origen. |
| Redirecciones abiertas | Envío de usuarios a destinos no autorizados. | Listas estrictas de URI permitidas. |
| SSRF | Solicitudes del servidor hacia destinos no autorizados. | Restricción de hosts y validación de destinos. |
| Exposición de secretos | Filtración de claves y credenciales. | Almacenamiento seguro y control de registros. |
| Inyección | Ejecución de instrucciones mediante entradas no validadas. | Consultas parametrizadas y validación contextual. |

Este modelo es una referencia inicial y no constituye un análisis de amenazas completo de todas las aplicaciones que utilicen ShieldPHP.

## 5. Seguridad de autenticación

El módulo Authentication administra la verificación de las credenciales.

Componentes principales:

```text
AuthManager
AuthResult
PasswordHasher
PasswordPolicy
UserIdentity
```

### Protección de contraseñas

ShieldPHP contempla `PasswordHasher`, que utiliza la API nativa de contraseñas de PHP mediante `PASSWORD_DEFAULT`.

Operaciones principales:

```php
$hash = $passwordHasher->hash($password);

$isValid = $passwordHasher->verify(
    $password,
    $storedHash
);

$needsRehash = $passwordHasher->needsRehash(
    $storedHash
);
```

### Recomendaciones

- No almacenar contraseñas en texto plano.
- No utilizar cifrado reversible para almacenar contraseñas.
- Utilizar mecanismos de hash diseñados para contraseñas.
- No registrar contraseñas ni hashes.
- Actualizar hashes cuando sea necesario.
- Proteger los endpoints de autenticación mediante limitación de solicitudes.
- Utilizar conexiones HTTPS.

### Protección contra enumeración

Los errores de autenticación deben proporcionar respuestas genéricas.

Por ejemplo:

```json
{
    "success": false,
    "error": "invalid_credentials",
    "message": "No fue posible autenticar al usuario."
}
```

La respuesta no debería revelar si el correo electrónico existe, si la contraseña es incorrecta o si la cuenta está inactiva.

También deben evitarse diferencias temporales evidentes entre los escenarios de autenticación fallida.

## 6. Seguridad de autorización

El módulo Authorization determina qué operaciones puede ejecutar una identidad autenticada.

Componentes principales:

```text
AccessControl
PermissionManager
Permission
RoleManager
Role
```

### Principios de autorización

**Mínimo privilegio:** no conceder permisos innecesarios.

**Denegación por defecto:** una operación debe rechazarse cuando no exista una autorización válida.

**Separación de responsabilidades:** autenticar a un usuario no significa que tenga permisos administrativos.

### Ejemplo conceptual

```php
if ($authenticatedUser === null) {
    throw new RuntimeException(
        'Se requiere autenticación.'
    );
}

// Comprobar el permiso antes de ejecutar
// la operación administrativa.

// La aplicación debe utilizar el mecanismo
// de autorización configurado en ShieldPHP.

if (!$isAuthorized) {
    throw new RuntimeException(
        'Operación no autorizada.'
    );
}
```

### Protección de recursos

Además de comprobar roles y permisos, es necesario verificar la propiedad de los recursos.

Por ejemplo, un usuario que tiene permiso para modificar publicaciones no necesariamente puede modificar las publicaciones pertenecientes a otros usuarios.

La aplicación debe comprobar tanto el permiso requerido como las condiciones particulares del recurso.

## 7. Seguridad criptográfica

El módulo Crypto utiliza primitivas criptográficas de libsodium.

Componentes principales:

| Componente | Responsabilidad |
|---|---|
| `Encryption` | Cifrado y descifrado. |
| `KeyDerivation` | Derivación de claves. |
| `KeyManager` | Administración de claves. |
| `RandomGenerator` | Generación de valores aleatorios. |
| `Signature` | Firmas digitales y verificación. |

### Cifrado simétrico

La implementación inicial de `Encryption` utiliza:

```php
sodium_crypto_secretbox();
```

y:

```php
sodium_crypto_secretbox_open();
```

Características:

- Clave de 32 bytes.
- Nonce de 24 bytes.
- Autenticación criptográfica del contenido.
- Formato versionado `shp:v1:`.
- Codificación Base64 para representar los datos.

El uso de SecretBox permite proteger la confidencialidad y detectar modificaciones del contenido cifrado.

### Generación de claves

Las claves deben generarse mediante fuentes criptográficamente seguras.

Ejemplo:

```php
$encryptionKey = random_bytes(
    SODIUM_CRYPTO_SECRETBOX_KEYBYTES
);
```

Para almacenar la clave en Base64:

```php
$encodedKey = base64_encode($encryptionKey);
```

La codificación Base64 no cifra ni protege por sí sola la clave.

### Firmas digitales

ShieldPHP contempla firmas mediante Ed25519.

La clave privada se utiliza para firmar y la clave pública para verificar.

Una firma digital proporciona autenticidad e integridad, pero no oculta la información firmada.

### Recomendaciones criptográficas

- No diseñar algoritmos criptográficos propios.
- No reutilizar claves para diferentes propósitos.
- No reutilizar nonces con una misma clave cuando el algoritmo requiera su unicidad.
- Mantener protegidas las claves privadas.
- Evitar almacenar claves junto a los datos cifrados sin una protección adicional.
- Definir procedimientos de rotación y recuperación.
- Verificar siempre las firmas antes de confiar en los datos.

## 8. Seguridad JWT

El módulo JWT utiliza Firebase PHP-JWT para las operaciones correspondientes.

Componentes principales:

```text
JWTConfig
JWTClaims
JWTManager
TokenValidator
TokenBlacklist
TokenPair
TokenRevocationStoreInterface
```

### Validaciones necesarias

Un token recibido debe comprobarse antes de autorizar una operación.

Entre las verificaciones necesarias se encuentran:

- Firma criptográfica.
- Algoritmo permitido.
- Emisor (`iss`).
- Audiencia (`aud`).
- Expiración (`exp`).
- Momento de validez (`nbf`).
- Identificador único (`jti`), cuando se utiliza revocación.

No debe confiarse en el payload de un token únicamente porque pueda decodificarse.

### Algoritmos admitidos por la configuración inicial

| Algoritmo | Tamaño mínimo de clave |
|---|---:|
| HS256 | 32 bytes |
| HS384 | 48 bytes |
| HS512 | 64 bytes |

La configuración inicial contempla algoritmos HMAC.

No debe aceptarse un algoritmo seleccionado arbitrariamente por el cliente.

### Expiración

Los valores iniciales contemplados son:

| Token | Duración |
|---|---:|
| Access Token | 15 minutos |
| Refresh Token | 7 días |

Una expiración corta limita el tiempo de exposición de los tokens comprometidos.

### Revocación

La revocación permite rechazar tokens antes de su expiración.

`TokenBlacklist` proporciona almacenamiento en memoria, apropiado para escenarios de desarrollo y pruebas.

Para producción se recomienda implementar almacenamiento persistente y compartido, como Redis o una base de datos.

### Refresh Token Rotation

La rotación de refresh tokens debe implementarse de manera que:

1. Un refresh token válido permita obtener nuevas credenciales.
2. El token anterior quede invalidado.
3. El nuevo token se almacene de manera protegida.
4. La reutilización de tokens anteriores pueda detectarse.
5. Las sesiones comprometidas puedan revocarse.

La rotación completa y la detección de reutilización todavía requieren implementación y pruebas adicionales en ShieldPHP.

## 9. Seguridad OAuth 2.0

El módulo OAuth permite integrar proveedores externos.

Componentes principales:

```text
OAuthManager
OAuthClient
OAuthStateManager
PKCEManager
TokenResponse
AuthorizationUrlBuilder
CallbackHandler
```

### Protección mediante state

El parámetro `state` ayuda a vincular una respuesta OAuth con la solicitud original.

Debe:

- Generarse mediante una fuente segura.
- Almacenarse temporalmente.
- Vincularse a la transacción iniciada.
- Validarse durante el callback.
- Consumirse una sola vez.
- Expirar después del periodo configurado.

### Protección PKCE

PKCE protege el intercambio de códigos de autorización.

Se recomienda utilizar el método:

```text
S256
```

El verificador debe mantenerse secreto durante el proceso y utilizarse en el intercambio correspondiente.

La disponibilidad de `PKCEManager` no garantiza que todos los proveedores externos admitan el mismo flujo. Debe comprobarse la compatibilidad de cada proveedor.

### Protección de URI de retorno

Las URI de redirección deben validarse mediante una lista de destinos autorizados.

No deben aceptarse redirecciones arbitrarias proporcionadas por usuarios.

### Seguridad HTTP

`OAuthClient` contempla:

- Conexiones HTTPS.
- Restricción de hosts.
- Solicitudes mediante interfaces PSR.
- Deshabilitación de redirecciones automáticas.

Estas medidas ayudan a reducir riesgos relacionados con solicitudes hacia destinos externos no autorizados.

### Protección de tokens externos

Los tokens recibidos de proveedores externos deben tratarse como credenciales sensibles.

No deben almacenarse ni registrarse sin aplicar medidas adecuadas de protección.

## 10. OpenID Connect e identificación de usuarios

OAuth 2.0 proporciona autorización delegada, mientras que OpenID Connect proporciona mecanismos estandarizados de autenticación.

Para proveedores como Google, el `id_token` debe validarse criptográficamente antes de confiar en las declaraciones de identidad.

Las verificaciones deben contemplar:

- Firma mediante claves públicas confiables.
- Emisor (`iss`).
- Audiencia (`aud`).
- Expiración (`exp`).
- Identificador de usuario (`sub`).
- `nonce`, cuando corresponda.

No es suficiente decodificar un JWT para considerarlo auténtico.

Para vincular identidades externas se recomienda utilizar la combinación del proveedor y su identificador externo estable.

Por ejemplo:

```text
provider = google
provider_user_id = GOOGLE_SUB
```

No se deben vincular automáticamente cuentas únicamente porque sus direcciones de correo coincidan.

La validación completa de ID Tokens requiere completar el verificador específico de OpenID Connect en el Core.

## 11. Seguridad de sesiones

El módulo Session contempla componentes para la administración de sesiones y cookies.

```text
SessionConfig
SessionManager
CookieManager
```

### Protección de sesiones

Se recomienda:

- Activar el modo estricto de sesiones.
- Regenerar el identificador después de autenticar.
- Destruir sesiones durante el cierre de sesión.
- Evitar exponer identificadores de sesión.
- Configurar correctamente su expiración.
- No almacenar secretos innecesarios en la sesión.
- Proteger el acceso a los datos almacenados.

### Protección de cookies

Se deben configurar adecuadamente los atributos:

| Atributo | Función |
|---|---|
| `Secure` | Restringe la transmisión a conexiones seguras. |
| `HttpOnly` | Impide el acceso directo mediante JavaScript. |
| `SameSite` | Controla el envío de cookies en contextos entre sitios. |
| `Path` | Limita la ruta de envío de la cookie. |
| `Domain` | Determina los dominios autorizados para recibirla. |

### Ejemplo de configuración

```dotenv
SHIELDPHP_SESSION_COOKIE_SECURE=true
SHIELDPHP_SESSION_COOKIE_HTTPONLY=true
SHIELDPHP_SESSION_COOKIE_SAMESITE=Lax

SHIELDPHP_SESSION_USE_STRICT_MODE=true
SHIELDPHP_SESSION_USE_ONLY_COOKIES=true
SHIELDPHP_SESSION_USE_TRANS_SID=false
```

Los valores deben adaptarse a la arquitectura y necesidades de la aplicación.

## 12. Protección CSRF

Los ataques Cross-Site Request Forgery buscan ejecutar operaciones en nombre de un usuario autenticado mediante solicitudes que no inició conscientemente.

Las aplicaciones que utilizan autenticación basada en cookies deben implementar protección CSRF adecuada.

Entre las medidas recomendadas se encuentran:

- Tokens CSRF impredecibles.
- Validación del token en operaciones sensibles.
- Uso apropiado de cookies `SameSite`.
- Validación de origen cuando corresponda.
- No utilizar operaciones de modificación de datos mediante solicitudes GET.
- Evitar confiar exclusivamente en controles del frontend.

La plantilla de configuración contempla:

```dotenv
SHIELDPHP_CSRF_ENABLED=true
SHIELDPHP_CSRF_TOKEN_NAME=_csrf
SHIELDPHP_CSRF_HEADER=X-CSRF-TOKEN
```

La presencia de estas opciones no significa que exista un mecanismo CSRF universal completamente integrado en todos los adaptadores.

## 13. Validación de entradas

Los datos recibidos desde clientes y servicios externos deben tratarse como no confiables.

ShieldPHP contempla un módulo Validation para apoyar la validación de diferentes tipos de información.

Se recomienda comprobar:

- Tipo de dato.
- Longitud.
- Formato.
- Rango permitido.
- Reglas específicas del negocio.
- URI de retorno.
- Datos recibidos de proveedores externos.

### Protección contra inyecciones

La validación de entradas no sustituye las medidas específicas de cada tecnología.

Por ejemplo, al acceder a una base de datos deben utilizarse consultas parametrizadas.

Ejemplo:

```php
$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE email = :email'
);

$stmt->execute([
    'email' => $email
]);
```

Nunca deben construirse consultas SQL concatenando directamente datos proporcionados por el usuario.

### Protección XSS

Los datos deben escaparse en el contexto en que se muestran.

Una aplicación web debe evitar insertar directamente contenido externo en HTML sin un proceso de codificación adecuado.

## 14. Seguridad de comunicaciones HTTP

Los componentes que realizan solicitudes externas deben aplicar controles específicos.

En particular, `OAuthClient` contempla restricciones para limitar los destinos a los que ShieldPHP puede realizar solicitudes.

### Recomendaciones

- Utilizar HTTPS.
- Verificar certificados TLS.
- Restringir los dominios autorizados.
- Evitar redirecciones automáticas no verificadas.
- Definir tiempos de espera.
- Validar códigos de respuesta.
- Limitar el tamaño de las respuestas.
- Rechazar contenido inesperado.
- No enviar credenciales a hosts desconocidos.

También deben considerarse los riesgos de SSRF cuando una aplicación acepta destinos HTTP proporcionados por usuarios.

## 15. Administración de secretos

Los secretos representan información que permite acceder a recursos o ejecutar operaciones privilegiadas.

Ejemplos:

- Claves JWT.
- Claves de cifrado.
- Claves privadas.
- Client Secrets OAuth.
- Credenciales de bases de datos.
- Tokens de acceso.
- Tokens de actualización.

### Reglas de protección

- No almacenar secretos en el código fuente.
- No publicar credenciales en GitHub.
- No compartir secretos entre diferentes entornos.
- Limitar permisos de lectura.
- Evitar imprimir secretos en registros.
- Utilizar gestores de secretos cuando corresponda.
- Establecer procedimientos de rotación.
- Mantener respaldos protegidos cuando sean necesarios.

### Archivos de entorno

ShieldPHP proporciona:

```text
.env.example
```

Este archivo contiene nombres y valores de referencia, pero nunca debe incluir credenciales reales.

El archivo `.env` debe permanecer excluido del control de versiones.

## 16. Manejo de errores y excepciones

ShieldPHP contempla excepciones específicas para representar errores de diferentes módulos.

Entre ellas:

| Excepción | Responsabilidad |
|---|---|
| `ShieldException` | Excepción general. |
| `AuthenticationException` | Errores de autenticación. |
| `ConfigurationException` | Configuración inválida. |
| `EncryptionException` | Errores criptográficos. |
| `ExpiredTokenException` | Tokens expirados. |
| `InvalidCredentialsException` | Credenciales incorrectas. |
| `InvalidTokenException` | Tokens inválidos. |

### Recomendaciones

- No exponer rutas internas ni trazas en respuestas públicas.
- No revelar claves ni credenciales.
- Utilizar respuestas HTTP apropiadas.
- Mantener mensajes públicos genéricos cuando corresponda.
- Registrar los errores necesarios para el diagnóstico.
- No ocultar fallos críticos de configuración.

Los detalles técnicos pueden registrarse internamente siempre que no incluyan información sensible.

## 17. Registro y auditoría de eventos

Los registros de seguridad ayudan a detectar actividades sospechosas e investigar incidentes.

Se recomienda registrar eventos como:

- Intentos de autenticación fallidos.
- Inicios y cierres de sesión.
- Cambios de contraseña.
- Cambios de roles.
- Modificaciones de permisos.
- Revocación de tokens.
- Fallos de validación OAuth.
- Errores criptográficos.
- Operaciones administrativas sensibles.

### Información que no debe registrarse

- Contraseñas.
- Hashes de contraseñas.
- Claves privadas.
- App Secrets.
- Tokens completos.
- Verificadores PKCE.
- Identificadores de sesión activos.
- Datos personales innecesarios.

La configuración de logging contempla:

```dotenv
SHIELDPHP_LOG_ENABLED=true
SHIELDPHP_LOG_LEVEL=info
SHIELDPHP_LOG_CHANNEL=default
```

Los mecanismos de registro y auditoría deben integrarse mediante los adaptadores correspondientes.

## 18. Seguridad en producción

Antes de desplegar una aplicación que utiliza ShieldPHP, se recomienda revisar los siguientes aspectos.

### Configuración general

- [ ] Desactivar el modo de depuración.
- [ ] Utilizar HTTPS.
- [ ] Configurar dominios y URI permitidos.
- [ ] Revisar las dependencias.
- [ ] Mantener actualizadas las extensiones PHP.
- [ ] Proteger los archivos de entorno.

### Autenticación

- [ ] Utilizar hashes seguros para contraseñas.
- [ ] Aplicar limitación de solicitudes.
- [ ] Utilizar respuestas genéricas.
- [ ] Configurar políticas de contraseñas.
- [ ] Proteger los mecanismos de recuperación de cuentas.

### JWT

- [ ] Utilizar claves suficientemente largas.
- [ ] Restringir los algoritmos permitidos.
- [ ] Validar emisor y audiencia.
- [ ] Verificar expiraciones.
- [ ] Implementar revocación cuando corresponda.
- [ ] Proteger refresh tokens.

### OAuth

- [ ] Validar `state`.
- [ ] Utilizar PKCE cuando sea compatible.
- [ ] Validar URI de redirección.
- [ ] Proteger credenciales de clientes.
- [ ] Verificar las identidades externas.
- [ ] No confiar únicamente en perfiles obtenidos mediante solicitudes no verificadas.

### Criptografía

- [ ] Utilizar claves generadas de forma segura.
- [ ] Separar claves por propósito.
- [ ] Proteger las claves privadas.
- [ ] Mantener respaldos protegidos.
- [ ] Definir estrategias de rotación.

### Autorización

- [ ] Aplicar mínimo privilegio.
- [ ] Denegar operaciones por defecto.
- [ ] Comprobar permisos en el servidor.
- [ ] Validar la propiedad de los recursos.
- [ ] Registrar cambios de privilegios.

## 19. Pruebas de seguridad

ShieldPHP contempla una suite específica para pruebas relacionadas con seguridad.

Directorio:

```text
packages/php-core/tests/Security/
```

También cuenta con pruebas unitarias y de integración para comprobar los componentes individuales y sus interacciones.

### Escenarios recomendados

| Área | Prueba |
|---|---|
| Auth | Credenciales incorrectas y cuentas inactivas. |
| Password | Hash y verificación de contraseñas. |
| Authorization | Intentos de acceso sin privilegios. |
| JWT | Firmas incorrectas y tokens expirados. |
| JWT | Revocación y reutilización de tokens. |
| OAuth | Estados inválidos y expirados. |
| PKCE | Verificadores incorrectos. |
| Redirect URI | Redirecciones no autorizadas. |
| Encryption | Manipulación de datos cifrados. |
| Signature | Verificación de firmas inválidas. |
| Session | Regeneración y protección de identificadores. |
| Input | Entradas inválidas y límites de datos. |

### Ejecutar las pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

Ejecutar las pruebas específicas de seguridad:

```bash
vendor/bin/phpunit tests/Security
```

### Análisis estático

```bash
composer analyse
```

Las pruebas automatizadas son una parte del proceso de seguridad, pero no sustituyen una revisión manual ni una auditoría independiente.

## 20. Seguridad en integraciones

ShieldPHP contempla adaptadores para diferentes frameworks.

### Slim Framework

La integración dispone de:

```text
slim/QuickAuthMiddleware.php
```

Su propósito es proteger rutas HTTP mediante validación JWT.

La aplicación debe seguir verificando permisos y reglas de negocio después de autenticar la solicitud.

### Laravel

La integración se encuentra en:

```text
integrations/laravel/
```

Contempla una estructura inicial para registrar servicios, configuración y middleware.

Ambas integraciones requieren completar y validar sus mecanismos de seguridad antes de utilizarse como soluciones listas para producción.

## 21. Gestión de vulnerabilidades

ShieldPHP mantiene una política específica para reportar y gestionar vulnerabilidades.

El archivo correspondiente se encuentra en:

```text
SECURITY.md
```

Los investigadores y colaboradores deben utilizar los mecanismos privados de comunicación cuando reporten vulnerabilidades que todavía no han sido corregidas.

No deben publicarse detalles explotables de vulnerabilidades sin coordinar previamente con los responsables del proyecto.

Para más información consulta la política de seguridad de la raíz del repositorio.

## 22. Estándares y buenas prácticas

ShieldPHP utiliza como referencia diferentes estándares y guías de seguridad.

| Referencia | Aplicación |
|---|---|
| OWASP ASVS | Requisitos de seguridad de aplicaciones. |
| OWASP Cheat Sheet Series | Recomendaciones prácticas de protección. |
| OAuth 2.0 RFC 6749 | Autorización delegada. |
| OAuth 2.0 Security BCP RFC 9700 | Seguridad de implementaciones OAuth. |
| RFC 7519 | JSON Web Tokens. |
| RFC 8725 | Buenas prácticas para JWT. |
| RFC 7636 | PKCE. |
| NIST | Referencias de gestión de seguridad. |
| PHP Security | Prácticas de programación segura en PHP. |

## 23. Estado actual de seguridad

La siguiente tabla distingue las capacidades iniciales del proyecto de las funcionalidades que todavía requieren integración o validación.

| Funcionalidad | Estado |
|---|---|
| Hash de contraseñas | Base implementada |
| Políticas de contraseñas | Base implementada |
| Respuestas genéricas de autenticación | Contempladas |
| Control de acceso | Base implementada |
| Cifrado SecretBox | Implementado inicialmente |
| Derivación de claves | Base implementada |
| Generación aleatoria segura | Base implementada |
| Firmas Ed25519 | Base implementada |
| Validación JWT | Base implementada |
| Lista de revocación en memoria | Implementación inicial |
| Revocación persistente | Pendiente |
| OAuth state | Base implementada |
| PKCE | Base implementada |
| Almacenamiento persistente OAuth | Pendiente |
| Validación completa de ID Tokens | Pendiente de completar |
| Protección de sesiones | Requiere validación |
| Integración CSRF | Pendiente |
| Limitación de solicitudes | Pendiente de integración |
| Adaptadores de auditoría | Pendientes |
| Auditoría externa de seguridad | No realizada |

## 24. Limitaciones y responsabilidad

ShieldPHP proporciona componentes de seguridad que deben integrarse correctamente dentro de cada aplicación.

Su utilización no garantiza por sí misma que una aplicación sea invulnerable.

La seguridad final depende de diferentes factores:

- Implementación de los componentes.
- Configuración de la aplicación.
- Protección de la infraestructura.
- Administración de secretos.
- Controles de acceso.
- Dependencias externas.
- Procedimientos operativos.
- Revisiones y pruebas de seguridad.

El proyecto no debe considerarse certificado ni auditado de manera independiente mientras no exista evidencia verificable de dichas actividades.

## 25. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Autorización.
- `docs/configuration.md` — Configuración.
- `docs/encryption.md` — Criptografía.
- `docs/jwt.md` — JSON Web Tokens.
- `docs/oauth2.md` — OAuth 2.0.
- `docs/google-login.md` — Google Login.
- `docs/facebook-login.md` — Facebook Login.
- `SECURITY.md` — Política de reporte de vulnerabilidades.

### Referencias externas

- [OWASP Foundation](https://owasp.org/)
- [OWASP Application Security Verification Standard](https://owasp.org/www-project-application-security-verification-standard/)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security](https://www.php.net/manual/en/security.php)
- [PHP Sodium](https://www.php.net/manual/en/book.sodium.php)
- [RFC 9700 — OAuth 2.0 Security Best Current Practice](https://www.rfc-editor.org/rfc/rfc9700)
- [RFC 8725 — JSON Web Token Best Current Practices](https://www.rfc-editor.org/rfc/rfc8725)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)

---

<p align="center">
  <strong>ShieldPHP Security</strong>
  <br>
  Seguridad por diseño, protección por capas y desarrollo responsable para PHP.
</p>