import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// The focused demo is all app code: our own CSS and the Echo/Reverb chat JS.
// Just the app's own stylesheet and the Echo/Reverb chat JS.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
