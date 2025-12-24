import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  build: {
    rollupOptions: {
      input: {
        student: path.resolve(__dirname, 'src/mainStudent.jsx'),
        advisor: path.resolve(__dirname, 'src/mainAdvisor.jsx'),
        coordinator: path.resolve(__dirname, 'src/mainCoordinator.jsx'),
      },
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
      },
    },
  },
})