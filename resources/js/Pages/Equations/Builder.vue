<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import FlowItem from '@/Components/Equations/FlowItem.vue'
import CashFlowDiagram from '@/Components/Equations/CashFlowDiagram.vue'
import RateInput from '@/Components/RateInput.vue'
import { useFlowItems } from '@/composables/useFlowItems'
import { useFormat } from '@/composables/useFormat'

const props = defineProps({
  options: { type: Object, required: true },
  input: { type: Object, default: null },
  result: { type: Object, default: null },
  diagram: { type: Array, default: () => [] },
})

const { money, pct } = useFormat()
const { items, add, remove, duplicate, toggleUnknown } = useFlowItems(props.input?.items ?? [])

const form = useForm({
  rate_mode: props.input?.rate_mode ?? 'flat',
  rate: props.input?.rate ?? null,
  rate_type: props.input?.rate_type ?? 'efectiva',
  rate_period: props.input?.rate_period ?? 'mensual',
  rate_reference: props.input?.rate_reference ?? 'anual',
  segments: props.input?.segments ?? [{ from: 0, rate: null }],
  focal_date: props.input?.focal_date ?? 0,
  solve_for: props.input?.solve_for ?? 'amount',
  unknown_period_amount: props.input?.unknown_period_amount ?? null,
  unknown_period_direction: props.input?.unknown_period_direction ?? 'egreso',
  items: items.value,
})

const rateFields = computed(() => ({
  rate: form.rate,
  rate_type: form.rate_type,
  rate_period: form.rate_period,
  rate_reference: form.rate_reference,
}))

const applyRate = (value) => Object.assign(form, value)

/**
 * Previsualización: los flujos conocidos van con su monto y los desconocidos
 * con altura fija, para que la estructura temporal se vea antes de calcular.
 */
const preview = computed(() => {
  if (props.diagram.length) return props.diagram

  return items.value
    .flatMap((item) => {
      const count = item.kind === 'single' ? 1 : Number(item.count) || 1
      const step = Number(item.step) || 1

      return Array.from({ length: Math.min(count, 60) }, (_, k) => ({
        period: Number(item.period) + k * step,
        amount: item.unknown ? null : Number(item.amount) || 0,
        direction: item.direction,
        unknown: item.unknown,
        label: item.unknown ? 'X' : null,
      }))
    })
    .filter((flow) => flow.unknown || flow.amount)
})

const submit = () => {
  form.items = items.value
  form.post(route('equations.solve'), { preserveScroll: true })
}

const segmentError = (i) =>
  Object.entries(form.errors).find(([key]) => key.startsWith(`segments.${i}.`))?.[1]

