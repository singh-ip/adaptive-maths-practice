import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],

  server: {
    // Bind to all interfaces so the container port is reachable from the host
    host: '0.0.0.0',
    port: 5173,
    // Windows bind-mounts don't emit inotify events inside the Linux container,
    // so Vite's default FSEvents watcher never fires. Polling detects changes by
    // stat-ing files on an interval — slower but reliable on Windows + Docker.
    watch: {
      usePolling: true,
      interval: 500,
    },
  },
})
