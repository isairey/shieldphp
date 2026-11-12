# Troubleshooting — ShieldPHP

<p align="center">
  **Guía de diagnóstico y solución de problemas**
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Project-ShieldPHP-blue?style=for-the-badge" alt="ShieldPHP">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

Esta guía reúne problemas frecuentes que pueden aparecer durante la instalación, configuración, integración y ejecución de **ShieldPHP**.

Su objetivo es ayudar a identificar las causas de los errores y proporcionar procedimientos de diagnóstico y posibles soluciones.

Los problemas se organizan por categorías:

- Requisitos de PHP.
- Composer y dependencias.
- Autoload y namespaces.
- Configuración.
- Autenticación.
- Contraseñas.
- JWT.
- OAuth 2.0.
- Cifrado.
- Sesiones y cookies.
- Integraciones HTTP.
- Pruebas automatizadas.
- Git y GitHub Actions.

ShieldPHP se encuentra en desarrollo. Algunos componentes y funcionalidades todavía requieren validación o integración, por lo que esta guía distingue entre problemas de configuración y funcionalidades que aún no están terminadas.

## 2. Diagnóstico inicial

Antes de modificar archivos o dependencias, conviene recopilar información básica del entorno.

### 2.1 Comprobar la versión de PHP

Ejecuta:

```bash
php -v
```

ShieldPHP establece PHP 8.2 como versión mínima de referencia.

Comprueba también la ruta del ejecutable:

```bash
where php
```

En Git Bash puedes utilizar:

```bash
which php
```

Si tienes varias instalaciones de PHP, verifica que Composer y tu terminal estén utilizando la misma versión.

### 2.2 Comprobar Composer

```bash
composer --version
```

Comprueba el estado general de Composer:

```bash
composer diagnose
```

### 2.3 Comprobar las extensiones de PHP

```bash
php -m
```

Entre las extensiones relevantes para el proyecto se encuentran:

| Extensión | Uso |
|---|---|
| `sodium` | Cifrado y operaciones criptográficas. |
| `json` | Procesamiento de datos JSON. |
| `openssl` | Operaciones criptográficas y conexiones TLS de PHP. |

Comprueba individualmente:

```bash
php -r "var_dump(extension_loaded('sodium'));"
php -r "var_dump(extension_loaded('json'));"
php -r "var_dump(extension_loaded('openssl'));"
```

La salida esperada para las extensiones disponibles es:

```text
bool(true)
```

### 2.4 Comprobar la configuración de PHP

```bash
php --ini
```

Este comando muestra el archivo `php.ini` que está utilizando PHP desde la terminal.

Es importante porque puedes tener configuraciones diferentes entre PHP CLI y el servidor web.

## 3. Problemas de Composer

### 3.1 Composer no reconoce PHP

**Error posible:**

```text
'php' is not recognized as an internal or external command
```

**Causa:**

PHP no está agregado a la variable de entorno `PATH` o la terminal está utilizando una instalación incorrecta.

**Solución:**

1. Localiza el directorio donde está instalado PHP.
2. Agrega ese directorio a la variable `PATH` de Windows.
3. Cierra y vuelve a abrir la terminal.
4. Ejecuta:

```bash
php -v
composer --version
```

Comprueba que ambas herramientas funcionen correctamente.

### 3.2 Error de dependencias

**Error posible:**

```text
Your requirements could not be resolved to an installable set of packages.
```

**Causas posibles:**

- Versión de PHP incompatible.
- Dependencias incompatibles.
- Restricciones de versiones incorrectas.
- Extensiones requeridas no disponibles.
- Conflictos entre dependencias principales y de desarrollo.

**Diagnóstico:**

```bash
composer validate
composer diagnose
composer show
```

**Solución:**

Primero comprueba los requisitos declarados en el `composer.json` correspondiente.

Después intenta resolver las dependencias:

```bash
composer update
```

Utiliza `composer update` de manera consciente, ya que puede modificar `composer.lock`.

