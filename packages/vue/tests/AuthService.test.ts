
import { describe, it, expect, vi, beforeEach } from 'vitest';
import type { AxiosInstance } from 'axios';

import AuthService from '../src/auth/AuthService';

import type {
    AuthUser,
    LoginCredentials,
    AuthSession,
} from '../src/auth/AuthService';

/**
 * AuthService Unit Tests
 *
 * Tests:
 * - User login.
 * - Credential validation.
 * - Authenticated user retrieval.
 * - User logout.
 * - Access token synchronization.
 * - API response validation.
 * - Custom endpoint configuration.
 */

describe('AuthService', () => {
    let service: AuthService;
    let http: AxiosInstance;

    let postMock: ReturnType<typeof vi.fn>;
    let getMock: ReturnType<typeof vi.fn>;

    let onAccessTokenChange: ReturnType<typeof vi.fn>;

    const mockUser: AuthUser = {
        id: 'user-123',
        email: 'usuario@example.com',
        name: 'Test User',
        roles: ['user'],
        permissions: ['profile.read'],
    };

    const mockSession: AuthSession = {
        access_token: 'mock-access-token',
        token_type: 'Bearer',
        expires_in: 900,
        user: mockUser,
    };

    const mockCredentials: LoginCredentials = {
        email: 'usuario@example.com',
        password: 'PasswordSeguro123!',
    };

    beforeEach(() => {
        vi.clearAllMocks();

        postMock = vi.fn();
        getMock = vi.fn();

        onAccessTokenChange = vi.fn();

        http = {
            post: postMock,
            get: getMock,
        } as unknown as AxiosInstance;

        service = new AuthService(http, {
            onAccessTokenChange,
        });
    });

    // ==========================================
    // CONSTRUCTOR
    // ==========================================

    describe('constructor', () => {
        it('should create an AuthService instance', () => {
            expect(service).toBeInstanceOf(AuthService);
        });

        it('should accept a custom login endpoint', async () => {
            const customService = new AuthService(http, {
                endpoints: {
                    login: '/custom/login',
                },
                onAccessTokenChange,
            });

            postMock.mockResolvedValue({
                data: mockSession,
            });

            await customService.login(mockCredentials);

            expect(postMock).toHaveBeenCalledWith(
                '/custom/login',
                mockCredentials
            );
        });
    });

    // ==========================================
    // LOGIN
    // ==========================================

    describe('login', () => {
        it('should authenticate a user successfully', async () => {
            postMock.mockResolvedValue({
                data: mockSession,
            });

            const result = await service.login(mockCredentials);

            expect(result).toEqual(mockSession);

            expect(postMock).toHaveBeenCalledWith(
                '/auth/login',
                mockCredentials
            );
        });

        it('should return the authenticated user', async () => {
            postMock.mockResolvedValue({
                data: mockSession,
            });

            const result = await service.login(mockCredentials);

            expect(result.user).toEqual(mockUser);
        });

        it('should synchronize the access token', async () => {
            postMock.mockResolvedValue({
                data: mockSession,
            });

            await service.login(mockCredentials);

            expect(onAccessTokenChange).toHaveBeenCalledWith(
                'mock-access-token'
            );
        });

        it('should trim the email before sending the request', async () => {
            postMock.mockResolvedValue({
                data: mockSession,
            });

            await service.login({
                email: '  usuario@example.com  ',
                password: mockCredentials.password,
            });

            expect(postMock).toHaveBeenCalledWith(
                '/auth/login',
                mockCredentials
            );
        });

        it('should reject an empty email', async () => {
            await expect(
                service.login({
                    email: '',
                    password: 'PasswordSeguro123!',
                })
            ).rejects.toThrow('Email is required.');

            expect(postMock).not.toHaveBeenCalled();
        });

        it('should reject an email containing only whitespace', async () => {
            await expect(
                service.login({
                    email: '   ',
                    password: 'PasswordSeguro123!',
                })
            ).rejects.toThrow('Email is required.');

            expect(postMock).not.toHaveBeenCalled();
        });

        it('should reject an empty password', async () => {
            await expect(
                service.login({
                    email: 'usuario@example.com',
                    password: '',
                })
            ).rejects.toThrow('Password is required.');

            expect(postMock).not.toHaveBeenCalled();
        });

        it('should support cookie-based authentication without an access token', async () => {
            const cookieSession: AuthSession = {
                user: mockUser,
            };

            postMock.mockResolvedValue({
                data: cookieSession,
            });

            const result = await service.login(mockCredentials);

            expect(result.user).toEqual(mockUser);

            expect(onAccessTokenChange).toHaveBeenCalledWith(null);
        });

        it('should reject an invalid authentication response', async () => {
            postMock.mockResolvedValue({
                data: {
                    access_token: 'mock-token',
                },
            });

            await expect(
                service.login(mockCredentials)
            ).rejects.toThrow('Invalid authentication response.');
        });

        it('should support standard API response envelopes', async () => {
            postMock.mockResolvedValue({
                data: {
                    success: true,
                    data: mockSession,
                },
            });

            const result = await service.login(mockCredentials);

            expect(result).toEqual(mockSession);
        });

        it('should reject API responses with success false', async () => {
            postMock.mockResolvedValue({
                data: {
                    success: false,
                    message: 'Invalid credentials.',
                },
            });

            await expect(
                service.login(mockCredentials)
            ).rejects.toThrow('Invalid credentials.');
        });
    });

    // ==========================================
    // CURRENT USER
    // ==========================================

    describe('me', () => {
        it('should retrieve the authenticated user', async () => {
            getMock.mockResolvedValue({
                data: mockUser,
            });

            const result = await service.me();

            expect(result).toEqual(mockUser);

            expect(getMock).toHaveBeenCalledWith(
                '/auth/me'
            );
        });

        it('should support an API response envelope', async () => {
            getMock.mockResolvedValue({
                data: {
                    success: true,
                    data: mockUser,
                },
            });

            const result = await service.me();

            expect(result).toEqual(mockUser);
        });

        it('should reject an invalid user response', async () => {
            getMock.mockResolvedValue({
                data: {
                    id: 123,
                    email: null,
                },
            });

            await expect(
                service.me()
            ).rejects.toThrow('Invalid user response.');
        });

        it('should propagate HTTP errors', async () => {
            const error = new Error('Unauthorized');

            getMock.mockRejectedValue(error);

            await expect(
                service.me()
            ).rejects.toThrow('Unauthorized');
        });
    });

    // ==========================================
    // LOGOUT
    // ==========================================

    describe('logout', () => {
        it('should send a logout request', async () => {
            postMock.mockResolvedValue({
                data: {
                    success: true,
                },
            });

            await service.logout();

            expect(postMock).toHaveBeenCalledWith(
                '/auth/logout'
            );
        });

        it('should clear the access token after logout', async () => {
            postMock.mockResolvedValue({
                data: {
                    success: true,
                },
            });

            await service.logout();

            expect(onAccessTokenChange).toHaveBeenCalledWith(null);
        });

        it('should clear the access token even when logout fails', async () => {
            postMock.mockRejectedValue(
                new Error('Server error')
            );

            await expect(
                service.logout()
            ).rejects.toThrow('Server error');

            expect(onAccessTokenChange).toHaveBeenCalledWith(null);
        });
    });

    // ==========================================
    // ACCESS TOKEN
    // ==========================================

    describe('access token management', () => {
        it('should expose the received access token through the callback', async () => {
            postMock.mockResolvedValue({
                data: mockSession,
            });

            await service.login(mockCredentials);

            expect(onAccessTokenChange).toHaveBeenCalledTimes(1);
            expect(onAccessTokenChange).toHaveBeenLastCalledWith(
                'mock-access-token'
            );
        });

        it('should clear the token manually', () => {
            service.clearAccessToken();

            expect(onAccessTokenChange).toHaveBeenCalledWith(null);
        });
    });
});
