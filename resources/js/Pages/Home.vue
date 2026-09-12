<script setup>
import { Link } from '@inertiajs/vue3'

const tools = [
  {
    name: 'rates.form',
    title: 'Conversión de tasas',
    body: 'Pase cualquier tasa a efectiva, nominal, anticipada o continua, y vea la tabla completa de equivalentes.',
  },
  {
    name: 'amortization.create',
    title: 'Tablas de amortización',
    body: 'Cuota uniforme, abono constante, periodo de gracia, cuotas con gradiente y deuda en moneda extranjera.',
  },
  {
    name: 'equations.create',
    title: 'Ecuaciones de valor',
    body: 'Arme el diagrama económico flujo por flujo y despeje el monto, el tiempo o la tasa en cualquier fecha focal.',
  },
]
</script>

<template>
  <div class="mx-auto max-w-3xl px-6 py-12">
    <h1 class="text-2xl font-semibold">Resolver problemas de matemáticas financieras</h1>
    <p class="mt-3 max-w-xl text-stone-600">
      Tres herramientas que cubren los siete capítulos del texto. Cada resultado muestra
      el procedimiento, no solo la cifra.
    </p>

    <div class="mt-8 divide-y divide-stone-200 border-y border-stone-200">
      <Link
        v-for="tool in tools"
        :key="tool.name"
        :href="route(tool.name)"
        class="block py-5 hover:bg-white"
      >
        <h2 class="font-medium">{{ tool.title }}</h2>
        <p class="mt-1 max-w-xl text-sm text-stone-600">{{ tool.body }}</p>
      </Link>
    </div>
  </div>
</template>
