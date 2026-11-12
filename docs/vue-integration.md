# Vue Integration — ShieldPHP

<p align="center">
  **Integración de ShieldPHP con aplicaciones Vue.js**
  <br>
  Autenticación, autorización y comunicación segura entre frontend y backend.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Vue.js-3-42B883?style=for-the-badge&logo=vuedotjs&logoColor=white" alt="Vue 3">
  <img src="https://img.shields.io/badge/TypeScript-Supported-3178C6?style=for-the-badge&logo=typescript&logoColor=white" alt="TypeScript">
  <img src="https://img.shields.io/badge/ShieldPHP-In%20Development-orange?style=for-the-badge" alt="ShieldPHP en desarrollo">
</p>

---

## 1. Introducción

**ShieldPHP Vue Integration** es una guía de integración para utilizar ShieldPHP como solución de autenticación y seguridad en aplicaciones frontend desarrolladas con Vue.js.

ShieldPHP proporciona componentes de seguridad en PHP, mientras que Vue se encarga de construir la interfaz de usuario y consumir los servicios HTTP del backend.

La integración permite organizar la comunicación entre ambas capas y establecer un flujo de autenticación consistente.

### Objetivos

- Conectar Vue con un backend PHP protegido mediante ShieldPHP.
- Implementar autenticación de usuarios.
- Consumir endpoints HTTP protegidos.
- Administrar el estado de autenticación.
- Integrar JWT cuando la aplicación utilice tokens Bearer.
- Gestionar sesiones mediante cookies.
- Proteger rutas del frontend.
- Manejar errores de autenticación.
- Facilitar la integración con Vue Router y Pinia.
- Aplicar buenas prácticas de seguridad en aplicaciones SPA.

**Importante:** esta documentación describe la integración de una aplicación Vue con un backend que utiliza ShieldPHP. No implica que exista un paquete npm oficial ni un adaptador Vue incluido en el repositorio.

## 2. Arquitectura

La arquitectura separa las responsabilidades del frontend y del backend.

```text
                  VUE APPLICATION
                         |
          ┌──────────────┼──────────────┐
          |              |              |
          v              v              v
       VIEWS          ROUTER          PINIA
          |              |              |
          └──────────────┼──────────────┘
                         |
                         v
                  AUTH SERVICES
                         |
                         v
                   HTTP CLIENT
                         |
                    HTTPS / API
                         |
                         v
                  PHP BACKEND
                         |
             ┌───────────┼───────────┐
             |           |           |
             v           v           v
          ShieldPHP    Controllers  Database
             |
       ┌─────┼──────┬──────────┐
       |     |      |          |
       v     v      v          v
      Auth   JWT  Sessions  Authorization
```

### Responsabilidades

| Componente | Responsabilidad |
|---|---|
| Vue 3 | Interfaz de usuario. |
| Vue Router | Navegación y protección visual de rutas. |
| Pinia | Estado global de autenticación. |
| Axios | Comunicación HTTP con el backend. |
| Auth Service | Operaciones de inicio y cierre de sesión. |
| ShieldPHP Auth | Verificación de credenciales. |
| ShieldPHP JWT | Emisión y validación de tokens, cuando se utilicen. |
| ShieldPHP Sessions | Administración de sesiones, cuando se utilicen. |
| ShieldPHP Authorization | Comprobación de permisos en el backend. |

## 3. Requisitos

### Backend

- PHP 8.2 o superior.
- Composer.
- ShieldPHP Core.
- Un servidor HTTP compatible con PHP.
- Una API HTTP que exponga los endpoints necesarios.
- HTTPS en producción.

### Frontend

- Node.js.
- npm.
- Vue 3.
- Vite.
- TypeScript recomendado.
- Vue Router.
- Pinia.
- Axios.

### Instalar dependencias

Dentro del proyecto Vue:

```bash
npm install
```

Instala las dependencias de integración:

```bash
npm install axios pinia vue-router
```

Para una aplicación Vue con TypeScript, utiliza los tipos correspondientes y configura correctamente el proyecto con Vite.

## 4. Estructura recomendada del frontend

Una estructura organizada facilita mantener separados los servicios HTTP, la autenticación y los componentes visuales.

