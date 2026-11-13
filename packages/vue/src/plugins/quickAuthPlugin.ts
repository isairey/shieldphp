
import type {
    App,
    Plugin,
} from 'vue';

import type { Router } from 'vue-router';

import QuickAuthClient from '../client/QuickAuthClient';

import {
    SHIELDPHP_INJECTION_KEY,
} from '../composables/useAuth';

import {
    installAuthGuard,
    type AuthGuardOptions,
} from '../guards/authGuard';

/**
 * QuickAuthPlugin configuration.
 */
export interface QuickAuthPluginOptions {
    /**
     * Vue Router instance.
     *
     * When provided, ShieldPHP automatically
     * registers its authentication navigation guard.
     */
    router?: Router;

    /**
     * Authentication guard configuration.
     */
    guard?: AuthGuardOptions;

    /**
     * Register QuickAuthClient through Vue provide/inject.
     *
     * Defaults to true.
     */
    provide?: boolean;

    /**
     * Expose the client through Vue global properties.
     *
     * Available as this.$shieldphp in Options API.
     *
     * Defaults to true.
     */
    globalProperties?: boolean;

    /**
     * Automatically initialize authentication
     * when the plugin is installed.
     *
     * Defaults to false.
     */
    initializeOnInstall?: boolean;

    /**
     * Callback invoked when automatic initialization fails.
     */
    onInitializationError?: (error: unknown) => void;
}

/**
 * Extend Vue component instance properties.
 */
declare module 'vue' {
    interface ComponentCustomProperties {
        /**
         * Global ShieldPHP client instance.
         */
        $shieldphp: QuickAuthClient;
    }
}

/**
 * QuickAuthPlugin
 *
 * Official Vue 3 plugin for ShieldPHP.
 *
 * Features:
 * - Register QuickAuthClient globally.
 * - Provide authentication dependencies.
 * - Integrate Vue Router authentication guards.
 * - Expose the client through global properties.
 * - Optionally initialize authentication.
 *
 * The plugin does not create a new authentication
 * client. It uses the existing QuickAuthClient instance.
 */
export function createQuickAuthPlugin(
    client: QuickAuthClient,
    options: QuickAuthPluginOptions = {}
): Plugin {
    const {
        router,
        guard = {},
        provide = true,
        globalProperties = true,
        initializeOnInstall = false,
        onInitializationError,
    } = options;

    /**
     * Prevent duplicate installation on the same Vue app.
     */
    const installedApps = new WeakSet<App>();

    const plugin: Plugin = {
        install(app: App): void {
            if (installedApps.has(app)) {
                return;
            }

            installedApps.add(app);

            // ======================================
            // DEPENDENCY INJECTION
            // ======================================

            if (provide) {
                app.provide(
                    SHIELDPHP_INJECTION_KEY,
                    client
                );
            }

            // ======================================
            // GLOBAL PROPERTIES
            // ======================================

            if (globalProperties) {
                app.config.globalProperties.$shieldphp = client;
            }

            // ======================================
            // VUE ROUTER INTEGRATION
            // ======================================

            if (router) {
                installAuthGuard(
                    router,
                    client.getAuthState(),
                    guard
                );
            }

            // ======================================
            // AUTHENTICATION INITIALIZATION
            // ======================================

            if (initializeOnInstall) {
                void client.initialize().catch((error: unknown) => {
                    onInitializationError?.(error);
                });
            }
        },
    };

    return plugin;
}

/**
 * QuickAuthPlugin factory alias.
 */
export const quickAuthPlugin = createQuickAuthPlugin;

/**
 * Default export.
 */
export default createQuickAuthPlugin;
