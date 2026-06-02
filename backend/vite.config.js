import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwind from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/css/app.css', 
        'resources/js/app.js',
        'resources/js/frontend-app.js',
        'resources/js/metronic-vue-demo1.ts',
        'resources/js/taxi-portal-app.ts'
      ],
      refresh: true,
    }),
    tailwind(),
    vue(),
  ],
  build: {
    sourcemap: false,
  },
})
