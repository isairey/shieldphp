
import type {
    NavigationGuard,
    RouteLocationRaw,
    Router,
} from 'vue-router';

import type AuthState from './AuthState';

import {
    userHasAllPermissions,
    userHasAnyPermission,
} from './AuthUser';

/**
 * Extends Vue Router route metadata.
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
         * Required user roles.
         */
        roles?: string[];

        /**
         * Role matching strategy.
         *
         * any: user must have at least one role.
         * all: user must have every role.
         */
        roleMatch?: 'any' | 'all';

        /**
         * Required user permissions.
         */
        permissions?: string[];

        /**
         * Permission matching strategy.
         *
         * any: user must have at least one permission.
         * all: user must have every permission.
         */
        permissionMatch?: 'any' | 'all';
    }
}

/**
 * AuthGuard configuration.
 */
export interface AuthGuardOptions {
    /**
     * Route used when authentication is required.
     *
     * Defaults to the named route "login".
     */
    loginRoute?: RouteLocationRaw;

    /**
     * Route used when an authenticated user
     * attempts to access a guest-only route.
     *
     * Defaults to the named route "dashboard".
     */
    guestRedirectRoute?: RouteLocationRaw;

    /**
     * Optional route used when authentication
     * cannot be verified due to a backend error.
     *
     * When omitted, navigation is cancelled.
     */
    errorRoute?: RouteLocationRaw;

    /**
     * Optional route used when the user does not
     * have the required roles or permissions.
     *
     * When omitted, navigation is cancelled.
     */
    forbiddenRoute?: RouteLocationRaw;

    /**
     * Preserve the requested destination when
     * redirecting to the login page.
     *
     * Defaults to true.
     */
    preserveRedirect?: boolean;
}

/**
 * AuthGuard
 *
 * Protects Vue Router navigation using ShieldPHP
 * authentication state.
 *
 * Responsibilities:
 * - Verify authentication before navigation.
 * - Protect private routes.
 * - Redirect unauthenticated users.
 * - Restrict guest-only routes.
 * - Validate frontend roles and permissions.
 * - Handle authentication initialization errors.
 *
 * IMPORTANT:
 * This guard is a frontend navigation mechanism.
 * The backend must independently enforce authentication
 * and authorization on every protected endpoint.
 */
export class AuthGuard {
    private readonly authState: AuthState;

    private readonly options: AuthGuardOptions;

    /**
     * Shared initialization promise prevents duplicate
     * authentication checks during concurrent navigation.
     */
    private initializationPromise: Promise<void> | null = null;

    constructor(
        authState: AuthState,
        options: AuthGuardOptions = {}
    ) {
        this.authState = authState;
        this.options = {
            preserveRedirect: true,
            ...options,
        };
    }

    /**
     * Vue Router navigation guard.
     */
    readonly guard: NavigationGuard = async (to) => {
        const hasRoles = Boolean(to.meta.roles?.length);
        const hasPermissions = Boolean(
            to.meta.permissions?.length
        );

        const requiresAuthentication =
            to.meta.requiresAuth === true ||
            hasRoles ||
            hasPermissions;

        const guestOnly = to.meta.guestOnly === true;

        // Public routes do not require authentication checks.
        if (!requiresAuthentication && !guestOnly) {
            return true;
        }

        // Restore or verify the authentication state.
        try {
            await this.ensureInitialized();
        } catch {
            return this.options.errorRoute ?? false;
        }

        // Handle backend connectivity or initialization errors.
        if (this.authState.status.value === 'error') {
            return this.options.errorRoute ?? false;
        }

        // Prevent authenticated users from accessing guest-only routes.
        if (
            guestOnly &&
            this.authState.isAuthenticated.value
        ) {
            return (
                this.options.guestRedirectRoute ??
                { name: 'dashboard' }
            );
        }

        // Protect routes requiring authentication.
        if (
            requiresAuthentication &&
            !this.authState.isAuthenticated.value
        ) {
            return this.getLoginRedirect(to.fullPath);
        }

        // Validate required roles.
        if (hasRoles && !this.hasRequiredRoles(to.meta.roles!)) {
            return this.getForbiddenRedirect();
        }

        // Validate required permissions.
        if (
            hasPermissions &&
            !this.hasRequiredPermissions(
                to.meta.permissions!,
                to.meta.permissionMatch ?? 'any'
            )
        ) {
            return this.getForbiddenRedirect();
        }

        return true;
    };

    /**
     * Register the guard with Vue Router.
     *
     * @returns Function to unregister the guard.
     */
    install(router: Router): () => void {
        return router.beforeEach(this.guard);
    }

    /**
     * Initialize authentication only once at a time.
     */
    private async ensureInitialized(): Promise<void> {
        if (this.authState.initialized.value) {
            return;
        }

        if (!this.initializationPromise) {
            this.initializationPromise =
                this.authState.initialize().finally(() => {
                    this.initializationPromise = null;
                });
        }

        await this.initializationPromise;
    }

    /**
     * Validate the required user roles.
     */
    private hasRequiredRoles(roles: string[]): boolean {
        if (roles.length === 0) {
            return true;
        }

        const strategy = this.optionsRoleMatch(roles);

        if (strategy === 'all') {
            return this.authState.hasAllRoles(roles);
        }

        return this.authState.hasAnyRole(roles);
    }

    /**
     * Validate the required user permissions.
     */
    private hasRequiredPermissions(
        permissions: string[],
        strategy: 'any' | 'all'
    ): boolean {
        const user = this.authState.user.value;

        if (!user) {
            return false;
        }

        if (strategy === 'all') {
            return userHasAllPermissions(user, permissions);
        }

        return userHasAnyPermission(user, permissions);
    }

    /**
     * Resolve the role matching strategy.
     *
     * This reads the current route metadata through
     * the active navigation guard.
     */
    private optionsRoleMatch(
        roles: string[]
    ): 'any' | 'all' {
        return this.currentRoleMatch ?? 'any';
    }

    private currentRoleMatch: 'any' | 'all' | undefined;

    /**
     * Create a redirect to the login route.
     */
    private getLoginRedirect(
        redirectPath: string
    ): RouteLocationRaw {
        const loginRoute =
            this.options.loginRoute ?? { name: 'login' };

        if (!this.options.preserveRedirect) {
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

        const existingQuery =
            'query' in loginRoute && loginRoute.query
                ? loginRoute.query
                : {};

        return {
            ...loginRoute,
            query: {
                ...existingQuery,
                redirect: redirectPath,
            },
        };
    }

    /**
     * Resolve the forbidden route.
     */
    private getForbiddenRedirect(): RouteLocationRaw | false {
        return this.options.forbiddenRoute ?? false;
    }
}

/**
 * Create a Vue Router navigation guard.
 *
 * @param authState ShieldPHP authentication state.
 * @param options Guard configuration.
 */
export function createAuthGuard(
    authState: AuthState,
    options: AuthGuardOptions = {}
): NavigationGuard {
    const authGuard = new AuthGuard(authState, options);

    return authGuard.guard;
}

export default AuthGuard;
