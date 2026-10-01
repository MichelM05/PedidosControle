import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/upload.css',
                'resources/js/upload.js',
                'resources/css/pedidos/form.css',
                'resources/js/pedidos/form.js',
                'resources/css/pedidos/show.css',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
