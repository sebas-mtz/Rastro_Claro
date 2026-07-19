import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    build: {
        rollupOptions: {
            // Esto ayuda a que Rollup sea más flexible con los nombres de rutas
            // e ignora advertencias severas de duplicados por mayúsculas
            preserveEntrySignatures: 'exports-only',
        }
    }
});
