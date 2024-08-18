import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            // Create an alias for jQWidgets to make it easier to import in your JS files
            '@jqwidgets': path.resolve(__dirname, 'node_modules/jqwidgets-scripts/jqwidgets'),
        },
    },
});
