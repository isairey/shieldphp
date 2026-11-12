
/**
 * ShieldPHP Vue
 *
 * AuthUser.ts
 *
 * Defines the authenticated user model and utilities.
 */

/**
 * Represents an authenticated user.
 *
 * This interface describes the user information exposed
 * to the frontend after successful authentication.
 */
export interface AuthUser {
    /**
     * Unique user identifier.
     */
    id: string;

    /**
     * User email address.
     */
    email: string;

    /**
     * Full display name.
     */
    name?: string;

    /**
     * Username associated with the account.
     */
    username?: string;

    /**
     * Profile image URL.
     */
    avatarUrl?: string;

    /**
     * Indicates whether the email has been verified.
     */
    emailVerified?: boolean;

    /**
     * Roles assigned to the user.
     */
    roles?: string[];

    /**
     * Permissions assigned to the user.
     */
    permissions?: string[];

    /**
     * Additional backend-specific user attributes.
     */
    [key: string]: unknown;
}

/**
 * Checks whether a value is a valid string array.
 */
function isStringArray(value: unknown): value is string[] {
    return (
        Array.isArray(value) &&
        value.every((item) => typeof item === 'string')
    );
}

/**
 * Checks whether a value represents a valid AuthUser.
 *
 * This validates the basic structure of the user object.
 * It does not prove that the user is authenticated.
 *
 * @param value Unknown value to validate.
 */
export function isAuthUser(value: unknown): value is AuthUser {
    if (
        typeof value !== 'object' ||
        value === null ||
        Array.isArray(value)
    ) {
        return false;
    }

    const user = value as Record<string, unknown>;

    if (
        typeof user.id !== 'string' ||
        user.id.trim().length === 0 ||
        typeof user.email !== 'string' ||
        user.email.trim().length === 0
    ) {
        return false;
    }

    if (
        user.roles !== undefined &&
        !isStringArray(user.roles)
    ) {
        return false;
    }

    if (
        user.permissions !== undefined &&
        !isStringArray(user.permissions)
    ) {
        return false;
    }

    if (
        user.name !== undefined &&
        typeof user.name !== 'string'
    ) {
        return false;
    }

    if (
        user.username !== undefined &&
        typeof user.username !== 'string'
    ) {
        return false;
    }

    if (
        user.avatarUrl !== undefined &&
        typeof user.avatarUrl !== 'string'
    ) {
        return false;
    }

    if (
        user.emailVerified !== undefined &&
        typeof user.emailVerified !== 'boolean'
    ) {
        return false;
    }

    return true;
}

/**
 * Returns the display name of a user.
 *
 * Fallback priority:
 * 1. name
 * 2. username
 * 3. email
 */
export function getUserDisplayName(user: AuthUser): string {
    return (
        user.name?.trim() ||
        user.username?.trim() ||
        user.email
    );
}

/**
 * Checks whether a user has a specific role.
 *
 * This is a frontend convenience method.
 * The backend must enforce authorization independently.
 */
export function userHasRole(
    user: AuthUser,
    role: string
): boolean {
    return user.roles?.includes(role) ?? false;
}

/**
 * Checks whether a user has at least one specified role.
 */
export function userHasAnyRole(
    user: AuthUser,
    roles: string[]
): boolean {
    return roles.some((role) => userHasRole(user, role));
}

/**
 * Checks whether a user has all specified roles.
 */
export function userHasAllRoles(
    user: AuthUser,
    roles: string[]
): boolean {
    return roles.every((role) => userHasRole(user, role));
}

/**
 * Checks whether a user has a specific permission.
 *
 * Permissions received by the frontend must not be
 * considered authoritative security decisions.
 */
export function userHasPermission(
    user: AuthUser,
    permission: string
): boolean {
    return user.permissions?.includes(permission) ?? false;
}

/**
 * Checks whether a user has at least one specified permission.
 */
export function userHasAnyPermission(
    user: AuthUser,
    permissions: string[]
): boolean {
    return permissions.some(
        (permission) => userHasPermission(user, permission)
    );
}

/**
 * Checks whether a user has all specified permissions.
 */
export function userHasAllPermissions(
    user: AuthUser,
    permissions: string[]
): boolean {
    return permissions.every(
        (permission) => userHasPermission(user, permission)
    );
}

/**
 * Creates a safe shallow copy of a user object.
 *
 * Role and permission arrays are copied to avoid sharing
 * their original array references.
 */
export function createAuthUser(
    value: unknown
): AuthUser {
    if (!isAuthUser(value)) {
        throw new TypeError(
            'Invalid AuthUser data provided.'
        );
    }

    return {
        ...value,
        roles: value.roles ? [...value.roles] : undefined,
        permissions: value.permissions
            ? [...value.permissions]
            : undefined,
    };
}