```text
vue-application/
│
├── public/
│
├── src/
│   │
│   ├── assets/
│   │
│   ├── components/
│   │   ├── auth/
│   │   │   ├── LoginForm.vue
│   │   │   └── LogoutButton.vue
│   │   │
│   │   └── common/
│   │
│   ├── layouts/
│   │   ├── AuthLayout.vue
│   │   └── AppLayout.vue
│   │
│   ├── router/
│   │   └── index.ts
│   │
│   ├── services/
│   │   ├── api.ts
│   │   └── auth.service.ts
│   │
│   ├── stores/
│   │   └── auth.store.ts
│   │
│   ├── types/
│   │   └── auth.types.ts
│   │
│   ├── views/
│   │   ├── auth/
│   │   │   ├── LoginView.vue
│   │   │   └── OAuthCallbackView.vue
│   │   │
│   │   ├── DashboardView.vue
│   │   └── NotFoundView.vue
│   │
│   ├── App.vue
│   └── main.ts
│
├── .env
├── .env.example
├── index.html
├── package.json
└── vite.config.ts
```

Esta estructura es una propuesta de organización para aplicaciones Vue que consuman ShieldPHP.

## 5. Configuración del backend

ShieldPHP funciona dentro del backend PHP. La aplicación debe exponer endpoints HTTP que utilicen los componentes de ShieldPHP.

Los siguientes endpoints representan un contrato de API de ejemplo. No son rutas que ShieldPHP registre automáticamente.

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/auth/login` | Autenticar usuario. |
| GET | `/api/auth/me` | Obtener usuario autenticado. |
| POST | `/api/auth/logout` | Cerrar sesión. |
| POST | `/api/auth/refresh` | Renovar credenciales, si el backend implementa esta funcionalidad. |
| GET | `/api/user/profile` | Obtener el perfil del usuario. |
| GET | `/api/user/permissions` | Consultar permisos. |

### Ejemplo de solicitud de login

```json
{
  "email": "usuario@example.com",
  "password": "PasswordSeguro123!"
}
```

### Ejemplo de respuesta

El backend puede devolver una respuesta como:

```json
{
  "success": true,
  "data": {
    "access_token": "ACCESS_TOKEN",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
      "id": "user-123",
      "email": "usuario@example.com",
      "roles": ["user"]
    }
  }
}
```

La estructura es ilustrativa. El backend debe implementar y documentar su propio formato de respuesta.

### Ejemplo de error

```json
{
  "success": false,
  "message": "Credenciales incorrectas"
}
```

No se deben revelar detalles sobre si una cuenta existe, está desactivada o tiene una contraseña incorrecta.

## 6. Variables de entorno

En la raíz del proyecto Vue crea:

```text
.env.example
```

Contenido:

```dotenv
# ==========================================
# SHIELDPHP API CONFIGURATION
# ==========================================

VITE_API_URL=http://localhost:8000/api

# ==========================================
# APPLICATION
# ==========================================

VITE_APP_NAME=ShieldPHP Application
VITE_APP_ENV=development
```

Crea tu archivo `.env` a partir de esta plantilla y ajusta la URL de tu backend.

### Consideraciones

Las variables que utilizan el prefijo `VITE_` se incorporan al código frontend y pueden ser visibles para los usuarios.

Nunca incluyas en ellas:

- Claves privadas.
- Secretos JWT.
- Secretos OAuth.
- Contraseñas de bases de datos.
- Claves de cifrado.
- Credenciales administrativas.

Los secretos deben permanecer en el backend.

## 7. Configuración de Axios

Se recomienda centralizar la comunicación HTTP para mantener una configuración consistente.

Archivo:

```text
src/services/api.ts
```

```typescript
import axios from 'axios';

let accessToken: string | null = null;

export function setAccessToken(token: string | null): void {
    accessToken = token;
}

export const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    timeout: 15000,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

