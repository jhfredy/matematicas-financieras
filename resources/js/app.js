import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import AppLayout from './Layouts/AppLayout.vue'
import '../css/app.css'

createInertiaApp({
  title: (title) => (title ? `${title} · Matemáticas financieras` : 'Matemáticas financieras'),

  resolve: async (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue')
    const page = (await pages[`./Pages/${name}.vue`]()).default

    page.layout = page.layout ?? AppLayout

    return page
  },

  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .mount(el)
  },

  progress: { color: '#059669' },
})