Cuando existe un archivo `composer.lock` válido y deseas instalar las versiones ya definidas, utiliza:

```bash
composer install
```

No elimines el archivo `composer.lock` como primera medida para resolver un conflicto.

### 3.3 Falta una dependencia

**Error posible:**

```text
Class "Firebase\JWT\JWT" not found
```

**Causa:**

La dependencia no está instalada o el autoload de Composer no se ha actualizado.

**Solución:**

Desde la raíz del repositorio:

```bash
composer install
composer dump-autoload
```

Comprueba si el paquete está instalado:

```bash
composer show firebase/php-jwt
```

Si no aparece, revisa el `composer.json` y la sección de dependencias correspondiente.

### 3.4 Problemas con ext-sodium

**Error posible:**

```text
The requested PHP extension ext-sodium is missing from your system.
```

**Causa:**

La instalación de PHP utilizada por Composer no tiene disponible Sodium.

**Diagnóstico:**

```bash
php -r "var_dump(extension_loaded('sodium'));"
```

**Solución:**

En Windows:

1. Ejecuta `php --ini`.
2. Identifica el archivo `php.ini` activo.
3. Comprueba la disponibilidad de Sodium en esa instalación.
4. Revisa que PHP no esté utilizando un directorio de extensiones incorrecto.
5. Reinicia la terminal o el servicio PHP cuando corresponda.

En instalaciones modernas de PHP, Sodium normalmente está disponible como extensión incluida.

No agregues configuraciones de extensión arbitrarias sin verificar los archivos de la distribución que utilizas.

## 4. Problemas de autoload y namespaces

### 4.1 Class not found

**Error posible:**

```text
Class "ShieldPHP\Core\Auth\AuthManager" not found
```

**Causas posibles:**

- Namespace incorrecto.
- Archivo ubicado en un directorio diferente.
- Nombre de clase incorrecto.
- Configuración PSR-4 incompleta.
- Autoload desactualizado.

**Solución:**

Comprueba la estructura:

```text
packages/
└── php-core/
    └── src/
        └── Auth/
            └── AuthManager.php
```

Comprueba que el namespace sea coherente:

```php
<?php

declare(strict_types=1);

namespace ShieldPHP\Core\Auth;
```

Verifica el autoload del `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "ShieldPHP\\Core\\": "packages/php-core/src/"
        }
    }
}
```

Reconstruye el autoload:

```bash
composer dump-autoload
```

### 4.2 Namespace incorrecto

**Error posible:**

```text
Class "ShieldPHP\Auth\AuthManager" not found
```

**Causa:**

El código intenta utilizar un namespace diferente al definido por el paquete Core.

**Solución:**

Comprueba las declaraciones `namespace` de la clase e importa el namespace correcto:

```php
use ShieldPHP\Core\Auth\AuthManager;
```

No cambies namespaces de forma global sin comprobar las referencias existentes.

### 4.3 Problemas de mayúsculas y minúsculas

PHP y Composer pueden trabajar en sistemas de archivos con comportamientos diferentes respecto a las mayúsculas y minúsculas.

Por ejemplo:

```text
SessionManager.php
sessionmanager.php
```

Aunque Windows pueda resolver algunas diferencias, Linux distingue ambos nombres.

**Solución:**

Mantén exactamente los mismos nombres en:

- Archivos.
- Directorios.
- Namespaces.
- Clases.
- Imports.

Comprueba el repositorio en un entorno Linux o mediante CI para detectar incompatibilidades.

## 5. Problemas de configuración

### 5.1 Configuración inválida

**Error posible:**

```text
Invalid configuration
```

**Causas posibles:**

- Valores incorrectos.
- Tipos de datos inesperados.
- Claves obligatorias ausentes.
- Configuraciones fuera de los límites establecidos.

**Solución:**

Revisa el archivo de configuración y compara sus valores con los requisitos de `ShieldConfig` y `ConfigValidator`.

Para JWT, revisa particularmente:

