import {defineConfig} from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

/**
 * Builds the admin-embeddable Remotion Player SPA into
 * backend/public/promo-videos-player/ so Laravel can serve it.
 */
export default defineConfig({
  plugins: [react()],
  root: path.resolve(__dirname, 'src/player'),
  publicDir: path.resolve(__dirname, 'public'),
  base: '/promo-videos-player/',
  build: {
    outDir: path.resolve(__dirname, '../backend/public/promo-videos-player'),
    emptyOutDir: true,
  },
  server: {
    port: 5179,
    strictPort: true,
  },
});
