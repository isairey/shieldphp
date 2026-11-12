
import type { AxiosInstance } from 'axios';

/**
 * Represents an authenticated user.
 */
export interface AuthUser {
    id: string;
    email: string;
    roles?: string[];
    [key: string]: unknown;
}

/**
 * Credentials required to authenticate a user.
 */
export interface LoginCredentials {
    email: string;
    password: string;
}

/**
 * Authentication response returned by the backend.
 *
 * access_token is optional to support cookie-based sessions.
 */
export interface AuthSession {
    access_token?: string;
    token_type?: string;
    expires_in?: number;
    user: AuthUser;
}

/**
 * Standard API response envelope.
 */
export interface ApiResponse<T> {
    success?: boolean;
    message?: string;
    data: T;
}

/**
 * Authentication API endpoints.
 */
export interface AuthEndpoints {
    login: string;
    me: string;
    logout: string;
}

/**
 * AuthService configuration.
 */
export interface AuthServiceOptions {
    endpoints?: Partial<AuthEndpoints>;

    /**
     * Called whenever the access token changes.
     * Use this callback to synchronize the token with
     * the Axios HTTP client.
     */
    onAccessTokenChange?: (token: string | null) => void;
}

/**
 * Authentication service error.
 */
export class AuthServiceError extends Error {
    constructor(message: string) {
        super(message);

        this.name = 'AuthServiceError';

        Object.setPrototypeOf(this, new.target.prototype);
    }
}

/**
 * Default authentication endpoints.
 */
const DEFAULT_ENDPOINTS: AuthEndpoints = {
    login: '/auth/login',
    me: '/auth/me',
    logout: '/auth/logout',
};

/**
 * AuthService
 *
 * Handles authentication requests against a ShieldPHP backend.
 *
 * Supports:
 * - User login.
 * - Current authenticated user.
 * - User logout.
 * - JWT access token synchronization.
 * - Cookie-based authentication sessions.
 *
 * The backend remains responsible for validating credentials,
 * verifying tokens, managing sessions and checking permissions.
 */
export class AuthService {
    private readonly http: AxiosInstance;

    private readonly endpoints: AuthEndpoints;

    private readonly onAccessTokenChange?: (
        token: string | null
    ) => void;

    constructor(
        http: AxiosInstance,
        options: AuthServiceOptions = {}
    ) {
        this.http = http;

        this.endpoints = {
            ...DEFAULT_ENDPOINTS,
            ...options.endpoints,
        };

        this.onAccessTokenChange = options.onAccessTokenChange;
    }

    /**
     * Authenticate a user.
     *
     * The backend must return an authenticated user.
     * An access token is optional for cookie-based sessions.
     *
     * @throws AuthServiceError When the response is invalid.
     */
    async login(
        credentials: LoginCredentials
    ): Promise<AuthSession> {
        if (!credentials.email.trim()) {
            throw new AuthServiceError(
                'Email is required.'
            );
        }

        if (!credentials.password) {
            throw new AuthServiceError(
                'Password is required.'
            );
        }

        const response = await this.http.post<
            AuthSession | ApiResponse<AuthSession>
        >(
            this.endpoints.login,
            {
                email: credentials.email.trim(),
                password: credentials.password,
            }
        );

        const session = this.unwrap<AuthSession>(
            response.data
        );

        if (
            !session ||
            typeof session !== 'object' ||
            !session.user ||
            typeof session.user.id !== 'string' ||
            typeof session.user.email !== 'string'
        ) {
            throw new AuthServiceError(
                'Invalid authentication response.'
            );
        }

        const token =
            typeof session.access_token === 'string' &&
            session.access_token.length > 0
                ? session.access_token
                : null;

        this.onAccessTokenChange?.(token);

        return session;
    }

    /**
     * Retrieve the currently authenticated user.
     *
     * Authentication is validated by the backend using
     * the configured JWT or session cookie.
     */
    async me(): Promise<AuthUser> {
        const response = await this.http.get<
            AuthUser | ApiResponse<AuthUser>
        >(this.endpoints.me);

        const user = this.unwrap<AuthUser>(
            response.data
        );

        if (
            !user ||
            typeof user !== 'object' ||
            typeof user.id !== 'string' ||
            typeof user.email !== 'string'
        ) {
            throw new AuthServiceError(
                'Invalid user response.'
            );
        }

        return user;
    }

    /**
     * Log out the current user.
     *
     * The local access token is cleared even if the
     * backend request fails.
     *
     * The backend must invalidate the current session
     * or credential according to its authentication strategy.
     */
    async logout(): Promise<void> {
        try {
            await this.http.post(
                this.endpoints.logout
            );
        } finally {
            this.onAccessTokenChange?.(null);
        }
    }

    /**
     * Remove the locally configured access token.
     *
     * This does not revoke a token on the backend.
     */
    clearAccessToken(): void {
        this.onAccessTokenChange?.(null);
    }

    /**
     * Unwrap a standard API response.
     *
     * Supports both:
     * { data: {...}, success: true }
     *
     * and direct responses:
     * {...}
     */
    private unwrap<T>(
        payload: T | ApiResponse<T>
    ): T {
        if (
            !payload ||
            typeof payload !== 'object'
        ) {
            throw new AuthServiceError(
                'Invalid API response.'
            );
        }

        const record = payload as Record<string, unknown>;

        if (record.success === false) {
            const message =
                typeof record.message === 'string'
                    ? record.message
                    : 'Authentication request failed.';

            throw new AuthServiceError(message);
        }

        if (
            Object.prototype.hasOwnProperty.call(
                record,
                'data'
            )
        ) {
            return record.data as T;
        }

        return payload as T;
    }
}

export default AuthService;
