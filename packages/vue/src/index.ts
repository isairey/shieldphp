
/**
 * ShieldPHP Vue
 *
 * Main Entry Point
 *
 * Official public API for the ShieldPHP Vue integration.
 *
 * Modules:
 * - Authentication
 * - User management
 * - Authorization
 * - HTTP client
 * - QuickAuthClient
 * - Vue composables
 * - Vue Router guards
 * - Vue plugins
 * - TypeScript definitions
 */

// ==============================================
// AUTHENTICATION
// ==============================================

export {
    default as AuthService,
    AuthServiceError,
} from './auth/AuthService';

// ==============================================
// AUTHENTICATION STATE
// ==============================================

export {
    default as AuthState,
} from './auth/AuthState';

// ==============================================
// AUTHENTICATED USER
// ==============================================

export {
    isAuthUser,
    createAuthUser,
    getUserDisplayName,

    userHasRole,
    userHasAnyRole,
    userHasAllRoles,

    userHasPermission,
    userHasAnyPermission,
    userHasAllPermissions,
} from './auth/AuthUser';

// ==============================================
// AUTHENTICATION GUARD CLASS
// ==============================================

export {
    default as AuthGuard,
} from './auth/AuthGuard';

// ==============================================
// HTTP CLIENT
// ==============================================

export {
    default as HttpClient,
} from './client/HttpClient';

// ==============================================
// QUICK AUTH CLIENT
// ==============================================

export {
    default as QuickAuthClient,
} from './client/QuickAuthClient';

// ==============================================
// AUTH COMPOSABLE
// ==============================================




export {
    useAuth,
    SHIELDPHP_INJECTION_KEY,
} from './composables/useAuth';
// ==============================================
// USER COMPOSABLE
// ==============================================

export {
    useUser,
} from './composables/useUser';

// ==============================================
// VUE ROUTER GUARDS
// ==============================================

export {
    createAuthGuard,
    installAuthGuard,
} from './guards/authGuard';

// ==============================================
// VUE PLUGIN
// ==============================================

export {
    createQuickAuthPlugin,
    quickAuthPlugin,
} from './plugins/quickAuthPlugin';

// ==============================================
// TYPESCRIPT DEFINITIONS
// ==============================================

export type * from './types';
