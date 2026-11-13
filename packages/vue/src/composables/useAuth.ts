
import { computed, inject } from 'vue';

import type { ComputedRef } from 'vue';

import QuickAuthClient from '../client/QuickAuthClient';

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
 * Must match the key used by app.provide('shieldphp', client).
 */
export const SHIELDPHP_INJECTION_KEY = 'shieldphp';

/**
 * Options for useAuth.
 */
export interface UseAuthOptions {
    /**
     * Optional ShieldPHP client instance.
     *
     * When omitted, the client is obtained through Vue injection.
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
     * Indicates whether authentication has been initialized.
     */
    initialized: ComputedRef<boolean>;

    /**
     * Indicates whether the user is authenticated.
     */
    isAuthenticated: ComputedRef<boolean>;

    /**
     * Indicates whether the user is an unauthenticated guest.
     */
    isGuest: ComputedRef<boolean>;

    /**
     * Current authentication error.
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
     * Initialize or restore authentication.
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
     * Check whether the user has at least one specified role.
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
     * Check whether the user has at least one specified permission.
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
 * Vue 3 composable for ShieldPHP authentication.
 *
 * Features:
 * - Reactive authenticated user.
 * - Authentication state.
 * - Login and logout.
 * - Session initialization.
 * - User information refresh.
 * - Role checks.
 * - Permission checks.
 *
 * Usage:
 *
 * const {
 *     user,
 *     isAuthenticated,
 *     login,
 *     logout,
 * } = useAuth();
 *
 * The QuickAuthClient must be provided by the Vue application
 * or passed explicitly through the options.
 */
export function useAuth(
    options: UseAuthOptions = {}
): UseAuthReturn {
    const client =
        options.client ??
        inject<QuickAuthClient | null>(
            SHIELDPHP_INJECTION_KEY,
            null
        );

    if (!client) {
        throw new Error(
            '[ShieldPHP] QuickAuthClient not found. ' +
            'Register it with app.provide("shieldphp", client) ' +
            'or pass it to useAuth({ client }).'
        );
    }

    // Access the shared reactive authentication state.
    const authState = client.getAuthState();

    /**
     * Indicates whether the current user is a guest.
     */
    const isGuest = computed(
        () =>
            authState.initialized.value &&
            !authState.isAuthenticated.value
    );

    /**
     * Authenticate a user.
     */
    async function login(
        credentials: LoginCredentials
    ): Promise<AuthUser> {
        return client.login(credentials);
    }

    /**
     * End the current authentication session.
     */
    async function logout(): Promise<void> {
        return client.logout();
    }

    /**
     * Initialize authentication.
     */
    async function initialize(): Promise<AuthUser | null> {
        return client.initialize();
    }

    /**
     * Refresh the current authenticated user.
     */
    async function refresh(): Promise<AuthUser> {
        return client.refreshUser();
    }

    /**
     * Clear local authentication information.
     *
     * This does not revoke credentials on the backend.
     */
    function clear(): void {
        client.clear();
    }

    /**
     * Check a user role.
     */
    function hasRole(role: string): boolean {
        return authState.hasRole(role);
    }

    /**
     * Check whether the user has at least one role.
     */
    function hasAnyRole(roles: string[]): boolean {
        return authState.hasAnyRole(roles);
    }

    /**
     * Check whether the user has every specified role.
     */
    function hasAllRoles(roles: string[]): boolean {
        return authState.hasAllRoles(roles);
    }

    /**
     * Check a user permission.
     */
    function hasPermission(permission: string): boolean {
        const currentUser = authState.user.value;

        return currentUser
            ? userHasPermission(currentUser, permission)
            : false;
    }

    /**
     * Check whether the user has at least one permission.
     */
    function hasAnyPermission(
        permissions: string[]
    ): boolean {
        const currentUser = authState.user.value;

        return currentUser
            ? userHasAnyPermission(currentUser, permissions)
            : false;
    }

    /**
     * Check whether the user has every specified permission.
     */
    function hasAllPermissions(
        permissions: string[]
    ): boolean {
        const currentUser = authState.user.value;

        return currentUser
            ? userHasAllPermissions(currentUser, permissions)
            : false;
    }

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
