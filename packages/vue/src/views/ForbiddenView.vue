
<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();
</script>

<template>
    <main class="forbidden">
        <div class="content">
            <span>403</span>

            <h1>Acceso denegado</h1>

            <p>
                No tienes permisos suficientes para acceder
                a este recurso.
            </p>

            <button @click="router.push({ name: 'dashboard' })">
                Volver al dashboard
            </button>
        </div>
    </main>
</template>

<style scoped>
.forbidden {
    display: grid;
    min-height: 100vh;
    place-items: center;
    padding: 24px;
    text-align: center;
}

.content span {
    color: #dc2626;
    font-size: 72px;
    font-weight: 800;
}

.content p {
    color: #64748b;
}

button {
    margin-top: 20px;
    padding: 12px 20px;
    border: 0;
    border-radius: 8px;
    background: #2563eb;
    color: white;
}
</style>
