import forms from '@tailwindcss/forms'

export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        // Verde para ingresos y deudas, rojo para egresos y pagos:
        // la misma convención de flechas del diagrama económico
        ingreso: {
          50: '#ecfdf5',
          200: '#a7f3d0',
          600: '#059669',
          800: '#065f46',
        },
        egreso: {
          50: '#fff1f2',
          200: '#fecdd3',
          600: '#e11d48',
          800: '#9f1239',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        // Las tablas de amortización se leen mejor con cifras de ancho fijo
        tabular: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [forms],
}
