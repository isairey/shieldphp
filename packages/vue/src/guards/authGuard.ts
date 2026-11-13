
import type {
    NavigationGuard,
    RouteLocationRaw,
    Router,
} from 'vue-router';

import type AuthState from '../auth/AuthState';

import {
    userHasAnyPermission,
    userHasAllPermissions,
} from '../auth/AuthUser';

/**
 * Extend Vue Router route metadata.
 */
declare module 'vue-router' {
    interface RouteMeta {
        /**
         * Requires an authenticated user.
         */
        requiresAuth?: boolean;

        /**
         * Only accessible to unauthenticated users.
         */
        guestOnly?: boolean;

        /**
         * Roles required to access the route.
         */
        roles?: string[];

        /**
         * Role validation strategy.
         *
         * any: at least one role is required.
         * all: all specified roles are required.
         */
        roleMatch?: 'any' | 'all';

        /**
         * Permissions required to access the route.
         */
        permissions?: string[];

        /**
         * Permission validation strategy.
         *
         * any: at least one permission is required.
         * all: all specified permissions are required.
         */
        permissionMatch?: 'any' | 'all';
    }
}

/**
 * AuthGuard configuration.
 */
export interface AuthGuardOptions {
    /**
     * Route used for unauthenticated users.
     *
     * Defaults to the named route "login".
     */
    loginRoute?: RouteLocationRaw;

    /**
     * Route used when an authenticated user
     * attempts to access a guest-only page.
     *
     * Defaults to the named route "dashboard".
     */
    guestRedirectRoute?: RouteLocationRaw;

    /**
     * Route used when the user lacks permissions.
     *
     * Defaults to cancelling navigation.
     */
    forbiddenRoute?: RouteLocationRaw;

    /**
     * Route used when authentication cannot
     * be verified due to a backend error.
     *
     * Defaults to cancelling navigation.
     */
    errorRoute?: RouteLocationRaw;

    /**
     * Preserve the requested destination when
     * redirecting to the login page.
     *
     * Defaults to true.
     */
    preserveRedirect?: boolean;
}

/**
 * Creates a login redirect.
 */
function createLoginRedirect(
    loginRoute: RouteLocationRaw,
    redirectPath: string,
    preserveRedirect: boolean
): RouteLocationRaw {
    if (!preserveRedirect) {
        return loginRoute;
    }

    if (typeof loginRoute === 'string') {
        return {
            path: loginRoute,
            query: {
                redirect: redirectPath,
            },
        };
    }

    return {
        ...loginRoute,
        query: {
            ...loginRoute.query,
            redirect: redirectPath,
        },
    } as RouteLocationRaw;
}

/**
 * Validates whether the user has the required roles.
 */
function validateRoles(
    authState: AuthState,
    roles: string[],
    strategy: 'any' | 'all'
): boolean {
    if (roles.length === 0) {
        return true;
    }

    return strategy === 'all'
        ? authState.hasAllRoles(roles)
        : authState.hasAnyRole(roles);
}

/**
 * Validates whether the user has the required permissions.
 */
function validatePermissions(
    authState: AuthState,
    permissions: string[],
    strategy: 'any' | 'all'
): boolean {
    const user = authState.user.value;

    if (!user) {
        return false;
    }

    if (strategy === 'all') {
        return userHasAllPermissions(user, permissions);
    }

    return userHasAnyPermission(user, permissions);
}

/**
 * Creates an authentication navigation guard.
 *
 * Responsibilities:
 * - Protect private routes.
 * - Redirect unauthenticated users.
 * - Restrict guest-only routes.
 * - Validate user roles.
 * - Validate user permissions.
 * - Initialize authentication when necessary.
 * - Handle authentication initialization errors.
 *
 * The backend must independently enforce
 * authentication and authorization.
 *
 * @param authState Shared ShieldPHP authentication state.
 * @param options Guard configuration.
 */
export function createAuthGuard(
    authState: AuthState,
    options: AuthGuardOptions = {}
): NavigationGuard {
    const {
        loginRoute = { name: 'login' },
        guestRedirectRoute = { name: 'dashboard' },
        forbiddenRoute = false,
        errorRoute = false,
        preserveRedirect = true,
    } = options;

    /**
     * Shared initialization promise.
     *
     * Prevents duplicate authentication requests
     * during concurrent route navigation.
     */
    let initializationPromise: Promise<void> | null = null;

    /**
     * Ensure authentication has been initialized.
     */
    async function ensureInitialized(): Promise<boolean> {
        if (authState.initialized.value) {
            return authState.status.value !== 'error';
        }

        if (!initializationPromise) {
            initializationPromise = authState.initialize()
                .finally(() => {
                    initializationPromise = null;
                });
        }

        try {
            await initializationPromise;

            return authState.status.value !== 'error';
        } catch {
            return false;
        }
    }

    /**
     * Vue Router navigation guard.
     */
    const guard: NavigationGuard = async (to) => {
        const roles = to.meta.roles ?? [];
        const permissions = to.meta.permissions ?? [];

        const requiresAuth =
            to.meta.requiresAuth === true ||
            roles.length > 0 ||
            permissions.length > 0;

        const guestOnly = to.meta.guestOnly === true;

        // Public routes do not require authentication checks.
        if (!requiresAuth && !guestOnly) {
            return true;
        }

        // Restore or verify authentication.
        const initialized = await ensureInitialized();

        if (!initialized) {
            return errorRoute;
        }

        const authenticated = authState.isAuthenticated.value;

        // Prevent authenticated users from accessing guest pages.
        if (guestOnly && authenticated) {
            return guestRedirectRoute;
        }

        // Redirect unauthenticated users.
        if (requiresAuth && !authenticated) {
            return createLoginRedirect(
                loginRoute,
                to.fullPath,
                preserveRedirect
            );
        }

        // Validate required roles.
        if (
            roles.length > 0 &&
            !validateRoles(
                authState,
                roles,
                to.meta.roleMatch ?? 'any'
            )
        ) {
            return forbiddenRoute;
        }

        // Validate required permissions.
        if (
            permissions.length > 0 &&
            !validatePermissions(
                authState,
                permissions,
                to.meta.permissionMatch ?? 'any'
            )
        ) {
            return forbiddenRoute;
        }

        return true;
    };

    return guard;
}

/**
 * Install the ShieldPHP authentication guard
 * into a Vue Router instance.
 *
 * @returns Function to remove the registered guard.
 */
export function installAuthGuard(
    router: Router,
    authState: AuthState,
    options: AuthGuardOptions = {}
): () => void {
    const guard = createAuthGuard(authState, options);

    return router.beforeEach(guard);
}

/**
 * Default export.
 */
export default createAuthGuard;