- Algoritmo.
- Longitud de la clave.
- Emisor.
- Audiencia.
- Duración del token.
- Tiempo de tolerancia.

No utilices valores de producción directamente en archivos que se suben al repositorio.

### 5.2 Las variables de entorno no se cargan

**Síntoma:**

La aplicación utiliza valores predeterminados aunque exista un archivo `.env`.

**Causa:**

PHP no carga automáticamente los archivos `.env`.

**Solución:**

Comprueba que la aplicación utilice un mecanismo de carga de variables de entorno, como `vlucas/phpdotenv`, si forma parte de sus dependencias.

Comprueba también:

- Que `.env` esté en la ubicación esperada.
- Que `.env` esté excluido de Git.
- Que los nombres de las variables coincidan exactamente.
- Que el entorno de ejecución tenga acceso a esas variables.

Ejemplo:

```dotenv
APP_ENV=local
APP_DEBUG=true

SHIELDPHP_JWT_ISSUER=https://example.com
SHIELDPHP_JWT_AUDIENCE=shieldphp-api
```

Este ejemplo representa variables de entorno ilustrativas. La aplicación debe mapearlas explícitamente a la configuración de ShieldPHP.

## 6. Problemas de autenticación

### 6.1 Las credenciales son rechazadas

**Síntoma:**

El usuario introduce sus datos correctamente, pero el proceso de autenticación devuelve un resultado fallido.

**Causas posibles:**

- Contraseña incorrecta.
- Hash almacenado inválido.
- Usuario desactivado.
- Usuario no encontrado.
- Provider que no recupera correctamente la identidad.
- Incompatibilidad entre el hash almacenado y el algoritmo utilizado.

**Diagnóstico:**

Comprueba:

1. Que el usuario exista.
2. Que la cuenta esté activa.
3. Que el hash almacenado sea válido.
4. Que el repositorio implemente correctamente el contrato de usuario.
5. Que `PasswordHasher` utilice el mecanismo de verificación esperado.

Ejemplo de comprobación:

```php
if (!$passwordHasher->verify(
    $plainPassword,
    $storedHash
)) {
    // Credenciales no válidas.
}
```

No registres contraseñas en texto plano en los logs.

### 6.2 Error al verificar contraseñas

**Error posible:**

```text
password_verify(): Argument #2 ($hash) must be of type string
```

**Causa:**

El hash almacenado no es una cadena válida o el repositorio devuelve un valor incorrecto.

**Solución:**

Verifica el tipo y la procedencia del hash antes de entregarlo al verificador.

Comprueba también que las contraseñas nuevas se almacenen con `PasswordHasher::hash()` y no directamente en la base de datos.

### 6.3 La política de contraseñas rechaza una contraseña

**Causa:**

La contraseña no cumple los límites definidos en `PasswordPolicy`.

**Solución:**

Comprueba la longitud mínima y máxima configuradas.

La política inicial documentada utiliza:

| Parámetro | Valor |
|---|---:|
| Longitud mínima | 12 caracteres |
| Longitud máxima | 1024 bytes |

Ten en cuenta que caracteres UTF-8 pueden ocupar más de un byte.

No confundas el límite de bytes con el número de caracteres visibles.

## 7. Problemas con JWT

### 7.1 Error de clave JWT demasiado corta

**Error posible:**

```text
The signing key is too short for the selected algorithm.
```

**Causa:**

La clave no cumple la longitud mínima definida para el algoritmo seleccionado.

**Longitudes de referencia de ShieldPHP:**

| Algoritmo | Longitud mínima |
|---|---:|
| HS256 | 32 bytes |
| HS384 | 48 bytes |
| HS512 | 64 bytes |

**Solución:**

Genera una clave criptográficamente segura:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Para HS256, el resultado hexadecimal contiene 64 caracteres que representan 32 bytes.

No utilices contraseñas humanas como claves de firma JWT.

### 7.2 Token expirado

**Error posible:**

```text
Expired token
```

