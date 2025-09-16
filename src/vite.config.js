import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', // Docker 内からアクセス可能
        port: 5173,
        hmr: {
            host: 'localhost', // ブラウザがアクセスするホスト名
            protocol: 'ws'
        }
    },
});
