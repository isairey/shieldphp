# Encryption — ShieldPHP

<p align="center">
  <strong>Cifrado, protección de información y herramientas criptográficas para PHP.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Cryptography-libsodium-blue?style=for-the-badge" alt="libsodium">
  <img src="https://img.shields.io/badge/Encryption-SecretBox-purple?style=for-the-badge" alt="SecretBox">
  <img src="https://img.shields.io/badge/Status-In%20Development-orange?style=for-the-badge" alt="En desarrollo">
</p>

---

## 1. Introducción

El módulo **Encryption** de ShieldPHP proporciona herramientas criptográficas para proteger información sensible dentro de aplicaciones PHP.

Su objetivo es facilitar operaciones de cifrado, descifrado, generación de claves, derivación de material criptográfico y validación de firmas digitales mediante primitivas criptográficas reconocidas.

El módulo utiliza principalmente **libsodium**, una biblioteca criptográfica moderna que proporciona implementaciones de algoritmos diseñados para reducir errores comunes en el desarrollo de sistemas de seguridad.

Entre sus capacidades se contemplan:

- Cifrado simétrico de información.
- Descifrado autenticado.
- Administración de claves criptográficas.
- Derivación de claves.
- Generación de valores aleatorios seguros.
- Firmas digitales.
- Verificación de firmas.
- Identificación de versiones de formatos criptográficos.

El módulo está diseñado para mantener separadas las operaciones criptográficas de la lógica de negocio de las aplicaciones.

## 2. Arquitectura

El módulo Crypto está organizado en componentes especializados.

| Componente | Responsabilidad |
|---|---|
| `Encryption` | Cifrado y descifrado simétrico. |
| `KeyDerivation` | Derivación de claves criptográficas. |
| `KeyManager` | Administración de claves. |
| `RandomGenerator` | Generación de valores aleatorios seguros. |
| `Signature` | Firma y verificación digital. |

Namespace principal:

```php
ShieldPHP\Core\Crypto\
```

### Diagrama de componentes

```text
                 APLICACIÓN PHP
                       |
                       v
              ┌──────────────────┐
              │   ShieldPHP Core │
              │                  │
              │      Crypto      │
              └────────┬─────────┘
                       |
         ┌─────────────┼──────────────┐
         |             |              |
         v             v              v
    Encryption    KeyManager    RandomGenerator
         |             |
         |             v
         |        KeyDerivation
         |
         v
    libsodium
         |
    ┌────┴─────┐
    |          |
    v          v
  Cifrado   Descifrado

             Signature
                 |
                 v
              Ed25519
```

## 3. Tecnologías criptográficas

ShieldPHP utiliza las primitivas criptográficas proporcionadas por libsodium.

| Tecnología | Aplicación |
|---|---|
| libsodium | Operaciones criptográficas. |
| `sodium_crypto_secretbox` | Cifrado simétrico autenticado. |
| `sodium_crypto_secretbox_open` | Descifrado y verificación de autenticidad. |
| Ed25519 | Firmas digitales. |
| PHP `random_bytes()` | Generación de valores aleatorios seguros. |
| Base64 | Representación de datos binarios como texto. |

La extensión Sodium debe estar habilitada en PHP.

Puedes comprobarlo mediante:

```bash
php -m
```

Busca la extensión:

```text
sodium
```

También puedes comprobar su disponibilidad desde PHP:

```php
if (!extension_loaded('sodium')) {
    throw new RuntimeException(
        'La extensión Sodium no está habilitada.'
    );
}
```

## 4. Encryption

`Encryption` es el componente principal para realizar operaciones de cifrado y descifrado simétrico.

**Namespace:**

```php
ShieldPHP\Core\Crypto\Encryption
```

La implementación utiliza:

```php
sodium_crypto_secretbox()
```

y:

```php
sodium_crypto_secretbox_open()
```

Este mecanismo proporciona confidencialidad e integridad autenticada mediante una clave secreta compartida.

### Características

- Cifrado simétrico.
- Claves binarias de 32 bytes.
- Nonces de 24 bytes.
- Autenticación criptográfica del contenido.
- Detección de modificaciones en los datos cifrados.
- Formato de almacenamiento versionado.
- Codificación Base64 para facilitar el almacenamiento y transporte.

### Constructor

La implementación utiliza una clave binaria de 32 bytes.

