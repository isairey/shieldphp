
import { computed, inject } from 'vue';
import type { ComputedRef } from 'vue';

import type QuickAuthClient from '../client/QuickAuthClient';

import type {
    AuthUser,
} from '../auth/AuthUser';

import type {
    AuthStatus,
} from '../auth/AuthState';

import type {
    LoginCredentials,
} from '../auth/AuthService';

import {
    userHasPermission,
    userHasAnyPermission,
    userHasAllPermissions,
} from '../auth/AuthUser';

/**
 * ShieldPHP dependency injection key.
 *
 * Register the client using:
 *
 * app.provide(SHIELDPHP_INJECTION_KEY, client);
 */
export const SHIELDPHP_INJECTION_KEY = 'shieldphp';

/**
 * useAuth configuration options.
 */
export interface UseAuthOptions {
    /**
     * Optional QuickAuthClient instance.
     *
     * If omitted, the client is retrieved from
     * Vue dependency injection.
     */
    client?: QuickAuthClient;
}

/**
 * Return type of useAuth.
 */
export interface UseAuthReturn {
    /**
     * Current authenticated user.
     */
    user: ComputedRef<AuthUser | null>;

    /**
     * Current authentication status.
     */
    status: ComputedRef<AuthStatus>;

    /**
     * Indicates whether an operation is running.
     */
    loading: ComputedRef<boolean>;

    /**
     * Indicates whether authentication was initialized.
     */
    initialized: ComputedRef<boolean>;

    /**
     * Indicates whether the user is authenticated.
     */
    isAuthenticated: ComputedRef<boolean>;

    /**
     * Indicates whether the current user is a guest.
     */
    isGuest: ComputedRef<boolean>;

    /**
     * Last authentication error.
     */
    error: ComputedRef<string | null>;

    /**
     * Authenticate a user.
     */
    login(credentials: LoginCredentials): Promise<AuthUser>;

    /**
     * End the current authentication session.
     */
    logout(): Promise<void>;

    /**
     * Initialize authentication.
     */
    initialize(): Promise<AuthUser | null>;

    /**
     * Refresh authenticated user information.
     */
    refresh(): Promise<AuthUser>;

    /**
     * Clear local authentication state.
     */
    clear(): void;

    /**
     * Check whether the user has a specific role.
     */
    hasRole(role: string): boolean;

    /**
     * Check whether the user has at least one role.
     */
    hasAnyRole(roles: string[]): boolean;

    /**
     * Check whether the user has all specified roles.
     */
    hasAllRoles(roles: string[]): boolean;

    /**
     * Check whether the user has a specific permission.
     */
    hasPermission(permission: string): boolean;

    /**
     * Check whether the user has at least one permission.
     */
    hasAnyPermission(permissions: string[]): boolean;

    /**
     * Check whether the user has all specified permissions.
     */
    hasAllPermissions(permissions: string[]): boolean;
}

/**
 * useAuth
 *
 * Main Vue 3 composable for ShieldPHP authentication.
 *
 * Features:
 * - Reactive authentication state.
 * - User information.
 * - Login and logout.
 * - Authentication initialization.
 * - User information refresh.
 * - Role management.
 * - Permission management.
 * - Guest state detection.
 *
 * The composable shares the AuthState instance
 * managed by QuickAuthClient.
 *
 * @example
 *
 * const {
 *     user,
 *     isAuthenticated,
 *     login,
 *     logout,
 * } = useAuth();
 */
export function useAuth(
    options: UseAuthOptions = {}
): UseAuthReturn {

    /**
     * Resolve the QuickAuthClient instance.
     *
     * Priority:
     * 1. Explicitly provided client.
     * 2. Vue dependency injection.
     */
    const resolvedClient: QuickAuthClient | undefined =
        options.client ??
        inject<QuickAuthClient>(
            SHIELDPHP_INJECTION_KEY
        );

    /**
     * Ensure that a valid client exists.
     */
    if (!resolvedClient) {
        throw new Error(
            '[ShieldPHP] QuickAuthClient not found. ' +
            'Register the client using ' +
            'app.provide(SHIELDPHP_INJECTION_KEY, client) ' +
            'or provide it explicitly using useAuth({ client }).'
        );
    }

    /**
     * Explicitly typed client instance.
     *
     * This prevents TypeScript from treating client
     * as an optional or unknown value.
     */
    const client: QuickAuthClient = resolvedClient;

    /**
     * Obtain the shared authentication state.
     */
    const authState = client.getAuthState();

    /**
     * Reactive guest state.
     */
    const isGuest = computed<boolean>(() => {
        return (
            authState.initialized.value &&
            !authState.isAuthenticated.value
        );
    });

    // ==========================================
    // AUTHENTICATION OPERATIONS
    // ==========================================

    /**
     * Authenticate a user.
     */
    async function login(
        credentials: LoginCredentials
    ): Promise<AuthUser> {
        return await client.login(credentials);
    }

    /**
     * End the current authentication session.
     */
    async function logout(): Promise<void> {
        await client.logout();
    }

    /**
     * Initialize or restore authentication.
     */
    async function initialize(): Promise<AuthUser | null> {
        return await client.initialize();
    }

    /**
     * Refresh authenticated user information.
     */
    async function refresh(): Promise<AuthUser> {
        return await client.refreshUser();
    }

    /**
     * Clear local authentication state.
     *
     * This does not revoke credentials on the backend.
     */
    function clear(): void {
        client.clear();
    }

    // ==========================================
    // ROLE MANAGEMENT
    // ==========================================

    /**
     * Check whether the current user has a role.
     */
    function hasRole(role: string): boolean {
        return authState.hasRole(role);
    }

    /**
     * Check whether the current user has
     * at least one of the specified roles.
     */
    function hasAnyRole(roles: string[]): boolean {
        return authState.hasAnyRole(roles);
    }

    /**
     * Check whether the current user has
     * all specified roles.
     */
    function hasAllRoles(roles: string[]): boolean {
        return authState.hasAllRoles(roles);
    }

    // ==========================================
    // PERMISSION MANAGEMENT
    // ==========================================

    /**
     * Check whether the current user has
     * a specific permission.
     */
    function hasPermission(
        permission: string
    ): boolean {
        const currentUser = authState.user.value;

        if (!currentUser) {
            return false;
        }

        return userHasPermission(
            currentUser,
            permission
        );
    }

    /**
     * Check whether the user has at least
     * one of the specified permissions.
     */
    function hasAnyPermission(
        permissions: string[]
    ): boolean {
        const currentUser = authState.user.value;

        if (!currentUser) {
            return false;
        }

        return userHasAnyPermission(
            currentUser,
            permissions
        );
    }

    /**
     * Check whether the user has all
     * specified permissions.
     */
    function hasAllPermissions(
        permissions: string[]
    ): boolean {
        const currentUser = authState.user.value;

        if (!currentUser) {
            return false;
        }

        return userHasAllPermissions(
            currentUser,
            permissions
        );
    }

    // ==========================================
    // RETURN COMPOSABLE API
    // ==========================================

    return {
        // Reactive state
        user: authState.user,
        status: authState.status,
        loading: authState.loading,
        initialized: authState.initialized,
        isAuthenticated: authState.isAuthenticated,
        isGuest,
        error: authState.error,

        // Authentication operations
        login,
        logout,
        initialize,
        refresh,
        clear,

        // Role management
        hasRole,
        hasAnyRole,
        hasAllRoles,

        // Permission management
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
    };
}

export default useAuth;
