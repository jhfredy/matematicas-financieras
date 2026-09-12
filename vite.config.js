import { defineConfig } from 'vite'
import path from 'node:path'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [
    laravel({
      input: 'resources/js/app.js',
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      // Los componentes importan con '@/...'
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
})
