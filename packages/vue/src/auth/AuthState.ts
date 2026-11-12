
import { computed, ref } from 'vue';
import type { ComputedRef } from 'vue';

import {
    AuthService,
    type AuthUser,
    type LoginCredentials,
} from './AuthService';

/**
 * Possible authentication states.
 */
export type AuthStatus =
    | 'idle'
    | 'loading'
    | 'authenticated'
    | 'unauthenticated'
    | 'error';

/**
 * Public authentication state.
 */
export interface AuthStateSnapshot {
    user: AuthUser | null;
    status: AuthStatus;
    loading: boolean;
    initialized: boolean;
    isAuthenticated: boolean;
    error: string | null;
}

/**
 * AuthState
 *
 * Reactive authentication state manager for Vue 3.
 *
 * Responsibilities:
 * - Maintain the authenticated user.
 * - Track authentication status.
 * - Handle login and logout operations.
 * - Restore authentication from the backend.
 * - Expose reactive authentication properties.
 * - Manage authentication errors.
 *
 * AuthState does not validate credentials or tokens itself.
 * These responsibilities belong to the backend and AuthService.
 */
export class AuthState {
    private readonly authService: AuthService;

    private readonly userState = ref<AuthUser | null>(null);
    private readonly statusState = ref<AuthStatus>('idle');
    private readonly initializedState = ref(false);
    private readonly errorState = ref<string | null>(null);

    private operationId = 0;

    /**
     * Current authenticated user.
     */
    readonly user: ComputedRef<AuthUser | null> = computed(
        () => this.userState.value
    );

    /**
     * Current authentication status.
     */
    readonly status: ComputedRef<AuthStatus> = computed(
        () => this.statusState.value
    );

    /**
     * Indicates whether an authentication operation is running.
     */
    readonly loading: ComputedRef<boolean> = computed(
        () => this.statusState.value === 'loading'
    );

    /**
     * Indicates whether the initial authentication check has completed.
     */
    readonly initialized: ComputedRef<boolean> = computed(
        () => this.initializedState.value
    );

    /**
     * Indicates whether the user is authenticated.
     */
    readonly isAuthenticated: ComputedRef<boolean> = computed(
        () => this.userState.value !== null
    );

    /**
     * Current authentication error.
     */
    readonly error: ComputedRef<string | null> = computed(
        () => this.errorState.value
    );

    /**
     * Create an authentication state manager.
     *
     * @param authService Authentication service instance.
     */
    constructor(authService: AuthService) {
        this.authService = authService;
    }

    /**
     * Initialize authentication state.
     *
     * Requests the current user from the backend.
     *
     * This supports restoring cookie-based sessions and
     * JWT authentication when a valid token has already
     * been restored by the application.
     */
    async initialize(): Promise<void> {
        const currentOperation = ++this.operationId;

        this.statusState.value = 'loading';
        this.errorState.value = null;

        try {
            const user = await this.authService.me();

            if (currentOperation !== this.operationId) {
                return;
            }

            this.userState.value = user;
            this.statusState.value = 'authenticated';
        } catch (error: unknown) {
            if (currentOperation !== this.operationId) {
                return;
            }

            this.userState.value = null;
            this.authService.clearAccessToken();

            const statusCode = this.getHttpStatus(error);

            if (statusCode === 401) {
                this.statusState.value = 'unauthenticated';
                this.errorState.value = null;
            } else {
                this.statusState.value = 'error';
                this.errorState.value = this.getErrorMessage(error);
            }
        } finally {
            if (currentOperation === this.operationId) {
                this.initializedState.value = true;
            }
        }
    }

