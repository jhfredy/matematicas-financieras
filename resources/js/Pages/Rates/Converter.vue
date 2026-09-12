<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import RateInput from '@/Components/RateInput.vue'
import { useFormat } from '@/composables/useFormat'

const props = defineProps({
  options: { type: Object, required: true },
  input: { type: Object, default: null },
  result: { type: Object, default: null },
})

const { pct } = useFormat()

const form = useForm({
  rate: props.input?.rate ?? null,
  rate_type: props.input?.rate_type ?? 'nominal_vencida',
  rate_period: props.input?.rate_period ?? 'mensual',
  rate_reference: props.input?.rate_reference ?? 'anual',
  target_type: props.input?.target_type ?? 'efectiva',
  target_period: props.input?.target_period ?? 'anual',
  target_reference: props.input?.target_reference ?? 'anual',
})

const sourceFields = computed(() => ({
  rate: form.rate,
  rate_type: form.rate_type,
  rate_period: form.rate_period,
  rate_reference: form.rate_reference,
}))

const applySource = (value) => Object.assign(form, value)
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6 px-6 py-8">
    <header>
      <h1 class="text-xl font-semibold">Conversión de tasas</h1>
      <p class="mt-1 text-sm text-stone-600">
        Una tasa nominal no mide el costo real del dinero. Acá se pasa a la modalidad
        y el periodo que necesite, y se ve la equivalencia completa.
      </p>
    </header>

    <form class="space-y-5" @submit.prevent="form.post(route('rates.convert'), { preserveScroll: true })">
      <section class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-medium">Tasa que tiene</h2>
        <RateInput
          :model-value="sourceFields"
          :options="options"
          :errors="form.errors"
          @update:model-value="applySource"
        />
      </section>

      <section class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-medium">Tasa que busca</h2>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
          <label class="block">
            <span class="text-xs text-stone-600">Modalidad</span>
            <select v-model="form.target_type" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option v-for="type in options.rateTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </label>

          <label class="block">
            <span class="text-xs text-stone-600">Periodo</span>
            <select v-model="form.target_period" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option v-for="period in options.periods" :key="period.value" :value="period.value">
                {{ period.label }}
              </option>
            </select>
          </label>
        </div>
      </section>

      <button
        type="submit" :disabled="form.processing"
        class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
      >Convertir</button>
    </form>

    <section v-if="result" class="space-y-4">
      <div class="rounded-lg border border-stone-200 bg-white p-5">
        <p class="text-xs text-stone-500">{{ result.label }}</p>
        <p class="cifras mt-1 text-3xl font-semibold">{{ pct(result.value) }}</p>
        <p class="mt-2 text-xs text-stone-500">
          Efectiva anual equivalente: <span class="cifras">{{ pct(result.effective_annual) }}</span>
        </p>
      </div>

      <div class="rounded-lg border border-stone-200 bg-white p-5">
        <h3 class="text-sm font-medium">La misma tasa en todas las modalidades</h3>
        <p class="mt-1 text-xs text-stone-500">
          Las celdas vacías son conversiones imposibles: una anticipada que llegaría a 100% o más.
        </p>

        <div class="mt-3 overflow-x-auto">
          <table class="w-full text-right text-sm">
            <thead class="border-b border-stone-200 text-xs text-stone-500">
              <tr>
                <th class="py-2 text-left font-normal">Periodo</th>
                <th class="py-2 font-normal">Efectiva</th>
                <th class="py-2 font-normal">Nominal vencida</th>
                <th class="py-2 font-normal">Periódica anticipada</th>
                <th class="py-2 font-normal">Nominal anticipada</th>
              </tr>
            </thead>
            <tbody class="cifras divide-y divide-stone-100">
              <tr v-for="row in result.equivalences" :key="row.period">
                <td class="py-2 text-left">{{ row.period_label }}</td>
                <td class="py-2">{{ pct(row.efectiva) }}</td>
                <td class="py-2">{{ pct(row.nominal_vencida) }}</td>
                <td class="py-2">{{ pct(row.periodica_anticipada) }}</td>
                <td class="py-2">{{ pct(row.nominal_anticipada) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</template>