// Errores que no tienen un campo visible donde mostrarse
const shownKeys = ['rate', 'rate_period', 'segments', 'unknown_period_amount', 'items', 'equation']
const otherErrors = computed(() =>
  Object.entries(form.errors)
    .filter(([key]) => !shownKeys.includes(key) && !key.startsWith('items.') && !key.startsWith('segments.'))
    .map(([, message]) => message)
)
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-6 px-6 py-8">
    <header>
      <h1 class="text-xl font-semibold">Ecuaciones de valor</h1>
      <p class="mt-1 text-sm text-stone-600">
        Arme el diagrama y elija qué despejar. Con interés compuesto el resultado
        no cambia según la fecha focal, y la verificación lo comprueba.
      </p>
    </header>

    <div class="rounded-lg border border-stone-200 bg-white p-4">
      <CashFlowDiagram :flows="preview" :focal-date="Number(form.focal_date)" />
    </div>

    <form class="space-y-6" @submit.prevent="submit">
      <section class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="text-sm font-medium">Tasa de interés</h2>

        <div class="mt-3 flex gap-5 text-sm">
          <label class="flex items-center gap-2">
            <input v-model="form.rate_mode" type="radio" value="flat" class="text-stone-800" />
            Una sola tasa
          </label>
          <label class="flex items-center gap-2">
            <input v-model="form.rate_mode" type="radio" value="piecewise" class="text-stone-800" />
            Cambia por tramos
          </label>
        </div>

        <div class="mt-4">
          <RateInput
            :model-value="rateFields"
            :options="options"
            :errors="form.errors"
            :show-rate="form.rate_mode === 'flat'"
            @update:model-value="applyRate"
          />
        </div>

        <div v-if="form.rate_mode === 'piecewise'" class="mt-4 space-y-2">
          <div v-for="(segment, i) in form.segments" :key="i" class="flex items-end gap-3">
            <label class="block">
              <span class="text-xs text-stone-600">Desde el periodo</span>
              <input
                v-model.number="segment.from" type="number" step="any" min="0"
                class="cifras mt-1 w-28 rounded-md border-stone-300 text-sm"
              />
            </label>
            <label class="block">
              <span class="text-xs text-stone-600">Tasa (%)</span>
              <input
                v-model.number="segment.rate" type="number" step="any"
                class="cifras mt-1 w-28 rounded-md border-stone-300 text-sm"
              />
            </label>
            <button
              v-if="form.segments.length > 1" type="button"
              class="pb-2 text-xs text-egreso-600"
              @click="form.segments.splice(i, 1)"
            >Quitar</button>
            <p v-if="segmentError(i)" class="pb-2 text-xs text-egreso-600">{{ segmentError(i) }}</p>
          </div>

          <button
            type="button" class="text-xs font-medium text-stone-700 hover:text-stone-900"
            @click="form.segments.push({ from: null, rate: null })"
          >Agregar tramo</button>

          <p v-if="form.errors.segments" class="text-xs text-egreso-600">{{ form.errors.segments }}</p>
        </div>

        <label class="mt-4 block max-w-xs">
          <span class="text-xs text-stone-600">Fecha focal</span>
          <input
            v-model.number="form.focal_date" type="number" step="any" min="0"
            class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
          />
        </label>
      </section>

      <section class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="text-sm font-medium">Qué desea encontrar</h2>

        <div class="mt-3 flex flex-wrap gap-5 text-sm">
          <label class="flex items-center gap-2">
            <input v-model="form.solve_for" type="radio" value="amount" class="text-stone-800" />
            Un monto
          </label>
          <label class="flex items-center gap-2">
            <input v-model="form.solve_for" type="radio" value="period" class="text-stone-800" />
            La fecha de un pago
          </label>
          <label class="flex items-center gap-2">
            <input v-model="form.solve_for" type="radio" value="rate" class="text-stone-800" />
            La tasa
          </label>
        </div>

        <div v-if="form.solve_for === 'period'" class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3">
          <label class="block">
            <span class="text-xs text-stone-600">Monto del pago único</span>
            <input
              v-model.number="form.unknown_period_amount" type="number" step="any"
              class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
            />
            <p v-if="form.errors.unknown_period_amount" class="mt-1 text-xs text-egreso-600">
              {{ form.errors.unknown_period_amount }}
            </p>
          </label>

          <label class="block">
            <span class="text-xs text-stone-600">Sentido</span>
            <select v-model="form.unknown_period_direction" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option value="egreso">Egreso o pago</option>
              <option value="ingreso">Ingreso o deuda</option>
            </select>
          </label>
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-medium">Flujos de caja</h2>
          <div class="flex gap-2 text-xs">
            <button
              type="button"
              class="rounded-md border border-ingreso-200 bg-white px-2.5 py-1.5 font-medium text-ingreso-800"
              @click="add({ direction: 'ingreso' })"
            >Agregar deuda</button>
            <button
              type="button"
              class="rounded-md border border-egreso-200 bg-white px-2.5 py-1.5 font-medium text-egreso-800"
              @click="add({ direction: 'egreso' })"
            >Agregar pago</button>
          </div>
        </div>

        <p v-if="form.errors.items" class="rounded-md border border-egreso-200 bg-egreso-50 p-3 text-xs text-egreso-800">
          {{ form.errors.items }}
        </p>

        <FlowItem
          v-for="(item, i) in items"
          :key="i"
          :item="item"
          :index="i"
          :errors="form.errors"
          @remove="remove"
          @duplicate="duplicate"
          @toggle-unknown="toggleUnknown"
        />
      </section>

      <div class="flex items-center gap-4">
        <button
          type="submit" :disabled="form.processing"
          class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
        >{{ form.processing ? 'Resolviendo' : 'Resolver' }}</button>

        <p v-if="form.errors.equation" class="text-sm text-egreso-600">{{ form.errors.equation }}</p>
        <p v-for="(message, i) in otherErrors" :key="i" class="text-sm text-egreso-600">{{ message }}</p>
      </div>
    </form>

    <section v-if="result" class="rounded-lg border border-stone-200 bg-white p-5">
      <h2 class="text-sm font-medium">Resultado</h2>

      <template v-if="result.type === 'amount'">
        <p class="cifras mt-2 text-2xl font-semibold">X = {{ money(result.x) }}</p>

        <ul class="mt-4 divide-y divide-stone-100 text-sm">
          <li v-for="(unknown, i) in result.resolved" :key="i" class="flex justify-between py-2">
            <span class="text-stone-600">{{ unknown.label }}, periodo {{ unknown.period }}</span>
            <span class="cifras font-medium">{{ money(unknown.amount) }}</span>
          </li>
        </ul>

        <details class="mt-4 text-xs">
          <summary class="cursor-pointer text-stone-500">
            Verificación: el resultado es el mismo en cualquier fecha focal
          </summary>
          <ul class="mt-2 space-y-1 text-stone-600">
            <li v-for="(value, date) in result.invariance" :key="date" class="cifras">
              fecha focal {{ date }} &rarr; {{ money(value) }}
            </li>
          </ul>
        </details>
      </template>

      <template v-else-if="result.type === 'period'">
        <p class="cifras mt-2 text-2xl font-semibold">n = {{ result.n.toFixed(4) }}</p>
        <p class="mt-1 text-xs text-stone-500">
          Periodos, resuelto por {{ result.method }}<span v-if="result.iterations"> en {{ result.iterations }} iteraciones</span>.
        </p>
      </template>

      <template v-else-if="result.type === 'rate'">
        <p class="cifras mt-2 text-2xl font-semibold">i = {{ pct(result.i) }}</p>

        <div class="mt-4 rounded-md bg-stone-50 p-4 text-xs">
          <p class="text-stone-700">
            Por interpolación lineal, como en el texto: <span class="cifras">{{ pct(result.interpolated) }}</span>
          </p>
          <p class="mt-1 text-stone-500">
            Se aparta <span class="cifras">{{ pct(result.error, 6) }}</span> del valor exacto.
          </p>

          <table class="mt-3 w-full text-right">
            <thead class="text-stone-500">
              <tr><th class="text-left font-normal">Tasa de tanteo</th><th class="font-normal">Residuo</th></tr>
            </thead>
            <tbody class="cifras">
              <tr v-for="(bound, i) in result.bounds" :key="i">
                <td class="text-left">{{ pct(bound.x) }}</td>
                <td>{{ money(bound.value) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </section>
  </div>
</template>
