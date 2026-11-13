
import axios, {
    type AxiosInstance,
    type AxiosRequestConfig,
    type AxiosResponse,
    type InternalAxiosRequestConfig,
    AxiosHeaders,
} from 'axios';

/**
 * Authentication modes supported by ShieldPHP.
 */
export type HttpAuthMode = 'bearer' | 'cookie' | 'none';

/**
 * HTTP client configuration.
 */
export interface HttpClientOptions {
    /**
     * Base URL of the ShieldPHP backend.
     */
    baseURL: string;

    /**
     * Request timeout in milliseconds.
     */
    timeout?: number;

    /**
     * Authentication strategy.
     *
     * bearer: Uses Authorization: Bearer.
     * cookie: Uses browser cookies.
     * none: Does not automatically attach credentials.
     */
    authMode?: HttpAuthMode;

    /**
     * Enables sending cookies with cross-origin requests.
     */
    withCredentials?: boolean;

    /**
     * Default HTTP headers.
     */
    headers?: Record<string, string>;

    /**
     * CSRF header name.
     */
    csrfHeaderName?: string;

    /**
     * Called when the backend responds with HTTP 401.
     */
    onUnauthorized?: () => void;
}

/**
 * HttpClient
 *
 * Centralized HTTP client for ShieldPHP Vue.
 *
 * Features:
 * - Axios instance management.
 * - JWT Bearer authentication.
 * - Cookie-based session authentication.
 * - CSRF header support.
 * - Request and response interceptors.
 * - Unauthorized response handling.
 * - Configurable HTTP timeout.
 * - Typed HTTP methods.
 *
 * Security:
 * Access tokens are maintained in memory.
 * This client does not persist tokens in localStorage.
 */
export class HttpClient {
    /**
     * Underlying Axios instance.
     */
    readonly instance: AxiosInstance;

    private readonly authMode: HttpAuthMode;

    private readonly csrfHeaderName: string;

    private accessToken: string | null = null;

    private csrfToken: string | null = null;

    private readonly onUnauthorized?: () => void;

    private requestInterceptorId: number;

    private responseInterceptorId: number;

    constructor(options: HttpClientOptions) {
        if (!options.baseURL?.trim()) {
            throw new Error(
                'HttpClient requires a valid baseURL.'
            );
        }

        this.authMode = options.authMode ?? 'bearer';

        this.csrfHeaderName =
            options.csrfHeaderName ?? 'X-CSRF-TOKEN';

        this.onUnauthorized = options.onUnauthorized;

        const withCredentials =
            options.withCredentials ??
            this.authMode === 'cookie';

        this.instance = axios.create({
            baseURL: options.baseURL,
            timeout: options.timeout ?? 15000,
            withCredentials,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...options.headers,
            },
        });

        this.requestInterceptorId =
            this.instance.interceptors.request.use(
                (config) => this.handleRequest(config),
                (error: unknown) => Promise.reject(error)
            );

        this.responseInterceptorId =
            this.instance.interceptors.response.use(
                (response) => response,
                (error: unknown) => this.handleResponseError(error)
            );
    }

    /**
     * Configure the current access token.
     *
     * The token is stored only in memory.
     */
    setAccessToken(token: string | null): void {
        this.accessToken = token?.trim() || null;
    }

    /**
     * Retrieve the current in-memory access token.
     */
    getAccessToken(): string | null {
        return this.accessToken;
    }

    /**
     * Remove the current access token.
     *
     * This does not revoke the token on the backend.
     */
    clearAccessToken(): void {
        this.accessToken = null;
    }

    /**
     * Configure the CSRF token.
     *
     * The backend is responsible for issuing and validating it.
     */
    setCsrfToken(token: string | null): void {
        this.csrfToken = token?.trim() || null;
    }

    /**
     * Remove the current CSRF token.
     */
    clearCsrfToken(): void {
        this.csrfToken = null;
    }

    /**
     * Change the base URL at runtime.
     */
    setBaseURL(baseURL: string): void {
        if (!baseURL.trim()) {
            throw new Error('Invalid API base URL.');
        }

        this.instance.defaults.baseURL = baseURL;
    }

    /**
     * Return the underlying Axios instance.
     *
     * Useful for integrating AuthService and other services.
     */
    getInstance(): AxiosInstance {
        return this.instance;
    }

    /**
     * Perform a GET request.
     */
    async get<T = unknown>(
        url: string,
        config?: AxiosRequestConfig
    ): Promise<AxiosResponse<T>> {
        return this.instance.get<T>(url, config);
    }

    /**
     * Perform a POST request.
     */
    async post<T = unknown, D = unknown>(
        url: string,
        data?: D,
        config?: AxiosRequestConfig<D>
    ): Promise<AxiosResponse<T>> {
        return this.instance.post<T>(url, data, config);
    }

    /**
     * Perform a PUT request.
     */
    async put<T = unknown, D = unknown>(
        url: string,
        data?: D,
        config?: AxiosRequestConfig<D>
    ): Promise<AxiosResponse<T>> {
        return this.instance.put<T>(url, data, config);
    }

    /**
     * Perform a PATCH request.
     */
    async patch<T = unknown, D = unknown>(
        url: string,
        data?: D,
        config?: AxiosRequestConfig<D>
    ): Promise<AxiosResponse<T>> {
        return this.instance.patch<T>(url, data, config);
    }

    /**
     * Perform a DELETE request.
     */
    async delete<T = unknown>(
        url: string,
        config?: AxiosRequestConfig
    ): Promise<AxiosResponse<T>> {
        return this.instance.delete<T>(url, config);
    }

    /**
     * Perform a custom HTTP request.
     */
    async request<T = unknown, D = unknown>(
        config: AxiosRequestConfig<D>
    ): Promise<AxiosResponse<T>> {
        return this.instance.request<T, AxiosResponse<T>, D>(config);
    }

    /**
     * Process outgoing requests.
     */
    private handleRequest(
        config: InternalAxiosRequestConfig
    ): InternalAxiosRequestConfig {
        config.headers = AxiosHeaders.from(config.headers);

        // Attach JWT authentication.
        if (
            this.authMode === 'bearer' &&
            this.accessToken
        ) {
            config.headers.set(
                'Authorization',
                `Bearer ${this.accessToken}`
            );
        }

        // Attach CSRF token to state-changing requests.
        const method = (config.method ?? 'get').toUpperCase();

        const requiresCsrfProtection = ![
            'GET',
            'HEAD',
            'OPTIONS',
        ].includes(method);

        if (
            this.authMode === 'cookie' &&
            requiresCsrfProtection &&
            this.csrfToken
        ) {
            config.headers.set(
                this.csrfHeaderName,
                this.csrfToken
            );
        }

        return config;
    }

    /**
     * Handle failed HTTP responses.
     */
    private handleResponseError(error: unknown): Promise<never> {
        if (
            axios.isAxiosError(error) &&
            error.response?.status === 401
        ) {
            if (this.authMode === 'bearer') {
                this.clearAccessToken();
            }

            this.onUnauthorized?.();
        }

        return Promise.reject(error);
    }

    /**
     * Remove installed Axios interceptors.
     *
     * Useful when destroying an instance.
     */
    destroy(): void {
        this.instance.interceptors.request.eject(
            this.requestInterceptorId
        );

        this.instance.interceptors.response.eject(
            this.responseInterceptorId
        );

        this.clearAccessToken();
        this.clearCsrfToken();
    }
}

export default HttpClient;
