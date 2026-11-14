
<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from 'shieldphp-vue';

const router = useRouter();

const {
    login,
    loading,
    error,
} = useAuth();

const form = reactive({
    email: '',
    password: '',
});

const localError = ref<string | null>(null);

async function handleLogin() {
    localError.value = null;

    try {
        await login({
            email: form.email,
            password: form.password,
        });

        await router.replace({
            name: 'dashboard',
        });
    } catch {
        localError.value = 'No fue posible iniciar sesión. Verifica tus credenciales.';
    }
}
</script>

<template>
    <main class="login-page">
        <section class="login-card">
            <div class="brand">
                <h1>ShieldPHP</h1>
                <p>Autenticación segura para aplicaciones PHP.</p>
            </div>

            <form @submit.prevent="handleLogin">
                <div class="form-group">
                    <label for="email">
                        Correo electrónico
                    </label>

                    <input
                        id="email"
                        v-model.trim="form.email"
                        type="email"
                        autocomplete="username"
                        placeholder="usuario@example.com"
                        required
                    />
                </div>

                <div class="form-group">
                    <label for="password">
                        Contraseña
                    </label>

                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Tu contraseña"
                        required
                    />
                </div>

                <p
                    v-if="localError || error"
                    class="error-message"
                    role="alert"
                >
                    {{ localError || error }}
                </p>

                <button
                    type="submit"
                    :disabled="loading"
                >
                    {{ loading ? 'Iniciando sesión...' : 'Iniciar sesión' }}
                </button>
            </form>

            <footer>
                Powered by ShieldPHP
            </footer>
        </section>
    </main>
</template>

<style scoped>
.login-page {
    display: grid;
    min-height: 100vh;
    place-items: center;
    padding: 24px;
}

.login-card {
    width: 100%;
    max-width: 420px;
    padding: 36px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 10px 35px rgb(15 23 42 / 5%);
}

.brand {
    margin-bottom: 30px;
    text-align: center;
}

.brand h1 {
    margin-bottom: 8px;
    font-size: 30px;
}

.brand p,
footer {
    color: #64748b;
    font-size: 14px;
}

.form-group {
    display: grid;
    gap: 8px;
    margin-bottom: 20px;
}

label {
    font-weight: 600;
    font-size: 14px;
}

input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 9px;
    outline: none;
}

input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgb(37 99 235 / 10%);
}

button {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 9px;
    background: #2563eb;
    color: white;
    font-weight: 600;
}

button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.error-message {
    color: #dc2626;
    font-size: 13px;
}

footer {
    margin-top: 26px;
    text-align: center;
}
</style>
