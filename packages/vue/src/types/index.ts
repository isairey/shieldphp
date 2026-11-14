
/**
 * ShieldPHP Vue
 *
 * Central Type Definitions
 *
 * This file exports the public TypeScript types used
 * throughout the ShieldPHP Vue integration package.
 *
 * It provides a centralized entry point for:
 * - Authentication
 * - User management
 * - Authorization
 * - HTTP communication
 * - Vue Router guards
 * - Vue composables
 * - Plugin configuration
 * - QuickAuthClient configuration
 */

// ==============================================
// AUTHENTICATION
// ==============================================

export type {
    LoginCredentials,
    AuthSession,
    AuthEndpoints,
    AuthServiceOptions,
    ApiResponse,
} from '../auth/AuthService';

// ==============================================
// AUTHENTICATED USER
// ==============================================

export type {
    AuthUser,
} from '../auth/AuthUser';

// ==============================================
// AUTHENTICATION STATE
// ==============================================

export type {
    AuthStatus,
    AuthStateSnapshot,
} from '../auth/AuthState';

// ==============================================
// HTTP CLIENT
// ==============================================

export type {
    HttpAuthMode,
    HttpClientOptions,
} from '../client/HttpClient';

// ==============================================
// QUICK AUTH CLIENT
// ==============================================

export type {
    QuickAuthClientOptions,
} from '../client/QuickAuthClient';

// ==============================================
// AUTHORIZATION GUARDS
// ==============================================

export type {
    AuthGuardOptions,
} from '../guards/authGuard';

// ==============================================
// AUTH COMPOSABLE
// ==============================================

export type {
    UseAuthOptions,
    UseAuthReturn,
} from '../composables/useAuth';

// ==============================================
// USER COMPOSABLE
// ==============================================

export type {
    UseUserOptions,
    UseUserReturn,
} from '../composables/useUser';

// ==============================================
// VUE PLUGIN
// ==============================================

export type {
    QuickAuthPluginOptions,
} from '../plugins/quickAuthPlugin';

/**
 * ShieldPHP Vue public type definitions.
 *
 * Import examples:
 *
 * import type {
 *     AuthUser,
 *     AuthSession,
 *     LoginCredentials,
 *     HttpClientOptions,
 *     QuickAuthClientOptions,
 *     UseAuthReturn,
 * } from 'shieldphp-vue';
 */