Ejemplo de utilización:

```php
use ShieldPHP\Core\Crypto\Encryption;

$encryption = new Encryption($encryptionKey);
```

La clave debe cumplir exactamente con el tamaño requerido por `sodium_crypto_secretbox`.

### Método encrypt()

Recibe información en texto y devuelve su representación cifrada.

```php
$encrypted = $encryption->encrypt(
    'Información confidencial'
);
```

El resultado es una cadena preparada para su almacenamiento o transmisión.

### Método decrypt()

Recupera el contenido original a partir de una cadena cifrada válida.

```php
$decrypted = $encryption->decrypt($encrypted);
```

Cuando el contenido ha sido modificado, la clave es incorrecta o la información está dañada, la operación de autenticación criptográfica debe fallar.

### Ejemplo completo

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use ShieldPHP\Core\Crypto\Encryption;

$encryptionKey = random_bytes(
    SODIUM_CRYPTO_SECRETBOX_KEYBYTES
);

$encryption = new Encryption($encryptionKey);

$originalMessage = 'Información confidencial de ShieldPHP';

// Cifrar información.
$encryptedMessage = $encryption->encrypt(
    $originalMessage
);

// Descifrar información.
$decryptedMessage = $encryption->decrypt(
    $encryptedMessage
);

echo $decryptedMessage;
```

**Importante:** este ejemplo genera una clave temporal. En una aplicación real debes utilizar una clave persistente protegida. No generes una nueva clave en cada ejecución si necesitas recuperar información cifrada anteriormente.

## 5. Formato de los datos cifrados

ShieldPHP utiliza un formato versionado para identificar los datos generados por el componente `Encryption`.

### Prefijo

```text
shp:v1:
```

### Estructura

```text
shp:v1:BASE64(nonce + ciphertext)
```

El contenido Base64 contiene:

1. Nonce utilizado durante el cifrado.
2. Mensaje cifrado, incluyendo la información de autenticación generada por SecretBox.

### Representación conceptual

```text
┌───────────────────────────────────────────────┐
│                  shp:v1:                      │
├───────────────────────────────────────────────┤
│                  Base64                       │
│                                               │
│   ┌─────────────────┬─────────────────────┐   │
│   │      Nonce      │     Ciphertext      │   │
│   │     24 bytes    │  Datos + MAC de 16  │   │
│   │                 │       bytes         │   │
│   └─────────────────┴─────────────────────┘   │
└───────────────────────────────────────────────┘
```

La etiqueta `v1` permite distinguir el formato utilizado y facilita futuras estrategias de migración.

**No debes modificar manualmente el nonce, el texto cifrado ni la codificación del resultado.**

## 6. Administración de claves

La seguridad del cifrado depende directamente de la protección y administración correcta de las claves.

ShieldPHP contempla el componente `KeyManager` para centralizar estas operaciones.

**Namespace:**

```php
ShieldPHP\Core\Crypto\KeyManager
```

### Responsabilidades

- Generar claves criptográficas.
- Administrar identificadores de claves.
- Consultar la clave activa.
- Recuperar claves mediante identificadores.
- Decodificar claves almacenadas en Base64.
- Facilitar la administración de diferentes claves.

### Características de las claves

Para el cifrado `secretbox`:

| Propiedad | Valor |
|---|---|
| Tamaño requerido | 32 bytes |
| Formato interno | Binario |
| Representación para configuración | Base64 |
| Algoritmo de cifrado | XSalsa20-Poly1305 |

La representación Base64 no debe confundirse con el tamaño de la clave binaria.

Una cadena Base64 que representa 32 bytes ocupa normalmente 44 caracteres, incluyendo el relleno correspondiente.

### Generar una clave

Puedes generar una clave segura mediante PHP:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

Ejemplo de configuración:

```dotenv
SHIELDPHP_ENCRYPTION_KEY_BASE64=TU_CLAVE_GENERADA
```

### Decodificar una clave

```php
$encodedKey = getenv(
    'SHIELDPHP_ENCRYPTION_KEY_BASE64'
);

if (!is_string($encodedKey) || $encodedKey === '') {
    throw new RuntimeException(
        'No se ha configurado la clave de cifrado.'
    );
}

$key = base64_decode($encodedKey, true);

