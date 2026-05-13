import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pages/calendar.js'],
      refresh: true,
    }),
  ],
  server: {
    host: '0.0.0.0',      // expose to LAN
    port: 5173,
    strictPort: true,
    hmr: {
      host: process.env.VITE_HMR_HOST ?? 'localhost',
      protocol: 'ws',
      port: 5173,
    },
  },
});
