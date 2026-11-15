
import {
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import QuickAuthClient from '../src/client/QuickAuthClient';

import type {
    QuickAuthClientOptions,
} from '../src/client/QuickAuthClient';

import type {
    AuthUser,
} from '../src/auth/AuthUser';

import type {
    LoginCredentials,
} from '../src/auth/AuthService';

// ==============================================
// MOCKS
// ==============================================

const mocks = vi.hoisted(() => ({
    httpOptions: null as Record<string, unknown> | null,
    httpClient: null as any,
    httpInstance: {} as Record<string, unknown>,

    authOptions: null as Record<string, unknown> | null,
    authHttp: null as unknown,

    state: null as any,
    stateAuthService: null as unknown,

    guard: null as any,

    setAccessToken: vi.fn(),
    setCsrfToken: vi.fn(),
    httpDestroy: vi.fn(),

    guardInstall: vi.fn(),

    uninstallGuard: vi.fn(),
}));

// ==============================================
// HTTP CLIENT MOCK
// ==============================================

vi.mock('../src/client/HttpClient', () => {
    class MockHttpClient {
        constructor(options: Record<string, unknown>) {
            mocks.httpOptions = options;
            mocks.httpClient = this;
        }

        getInstance() {
            return mocks.httpInstance;
        }

        setAccessToken(token: string | null) {
            mocks.setAccessToken(token);
        }

        setCsrfToken(token: string | null) {
            mocks.setCsrfToken(token);
        }

        destroy() {
            mocks.httpDestroy();
        }
    }

    return {
        default: MockHttpClient,
    };
});

// ==============================================
// AUTH SERVICE MOCK
// ==============================================

vi.mock('../src/auth/AuthService', () => {
    class MockAuthService {
        constructor(
            http: unknown,
            options: Record<string, unknown>
        ) {
            mocks.authHttp = http;
            mocks.authOptions = options;
        }
    }

    return {
        default: MockAuthService,
    };
});

// ==============================================
// AUTH STATE MOCK
// ==============================================

vi.mock('../src/auth/AuthState', () => {
    class MockAuthState {
        user = {
            value: null as unknown,
        };

        initialized = {
            value: false,
        };

        status = {
            value: 'idle',
        };

        get isAuthenticated() {
            return {
                value: this.user.value !== null,
            };
        }

        get loading() {
            return {
                value: this.status.value === 'loading',
            };
        }

        initialize = vi.fn(async () => {
            this.initialized.value = true;
        });

        login = vi.fn(
            async (_credentials: unknown) => null as unknown
        );

        logout = vi.fn(async () => undefined);

        refresh = vi.fn(async () => null as unknown);

        clear = vi.fn(() => {
            this.user.value = null;
            this.initialized.value = true;
            this.status.value = 'unauthenticated';
        });

        hasRole = vi.fn((_role: string) => false);

        hasAnyRole = vi.fn((_roles: string[]) => false);

        hasAllRoles = vi.fn((_roles: string[]) => false);

        constructor(authService: unknown) {
            mocks.state = this;
            mocks.stateAuthService = authService;
        }
    }

    return {
        default: MockAuthState,
    };
});

// ==============================================
// AUTH GUARD MOCK
// ==============================================

vi.mock('../src/auth/AuthGuard', () => {
    class MockAuthGuard {
        constructor(
            state: unknown,
            options: unknown
        ) {
            mocks.guard = {
                state,
                options,
            };
        }

        install(router: unknown) {
            return mocks.guardInstall(router);
        }
    }

    return {
        default: MockAuthGuard,
    };
});

// ==============================================
// TEST DATA
// ==============================================

const mockUser: AuthUser = {
    id: 'user-123',
    email: 'usuario@example.com',
    name: 'Test User',
    roles: ['user', 'developer'],
    permissions: ['profile.read', 'users.read'],
};

const mockCredentials: LoginCredentials = {
    email: 'usuario@example.com',
    password: 'PasswordSeguro123!',
};

// ==============================================
// TEST HELPERS
// ==============================================

function createClient(
    options: Partial<QuickAuthClientOptions> = {}
): QuickAuthClient {
    return new QuickAuthClient({
        baseURL: 'https://api.example.com',
        timeout: 15000,
        authMode: 'bearer',
        ...options,
    });
}

// ==============================================
// TEST SUITE
// ==============================================

describe('QuickAuthClient', () => {
    let client: QuickAuthClient;

    beforeEach(() => {
        vi.clearAllMocks();

        mocks.httpOptions = null;
        mocks.httpClient = null;
        mocks.httpInstance = {};

        mocks.authOptions = null;
        mocks.authHttp = null;

        mocks.state = null;
        mocks.stateAuthService = null;

        mocks.guard = null;

        mocks.guardInstall.mockReturnValue(
            mocks.uninstallGuard
        );

        client = createClient();
    });

    // ==========================================
    // CONSTRUCTOR
    // ==========================================

    describe('constructor', () => {
        it('should create a QuickAuthClient instance', () => {
            expect(client).toBeInstanceOf(QuickAuthClient);
        });

        it('should initialize the HTTP client', () => {
            expect(mocks.httpOptions).not.toBeNull();

            expect(mocks.httpOptions?.baseURL).toBe(
                'https://api.example.com'
            );
        });

        it('should configure the HTTP timeout', () => {
            expect(mocks.httpOptions?.timeout).toBe(15000);
        });

        it('should initialize AuthService', () => {
            expect(mocks.authOptions).not.toBeNull();
        });

        it('should initialize AuthState', () => {
            expect(mocks.state).not.toBeNull();
        });

        it('should initialize AuthGuard', () => {
            expect(mocks.guard).not.toBeNull();
        });

        it('should share the same HTTP instance with AuthService', () => {
            expect(mocks.authHttp).toBe(mocks.httpInstance);
        });

        it('should share the same AuthService with AuthState', () => {
            expect(mocks.stateAuthService).toBe(
                client.getAuthService()
            );
        });

        it('should pass guard configuration', () => {
            const options = {
                loginRoute: { name: 'login' },
            };

            const configuredClient = createClient({
                guard: options,
            });

            expect(configuredClient.guard).toBeDefined();

            expect(mocks.guard.options).toEqual(options);
        });
    });

    // ==========================================
    // INITIALIZATION
    // ==========================================

    describe('initialize', () => {
        it('should initialize authentication', async () => {
            await client.initialize();

            expect(mocks.state.initialize).toHaveBeenCalledTimes(1);
        });

        it('should return the authenticated user', async () => {
            mocks.state.user.value = mockUser;

            const result = await client.initialize();

            expect(result).toEqual(mockUser);
        });

        it('should return null when no user exists', async () => {
            mocks.state.user.value = null;

            const result = await client.initialize();

            expect(result).toBeNull();
        });

        it('should return null when initialization fails', async () => {
            mocks.state.initialize.mockRejectedValue(
                new Error('Backend unavailable')
            );

            const result = await client.initialize();

            expect(result).toBeNull();
        });
    });

    // ==========================================
    // LOGIN
    // ==========================================

    describe('login', () => {
        it('should authenticate a user', async () => {
            mocks.state.login.mockResolvedValue(mockUser);

            const result = await client.login(mockCredentials);

            expect(result).toEqual(mockUser);
        });

        it('should delegate credentials to AuthState', async () => {
            mocks.state.login.mockResolvedValue(mockUser);

            await client.login(mockCredentials);

            expect(mocks.state.login).toHaveBeenCalledWith(
                mockCredentials
            );
        });

        it('should propagate login errors', async () => {
            mocks.state.login.mockRejectedValue(
                new Error('Invalid credentials')
            );

            await expect(
                client.login(mockCredentials)
            ).rejects.toThrow('Invalid credentials');
        });
    });

    // ==========================================
    // LOGOUT
    // ==========================================

    describe('logout', () => {
        it('should delegate logout to AuthState', async () => {
            await client.logout();

            expect(mocks.state.logout).toHaveBeenCalledTimes(1);
        });

        it('should propagate logout errors', async () => {
            mocks.state.logout.mockRejectedValue(
                new Error('Logout failed')
            );

            await expect(
                client.logout()
            ).rejects.toThrow('Logout failed');
        });
    });

    // ==========================================
    // USER REFRESH
    // ==========================================

    describe('refreshUser', () => {
        it('should refresh authenticated user information', async () => {
            mocks.state.refresh.mockResolvedValue(mockUser);

            const result = await client.refreshUser();

            expect(result).toEqual(mockUser);
        });

        it('should delegate refresh to AuthState', async () => {
            mocks.state.refresh.mockResolvedValue(mockUser);

            await client.refreshUser();

            expect(mocks.state.refresh).toHaveBeenCalledTimes(1);
        });
    });

    // ==========================================
    // USER INFORMATION
    // ==========================================

    describe('user information', () => {
        it('should return the current user', () => {
            mocks.state.user.value = mockUser;

            expect(client.getUser()).toEqual(mockUser);
        });

        it('should return null when no user exists', () => {
            mocks.state.user.value = null;

            expect(client.getUser()).toBeNull();
        });

        it('should identify an authenticated user', () => {
            mocks.state.user.value = mockUser;

            expect(client.isAuthenticated()).toBe(true);
        });

        it('should identify an unauthenticated user', () => {
            mocks.state.user.value = null;

            expect(client.isAuthenticated()).toBe(false);
        });

        it('should expose loading state', () => {
            mocks.state.status.value = 'loading';

            expect(client.isLoading()).toBe(true);
        });

        it('should expose initialized state', () => {
            mocks.state.initialized.value = true;

            expect(client.isInitialized()).toBe(true);
        });
    });

    // ==========================================
    // ROLE MANAGEMENT
    // ==========================================

    describe('role management', () => {
        it('should check a role', () => {
            mocks.state.hasRole.mockReturnValue(true);

            expect(client.hasRole('admin')).toBe(true);

            expect(mocks.state.hasRole).toHaveBeenCalledWith(
                'admin'
            );
        });

        it('should check whether the user has any role', () => {
            mocks.state.hasAnyRole.mockReturnValue(true);

            const result = client.hasAnyRole([
                'admin',
                'developer',
            ]);

            expect(result).toBe(true);

            expect(mocks.state.hasAnyRole).toHaveBeenCalledWith([
                'admin',
                'developer',
            ]);
        });

        it('should check whether the user has all roles', () => {
            mocks.state.hasAllRoles.mockReturnValue(true);

            const result = client.hasAllRoles([
                'admin',
                'developer',
            ]);

            expect(result).toBe(true);

            expect(mocks.state.hasAllRoles).toHaveBeenCalledWith([
                'admin',
                'developer',
            ]);
        });
    });

    // ==========================================
    // TOKEN MANAGEMENT
    // ==========================================

    describe('token management', () => {
        it('should configure the access token', () => {
            client.setAccessToken('mock-access-token');

            expect(mocks.setAccessToken).toHaveBeenCalledWith(
                'mock-access-token'
            );
        });

        it('should allow clearing the access token', () => {
            client.setAccessToken(null);

            expect(mocks.setAccessToken).toHaveBeenCalledWith(null);
        });

        it('should configure the CSRF token', () => {
            client.setCsrfToken('mock-csrf-token');

            expect(mocks.setCsrfToken).toHaveBeenCalledWith(
                'mock-csrf-token'
            );
        });

        it('should synchronize tokens from AuthService', () => {
            const callback = mocks.authOptions
                ?.onAccessTokenChange as (
                    token: string | null
                ) => void;

            expect(callback).toBeTypeOf('function');

            callback('new-access-token');

            expect(mocks.setAccessToken).toHaveBeenCalledWith(
                'new-access-token'
            );
        });
    });

    // ==========================================
    // HTTP CLIENT
    // ==========================================

    describe('HTTP client access', () => {
        it('should return the HTTP client', () => {
            expect(client.getHttpClient()).toBe(mocks.httpClient);
        });

        it('should return the AuthService', () => {
            expect(client.getAuthService()).toBeDefined();
        });

        it('should return the AuthState', () => {
            expect(client.getAuthState()).toBe(mocks.state);
        });
    });

    // ==========================================
    // VUE ROUTER
    // ==========================================

    describe('router integration', () => {
        it('should install the authentication guard', () => {
            const router = {
                beforeEach: vi.fn(),
            };

            client.install(router as any);

            expect(mocks.guardInstall).toHaveBeenCalledWith(
                router
            );
        });

        it('should return the unregister function', () => {
            const router = {
                beforeEach: vi.fn(),
            };

            const unregister = client.install(router as any);

            expect(unregister).toBe(mocks.uninstallGuard);
        });
    });

    // ==========================================
    // CLEAR AUTHENTICATION
    // ==========================================

    describe('clear', () => {
        it('should clear the authentication state', () => {
            client.clear();

            expect(mocks.state.clear).toHaveBeenCalledTimes(1);
        });
    });

    // ==========================================
    // UNAUTHORIZED RESPONSE
    // ==========================================

    describe('unauthorized responses', () => {
        it('should clear authentication when HTTP 401 occurs', () => {
            const unauthorizedCallback =
                mocks.httpOptions?.onUnauthorized as
                    (() => void) | undefined;

            expect(unauthorizedCallback).toBeTypeOf('function');

            unauthorizedCallback?.();

            expect(mocks.state.clear).toHaveBeenCalledTimes(1);
        });

        it('should invoke the custom unauthorized callback', () => {
            const onUnauthorized = vi.fn();

            const configuredClient = createClient({
                onUnauthorized,
            });

            const callback =
                mocks.httpOptions?.onUnauthorized as
                    (() => void) | undefined;

            callback?.();

            expect(onUnauthorized).toHaveBeenCalledTimes(1);
            expect(configuredClient).toBeInstanceOf(QuickAuthClient);
        });
    });

    // ==========================================
    // DESTRUCTION
    // ==========================================

    describe('destroy', () => {
        it('should clear the authentication state', () => {
            client.destroy();

            expect(mocks.state.clear).toHaveBeenCalledTimes(1);
        });

        it('should destroy the HTTP client', () => {
            client.destroy();

            expect(mocks.httpDestroy).toHaveBeenCalledTimes(1);
        });

        it('should be safe to destroy multiple times', () => {
            client.destroy();
            client.destroy();

            expect(mocks.state.clear).toHaveBeenCalledTimes(1);
            expect(mocks.httpDestroy).toHaveBeenCalledTimes(1);
        });

        it('should reject operations after destruction', () => {
            client.destroy();

            expect(() => client.getUser()).toThrow(
                'QuickAuthClient has been destroyed.'
            );
        });

        it('should reject login after destruction', async () => {
            client.destroy();

            await expect(
                client.login(mockCredentials)
            ).rejects.toThrow(
                'QuickAuthClient has been destroyed.'
            );
        });

        it('should reject initialization after destruction', async () => {
            client.destroy();

            await expect(
                client.initialize()
            ).rejects.toThrow(
                'QuickAuthClient has been destroyed.'
            );
        });
    });
});