if (
    $key === false ||
    strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES
) {
    throw new RuntimeException(
        'La clave de cifrado no es válida.'
    );
}
```

La clave resultante puede utilizarse en el componente de cifrado.

### Recomendaciones

- Mantener las claves fuera del repositorio.
- No compartir claves entre ambientes.
- Limitar el acceso a los secretos.
- Utilizar administradores de secretos cuando corresponda.
- Mantener copias de respaldo protegidas.
- Definir procedimientos de rotación.
- No almacenar claves junto a los datos cifrados sin protección adicional.

## 7. KeyDerivation

`KeyDerivation` proporciona herramientas para derivar claves criptográficas.

**Namespace:**

```php
ShieldPHP\Core\Crypto\KeyDerivation
```

Su propósito es transformar material de entrada en claves apropiadas para operaciones criptográficas.

### Aplicaciones

- Derivación de material criptográfico.
- Separación de claves entre diferentes propósitos.
- Obtención de claves a partir de material principal.
- Soporte para estrategias de administración de claves.

### Consideraciones

La derivación de claves debe utilizar primitivas diseñadas específicamente para ese propósito.

No se debe utilizar un hash rápido convencional como sustituto de un mecanismo de derivación de claves.

Cuando se derivan claves a partir de contraseñas humanas, deben utilizarse funciones apropiadas para contraseñas, con parámetros y sales adecuados.

Las firmas exactas de los métodos de `KeyDerivation` deben consultarse en su implementación antes de utilizarlas.

## 8. RandomGenerator

`RandomGenerator` proporciona mecanismos para producir valores aleatorios mediante fuentes criptográficamente seguras.

**Namespace:**

```php
ShieldPHP\Core\Crypto\RandomGenerator
```

### Operaciones contempladas

| Operación | Aplicación |
|---|---|
| Bytes aleatorios | Generar material criptográfico. |
| Valores hexadecimales | Representar datos aleatorios. |
| Tokens | Crear valores aleatorios para aplicaciones. |
| Enteros | Generar valores dentro de rangos permitidos. |
| UUID | Crear identificadores únicos. |

### Uso de fuentes seguras

Para generar bytes seguros, PHP proporciona:

```php
$randomBytes = random_bytes(32);
```

Los valores producidos mediante fuentes criptográficamente seguras pueden utilizarse como material inicial para diferentes mecanismos.

### Consideraciones

- No utilizar `rand()` para generar secretos.
- No utilizar `mt_rand()` para generar tokens de seguridad.
- No reutilizar valores aleatorios cuando el algoritmo requiera nuevos valores únicos.
- Validar los tamaños requeridos por cada primitiva criptográfica.

Las firmas concretas de los métodos de `RandomGenerator` deben verificarse en su clase correspondiente.

## 9. Signature

`Signature` implementa operaciones de firma digital y verificación mediante Ed25519.

**Namespace:**

```php
ShieldPHP\Core\Crypto\Signature
```

A diferencia del cifrado simétrico, las firmas digitales utilizan un par de claves:

- Clave privada para firmar.
- Clave pública para verificar.

### Características

- Firmas digitales Ed25519.
- Verificación mediante clave pública.
- Representación Base64 de las firmas.
- Detección de modificaciones en la información firmada.

### Flujo de firma

```text
                INFORMACIÓN ORIGINAL
                         |
                         v
                   CLAVE PRIVADA
                         |
                         v
                  FIRMA DIGITAL
                         |
                         v
                   DATOS FIRMADOS
                         |
                         v
                   TRANSMISIÓN
                         |
                         v
                   CLAVE PÚBLICA
                         |
                         v
                 VERIFICAR FIRMA
                         |
                   ┌─────┴─────┐
                   |           |
                   v           v
                 VÁLIDA     INVÁLIDA