**Causa:**

El tiempo actual supera la fecha de expiración del token.

**Solución:**

- Comprueba el tiempo de vida configurado.
- Verifica la sincronización del reloj del servidor.
- Comprueba los valores `iat`, `nbf` y `exp`.
- Solicita un nuevo proceso de autenticación cuando corresponda.

No amplíes indiscriminadamente la tolerancia temporal para ocultar errores de expiración.

### 7.3 Token inválido

**Síntoma:**

La validación rechaza un token aparentemente correcto.

**Causas posibles:**

- Firma incorrecta.
- Clave diferente a la utilizada para firmar.
- Algoritmo incompatible.
- Token manipulado.
- Emisor no válido.
- Audiencia no válida.
- Claims obligatorios ausentes.
- Token revocado.

**Solución:**

Revisa la configuración utilizada para emitir y validar tokens.

Ambos procesos deben utilizar criterios compatibles y una política explícita de validación de claims.

No desactives la verificación de firmas para solucionar este problema.

### 7.4 El token aparece como revocado

**Causa:**

El identificador `jti` está registrado como revocado en el almacén de revocación.

**Solución:**

Comprueba el registro de revocación y la fecha de expiración asociada.

En el almacenamiento temporal `TokenBlacklist`, los datos no deben considerarse persistentes entre reinicios del proceso.

Para un entorno distribuido se necesita un almacén compartido y persistente.

### 7.5 Error al decodificar un token

**Causa:**

El token tiene un formato incorrecto o no cumple el formato esperado por la biblioteca utilizada.

**Diagnóstico:**

Comprueba que el token tenga tres segmentos separados por puntos:

```text
header.payload.signature
```

No compartas tokens reales al solicitar soporte. Utiliza valores ficticios o elimina las partes sensibles.

## 8. Problemas de OAuth 2.0

### 8.1 Error de redirect URI

**Error posible:**

```text
redirect_uri_mismatch
```

**Causa:**

La URL de redirección enviada durante la autorización no coincide con la registrada en el proveedor.

**Solución:**

Comprueba que coincidan exactamente:

- Protocolo.
- Dominio.
- Puerto.
- Ruta.
- Barra final, cuando corresponda.

Ejemplo:

```text
https://example.com/oauth/google/callback
```

No confundas la URL de inicio de sesión con la URL de callback.

### 8.2 Invalid state

**Síntoma:**

El callback de OAuth rechaza el parámetro `state`.

**Causas posibles:**

- El valor fue modificado.
- El estado expiró.
- Ya fue consumido.
- El almacenamiento perdió el registro.
- El navegador no conserva correctamente el contexto requerido.

**Solución:**

Verifica el ciclo de vida de `OAuthStateManager`.

El estado debe generarse de manera segura, almacenarse temporalmente y consumirse una sola vez.

No elimines la validación de `state` como solución alternativa.

### 8.3 Error de PKCE

**Síntoma:**

El proveedor rechaza la solicitud durante el intercambio del código.

**Causa:**

El `code_verifier` no corresponde al `code_challenge` utilizado en la autorización.

**Solución:**

Comprueba que:

1. El verificador se genere de forma segura.
2. El challenge se derive del verificador correcto.
3. Ambos pertenezcan al mismo flujo de autorización.
4. El verificador no haya expirado.
5. El proveedor admita el método utilizado.

La disponibilidad de componentes PKCE en ShieldPHP no implica que todos los proveedores implementen los mismos flujos.

### 8.4 Error al obtener el perfil del usuario

**Causas posibles:**

- Token de acceso inválido.
- Token expirado.
- Permisos insuficientes.
- Endpoint incorrecto.
- Respuesta inesperada del proveedor.
- Datos opcionales ausentes.

**Solución:**

Comprueba la respuesta HTTP y el formato JSON.

No asumas que el proveedor siempre devuelve un correo electrónico.

Tampoco vincules automáticamente cuentas únicamente porque dos proveedores devuelvan el mismo correo.

