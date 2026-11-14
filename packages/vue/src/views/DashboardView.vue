
<script setup lang="ts">
import { useRouter } from 'vue-router';
import { useAuth } from 'shieldphp-vue';
import { useUser } from 'shieldphp-vue';

const router = useRouter();

const {
    logout,
    hasRole,
} = useAuth();

const {
    displayName,
    email,
    roles,
} = useUser();

async function handleLogout() {
    try {
        await logout();
    } finally {
        await router.replace({
            name: 'login',
        });
    }
}
</script>

<template>
    <main class="dashboard">
        <header>
            <h1>ShieldPHP Dashboard</h1>

            <button @click="handleLogout">
                Cerrar sesión
            </button>
        </header>

        <section class="user-card">
            <span class="label">Usuario autenticado</span>

            <h2>{{ displayName }}</h2>

            <p>{{ email }}</p>

            <div class="roles">
                <span
                    v-for="role in roles"
                    :key="role"
                    class="role"
                >
                    {{ role }}
                </span>
            </div>
        </section>

        <section v-if="hasRole('admin')" class="admin-card">
            <h3>Panel de administración</h3>
            <p>El usuario tiene el rol de administrador.</p>
        </section>
    </main>
</template>

<style scoped>
.dashboard {
    max-width: 1100px;
    margin: auto;
    padding: 40px 24px;
}

header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 36px;
}

button {
    padding: 11px 18px;
    border: 0;
    border-radius: 8px;
    background: #dc2626;
    color: white;
}

.user-card,
.admin-card {
    padding: 26px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: white;
}

.label {
    color: #64748b;
    font-size: 13px;
}

.roles {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.role {
    padding: 6px 12px;
    border-radius: 20px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: 13px;
}

.admin-card {
    background: #f8fafc;
}
</style>
