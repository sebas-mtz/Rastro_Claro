import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path'; // Importamos la herramienta de rutas de Node

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            // Esto crea un alias universal para tus páginas que suaviza la resolución de nombres
            '@Pages': path.resolve(__dirname, 'resources/js/Pages'),
        }
    }
});