### 8.5 Error de conexión HTTPS

**Síntoma:**

No se puede establecer una conexión con el servidor OAuth.

**Causas posibles:**

- Problemas de certificados TLS.
- Configuración incorrecta de certificados CA.
- Dominio no permitido.
- Conectividad de red.
- Restricciones de firewall.
- Configuración DNS incorrecta.

**Solución:**

Comprueba el certificado, la configuración TLS y la lista de hosts permitidos en `OAuthClient`.

No desactives la verificación de certificados para resolver errores de conexión.

## 9. Problemas de cifrado

### 9.1 Clave de cifrado inválida

**Error posible:**

```text
Invalid encryption key length.
```

**Causa:**

La clave no tiene el tamaño requerido por el algoritmo utilizado.

La implementación inicial de `Encryption` utiliza Sodium Secretbox, que requiere una clave binaria de 32 bytes.

**Solución:**

Genera una clave segura:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Si se almacena en Base64, recuerda decodificarla antes de utilizarla como clave binaria.

Comprueba el tamaño:

```php
$key = base64_decode(
    $encodedKey,
    true
);

if ($key === false || strlen($key) !== 32) {
    throw new RuntimeException(
        'La clave de cifrado no es válida.'
    );
}
```

No confundas una cadena Base64 de 44 caracteres aproximadamente con una clave binaria de 32 bytes.

### 9.2 Error al descifrar información

**Causas posibles:**

- Clave incorrecta.
- Datos modificados.
- Formato inválido.
- Nonce incorrecto.
- Datos truncados.
- Versión incompatible del formato de cifrado.

**Solución:**

Verifica:

- Que la clave sea la misma utilizada para cifrar.
- Que el valor esté completo.
- Que el prefijo de versión sea válido.
- Que la codificación Base64 sea correcta.
- Que los datos no hayan sido modificados.

La implementación inicial utiliza el formato:

```text
shp:v1:BASE64(nonce+ciphertext)
```

No intentes descifrar valores con una clave diferente ni elimines la validación de autenticidad criptográfica.

### 9.3 Incompatibilidad del algoritmo

La implementación inicial de `Encryption` utiliza:

```text
Sodium crypto_secretbox
XSalsa20-Poly1305
```

No debe confundirse con:

```text
XChaCha20-Poly1305
```

Aunque ambos pertenecen a la familia de construcciones disponibles mediante Sodium, utilizan interfaces, parámetros y tamaños de nonce diferentes.

Los valores de configuración y la documentación deben coincidir con el algoritmo realmente utilizado.

## 10. Problemas de sesiones y cookies

### 10.1 Headers already sent

**Error posible:**

```text
Cannot modify header information - headers already sent
```

**Causa:**

La aplicación intentó iniciar una sesión o modificar una cookie después de enviar contenido al navegador.

**Solución:**

- Inicia la sesión antes de producir contenido.
- Evita espacios o saltos de línea antes de `<?php`.
- Comprueba si existe salida accidental.
- Evita imprimir mensajes de depuración antes de establecer cookies.

### 10.2 La sesión no persiste

**Síntoma:**

La información almacenada desaparece en la siguiente solicitud.

**Causas posibles:**

- El navegador no conserva la cookie.
- El identificador cambia entre solicitudes.
- La configuración del dominio o ruta es incorrecta.
- La sesión expira.
- El almacenamiento no está disponible.
- La aplicación destruye la sesión accidentalmente.

**Solución:**

Comprueba:

1. Que la sesión se inicie correctamente.
2. Que el navegador reciba la cookie.
3. Que envíe la cookie en la siguiente solicitud.
4. Que el servidor pueda recuperar los datos.
5. Que las opciones de dominio y ruta sean correctas.

### 10.3 Cookie no enviada en HTTP

**Causa:**

La cookie tiene habilitado el atributo `Secure`, pero se está accediendo mediante HTTP.

**Solución:**

Utiliza HTTPS.

