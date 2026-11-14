
import { createApp } from 'vue';

import App from './App.vue';
import router from './router';
import { shield } from './shield';

import {
    createQuickAuthPlugin,
} from 'shieldphp-vue';

/**
 * Bootstrap ShieldPHP Vue application.
 */
async function bootstrap(): Promise<void> {
    const app = createApp(App);

    // Register ShieldPHP plugin.
    app.use(
        createQuickAuthPlugin(shield, {
            router,

            guard: {
                loginRoute: { name: 'login' },
                guestRedirectRoute: { name: 'dashboard' },
                forbiddenRoute: { name: 'forbidden' },
                preserveRedirect: true,
            },

            provide: true,
            globalProperties: true,
            initializeOnInstall: false,
        })
    );

    // Restore authentication.
    await shield.initialize();

    // Register router.
    app.use(router);

    // Wait for initial navigation.
    await router.isReady();

    // Mount application.
    app.mount('#app');
}

bootstrap().catch((error: unknown) => {
    console.error(
        '[ShieldPHP] Application startup failed:',
        error
    );
});