api.interceptors.request.use(
    (config) => {
        if (accessToken) {
            config.headers.Authorization = `Bearer ${accessToken}`;
        }

        return config;
    },
    (error) => Promise.reject(error)
);

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            setAccessToken(null);
        }

        return Promise.reject(error);
    }
);
```

### Características

- Centraliza las solicitudes HTTP.
- Configura la URL base.
- Define un tiempo máximo de espera.
- Adjunta el token Bearer cuando existe.
- Maneja respuestas HTTP 401.
- Mantiene el token en memoria, no en almacenamiento persistente del navegador.

Esta implementación es una base sencilla para el flujo JWT. La aplicación debe completar la gestión del estado y la recuperación de sesión según su estrategia de autenticación.

## 8. Tipos de autenticación

La integración contempla dos estrategias principales.

### 8.1 Autenticación mediante JWT

En este modelo el backend emite un token de acceso después de verificar las credenciales.

Vue utiliza el token para realizar solicitudes protegidas.

```text
             VUE
              |
              v
       POST /auth/login
              |
              v
        PHP BACKEND
              |
              v
        ShieldPHP Auth
              |
              v
       Credenciales válidas
              |
              v
        ShieldPHP JWT
              |
              v
        Access Token
              |
              v
             VUE
              |
              v
     Authorization: Bearer
              |
              v
       Protected Endpoint
```

El token debe enviarse en el encabezado:

```http
Authorization: Bearer ACCESS_TOKEN
```

**Recomendación:** conserva el access token en memoria siempre que sea posible. Evita guardar tokens de larga duración en `localStorage` o `sessionStorage`, porque un ataque XSS puede permitir su extracción.

Si el navegador necesita conservar la autenticación después de recargar la página, define un mecanismo de renovación seguro en el backend. No presupongas que existe rotación persistente de refresh tokens en ShieldPHP mientras esa funcionalidad no esté implementada y validada.

### 8.2 Autenticación mediante sesiones

Este modelo utiliza sesiones administradas por PHP y cookies enviadas al navegador.

```text
             VUE
              |
              v
       POST /auth/login
              |
              v
        PHP BACKEND
              |
              v
        ShieldPHP Auth
              |
              v
        SessionManager
              |
              v
       Session Cookie
              |
              v
             VUE
              |
              v
       Protected Endpoint
              |
              v
       Validación de sesión
```

Para este modelo, configura Axios con:

```typescript
import axios from 'axios';

export const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    timeout: 15000,
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});
```

La cookie debe configurarse desde PHP con los atributos apropiados:

- `HttpOnly`.
- `Secure`.
- `SameSite`.
- `Path`.
- `Domain`, cuando corresponda.

Una cookie `HttpOnly` no debe leerse desde JavaScript. El navegador se encarga de enviarla cuando las condiciones de la cookie lo permiten.

Las solicitudes que modifican datos deben incorporar protección CSRF.

### Elección de estrategia

| Necesidad | Estrategia |
|---|---|
| Aplicación web del mismo sitio que el backend | Sesiones con cookies. |
| API consumida por diferentes clientes | JWT, con una política segura de tokens. |
| Aplicación SPA con backend propio | Sesiones o JWT según arquitectura. |
| Aplicación con cookies de autenticación | Protección CSRF obligatoria para operaciones sensibles. |

No se recomienda combinar ambas estrategias sin definir claramente cuál es la autoridad de autenticación.

## 9. Servicio de autenticación

El servicio `auth.service.ts` concentra las operaciones de comunicación con los endpoints de autenticación.

Archivo:

```text
src/services/auth.service.ts
```

```typescript
import { api } from './api';

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface AuthUser {
    id: string;
    email: string;
    roles: string[];
}

export interface LoginResponse {
    success: boolean;
    data: {
        access_token: string;
        token_type: 'Bearer';
        expires_in: number;
        user: AuthUser;
    };
}

export const authService = {
    async login(credentials: LoginCredentials) {
        const response = await api.post<LoginResponse>(
            '/auth/login',
            credentials
        );

        return response.data;
    },

    async me() {
        const response = await api.get<AuthUser>('/auth/me');

        return response.data;
    },

    async logout() {
        const response = await api.post('/auth/logout');

        return response.data;
    },
};
```

Los tipos deben adaptarse al JSON real que devuelva el backend. En particular, el servicio `me()` debe utilizar el mismo formato de respuesta que implemente el endpoint.

## 10. Estado global con Pinia

Pinia permite centralizar la información del usuario autenticado.

Archivo:

```text
src/stores/auth.store.ts
```

```typescript
import { computed, ref } from 'vue';
import { defineStore } from 'pinia';

