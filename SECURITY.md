# Security Policy – ShieldPHP

## 🔐 Política de seguridad

La seguridad es uno de los objetivos principales de **ShieldPHP**.

Este proyecto proporciona componentes de autenticación, autorización, administración de sesiones, cifrado y protección de aplicaciones PHP.

Se agradecen los reportes responsables de vulnerabilidades que puedan comprometer la confidencialidad, integridad o disponibilidad de las aplicaciones que utilizan ShieldPHP.

Esta política establece cómo reportar problemas de seguridad y qué prácticas deben seguirse durante su investigación y divulgación.

---

## 📌 Versiones compatibles

ShieldPHP se encuentra actualmente en desarrollo.

| Versión | Estado de seguridad |
|---|---|
| `0.1.x` | En desarrollo |
| `1.x` | Pendiente de publicación |

Las versiones de desarrollo no deben considerarse adecuadas para aplicaciones críticas ni entornos de producción.

El mantenimiento de versiones anteriores dependerá de la disponibilidad de actualizaciones y del ciclo de vida de cada versión.

---

## 🚨 Reportar una vulnerabilidad

Si identificas una vulnerabilidad de seguridad, evita publicarla inicialmente en un issue, Pull Request o discusión pública.

Utiliza preferentemente el mecanismo privado de reporte de vulnerabilidades de GitHub, mediante **Security Advisories**, cuando esté habilitado para el repositorio.

Si esta opción no está disponible, contacta de forma privada con los responsables del proyecto mediante un canal de comunicación verificable.

No se debe asumir que una dirección de correo o contacto no publicado pertenece a los mantenedores.

### Información que debe incluir el reporte

Para facilitar la investigación, proporciona:

- Descripción de la vulnerabilidad.
- Componente o módulo afectado.
- Versión de ShieldPHP utilizada.
- Versión de PHP y entorno de ejecución.
- Pasos para reproducir el problema.
- Impacto potencial.
- Código de prueba de concepto, cuando sea necesario.
- Posibles medidas de mitigación.

Los reportes deben contener información suficiente para reproducir el problema sin poner en riesgo información real ni sistemas de terceros.

---

## 🛡️ Alcance de seguridad

Los siguientes módulos se consideran dentro del alcance de esta política:

| Módulo | Riesgos de seguridad contemplados |
|---|---|
| Authentication | Suplantación de identidad, bypass de autenticación y exposición de credenciales. |
| Authorization | Escalación de privilegios y acceso no autorizado. |
| JWT | Manipulación de tokens, validación incorrecta de firmas y reutilización de tokens revocados. |
| OAuth | CSRF, ataques de repetición, errores de validación de `state` y PKCE. |
| Crypto | Exposición de claves, cifrado inseguro y validación incorrecta de firmas. |
| Session | Secuestro de sesiones, fijación de sesiones y cookies inseguras. |
| Middleware | Bypass de controles de acceso, CSRF y limitación de solicitudes. |
| Validation | Validación insuficiente de entradas y URI de redirección. |
| Configuration | Exposición accidental de secretos y configuraciones inseguras. |

También se consideran relevantes las vulnerabilidades en las integraciones oficiales y los componentes auxiliares desarrollados dentro del repositorio.

---

## 🔍 Tipos de vulnerabilidades relevantes

Se consideran especialmente importantes:

### Authentication

- Bypass de autenticación.
- Verificación incorrecta de contraseñas.
- Exposición de información que permita enumerar usuarios.
- Fallos en la validación del estado de las cuentas.
- Debilidades en el proceso de inicio de sesión.

### JWT

- Aceptación de firmas inválidas.
- Confusión o sustitución de algoritmos.
- Aceptación de tokens expirados.
- Validación incorrecta de `issuer` o `audience`.
- Reutilización de tokens revocados.
- Manipulación de claims.

### OAuth 2.0

- Validación incorrecta del parámetro `state`.
- Ausencia o bypass de protección PKCE.
- Ataques de repetición.
- Redirecciones hacia dominios no autorizados.
- Intercambio inseguro de códigos de autorización.
- Validación incorrecta de perfiles externos.

### Cryptography

- Uso inseguro de claves criptográficas.
- Generación de valores aleatorios predecibles.
- Uso incorrecto de algoritmos criptográficos.
- Fallos de autenticidad durante el descifrado.
- Exposición de material criptográfico.

### Sessions

- Fijación de identificadores de sesión.
- Secuestro de sesiones.
- Regeneración incorrecta de identificadores.
- Cookies sin atributos de protección adecuados.
- Persistencia indebida de sesiones expiradas.

