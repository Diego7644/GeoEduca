import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    setupFiles: './src/test/setup.js',
    css: false,
  },
  server: {
    host: true,
    port: 5173,
    strictPort: true,
    // Las IPv4 de esta máquina ya las acepta Vite. Aquí solo entran nombres:
    // el sufijo privado de Tailscale y, si hace falta, GEOEDUCA_DEV_HOSTS.
    allowedHosts: [
      '.ts.net',
      ...(process.env.GEOEDUCA_DEV_HOSTS || '').split(',').map((host) => host.trim()).filter(Boolean),
    ],
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
});