import {
    authService,
    type AuthUser,
    type LoginCredentials,
} from '../services/auth.service';

import { setAccessToken } from '../services/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<AuthUser | null>(null);

    const loading = ref(false);
    const initialized = ref(false);

    const isAuthenticated = computed(() => user.value !== null);

    async function login(credentials: LoginCredentials): Promise<void> {
        loading.value = true;

        try {
            const response = await authService.login(credentials);

            setAccessToken(response.data.access_token);
            user.value = response.data.user;
        } finally {
            loading.value = false;
        }
    }

    async function logout(): Promise<void> {
        loading.value = true;

        try {
            if (isAuthenticated.value) {
                await authService.logout();
            }
        } finally {
            user.value = null;
            setAccessToken(null);
            loading.value = false;
        }
    }

    function clearSession(): void {
        user.value = null;
        setAccessToken(null);
    }

    return {
        user,
        loading,
        initialized,
        isAuthenticated,
        login,
        logout,
        clearSession,
    };
});
```

Este ejemplo utiliza el flujo JWT con token en memoria. Para una aplicación basada exclusivamente en cookies de sesión, elimina la administración del access token y configura Axios con `withCredentials`.

El método `logout()` limpia el estado del frontend incluso cuando la solicitud al backend falla. El backend debe encargarse de invalidar correctamente la sesión o credencial.

## 11. Configuración de Pinia

Archivo:

```text
src/main.ts
```

```typescript
import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';

const app = createApp(App);

const pinia = createPinia();

app.use(pinia);
app.use(router);

app.mount('#app');
```

Pinia debe registrarse antes de utilizar los stores en componentes y guards de navegación.

## 12. Protección de rutas con Vue Router

Vue Router permite controlar la navegación visual de la aplicación según el estado de autenticación.

Archivo:

```text
src/router/index.ts
```

```typescript
import {
    createRouter,
    createWebHistory,
} from 'vue-router';

import { useAuthStore } from '../stores/auth.store';

import LoginView from '../views/auth/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import NotFoundView from '../views/NotFoundView.vue';

const router = createRouter({
    history: createWebHistory(),

    routes: [
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: {
                guestOnly: true,
            },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: DashboardView,
            meta: {
                requiresAuth: true,
            },
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: NotFoundView,
        },
    ],
});

router.beforeEach((to) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        };
    }

    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return {
            name: 'dashboard',
        };
    }

    return true;
});

export default router;
```

### Importante

Un guard de Vue Router no constituye un mecanismo de seguridad del backend.

Los usuarios pueden modificar el frontend o realizar solicitudes HTTP directamente.

Por ello:

- El backend debe verificar la autenticación en cada endpoint protegido.
- El backend debe comprobar los permisos necesarios.
- No se deben confiar los roles enviados por el navegador.
- Las decisiones de autorización deben realizarse en el servidor.

## 13. Ejemplo de LoginView

Archivo:

```text
src/views/auth/LoginView.vue
```

```vue
<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { useAuthStore } from '../../stores/auth.store';

const authStore = useAuthStore();

const router = useRouter();
const route = useRoute();

const form = reactive({
    email: '',
    password: '',
});

const errorMessage = ref('');

async function handleLogin() {
    errorMessage.value = '';

    if (!form.email || !form.password) {
        errorMessage.value = 'Completa todos los campos.';
        return;
    }

    try {
        await authStore.login({
            email: form.email,
            password: form.password,
        });

        const redirect = route.query.redirect;

        if (typeof redirect === 'string' && redirect.startsWith('/')) {
            await router.replace(redirect);
        } else {
            await router.replace({ name: 'dashboard' });
        }
    } catch {
        errorMessage.value = 'No fue posible iniciar sesión.';
    }
}
</script>

<template>
    <main class="login-container">
        <section class="login-card">
            <h1>Iniciar sesión</h1>

            <p>Accede a tu cuenta.</p>

            <form @submit.prevent="handleLogin">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>

                    <input
                        id="email"
                        v-model.trim="form.email"
                        type="email"
                        autocomplete="username"
                        required
                    />
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>

                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        required
                    />
                </div>

                <p v-if="errorMessage" role="alert">
                    {{ errorMessage }}
                </p>

                <button
                    type="submit"
                    :disabled="authStore.loading"
                >
                    {{
                        authStore.loading
                            ? 'Iniciando sesión...'
                            : 'Iniciar sesión'
                    }}
                </button>
            </form>
        </section>
    </main>