### Authorization

- Escalación de privilegios.
- Acceso a recursos sin permisos.
- Errores en la evaluación de roles.
- Asignación o revocación incorrecta de permisos.

---

## ⚠️ Actividades fuera de alcance

Salvo que demuestren una vulnerabilidad real en ShieldPHP, no se consideran reportes de seguridad válidos:

- Errores puramente visuales.
- Problemas de formato o documentación sin impacto de seguridad.
- Vulnerabilidades hipotéticas sin un escenario de explotación verificable.
- Resultados automatizados sin evidencia suficiente.
- Problemas que requieren que el usuario haya comprometido previamente su propio entorno.
- Recomendaciones generales que no demuestren un defecto concreto.

No se deben realizar pruebas destructivas, ataques de denegación de servicio ni evaluaciones contra infraestructura o sistemas de terceros sin autorización.

---

## 🤝 Divulgación responsable

ShieldPHP promueve la divulgación responsable de vulnerabilidades.

Se solicita a los investigadores:

1. Reportar el problema de manera privada.
2. Proporcionar los detalles técnicos necesarios para reproducirlo.
3. Evitar acceder, modificar o divulgar información de terceros.
4. No publicar detalles explotables antes de coordinar una solución.
5. Permitir que los responsables investiguen y preparen una corrección.

Los mantenedores evaluarán el impacto, la reproducibilidad y las medidas correctivas.

Cuando corresponda, podrán publicarse:

- Una versión corregida.
- Una descripción del problema.
- El alcance de la vulnerabilidad.
- Las versiones afectadas.
- Las medidas de mitigación.
- Créditos al investigador, si este lo autoriza.

No se garantiza un plazo específico de respuesta o corrección mientras el proyecto se encuentre en desarrollo.

---

## 🔧 Seguridad durante el desarrollo

Las contribuciones que afecten componentes sensibles deben contemplar:

- Validación estricta de entradas.
- Manejo seguro de excepciones.
- Protección de credenciales y secretos.
- Uso de funciones criptográficas adecuadas.
- Verificación de firmas y datos de tokens.
- Pruebas de escenarios negativos.
- Prevención de regresiones de seguridad.
- Revisión de dependencias externas.

Los cambios en autenticación, autorización, OAuth, JWT y criptografía deben incluir pruebas automatizadas cuando corresponda.

### Secretos y credenciales

Nunca deben incluirse en el repositorio:

- Contraseñas reales.
- Claves privadas.
- Claves de cifrado.
- Secretos OAuth.
- Tokens de acceso.
- Credenciales de bases de datos.
- Archivos `.env` con información sensible.

Se recomienda utilizar variables de entorno y archivos de configuración locales excluidos del control de versiones.

---

## 🧪 Pruebas de seguridad

El proyecto mantiene una suite de pruebas organizada por niveles:

```text
tests/
├── Unit/
│   ├── Auth/
│   ├── Authorization/
│   ├── Config/
│   ├── Crypto/
│   ├── JWT/
│   ├── OAuth/
│   ├── Session/
│   ├── Validation/
│   └── Exceptions/
│
├── Integration/
│
└── Security/
    ├── ExpiredTokenTest.php
    ├── OAuthStateTest.php
    ├── TokenTamperingTest.php
    └── TokenReplayTest.php
```

Ejecutar las pruebas de seguridad:

```bash
vendor/bin/phpunit tests/Security
```

Ejecutar todas las pruebas:

```bash
composer test
```

Las pruebas automatizadas ayudan a detectar comportamientos incorrectos, pero no sustituyen una auditoría independiente de seguridad.

---

## 📢 Actualizaciones de seguridad

Las correcciones importantes deberán documentarse en el historial de cambios del proyecto.

Cuando corresponda, se publicarán avisos para informar sobre:

- Vulnerabilidades corregidas.
- Versiones afectadas.
- Versiones corregidas.
- Impacto potencial.
- Recomendaciones de actualización.

Se recomienda mantener ShieldPHP y sus dependencias actualizadas.

---

## 📄 Licencia y responsabilidad

ShieldPHP es un proyecto en desarrollo y se distribuye conforme a los términos establecidos en su archivo `LICENSE`.

La implementación de ShieldPHP no garantiza por sí misma la seguridad de una aplicación. Los desarrolladores deben configurar correctamente los componentes, implementar almacenamiento persistente seguro, proteger las claves y revisar los mecanismos de autenticación y autorización antes del despliegue.

---

<p align="center">
  <strong>ShieldPHP</strong>
  <br>
  <i>Security. Simplicity. Control.</i>
</p>