```

### Aplicaciones

- Verificación de integridad.
- Autenticación de mensajes.
- Validación de documentos.
- Intercambio de información entre servicios.
- Verificación de datos proporcionados por sistemas externos.

### Consideraciones

Una firma digital no cifra el contenido. Si necesitas confidencialidad, debes utilizar además un mecanismo de cifrado.

Las firmas Ed25519 tampoco deben confundirse con las firmas JWT realizadas mediante HMAC.

Las operaciones concretas deben utilizar las firmas de métodos y formatos establecidos en la implementación de `Signature`.

## 10. Diferencias entre cifrado y firma digital

Son mecanismos distintos y deben utilizarse según la necesidad de seguridad.

| Característica | Cifrado simétrico | Firma digital |
|---|---|---|
| Objetivo | Confidencialidad e integridad. | Autenticidad e integridad. |
| Claves | Una clave compartida. | Clave privada y pública. |
| Operación principal | Cifrar y descifrar. | Firmar y verificar. |
| Algoritmo utilizado | SecretBox. | Ed25519. |
| Permite ocultar datos | Sí. | No. |
| Detecta modificaciones | Sí. | Sí. |

### ¿Cuándo utilizar cada mecanismo?

**Cifrado simétrico:**

Cuando necesitas proteger información que debe recuperarse posteriormente.

Ejemplos:

- Información confidencial.
- Datos personales almacenados.
- Configuraciones sensibles.
- Información intercambiada entre componentes.

**Firma digital:**

Cuando necesitas demostrar que un mensaje fue firmado por quien controla determinada clave privada y que no fue modificado.

Ejemplos:

- Validación de documentos.
- Mensajes entre servicios.
- Verificación de integridad.
- Firmas de información.

## 11. Manejo de errores

Las operaciones criptográficas pueden fallar por diferentes motivos:

- Claves inválidas.
- Claves con longitudes incorrectas.
- Datos cifrados dañados.
- Formatos desconocidos.
- Fallos en la autenticación criptográfica.
- Firmas inválidas.

ShieldPHP contempla `EncryptionException` como una excepción específica relacionada con operaciones de cifrado.

**Namespace:**

```php
ShieldPHP\Core\Exceptions\EncryptionException
```

### Manejo conceptual

```php
use ShieldPHP\Core\Exceptions\EncryptionException;

try {
    $decrypted = $encryption->decrypt($encrypted);

} catch (EncryptionException $exception) {

    // Registrar el error sin exponer información sensible.
    error_log('No se pudo descifrar la información.');

    throw new RuntimeException(
        'No fue posible recuperar la información.'
    );
}
```

El código de la aplicación debe evitar revelar claves, contenido confidencial o detalles criptográficos internos mediante mensajes de error.

La excepción concreta debe corresponder a los errores definidos por la implementación de ShieldPHP.

## 12. Buenas prácticas de seguridad

La utilización correcta de componentes criptográficos requiere prestar atención a la administración de claves y al ciclo de vida de la información.

### Protección de claves

- Generar claves mediante fuentes criptográficamente seguras.
- Evitar claves débiles o predecibles.
- No publicar claves en repositorios.
- Separar claves de cifrado y firma.
- Establecer mecanismos de rotación.
- Limitar el acceso a las claves.
- Proteger las copias de respaldo.

### Operaciones de cifrado

- Utilizar siempre nonces nuevos.
- No reutilizar un nonce con la misma clave.
- No modificar manualmente los datos cifrados.
- Verificar los datos antes de utilizarlos.
- Mantener compatibilidad con el formato versionado.
- Evitar utilizar algoritmos criptográficos improvisados.

### Contraseñas

**No utilizar `Encryption` para almacenar contraseñas de usuarios.**

Las contraseñas deben procesarse con mecanismos de hash diseñados específicamente para ese propósito.

ShieldPHP dispone del componente:

```php
ShieldPHP\Core\Auth\PasswordHasher
```

Este componente utiliza la API nativa de hash de contraseñas de PHP.

### Entornos de producción

- Mantener HTTPS habilitado.
- Separar secretos de desarrollo y producción.
- Evitar registrar información sensible.
- Limitar los permisos de acceso a las claves.
- Planificar mecanismos de recuperación.
- Mantener actualizadas las dependencias criptográficas.

## 13. Integración con otros módulos

El módulo Crypto puede utilizarse en conjunto con otros componentes de ShieldPHP.

| Módulo | Relación con Crypto |
|---|---|
| Auth | Protección de información y utilización de hashes de contraseñas mediante su componente específico. |
| JWT | Administración independiente de claves de firma y validación. |
| OAuth | Protección de credenciales y secretos de proveedores. |
| Config | Administración de referencias de configuración criptográfica. |
| Session | Protección de información sensible de sesiones. |

Es importante no reutilizar automáticamente las claves entre estos mecanismos.

Cada operación debe utilizar claves y formatos adecuados para su finalidad.

## 14. Pruebas

El módulo Crypto dispone de una estructura de pruebas unitarias en:

```text
packages/php-core/tests/Unit/Crypto/
```

Los archivos principales son:

| Prueba | Objetivo |
|---|---|
| `EncryptionTest.php` | Cifrado, descifrado y manipulación de datos. |
| `KeyDerivationTest.php` | Comportamiento de derivación de claves. |
| `KeyManagerTest.php` | Administración y recuperación de claves. |
| `RandomGeneratorTest.php` | Generación de valores aleatorios. |
| `SignatureTest.php` | Firmas digitales y verificación. |

### Ejecutar todas las pruebas

Desde el paquete Core:

```bash
cd packages/php-core