</template>
```

Este componente es un ejemplo funcional del lado de Vue, siempre que el backend implemente el contrato de API descrito anteriormente.

## 14. Consultar el usuario autenticado

El endpoint `/api/auth/me` permite conocer la identidad asociada a la credencial actual.

La aplicación puede consultar este endpoint para comprobar el estado de autenticación.

Ejemplo:

```typescript
const user = await authService.me();

console.log(user);
```

El backend debe obtener la identidad de la solicitud autenticada, no confiar en un identificador de usuario proporcionado por el frontend.

En un flujo JWT, el endpoint debe validar el token recibido.

En un flujo de sesión, debe recuperar la identidad desde el estado autenticado de la sesión.

## 15. Manejo de errores HTTP

Es recomendable manejar los errores de forma centralizada.

| Código HTTP | Significado | Acción en Vue |
|---|---|---|
| 400 | Solicitud incorrecta | Mostrar validaciones. |
| 401 | No autenticado | Limpiar credenciales y redirigir al login. |
| 403 | Sin permisos | Mostrar acceso denegado. |
| 404 | Recurso no encontrado | Mostrar página correspondiente. |
| 422 | Error de validación | Mostrar errores del formulario. |
| 429 | Demasiadas solicitudes | Mostrar mensaje y respetar `Retry-After`. |
| 500 | Error del servidor | Mostrar mensaje genérico. |

No muestres al usuario mensajes internos, consultas SQL, trazas de PHP ni información criptográfica.

## 16. Integración con OAuth 2.0

ShieldPHP contempla proveedores OAuth, como Google y Facebook.

Vue puede iniciar el flujo enviando al usuario al endpoint del backend que construya la URL de autorización.

### Flujo recomendado

```text
                VUE APPLICATION
                       |
                       v
               GET /auth/google
                       |
                       v
                 PHP BACKEND
                       |
                       v
               OAuthManager
                       |
                       v
               GOOGLE PROVIDER
                       |
                       v
              GOOGLE AUTHORIZATION
                       |
                       v
                 USER CONSENT
                       |
                       v
                PROVIDER CALLBACK
                       |
                       v
                 PHP BACKEND
                       |
                       v
             VALIDATE STATE / PKCE
                       |
                       v
              CREATE AUTH SESSION
                       |
                       v
                REDIRECT TO VUE
```

### Iniciar autenticación con Google

```typescript
export function loginWithGoogle(): void {
    window.location.assign(
        `${import.meta.env.VITE_API_URL}/auth/google`
    );
}
```

La URL del ejemplo debe corresponder a una ruta implementada por el backend.

El callback debe procesarse preferentemente en el servidor. Después de completar la autenticación, el backend puede establecer una sesión segura o entregar un flujo de acceso diseñado para el frontend.

No envíes tokens de acceso o secretos como parámetros de una URL.

### Ejemplo de botón

```vue
<script setup lang="ts">
function loginWithGoogle() {
    window.location.assign(
        `${import.meta.env.VITE_API_URL}/auth/google`
    );
}
</script>

<template>
    <button @click="loginWithGoogle">
        Continuar con Google
    </button>
</template>
```

### Consideraciones

- Validar `state`.
- Utilizar PKCE cuando el proveedor y el flujo lo admitan.
- Registrar exactamente las URL de callback.
- Verificar la identidad en el backend.
- Comprobar la respuesta del proveedor.
- No confiar únicamente en el correo electrónico.
- No exponer secretos OAuth en Vue.

La validación criptográfica completa de ID Tokens de Google debe estar implementada y verificada en el backend antes de considerarla una integración terminada.

## 17. Configuración CORS

Cuando Vue y PHP se ejecutan en dominios o puertos diferentes, el backend debe configurar correctamente CORS.

Ejemplo conceptual:

```text
Frontend:
http://localhost:5173

