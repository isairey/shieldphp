# Registro de cambios

En este archivo se documentan los cambios importantes realizados en **ShieldPHP**, incluyendo nuevas funcionalidades, modificaciones, correcciones y mejoras de seguridad.

Este proyecto sigue las convenciones de [Keep a Changelog](https://keepachangelog.com/es-ES/) y utiliza el versionado semántico (Semantic Versioning).

## [Sin publicar]

### Añadido
- Estructura inicial del monorepositorio ShieldPHP.
- Paquete PHP Core organizado mediante autoloading PSR-4.
- Base del sistema de autenticación:
  - `AuthManager` para la verificación de credenciales.
  - `AuthResult` para representar los resultados de autenticación.
  - `PasswordHasher` para generar y verificar hashes de contraseñas.
  - `PasswordPolicy` para establecer requisitos de seguridad en contraseñas.
  - `UserIdentity` para representar usuarios autenticados.
- Componentes de autorización para administrar roles, permisos y controles de acceso.
- Administración de configuración mediante `ShieldConfig` y `ConfigValidator`.
- Herramientas criptográficas:
  - Cifrado simétrico mediante libsodium.
  - Derivación y administración de claves.
  - Generación de valores aleatorios seguros.
  - Firmas digitales mediante Ed25519.
- Componentes de autenticación JWT:
  - Configuración y administración de declaraciones JWT.
  - Emisión y validación de tokens.
  - Compatibilidad con tokens de acceso y actualización.
  - Revocación de tokens y mecanismos de lista negra.
- Base del sistema OAuth 2.0:
  - Generador de URL de autorización.
  - Cliente HTTP OAuth con restricciones HTTPS y validación de dominios permitidos.
  - Validación de estado y soporte PKCE.
  - Implementaciones iniciales de proveedores Google y Facebook.
- Componentes para administración de sesiones y cookies.
- Herramientas de validación de datos, incluyendo correos electrónicos, URL, cadenas y números.
- Estructura inicial de pruebas PHPUnit para pruebas unitarias, de integración y seguridad.
- Aplicación de ejemplo en PHP.
- Documentación de los ejemplos de autenticación OAuth con Google y Facebook.
- Estructura inicial de integración con Laravel.
- Plantilla de variables de entorno mediante `.env.example`.
- Archivos de configuración del repositorio:
  - `.editorconfig`
  - `.gitattributes`
  - `.gitignore`

### Seguridad
- Respuestas genéricas ante fallos de autenticación para reducir la enumeración de usuarios.
- Hash de contraseñas mediante la API nativa de PHP.
- Operaciones criptográficas seguras utilizando libsodium.
- Validación de la longitud de las claves de firma JWT.
- Validación del estado OAuth y soporte PKCE.
- Aplicación de HTTPS y validación de dominios permitidos en el cliente HTTP OAuth.
- Opciones de configuración para proteger cookies y sesiones.

### Documentación
- Archivos README iniciales para el paquete PHP Core y los ejemplos.
- Documentación inicial de la integración con Laravel.
- Plantilla de referencia para variables de entorno.
- Convenciones de formato y organización del repositorio.

### En desarrollo
- Completar y validar las API del paquete Core.
- Ejecutar y corregir la suite de pruebas PHPUnit conforme a las implementaciones reales.
- Completar los enlaces de servicios, middleware e integración con Laravel.
- Implementar adaptadores de almacenamiento persistente para sesiones, estado OAuth, PKCE y revocación de tokens.
- Completar el manejo de respuestas y callbacks OAuth para producción.
- Incorporar pruebas de integración y seguridad.
- Preparar la documentación de instalación y despliegue.

---

## Convenciones de versionado

ShieldPHP utiliza el versionado semántico:

- **MAJOR (Versión principal):** cambios incompatibles con versiones anteriores.
- **MINOR (Versión secundaria):** incorporación de funcionalidades compatibles con versiones anteriores.
- **PATCH (Corrección):** correcciones de errores y mejoras de seguridad compatibles con versiones anteriores.

## Convenciones del registro de cambios

Al incorporar una nueva modificación:

1. Agregarla dentro de la sección `[Sin publicar]`.
2. Clasificarla en la categoría correspondiente: `Añadido`, `Modificado`, `Obsoleto`, `Eliminado`, `Corregido` o `Seguridad`.
3. Describir el cambio desde la perspectiva de quien utiliza ShieldPHP.
4. Al publicar una versión, trasladar los cambios correspondientes a una sección identificada con su número de versión y fecha de publicación.