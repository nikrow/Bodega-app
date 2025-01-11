import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/app.css',
            'resources/js/app.js',
        ]),
        vue(),
    ],
    server: {
        host: '0.0.0.0',  // Permitir accesos externos
        port: 5173,
        cors: {
            origin: '*',  // Asegurar que se permita cualquier origen
            credentials: true
        },
        strictPort: true,
    },
    build: {
        sourcemap: false
    },
    resolve: {
        alias: {
            '@': '/resources/js',
            'vendor': path.resolve(__dirname, 'vendor'),
        },
    },
});
