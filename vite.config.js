import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

/**
 * Bouwt src/js/app.js (met app.css) naar web/dist. De Twig-functie vite() leest
 * web/dist/.vite/manifest.json en zet de juiste <script>- en <link>-tags in de pagina.
 */
export default defineConfig({
  plugins: [tailwindcss()],
  publicDir: false,
  base: '/dist/',
  build: {
    outDir: 'web/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'src/js/app.js',
    },
  },
});