    /**
     * Authenticate a user.
     *
     * @param credentials User login credentials.
     * @returns Authenticated user.
     */
    async login(
        credentials: LoginCredentials
    ): Promise<AuthUser> {
        const currentOperation = ++this.operationId;

        this.statusState.value = 'loading';
        this.errorState.value = null;

        try {
            const session = await this.authService.login(credentials);

            if (currentOperation !== this.operationId) {
                throw new Error(
                    'Authentication operation was interrupted.'
                );
            }

            this.userState.value = session.user;
            this.statusState.value = 'authenticated';
            this.initializedState.value = true;

            return session.user;
        } catch (error: unknown) {
            if (currentOperation === this.operationId) {
                this.userState.value = null;
                this.statusState.value = 'unauthenticated';
                this.errorState.value = this.getErrorMessage(error);
            }

            throw error;
        }
    }

    /**
     * Retrieve the authenticated user again.
     *
     * Useful when refreshing user information.
     */
    async refresh(): Promise<AuthUser> {
        const currentOperation = ++this.operationId;

        this.statusState.value = 'loading';
        this.errorState.value = null;

        try {
            const user = await this.authService.me();

            if (currentOperation !== this.operationId) {
                throw new Error(
                    'Authentication operation was interrupted.'
                );
            }

            this.userState.value = user;
            this.statusState.value = 'authenticated';
            this.initializedState.value = true;

            return user;
        } catch (error: unknown) {
            if (currentOperation === this.operationId) {
                this.userState.value = null;
                this.authService.clearAccessToken();
                this.statusState.value = 'unauthenticated';
                this.errorState.value = this.getErrorMessage(error);
            }

            throw error;
        }
    }

    /**
     * Log out the current user.
     *
     * Local authentication state is cleared even if
     * the backend request fails.
     */
    async logout(): Promise<void> {
        ++this.operationId;

        this.statusState.value = 'loading';
        this.errorState.value = null;

        try {
            await this.authService.logout();
        } catch (error: unknown) {
            this.errorState.value = this.getErrorMessage(error);
            throw error;
        } finally {
            this.userState.value = null;
            this.authService.clearAccessToken();

            this.statusState.value = 'unauthenticated';
            this.initializedState.value = true;
        }
    }

    /**
     * Clear local authentication state.
     *
     * This does not invalidate credentials on the backend.
     */
    clear(): void {
        ++this.operationId;

        this.userState.value = null;
        this.errorState.value = null;

        this.authService.clearAccessToken();

        this.statusState.value = 'unauthenticated';
        this.initializedState.value = true;
    }

    /**
     * Check whether the current user has a role.
     *
     * This is only a frontend convenience check.
     * The backend must enforce authorization.
     */
    hasRole(role: string): boolean {
        return this.userState.value?.roles?.includes(role) ?? false;
    }

    /**
     * Check whether the current user has at least one role.
     */
    hasAnyRole(roles: string[]): boolean {
        return roles.some((role) => this.hasRole(role));
    }

    /**
     * Check whether the current user has all specified roles.
     */
    hasAllRoles(roles: string[]): boolean {
        return roles.every((role) => this.hasRole(role));
    }

    /**
     * Get a snapshot of the current authentication state.
     */
    getSnapshot(): AuthStateSnapshot {
        return {
            user: this.userState.value,
            status: this.statusState.value,
            loading: this.loading.value,
            initialized: this.initializedState.value,
            isAuthenticated: this.isAuthenticated.value,
            error: this.errorState.value,
        };
    }

    /**
     * Get the HTTP status from an unknown error.
     */
    private getHttpStatus(error: unknown): number | null {
        if (
            typeof error !== 'object' ||
            error === null ||
            !('response' in error)
        ) {
            return null;
        }

        const response = error.response;

        if (
            typeof response !== 'object' ||
            response === null ||
            !('status' in response)
        ) {
            return null;
        }

        return typeof response.status === 'number'
            ? response.status
            : null;
    }

    /**
     * Convert an unknown error to a safe message.
     */
    private getErrorMessage(error: unknown): string {
        if (error instanceof Error) {
            return error.message;
        }

        return 'An authentication error occurred.';
    }
}

export default AuthState;
