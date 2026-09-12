<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
  options: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  showRate: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue'])

const update = (key, value) => emit('update:modelValue', { ...props.modelValue, [key]: value })

// El periodo de referencia solo aplica a las tasas nominales
const needsReference = computed(() => {
  const type = props.options.rateTypes.find((t) => t.value === props.modelValue.rate_type)

  return type?.needsReference ?? false
})
</script>

<template>
  <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
    <label v-if="showRate" class="block">
      <span class="text-xs text-stone-600">Tasa (%)</span>
      <input
        :value="modelValue.rate"
        type="number"
        step="any"
        class="cifras mt-1 w-full rounded-md border-stone-300 text-sm"
        @input="update('rate', $event.target.valueAsNumber)"
      />
      <p v-if="errors.rate" class="mt-1 text-xs text-egreso-600">{{ errors.rate }}</p>
    </label>

    <label class="block">
      <span class="text-xs text-stone-600">Modalidad</span>
      <select
        :value="modelValue.rate_type"
        class="mt-1 w-full rounded-md border-stone-300 text-sm"
        @change="update('rate_type', $event.target.value)"
      >
        <option v-for="type in options.rateTypes" :key="type.value" :value="type.value">
          {{ type.label }}
        </option>
      </select>
    </label>

    <label class="block">
      <span class="text-xs text-stone-600">Capitaliza cada</span>
      <select
        :value="modelValue.rate_period"
        class="mt-1 w-full rounded-md border-stone-300 text-sm"
        @change="update('rate_period', $event.target.value)"
      >
        <option v-for="period in options.periods" :key="period.value" :value="period.value">
          {{ period.label }}
        </option>
      </select>
      <p v-if="errors.rate_period" class="mt-1 text-xs text-egreso-600">{{ errors.rate_period }}</p>
    </label>

    <label v-if="needsReference" class="block">
      <span class="text-xs text-stone-600">Referida a</span>
      <select
        :value="modelValue.rate_reference"
        class="mt-1 w-full rounded-md border-stone-300 text-sm"
        @change="update('rate_reference', $event.target.value)"
      >
        <option v-for="period in options.periods" :key="period.value" :value="period.value">
          {{ period.label }}
        </option>
      </select>
    </label>
  </div>
</template>
