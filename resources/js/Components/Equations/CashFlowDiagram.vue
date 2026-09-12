<script setup>
import { computed } from 'vue'
import { useFormat } from '@/composables/useFormat'

const props = defineProps({
  flows: { type: Array, default: () => [] },
  focalDate: { type: Number, default: 0 },
})

const { plain } = useFormat()

const W = 760
const H = 300
const AXIS_Y = H / 2
const PAD = 52
const PLACEHOLDER = 56

const maxPeriod = computed(() => Math.max(1, ...props.flows.map((f) => f.period)))

// Los flujos desconocidos no tienen monto hasta resolver, así que no cuentan
const maxAmount = computed(() => {
  const known = props.flows.filter((f) => f.amount !== null).map((f) => Math.abs(f.amount))

  return Math.max(1, ...known)
})

const x = (period) => PAD + (period / maxPeriod.value) * (W - 2 * PAD)

// Raíz cuadrada: sin esto un flujo cien veces mayor aplasta los demás
const height = (amount) => {
  if (amount === null) return PLACEHOLDER

  return Math.sqrt(Math.abs(amount) / maxAmount.value) * (AXIS_Y - PAD)
}

const tipY = (flow) =>
  flow.direction === 'ingreso' ? AXIS_Y - height(flow.amount) : AXIS_Y + height(flow.amount)

const labelY = (flow) =>
  flow.direction === 'ingreso' ? tipY(flow) - 8 : tipY(flow) + 16

const ticks = computed(() => {
  const step = Math.max(1, Math.ceil(maxPeriod.value / 12))
  const out = []

  for (let p = 0; p <= maxPeriod.value; p += step) out.push(p)

  return out
})

const caption = (flow) => (flow.amount === null ? flow.label ?? 'X' : plain(flow.amount))
</script>

<template>
  <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" role="img" aria-label="Diagrama económico">
    <line :x1="PAD" :y1="AXIS_Y" :x2="W - PAD" :y2="AXIS_Y" class="stroke-stone-400" stroke-width="1.5" />

    <g v-for="tick in ticks" :key="`t-${tick}`">
      <line :x1="x(tick)" :y1="AXIS_Y - 4" :x2="x(tick)" :y2="AXIS_Y + 4" class="stroke-stone-400" />
      <text :x="x(tick)" :y="AXIS_Y + 18" text-anchor="middle" class="fill-stone-500 text-[10px]">
        {{ tick }}
      </text>
    </g>

    <line
      :x1="x(focalDate)" y1="18" :x2="x(focalDate)" :y2="H - 18"
      class="stroke-stone-800" stroke-width="1.5" stroke-dasharray="5 4"
    />
    <text :x="x(focalDate)" y="13" text-anchor="middle" class="fill-stone-800 text-[10px] font-medium">
      fecha focal
    </text>

    <g v-for="(flow, i) in flows" :key="i">
      <line
        :x1="x(flow.period)" :y1="AXIS_Y" :x2="x(flow.period)" :y2="tipY(flow)"
        :class="flow.amount === null
          ? 'stroke-stone-500'
          : (flow.direction === 'ingreso' ? 'stroke-ingreso-600' : 'stroke-egreso-600')"
        :stroke-dasharray="flow.amount === null ? '4 3' : undefined"
        stroke-width="2"
        :marker-end="flow.direction === 'ingreso' ? 'url(#arrow-up)' : 'url(#arrow-down)'"
      />
      <text
        :x="x(flow.period)" :y="labelY(flow)" text-anchor="middle"
        class="cifras text-[9px]"
        :class="flow.amount === null ? 'fill-stone-700 font-semibold' : 'fill-stone-700'"
      >{{ caption(flow) }}</text>
    </g>

    <defs>
      <marker id="arrow-up" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
        <path d="M 0 10 L 5 0 L 10 10 z" class="fill-ingreso-600" />
      </marker>
      <marker id="arrow-down" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="5" markerHeight="5" orient="auto">
        <path d="M 0 0 L 5 10 L 10 0 z" class="fill-egreso-600" />
      </marker>
    </defs>
  </svg>
</template>