composer test
```

### Ejecutar únicamente pruebas criptográficas

```bash
vendor/bin/phpunit tests/Unit/Crypto
```

### Análisis estático

```bash
composer analyse
```

Las pruebas deben contemplar escenarios exitosos y fallidos, incluyendo claves incorrectas, mensajes alterados, firmas inválidas y formatos criptográficos desconocidos.

## 15. Estructura del módulo

```text
packages/php-core/
│
├── src/
│   ├── Crypto/
│   │   ├── Encryption.php
│   │   ├── KeyDerivation.php
│   │   ├── KeyManager.php
│   │   ├── RandomGenerator.php
│   │   └── Signature.php
│   │
│   └── Exceptions/
│       └── EncryptionException.php
│
└── tests/
    └── Unit/
        └── Crypto/
            ├── EncryptionTest.php
            ├── KeyDerivationTest.php
            ├── KeyManagerTest.php
            ├── RandomGeneratorTest.php
            └── SignatureTest.php
```

## 16. Estado actual

| Funcionalidad | Estado |
|---|---|
| Encryption | Base implementada |
| Cifrado SecretBox | Implementado |
| Descifrado autenticado | Implementado |
| Formato versionado `shp:v1:` | Implementado |
| Validación de claves | Implementación inicial |
| KeyDerivation | Base implementada |
| KeyManager | Base implementada |
| RandomGenerator | Base implementada |
| Signature | Base implementada |
| Firmas Ed25519 | Implementación inicial |
| Rotación de claves | En desarrollo |
| Persistencia segura de claves | Pendiente de adaptadores |
| Recuperación de claves | Pendiente de completar |
| Auditoría criptográfica independiente | No realizada |

## 17. Consideraciones técnicas

ShieldPHP utiliza primitivas criptográficas proporcionadas por libsodium para reducir la necesidad de implementar algoritmos de seguridad manualmente.

La clase `Encryption` utiliza `sodium_crypto_secretbox`, por lo que su formato criptográfico corresponde a XSalsa20-Poly1305.

La opción `xchacha20poly1305` que aparece en la plantilla `.env.example` no debe considerarse compatible automáticamente con esta implementación. Es necesario mantener un único algoritmo configurado y coherente con el código real.

También se debe considerar que:

- El cifrado simétrico requiere proteger la clave compartida.
- Las firmas digitales requieren proteger la clave privada.
- La codificación Base64 no proporciona confidencialidad.
- La rotación de claves requiere conservar la capacidad de descifrar información antigua.
- Una implementación criptográfica debe probarse y revisarse antes de utilizarse en sistemas críticos.

---

## 18. Recursos relacionados

### Documentación de ShieldPHP

- `docs/api-reference.md` — Referencia general de la API.
- `docs/configuration.md` — Configuración de ShieldPHP.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Autorización.
- `SECURITY.md` — Política de seguridad.
- `.env.example` — Variables de entorno.

### Referencias externas

- [Libsodium Documentation](https://doc.libsodium.org/)
- [PHP Sodium Extension](https://www.php.net/manual/en/book.sodium.php)
- [Sodium SecretBox](https://doc.libsodium.org/secret-key_cryptography/secretbox)
- [Sodium Key Derivation](https://doc.libsodium.org/key_derivation)
- [Sodium Digital Signatures](https://doc.libsodium.org/public-key_cryptography/public-key_signatures)
- [PHP Random Bytes](https://www.php.net/manual/en/function.random-bytes.php)
- [OWASP Cryptographic Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html)
- [OWASP Key Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Key_Management_Cheat_Sheet.html)

---

<p align="center">
  <strong>ShieldPHP Encryption</strong>
  <br>
  Criptografía moderna y protección de información para PHP.
</p>