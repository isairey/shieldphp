
import type { Router } from 'vue-router';

import HttpClient, {
    type HttpClientOptions,
} from './HttpClient';

import AuthService, {
    type AuthServiceOptions,
    type LoginCredentials,
} from '../auth/AuthService';

import AuthState from '../auth/AuthState';

import type { AuthUser } from '../auth/AuthUser';

import AuthGuard, {
    type AuthGuardOptions,
} from '../auth/AuthGuard';

/**
 * QuickAuthClient configuration.
 */
export interface QuickAuthClientOptions
    extends Omit<HttpClientOptions, 'onUnauthorized'> {

    /**
     * Authentication service configuration.
     */
    auth?: Omit<AuthServiceOptions, 'onAccessTokenChange'>;

    /**
     * Navigation guard configuration.
     */
    guard?: AuthGuardOptions;

    /**
     * Called when the backend rejects authentication.
     */
    onUnauthorized?: () => void;
}

/**
 * QuickAuthClient
 *
 * Main entry point for ShieldPHP Vue.
 *
 * Responsibilities:
 * - Initialize the HTTP client.
 * - Manage authentication services.
 * - Maintain reactive authentication state.
 * - Configure JWT access tokens.
 * - Support cookie-based sessions.
 * - Install Vue Router authentication guards.
 * - Provide a unified API for authentication.
 *
 * Usage:
 *
 * const client = new QuickAuthClient({
 *     baseURL: 'https://api.example.com/api'
 * });
 *
 * await client.initialize();
 */
export class QuickAuthClient {
    /**
     * HTTP communication layer.
     */
    readonly http: HttpClient;

    /**
     * Authentication service.
     */
    readonly auth: AuthService;

    /**
     * Reactive authentication state.
     */
    readonly state: AuthState;

    /**
     * Vue Router authentication guard.
     */
    readonly guard: AuthGuard;

    private destroyed = false;

    /**
     * Creates a ShieldPHP client instance.
     */
    constructor(options: QuickAuthClientOptions) {
        let authStateReference: AuthState | null = null;

        const {
            auth: authOptions,
            guard: guardOptions,
            onUnauthorized,
            ...httpOptions
        } = options;

        // Initialize HTTP client.
        this.http = new HttpClient({
            ...httpOptions,

            onUnauthorized: () => {
                // Synchronize unauthorized responses
                // with the reactive authentication state.
                authStateReference?.clear();

                onUnauthorized?.();
            },
        });

        // Initialize authentication service.
        this.auth = new AuthService(
            this.http.getInstance(),
            {
                ...authOptions,

                onAccessTokenChange: (token) => {
                    this.http.setAccessToken(token);
                },
            }
        );

        // Initialize reactive authentication state.
        this.state = new AuthState(this.auth);

        authStateReference = this.state;

        // Initialize navigation guard.
        this.guard = new AuthGuard(
            this.state,
            guardOptions
        );
    }

    /**
     * Initialize the client.
     *
     * Checks whether an authenticated session already exists.
     *
     * Returns the authenticated user or null.
     */
    async initialize(): Promise<AuthUser | null> {
        this.ensureActive();

        try {
            await this.state.initialize();

            return this.state.user.value;
        } catch {
            return null;
        }
    }

    /**
     * Authenticate a user.
     *
     * @param credentials User credentials.
     * @returns Authenticated user.
     */
    async login(
        credentials: LoginCredentials
    ): Promise<AuthUser> {
        this.ensureActive();

        return this.state.login(credentials);
    }

    /**
     * End the current authentication session.
     */
    async logout(): Promise<void> {
        this.ensureActive();

        await this.state.logout();
    }

    /**
     * Refresh authenticated user information.
     */
    async refreshUser(): Promise<AuthUser> {
        this.ensureActive();

        return this.state.refresh();
    }

    /**
     * Get the current authenticated user.
     */
    getUser(): AuthUser | null {
        return this.state.user.value;
    }

    /**
     * Check whether a user is authenticated.
     */
    isAuthenticated(): boolean {
        return this.state.isAuthenticated.value;
    }

    /**
     * Check whether an authentication operation is running.
     */
    isLoading(): boolean {
        return this.state.loading.value;
    }

    /**
     * Check whether the client has completed initialization.
     */
    isInitialized(): boolean {
        return this.state.initialized.value;
    }

    /**
     * Check whether the current user has a specific role.
     */
    hasRole(role: string): boolean {
        return this.state.hasRole(role);
    }

    /**
     * Check whether the current user has at least one role.
     */
    hasAnyRole(roles: string[]): boolean {
        return this.state.hasAnyRole(roles);
    }

    /**
     * Check whether the current user has all specified roles.
     */
    hasAllRoles(roles: string[]): boolean {
        return this.state.hasAllRoles(roles);
    }

    /**
     * Set the JWT access token.
     *
     * The token is maintained in memory.
     */
    setAccessToken(token: string | null): void {
        this.ensureActive();

        this.http.setAccessToken(token);
    }

    /**
     * Set the CSRF token for cookie-based authentication.
     */
    setCsrfToken(token: string | null): void {
        this.ensureActive();

        this.http.setCsrfToken(token);
    }

    /**
     * Install authentication guard into Vue Router.
     *
     * @returns Function to unregister the guard.
     */
    install(router: Router): () => void {
        this.ensureActive();

        return this.guard.install(router);
    }

    /**
     * Get the underlying HTTP client.
     */
    getHttpClient(): HttpClient {
        this.ensureActive();

        return this.http;
    }

    /**
     * Get the authentication service.
     */
    getAuthService(): AuthService {
        this.ensureActive();

        return this.auth;
    }

    /**
     * Get the reactive authentication state.
     */
    getAuthState(): AuthState {
        this.ensureActive();

        return this.state;
    }

    /**
     * Reset local authentication information.
     *
     * This does not invalidate credentials on the backend.
     */
    clear(): void {
        this.ensureActive();

        this.state.clear();
    }

    /**
     * Destroy the client and its HTTP interceptors.
     */
    destroy(): void {
        if (this.destroyed) {
            return;
        }

        this.state.clear();

        this.http.destroy();

        this.destroyed = true;
    }

    /**
     * Prevent operations after destruction.
     */
    private ensureActive(): void {
        if (this.destroyed) {
            throw new Error(
                'QuickAuthClient has been destroyed.'
            );
        }
    }
}

export default QuickAuthClient;
