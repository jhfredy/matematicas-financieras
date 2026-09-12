<script setup>
import { computed } from 'vue'

const props = defineProps({
  item: { type: Object, required: true },
  index: { type: Number, required: true },
  errors: { type: Object, default: () => ({}) },
})

defineEmits(['remove', 'duplicate', 'toggle-unknown'])

const isSeries = computed(() => props.item.kind !== 'single')
const error = (field) => props.errors[`items.${props.index}.${field}`]
</script>

<template>
  <fieldset
    class="rounded-lg border p-4"
    :class="item.direction === 'ingreso'
      ? 'border-ingreso-200 bg-ingreso-50/50'
      : 'border-egreso-200 bg-egreso-50/50'"
  >
    <div class="flex items-center justify-between gap-2">
      <legend class="text-sm font-medium">
        Flujo {{ index + 1 }}
        <span v-if="item.unknown" class="ml-1 rounded bg-stone-800 px-1.5 py-0.5 text-xs text-white">
          incógnita
        </span>
      </legend>

      <div class="flex gap-3 text-xs">
        <button type="button" class="text-stone-500 hover:text-stone-900" @click="$emit('duplicate', index)">
          Duplicar
        </button>
        <button type="button" class="text-egreso-600 hover:text-egreso-800" @click="$emit('remove', index)">
          Quitar
        </button>
      </div>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-4">
      <label class="block">
        <span class="text-xs text-stone-600">Sentido</span>
        <select v-model="item.direction" class="mt-1 w-full rounded-md border-stone-300 text-sm">
          <option value="ingreso">Ingreso o deuda</option>
          <option value="egreso">Egreso o pago</option>
        </select>
      </label>

      <label class="block">
        <span class="text-xs text-stone-600">Tipo</span>
        <select v-model="item.kind" class="mt-1 w-full rounded-md border-stone-300 text-sm">
          <option value="single">Pago único</option>
          <option value="annuity">Serie uniforme</option>
          <option value="arithmetic">Gradiente aritmético</option>
          <option value="geometric">Gradiente geométrico</option>
        </select>
      </label>

      <label class="block">
        <span class="text-xs text-stone-600">{{ isSeries ? 'Primer periodo' : 'Periodo' }}</span>
        <input
          v-model.number="item.period" type="number" step="any" min="0"
          class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
        />
        <p v-if="error('period')" class="mt-1 text-xs text-egreso-600">{{ error('period') }}</p>
      </label>

      <label class="flex items-end gap-2 pb-2">
        <input
          :checked="item.unknown" type="checkbox"
          class="rounded border-stone-300 text-stone-800"
          @change="$emit('toggle-unknown', index)"
        />
        <span class="text-xs text-stone-600">Es incógnita</span>
      </label>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-3">
      <label v-if="!item.unknown" class="block">
        <span class="text-xs text-stone-600">{{ isSeries ? 'Primera cuota' : 'Monto' }}</span>
        <input
          v-model.number="item.amount" type="number" step="any"
          class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
        />
        <p v-if="error('amount')" class="mt-1 text-xs text-egreso-600">{{ error('amount') }}</p>
      </label>

      <template v-else>
        <label class="block">
          <span class="text-xs text-stone-600">Multiplica a X por</span>
          <input
            v-model.number="item.coefficient" type="number" step="any"
            class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
          />
          <p class="mt-1 text-xs text-stone-500">3 si el enunciado dice "el 300% de X"</p>
        </label>

        <label class="block">
          <span class="text-xs text-stone-600">Suma o resta</span>
          <input
            v-model.number="item.constant" type="number" step="any"
            class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
          />
          <p class="mt-1 text-xs text-stone-500">200000 si dice "X más 200.000"</p>
        </label>
      </template>
    </div>

    <div v-if="isSeries" class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-4">
      <label class="block">
        <span class="text-xs text-stone-600">Cantidad de flujos</span>
        <input
          v-model.number="item.count" type="number" min="1"
          class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
        />
        <p v-if="error('count')" class="mt-1 text-xs text-egreso-600">{{ error('count') }}</p>
      </label>

      <label class="block">
        <span class="text-xs text-stone-600">Separación</span>
        <input
          v-model.number="item.step" type="number" step="any" min="0.01"
          class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
        />
        <p class="mt-1 text-xs text-stone-500">3 para cuotas trimestrales en eje mensual</p>
      </label>

      <label v-if="item.kind === 'arithmetic'" class="block">
        <span class="text-xs text-stone-600">Variación en pesos</span>
        <input
          v-model.number="item.variation" type="number" step="any"
          class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
        />
      </label>

      <label v-if="item.kind === 'geometric'" class="block">
        <span class="text-xs text-stone-600">Crecimiento (%)</span>
        <input
          v-model.number="item.growth" type="number" step="any"
          class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
        />
      </label>

      <label v-if="item.kind !== 'annuity'" class="block">
        <span class="text-xs text-stone-600">Dirección</span>
        <select v-model="item.gradient_direction" class="mt-1 w-full rounded-md border-stone-300 text-sm">
          <option value="creciente">Creciente</option>
          <option value="decreciente">Decreciente</option>
        </select>
      </label>
    </div>
  </fieldset>
</template>
