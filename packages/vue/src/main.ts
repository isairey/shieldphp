
import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';
import { shield } from './shield';

import { createQuickAuthPlugin } from 'shieldphp-vue';

/**
 * Bootstrap ShieldPHP Vue application.
 */
async function bootstrap(): Promise<void> {
    const app = createApp(App);

    // Register Pinia.
    app.use(createPinia());

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

    // Restore authentication before mounting.
    await shield.initialize();

    // Register router.
    app.use(router);

    // Wait for the initial navigation.
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