Backend:
http://localhost:8000
```

El servidor debe permitir el origen exacto del frontend y los métodos y encabezados necesarios.

Para solicitudes con cookies:

- Configurar `Access-Control-Allow-Credentials: true`.
- Definir un origen explícito.
- No utilizar `*` como origen permitido.
- Configurar correctamente los atributos de las cookies.
- Implementar protección CSRF.

Para solicitudes JWT con `Authorization`, permitir explícitamente ese encabezado en la política CORS.

CORS no reemplaza la autenticación ni la autorización.

## 18. Protección de datos en el frontend

Vue debe tratar los datos recibidos desde el backend como información no confiable hasta que hayan sido validados y procesados correctamente.

### Recomendaciones

- Evitar almacenar contraseñas después del login.
- No guardar claves privadas en el código frontend.
- Evitar persistir access tokens en `localStorage`.
- Utilizar conexiones HTTPS.
- Validar entradas del usuario.
- Evitar renderizar contenido no confiable mediante `v-html`.
- No incluir credenciales en logs.
- No confiar en permisos definidos exclusivamente en el frontend.
- Utilizar protección CSRF cuando se autentique mediante cookies.
- Configurar correctamente la política CORS.
- Evitar incluir tokens en URL, parámetros de navegación o mensajes de error.

### Prevención de XSS

Vue escapa automáticamente las interpolaciones de texto habituales.

Sin embargo, se debe tener especial cuidado con:

```vue
<div v-html="untrustedContent"></div>
```

No utilices `v-html` con contenido controlado por usuarios sin una sanitización adecuada.

## 19. Logout

El cierre de sesión debe realizarse tanto en el backend como en el frontend.

Ejemplo:

```typescript
await authStore.logout();