En entornos locales de desarrollo, configura los valores de acuerdo con el entorno y evita trasladar configuraciones inseguras a producción.

### 10.4 Problemas con SameSite

**Síntoma:**

La cookie de sesión no llega al callback o a una solicitud entre sitios.

**Causa:**

La política `SameSite` no es compatible con el flujo de navegación.

**Solución:**

Comprueba si la aplicación necesita `Lax`, `Strict` o `None`.

Cuando se utiliza:

```text
SameSite=None
```

también debe utilizarse:

```text
Secure
```

Evalúa cuidadosamente los riesgos del flujo y conserva las medidas de protección CSRF necesarias.

### 10.5 No se puede regenerar el identificador

**Causas posibles:**

- No existe una sesión activa.
- La sesión ya fue destruida.
- Los encabezados ya fueron enviados.
- El almacenamiento presenta errores.
- La operación se ejecuta fuera del momento adecuado del ciclo de vida.

**Solución:**

Comprueba el estado de la sesión antes de regenerar el identificador y realiza la operación antes de enviar encabezados HTTP.

## 11. Problemas de integraciones HTTP

### 11.1 Interface not found

**Error posible:**

```text
Interface "Psr\Http\Client\ClientInterface" not found
```

**Causa:**

Falta una dependencia PSR requerida por la integración HTTP.

**Solución:**

Comprueba las dependencias:

```bash
composer show psr/http-client
composer show psr/http-message
composer show psr/http-factory
```

Si falta alguna, revisa el `composer.json` del paquete que la necesita e instala las dependencias correspondientes.

### 11.2 Error al crear una respuesta HTTP

**Causas posibles:**

- No se ha instalado una implementación de PSR-17.
- El response factory no corresponde al contrato esperado.
- El contenedor de dependencias no tiene registrada la implementación.

**Solución:**

Verifica que el framework o la aplicación proporcione las interfaces y sus implementaciones correspondientes.

No confundas una interfaz PSR con una implementación concreta.

### 11.3 Middleware no ejecutado

**Causas posibles:**

- Middleware no registrado.
- Orden incorrecto de ejecución.
- Ruta fuera del grupo protegido.
- Configuración incorrecta del framework.
- Diferencias entre las versiones de PSR utilizadas.

**Solución:**

Comprueba el registro del middleware y la secuencia del pipeline HTTP.

En integraciones Slim, verifica además que el middleware se ejecute antes del controlador que requiere autenticación.

## 12. Problemas de PHPUnit y pruebas

### 12.1 PHPUnit no encontrado

**Error posible:**

```text
vendor/bin/phpunit: No such file or directory
```

**Causa:**

Las dependencias de desarrollo no están instaladas.

**Solución:**

Desde la raíz:

```bash
composer install
```

Comprueba:

```bash
composer show phpunit/phpunit
```

### 12.2 No se encuentran pruebas

**Síntoma:**

PHPUnit no encuentra archivos de prueba o indica que no hay tests ejecutables.

**Causas posibles:**

- Ruta incorrecta.
- Nombres de archivos incorrectos.
- Configuración de PHPUnit incompleta.
- Tests excluidos por la configuración.
- Dependencias no disponibles.

**Solución:**

Desde la raíz del repositorio ejecuta:

```bash
vendor/bin/phpunit --list-tests
```

Después ejecuta una carpeta específica:

```bash
vendor/bin/phpunit packages/php-core/tests/Unit
```

Comprueba que los archivos tengan el sufijo `Test.php` y que correspondan a la configuración activa.

### 12.3 Pruebas de sesión que fallan

**Causa:**

Las sesiones de PHP utilizan estado global y pueden interferir unas con otras.

**Solución:**

- Aísla las pruebas.
- Limpia el estado al finalizar.
- Evita ejecutar múltiples sesiones activas dentro de una misma prueba sin control.
- No dependas del estado de pruebas anteriores.
- Utiliza almacenamiento temporal o manejadores de prueba cuando sea necesario.

