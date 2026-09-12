<script setup>
import { Link } from '@inertiajs/vue3'

const nav = [
  { name: 'rates.form', label: 'Tasas' },
  { name: 'amortization.create', label: 'Amortización' },
  { name: 'equations.create', label: 'Ecuaciones de valor' },
]
</script>

<template>
  <div class="min-h-screen">
    <header class="border-b border-stone-200 bg-white">
      <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-6 py-4">
        <Link :href="route('home')" class="text-sm font-semibold text-stone-900">
          Matemáticas financieras
        </Link>

        <nav class="flex gap-4 text-sm">
          <Link
            v-for="item in nav"
            :key="item.name"
            :href="route(item.name)"
            class="text-stone-600 hover:text-stone-900"
            :class="{ 'font-medium text-stone-900': route().current(item.name) }"
          >
            {{ item.label }}
          </Link>
        </nav>
      </div>
    </header>

    <main>
      <slot />
    </main>

    <footer class="mx-auto max-w-6xl px-6 py-8 text-xs text-stone-500">
      Basado en <em>Fundamentos de matemáticas financieras</em>, Ramírez Molinares,
      García Barbosa, Pantoja Algarín y Zambrano Meza. Universidad Libre, Cartagena, 2009.
    </footer>
  </div>
</template>