await router.replace({
    name: 'login',
});
```

El backend debe realizar las operaciones pertinentes:

- Invalidar la sesión actual cuando se utiliza autenticación por sesión.
- Revocar la credencial cuando exista un mecanismo de revocación compatible.
- Invalidar refresh tokens cuando la estrategia de autenticación los utilice.
- Eliminar las cookies correspondientes.

Eliminar un token de la memoria de Vue no invalida automáticamente un JWT que ya fue emitido.

## 20. Pruebas de integración

La integración debe probarse tanto en Vue como en el backend.

### Frontend

Se recomienda probar:

- Renderizado del formulario de acceso.
- Validaciones de campos.
- Inicio de sesión exitoso.
- Inicio de sesión fallido.
- Limpieza del estado al cerrar sesión.
- Protección de rutas.
- Respuestas HTTP 401 y 403.
- Comportamiento ante errores de red.
- Restauración de autenticación según la estrategia seleccionada.

### Backend

Se recomienda probar:

- Validación de credenciales.
- Emisión y validación de JWT, si aplica.
- Gestión de sesiones, si aplica.
- Validación de permisos.
- Expiración de credenciales.
- Revocación.
- Protección CSRF.
- Configuración CORS.
- Flujos OAuth.

### Pruebas manuales

Para verificar la integración:

1. Inicia el backend PHP.
2. Inicia Vue con Vite.
3. Comprueba que el frontend pueda comunicarse con la API.
4. Intenta iniciar sesión con credenciales válidas.
5. Intenta iniciar sesión con credenciales inválidas.
6. Accede a una ruta protegida sin autenticarte.
7. Realiza una solicitud a un endpoint protegido.
8. Cierra la sesión.
9. Comprueba que la credencial haya sido invalidada en el servidor.

## 21. Solución de problemas

### Error CORS

**Síntoma:**

```text
Access to XMLHttpRequest has been blocked by CORS policy.
```

**Solución:**

- Comprueba el origen del frontend.
- Verifica la configuración CORS del backend.
- Revisa los encabezados permitidos.
- Comprueba las solicitudes OPTIONS.
- Si utilizas cookies, habilita credenciales y evita el origen `*`.

### Error 401

**Síntoma:**

```text
Request failed with status code 401
```

**Causas posibles:**

- Token ausente.
- Token expirado.
- Cookie no enviada.
- Sesión inexistente.
- Firma JWT inválida.
- Backend sin autenticación válida.

**Solución:**

Comprueba el encabezado `Authorization` o la cookie de sesión, de acuerdo con la estrategia seleccionada.

### Error 403

**Síntoma:**

El usuario está autenticado, pero no puede acceder al recurso.

**Causa:**

La identidad no tiene los permisos necesarios.

**Solución:**

Revisa la configuración de autorización del backend. No intentes resolver un error 403 permitiendo el acceso desde Vue.

### El usuario pierde la autenticación al recargar

**Causa:**

El access token se mantiene únicamente en memoria y desaparece al recargar la aplicación.

**Solución:**

Define una estrategia segura de restauración de autenticación.

Las alternativas incluyen:

- Sesiones persistidas mediante cookies `HttpOnly`.
- Un mecanismo backend de renovación de credenciales.
- Un flujo de autenticación nuevamente iniciado cuando sea necesario.

No utilices automáticamente `localStorage` para persistir tokens sensibles.

### Error al consultar `/auth/me`

Comprueba que el backend implemente el endpoint, valide correctamente la credencial y devuelva el formato esperado por el servicio frontend.

## 22. Buenas prácticas

### Arquitectura

- Mantener separadas las vistas, servicios y stores.
- Centralizar las solicitudes HTTP.
- Utilizar TypeScript para definir contratos de datos.
- Evitar duplicar la lógica de autenticación.
- Mantener el backend como autoridad sobre la identidad y los permisos.

### Seguridad

- Utilizar HTTPS en producción.
- Mantener los secretos exclusivamente en el servidor.
- Preferir cookies `HttpOnly` cuando la arquitectura lo permita.
- Proteger solicitudes basadas en cookies contra CSRF.
- Evitar almacenar tokens de larga duración en almacenamiento accesible a JavaScript.
- Validar todas las credenciales en el backend.
- Aplicar límites de solicitudes al proceso de autenticación.
- No confiar en los guards del frontend como mecanismo de autorización.

### Mantenimiento

- Mantener los contratos API documentados.
- Versionar endpoints cuando sea necesario.
- Ejecutar pruebas de frontend y backend.
- Revisar dependencias.
- Controlar errores sin filtrar información sensible.

## 23. Estado de la integración

| Funcionalidad | Estado |
|---|---|
| Guía de conexión Vue 3 | Documentada |
| Configuración Axios | Ejemplo proporcionado |
| Servicio de autenticación | Ejemplo proporcionado |
| Pinia | Ejemplo proporcionado |
| Vue Router | Ejemplo proporcionado |
| Flujo JWT | Documentado |
| Flujo de sesiones | Documentado |
| OAuth Google | Flujo documentado |
| OAuth Facebook | Flujo extensible según proveedor |
| CORS | Consideraciones documentadas |
| Protección CSRF | Recomendaciones documentadas |
| Adaptador npm oficial | No implementado |
| Paquete Vue oficial | No implementado |
| Pruebas E2E de la integración | Pendientes |
| Integración de referencia completa | Pendiente de validación |

La guía ofrece una base para conectar aplicaciones Vue con un backend que utiliza ShieldPHP. Los ejemplos requieren adaptarse a las rutas y contratos que implemente cada backend.

## 24. Recursos relacionados

### Documentación de ShieldPHP

- `README.md` — Documentación principal.
- `docs/api-reference.md` — Referencia de la API.
- `docs/authentication.md` — Autenticación.
- `docs/authorization.md` — Autorización.
- `docs/configuration.md` — Configuración.
- `docs/jwt.md` — JSON Web Tokens.
- `docs/oauth2.md` — OAuth 2.0.
- `docs/sessions.md` — Sesiones.
- `docs/security.md` — Seguridad.
- `docs/troubleshooting.md` — Solución de problemas.

### Recursos externos

- [Vue.js](https://vuejs.org/)
- [Vue Router](https://router.vuejs.org/)
- [Pinia](https://pinia.vuejs.org/)
- [Axios](https://axios-http.com/)
- [Vite](https://vite.dev/)
- [TypeScript](https://www.typescriptlang.org/)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)

---

<p align="center">
  **ShieldPHP Vue Integration**
  <br>
  Una integración organizada entre PHP y Vue.js.
</p>

<p align="center">
  <sub>Proyecto en desarrollo. Los ejemplos de API son ilustrativos y deben adaptarse al backend real.</sub>
</p>