### 12.4 Pruebas que funcionan localmente, pero fallan en GitHub Actions

**Causas posibles:**

- Diferente versión de PHP.
- Extensiones ausentes.
- Diferencias de sistema operativo.
- Sensibilidad a mayúsculas y minúsculas.
- Variables de entorno no configuradas.
- Dependencias no instaladas.
- Configuración distinta entre entornos.

**Solución:**

Compara el entorno local con el definido en `.github/workflows/`.

Comprueba especialmente PHP, extensiones, Composer, variables y rutas.

## 13. Análisis estático y formato

### 13.1 PHPStan detecta errores

Ejecuta:

```bash
composer analyse
```

**Causas posibles:**

- Tipos incompatibles.
- Propiedades no inicializadas.
- Métodos inexistentes.
- Retornos incorrectos.
- Tipos nullable no controlados.
- Imports incorrectos.

**Solución:**

Revisa la línea reportada y corrige la causa en lugar de suprimir indiscriminadamente los errores.

### 13.2 Problemas de formato

Ejecuta:

```bash
composer format:check
```

Para aplicar el formato configurado:

```bash
composer format
```

El formato no reemplaza las pruebas ni el análisis estático.

### 13.3 Ejecutar verificaciones del proyecto

Si los scripts están disponibles en el `composer.json` raíz:

```bash
composer check
```

Este comando agrupa las verificaciones definidas por el proyecto.

Comprueba el contenido real de la sección `scripts` si Composer indica que el comando no existe.

## 14. Problemas de Git y GitHub

### 14.1 Push rechazado

**Error posible:**

```text
! [rejected] Dev -> Dev (fetch first)
```

**Causa:**

La rama remota contiene commits que no están integrados en la rama local.

**Solución:**

Comprueba el estado:

```bash
git status
git fetch origin
git log --oneline --graph --decorate --all -20
```

Si no existe un rebase o merge en curso, integra los cambios remotos mediante la estrategia correspondiente.

Por ejemplo:

```bash
git pull --rebase origin Dev
```

Si aparecen conflictos, resuélvelos cuidadosamente y continúa el rebase:

```bash
git status
git add .
git rebase --continue
```

No ejecutes `git push --force` como solución automática.

### 14.2 Merge o rebase incompleto

Comprueba:

```bash
git status
```

Si aparece:

```text
You are currently rebasing
```

debes resolver o abortar el rebase según la situación.

Si aparece:

```text
All conflicts fixed but you are still merging
```

debes completar el merge mediante el commit correspondiente.

No mezcles comandos de rebase y merge sin identificar primero el estado actual del repositorio.

### 14.3 Archivo que no debería aparecer en el commit

Comprueba:

```bash
git status --short
git diff --cached --name-status
```

Para retirar un archivo del área de preparación:

```bash
git restore --staged ruta/al/archivo
```

Para revisar el contenido de un commit:

```bash
git show --stat --oneline HEAD
```

No elimines archivos o commits hasta identificar si contienen cambios necesarios.

### 14.4 Secretos publicados accidentalmente

Si una clave privada, token, contraseña o credencial fue publicada:

1. Revoca o rota inmediatamente la credencial.
2. Revisa los sistemas que pudieron utilizarla.
3. Comprueba los registros de acceso.
4. Retira el secreto del repositorio.
5. Evalúa si es necesaria la limpieza del historial.
6. Revisa `SECURITY.md` para seguir el procedimiento de seguridad.

Eliminar un archivo del último commit no invalida una credencial que ya fue expuesta.

## 15. Checklist general de diagnóstico

Antes de solicitar soporte, comprueba los siguientes puntos:

- [ ] PHP 8.2 o superior está instalado.
- [ ] Composer funciona correctamente.
- [ ] Las dependencias están instaladas.
- [ ] Las extensiones requeridas están disponibles.
- [ ] El autoload de Composer está actualizado.
- [ ] Los namespaces corresponden a la estructura.
- [ ] Las variables de entorno están configuradas.
- [ ] Las claves criptográficas tienen el tamaño adecuado.
- [ ] Las URL de callback están registradas correctamente.
- [ ] Las cookies utilizan atributos compatibles con el entorno.
- [ ] Los tests pueden ejecutarse.
- [ ] PHPStan no presenta errores pendientes.
- [ ] No hay secretos publicados en Git.

## 16. Comandos útiles

### Diagnóstico del entorno

```bash
php -v
php --ini
php -m
composer --version
composer diagnose
```

### Dependencias

```bash
composer validate
composer install
composer show
composer dump-autoload
```

### Pruebas

```bash
composer test
vendor/bin/phpunit --list-tests
vendor/bin/phpunit packages/php-core/tests/Unit
```

### Calidad del código

```bash
composer analyse
composer format:check
composer check
```

### Git

```bash
git status
git fetch origin
git log --oneline --graph --decorate -15
git diff
git diff --cached
```

Los comandos `composer test`, `composer analyse`, `composer format:check` y `composer check` dependen de que estén definidos en el `composer.json` raíz.

## 17. Cómo reportar un problema

Cuando un error no pueda resolverse mediante esta guía, prepara un reporte que permita reproducirlo.

Incluye:

| Información | Descripción |
|---|---|
| Versión de ShieldPHP | Commit o versión utilizada. |
| Versión de PHP | Resultado de `php -v`. |
| Versión de Composer | Resultado de `composer --version`. |
| Sistema operativo | Windows, Linux o macOS. |
| Componente afectado | Auth, JWT, OAuth, Crypto, Session, etc. |
| Descripción | Qué operación intentabas realizar. |
| Resultado esperado | Qué debía suceder. |
| Resultado obtenido | Qué ocurrió realmente. |
| Mensaje de error | Texto completo sin secretos. |
| Pasos para reproducir | Secuencia mínima de operaciones. |

### Plantilla de reporte

```markdown
## Descripción del problema

Explica brevemente el problema.

## Entorno

- ShieldPHP:
- PHP:
- Composer:
- Sistema operativo:

## Componente afectado

Auth / JWT / OAuth / Crypto / Session / Otro.

## Pasos para reproducir

1. 
2. 
3. 

## Resultado esperado

Describe el comportamiento esperado.

## Resultado obtenido

Describe el comportamiento observado.

## Mensaje de error

Pega aquí el mensaje completo, eliminando tokens,
contraseñas, claves y datos sensibles.

## Información adicional

Incluye archivos de configuración relevantes
sin credenciales ni secretos.
```

### Información sensible

Nunca publiques:

- Contraseñas.
- Tokens de acceso.
- Refresh tokens.
- Claves privadas.
- Secretos de OAuth.
- Claves de cifrado.
- Identificadores de sesión activos.
- Archivos `.env` con credenciales reales.

## 18. Recursos relacionados

### Documentación de ShieldPHP

- `README.md` — Descripción general.
- `SECURITY.md` — Política de seguridad.
- `docs/api-reference.md` — Referencia de la API.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Autorización.
- `docs/configuration.md` — Configuración.
- `docs/encryption.md` — Cifrado.
- `docs/installation.md` — Instalación.
- `docs/jwt.md` — JSON Web Tokens.
- `docs/oauth2.md` — OAuth 2.0.
- `docs/security.md` — Seguridad.
- `docs/sessions.md` — Sesiones.

### Documentación externa

- [PHP Documentation](https://www.php.net/docs.php)
- [Composer Documentation](https://getcomposer.org/doc/)
- [PHPUnit Documentation](https://docs.phpunit.de/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/cheatsheets/)

---

<p align="center">
  **ShieldPHP**
  <br>
  Diagnóstico, mantenimiento y resolución de problemas.
</p>

<p align="center">
  <sub>Proyecto en desarrollo. Consulta siempre la implementación actual antes de aplicar soluciones específicas.</sub>
</p>