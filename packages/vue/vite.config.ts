
import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';

/**
 * ShieldPHP Vue
 *
 * Vite Library Configuration
 *
 * Builds ShieldPHP Vue as a reusable TypeScript library.
 *
 * External dependencies:
 * - Vue 3
 * - Vue Router 4
 * - Axios
 */

const entry = fileURLToPath(
    new URL('./src/index.ts', import.meta.url)
);

export default defineConfig({
    build: {
        lib: {
            entry,

            name: 'ShieldPHPVue',

            formats: ['es', 'cjs'],

            fileName: (format) => {
                return format === 'es'
                    ? 'index.js'
                    : 'index.cjs';
            },
        },

        rollupOptions: {
            external: [
                'vue',
                'vue-router',
                'axios',
            ],
        },

        sourcemap: true,

        emptyOutDir: false,

        minify: 'esbuild',
    },

    resolve: {
        extensions: [
            '.mjs',
            '.js',
            '.ts',
            '.json',
        ],
    },
});
