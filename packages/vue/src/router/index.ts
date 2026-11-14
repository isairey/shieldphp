
import {
    createRouter,
    createWebHistory,
} from 'vue-router';

import LoginView from '../views/auth/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import ForbiddenView from '../views/ForbiddenView.vue';

/**
 * Application routes.
 */
const router = createRouter({
    history: createWebHistory(),

    routes: [
        {
            path: '/',
            redirect: '/dashboard',
        },
        {
            path: '/login',
            name: 'login',
            component: LoginView,
            meta: {
                guestOnly: true,
            },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: DashboardView,
            meta: {
                requiresAuth: true,
            },
        },
        {
            path: '/forbidden',
            name: 'forbidden',
            component: ForbiddenView,
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: {
                template: `
                    <main>
                        <h1>404</h1>
                        <p>La página que buscas no existe.</p>
                        <RouterLink to="/dashboard">
                            Volver al inicio
                        </RouterLink>
                    </main>
                `,
            },
        },
    ],
});

export default router;
