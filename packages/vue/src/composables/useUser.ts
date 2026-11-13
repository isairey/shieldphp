
import { computed } from 'vue';
import type { ComputedRef } from 'vue';

import type { AuthUser } from '../auth/AuthUser';
import {
    getUserDisplayName,
    userHasRole,
    userHasAnyRole,
    userHasAllRoles,
    userHasPermission,
    userHasAnyPermission,
    userHasAllPermissions,
} from '../auth/AuthUser';

import type { QuickAuthClient } from '../client/QuickAuthClient';
import { useAuth } from './useAuth';

/**
 * Configuration options for useUser.
 */
export interface UseUserOptions {
    /**
     * Optional ShieldPHP client instance.
     *
     * When omitted, the client is obtained through
     * Vue dependency injection.
     */
    client?: QuickAuthClient;
}

/**
 * Return type of useUser.
 */
export interface UseUserReturn {
    /**
     * Complete authenticated user object.
     */
    user: ComputedRef<AuthUser | null>;

    /**
     * User unique identifier.
     */
    id: ComputedRef<string | null>;

    /**
     * User email address.
     */
    email: ComputedRef<string | null>;

    /**
     * User display name.
     */
    displayName: ComputedRef<string | null>;

    /**
     * User username.
     */
    username: ComputedRef<string | null>;

    /**
     * User avatar URL.
     */
    avatarUrl: ComputedRef<string | null>;

    /**
     * Email verification status.
     */
    emailVerified: ComputedRef<boolean>;

    /**
     * User assigned roles.
     */
    roles: ComputedRef<string[]>;

    /**
     * User assigned permissions.
     */
    permissions: ComputedRef<string[]>;

    /**
     * Indicates whether a user is authenticated.
     */
    isAuthenticated: ComputedRef<boolean>;

    /**
     * Indicates whether a user is available.
     */
    exists: ComputedRef<boolean>;

    /**
     * Refresh user information from the backend.
     */
    refresh(): Promise<AuthUser>;

    /**
     * Check whether the user has a specific role.
     */
    hasRole(role: string): boolean;

    /**
     * Check whether the user has at least one role.
     */
    hasAnyRole(roles: string[]): boolean;

    /**
     * Check whether the user has every specified role.
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
     * Check whether the user has every specified permission.
     */
    hasAllPermissions(permissions: string[]): boolean;
}

/**
 * useUser
 *
 * Vue 3 composable for accessing the authenticated
 * ShieldPHP user.
 *
 * Features:
 * - Reactive user information.
 * - User profile properties.
 * - Roles and permissions.
 * - User display name.
 * - Email verification status.
 * - User information refresh.
 * - Role and permission checks.
 *
 * The composable shares the authentication state
 * managed by QuickAuthClient.
 *
 * Usage:
 *
 * const {
 *     user,
 *     email,
 *     displayName,
 *     roles,
 *     hasRole,
 * } = useUser();
 */
export function useUser(
    options: UseUserOptions = {}
): UseUserReturn {
    const auth = useAuth({
        client: options.client,
    });

    /**
     * Current authenticated user.
     */
    const user = auth.user;

    /**
     * User unique identifier.
     */
    const id = computed(
        () => user.value?.id ?? null
    );

    /**
     * User email.
     */
    const email = computed(
        () => user.value?.email ?? null
    );

    /**
     * User display name.
     */
    const displayName = computed(
        () => user.value
            ? getUserDisplayName(user.value)
            : null
    );

    /**
     * Username.
     */
    const username = computed(
        () => user.value?.username ?? null
    );

    /**
     * Profile image URL.
     */
    const avatarUrl = computed(
        () => user.value?.avatarUrl ?? null
    );

    /**
     * Email verification status.
     */
    const emailVerified = computed(
        () => user.value?.emailVerified ?? false
    );

    /**
     * Assigned user roles.
     */
    const roles = computed(
        () => user.value?.roles ?? []
    );

    /**
     * Assigned user permissions.
     */
    const permissions = computed(
        () => user.value?.permissions ?? []
    );

    /**
     * Indicates whether an authenticated user exists.
     */
    const exists = computed(
        () => user.value !== null
    );

    /**
     * Refresh the current user information.
     *
     * Fetches the user from the backend through
     * the configured authentication service.
     */
    async function refresh(): Promise<AuthUser> {
        return auth.refresh();
    }

    /**
     * Check a specific user role.
     */
    function hasRole(role: string): boolean {
        return user.value
            ? userHasRole(user.value, role)
            : false;
    }

    /**
     * Check whether the user has at least one role.
     */
    function hasAnyRole(
        requiredRoles: string[]
    ): boolean {
        return user.value
            ? userHasAnyRole(user.value, requiredRoles)
            : false;
    }

    /**
     * Check whether the user has every specified role.
     */
    function hasAllRoles(
        requiredRoles: string[]
    ): boolean {
        return user.value
            ? userHasAllRoles(user.value, requiredRoles)
            : false;
    }

    /**
     * Check a specific permission.
     */
    function hasPermission(
        permission: string
    ): boolean {
        return user.value
            ? userHasPermission(user.value, permission)
            : false;
    }

    /**
     * Check whether the user has at least one permission.
     */
    function hasAnyPermission(
        requiredPermissions: string[]
    ): boolean {
        return user.value
            ? userHasAnyPermission(
                user.value,
                requiredPermissions
            )
            : false;
    }

    /**
     * Check whether the user has every specified permission.
     */
    function hasAllPermissions(
        requiredPermissions: string[]
    ): boolean {
        return user.value
            ? userHasAllPermissions(
                user.value,
                requiredPermissions
            )
            : false;
    }

    return {
        user,

        id,
        email,
        displayName,
        username,
        avatarUrl,
        emailVerified,

        roles,
        permissions,

        isAuthenticated: auth.isAuthenticated,
        exists,

        refresh,

        hasRole,
        hasAnyRole,
        hasAllRoles,

        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
    };
}

export default useUser;
