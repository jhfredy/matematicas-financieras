<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import RateInput from '@/Components/RateInput.vue'
import { useFormat } from '@/composables/useFormat'

const props = defineProps({
  options: { type: Object, required: true },
  input: { type: Object, default: null },
  schedule: { type: Object, default: null },
  summary: { type: Object, default: null },
})

const { money, pct } = useFormat()

const form = useForm({
  principal: props.input?.principal ?? null,
  periods: props.input?.periods ?? 12,
  payment_period: props.input?.payment_period ?? 'mensual',
  method: props.input?.method ?? 'cuota_fija',
  rate: props.input?.rate ?? null,
  rate_type: props.input?.rate_type ?? 'efectiva',
  rate_period: props.input?.rate_period ?? 'mensual',
  rate_reference: props.input?.rate_reference ?? 'anual',
  scheduled_extras: props.input?.scheduled_extras ?? [],
  extra_period: props.input?.extra_period ?? null,
  extra_amount: props.input?.extra_amount ?? null,
  behaviour: props.input?.behaviour ?? 'reliquidar',
  grace_periods: props.input?.grace_periods ?? null,
  grace_type: props.input?.grace_type ?? 'muerto',
  interest_timing: props.input?.interest_timing ?? 'vencido',
  variation: props.input?.variation ?? null,
  gradient_kind: props.input?.gradient_kind ?? 'aritmetica',
  gradient_direction: props.input?.gradient_direction ?? 'creciente',
  exchange_rate: props.input?.exchange_rate ?? null,
  exchange_changes: props.input?.exchange_changes ?? [{ type: 'devaluacion', value: null, periods: 1 }],
  foreign_method: props.input?.foreign_method ?? 'cuota_fija',
})

const rateFields = computed(() => ({
  rate: form.rate,
  rate_type: form.rate_type,
  rate_period: form.rate_period,
  rate_reference: form.rate_reference,
}))

const applyRate = (value) => Object.assign(form, value)

const is = (method) => form.method === method

// Errores de items anidados (exchange_changes.0.value, scheduled_extras.1.amount)
const itemError = (list, i) =>
  Object.entries(form.errors).find(([key]) => key.startsWith(`${list}.${i}.`))?.[1]

// Cualquier error que no tenga un campo visible donde mostrarse
const shownKeys = ['principal', 'periods', 'rate', 'rate_period', 'variation', 'calculation']
const otherErrors = computed(() =>
  Object.entries(form.errors)
    .filter(([key]) => !shownKeys.includes(key) && !key.startsWith('exchange_changes') && !key.startsWith('scheduled_extras'))
    .map(([, message]) => message)
)
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-6 px-6 py-8">
    <header>
      <h1 class="text-xl font-semibold">Tablas de amortización</h1>
      <p class="mt-1 text-sm text-stone-600">
        Seis formas de pagar una deuda. La tabla muestra periodo por periodo cuánto
        es interés y cuánto abona a capital.
      </p>
    </header>

    <form class="space-y-5" @submit.prevent="form.post(route('amortization.store'), { preserveScroll: true })">
      <section class="rounded-lg border border-stone-200 bg-white p-4">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
          <label class="block">
            <span class="text-xs text-stone-600">Capital</span>
            <input v-model.number="form.principal" type="number" step="any"
                   class="cifras mt-1 w-full rounded-md border-stone-300 text-sm" />
            <p v-if="form.errors.principal" class="mt-1 text-xs text-egreso-600">{{ form.errors.principal }}</p>
          </label>

          <label class="block">
            <span class="text-xs text-stone-600">Número de cuotas</span>
            <input v-model.number="form.periods" type="number" min="1"
                   class="cifras mt-1 w-full rounded-md border-stone-300 text-sm" />
            <p v-if="form.errors.periods" class="mt-1 text-xs text-egreso-600">{{ form.errors.periods }}</p>
          </label>

          <label class="block">
            <span class="text-xs text-stone-600">Las cuotas son</span>
            <select v-model="form.payment_period" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option v-for="period in options.periods" :key="period.value" :value="period.value">
                {{ period.label }}
              </option>
            </select>
          </label>

          <label class="block">
            <span class="text-xs text-stone-600">Método</span>
            <select v-model="form.method" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option v-for="method in options.methods" :key="method.value" :value="method.value">
                {{ method.label }}
              </option>
            </select>
          </label>
        </div>

        <div class="mt-4 border-t border-stone-100 pt-4">
          <RateInput :model-value="rateFields" :options="options" :errors="form.errors"
                     @update:model-value="applyRate" />
        </div>
      </section>

      <section v-if="is('extra_no_pactada')" class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="text-sm font-medium">Abono extra no pactado</h2>
        <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-3">
          <label class="block">
            <span class="text-xs text-stone-600">En el periodo</span>
            <input v-model.number="form.extra_period" type="number" min="1"
                   class="cifras mt-1 w-full rounded-md border-stone-300 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs text-stone-600">Monto</span>
            <input v-model.number="form.extra_amount" type="number" step="any"
                   class="cifras mt-1 w-full rounded-md border-stone-300 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs text-stone-600">Qué hacer con el abono</span>
            <select v-model="form.behaviour" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option value="reliquidar">Bajar la cuota, mantener el plazo</option>
              <option value="acortar">Mantener la cuota, acortar el plazo</option>
            </select>
          </label>
        </div>
      </section>

      <section v-if="is('gracia') || is('gradiente')" class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="text-sm font-medium">Periodo de gracia</h2>
        <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-3">
          <label class="block">
            <span class="text-xs text-stone-600">Cuántos periodos</span>
            <input v-model.number="form.grace_periods" type="number" min="0"
                   class="cifras mt-1 w-full rounded-md border-stone-300 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs text-stone-600">Tipo</span>
            <select v-model="form.grace_type" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option value="muerto">Muerto: no se paga nada y la deuda crece</option>
              <option value="reducida">Solo intereses: el capital queda quieto</option>
            </select>
          </label>
        </div>
      </section>

      <section v-if="is('abono_constante')" class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="text-sm font-medium">Cobro de intereses</h2>
        <label class="mt-3 block max-w-xs">
          <select v-model="form.interest_timing" class="mt-1 w-full rounded-md border-stone-300 text-sm">
            <option value="vencido">Vencido, al final del periodo</option>
            <option value="anticipado">Anticipado, al inicio</option>
          </select>
        </label>
      </section>

      <section v-if="is('gradiente')" class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="text-sm font-medium">Variación de las cuotas</h2>
        <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-3">
          <label class="block">
            <span class="text-xs text-stone-600">Tipo</span>
            <select v-model="form.gradient_kind" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option value="aritmetica">En pesos, cantidad fija</option>
              <option value="geometrica">En porcentaje</option>
            </select>
          </label>
          <label class="block">
            <span class="text-xs text-stone-600">
              {{ form.gradient_kind === 'aritmetica' ? 'Variación en pesos' : 'Variación (%)' }}
            </span>
            <input v-model.number="form.variation" type="number" step="any"
                   class="cifras mt-1 w-full rounded-md border-stone-300 text-sm" />
            <p v-if="form.errors.variation" class="mt-1 text-xs text-egreso-600">{{ form.errors.variation }}</p>
          </label>
          <label class="block">
            <span class="text-xs text-stone-600">Dirección</span>
            <select v-model="form.gradient_direction" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option value="creciente">Creciente</option>
              <option value="decreciente">Decreciente</option>
            </select>
          </label>
        </div>
      </section>

      <section v-if="is('moneda_extranjera')" class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="text-sm font-medium">Deuda en moneda extranjera</h2>
        <p class="mt-1 text-xs text-stone-500">
          El capital va en la moneda extranjera y las cuotas se pagan en pesos.
        </p>

        <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-3">
          <label class="block">
            <span class="text-xs text-stone-600">Tasa de cambio hoy</span>
            <input v-model.number="form.exchange_rate" type="number" step="any"
                   class="cifras mt-1 w-full rounded-md border-stone-300 text-sm" />
          </label>
          <label class="block">
            <span class="text-xs text-stone-600">Forma de pago</span>
            <select v-model="form.foreign_method" class="mt-1 w-full rounded-md border-stone-300 text-sm">
              <option value="cuota_fija">Cuota uniforme</option>
              <option value="abono_constante">Abono constante a capital</option>
            </select>
          </label>
        </div>

        <div class="mt-4 space-y-2">
          <p class="text-xs font-medium text-stone-600">Cómo cambia la moneda</p>

          <div v-for="(change, i) in form.exchange_changes" :key="i" class="flex items-end gap-3">
            <label class="block">
              <span class="text-xs text-stone-600">Movimiento</span>
              <select v-model="change.type" class="mt-1 rounded-md border-stone-300 text-sm">
                <option value="devaluacion">El peso se devalúa</option>
                <option value="revaluacion">La divisa se revalúa</option>
              </select>
            </label>
            <label class="block">
              <span class="text-xs text-stone-600">%</span>
              <input v-model.number="change.value" type="number" step="any"
                     class="cifras mt-1 w-24 rounded-md border-stone-300 text-sm" />
            </label>
            <label class="block">
              <span class="text-xs text-stone-600">Periodos</span>
              <input v-model.number="change.periods" type="number" min="1"
                     class="cifras mt-1 w-24 rounded-md border-stone-300 text-sm" />
            </label>
            <button v-if="form.exchange_changes.length > 1" type="button"
                    class="pb-2 text-xs text-egreso-600"
                    @click="form.exchange_changes.splice(i, 1)">Quitar</button>
            <p v-if="itemError('exchange_changes', i)" class="pb-2 text-xs text-egreso-600">
              {{ itemError('exchange_changes', i) }}
            </p>
          </div>

          <button type="button" class="text-xs font-medium text-stone-700 hover:text-stone-900"
                  @click="form.exchange_changes.push({ type: 'devaluacion', value: null, periods: 1 })">
            Agregar tramo
          </button>

          <p v-if="form.errors.exchange_changes" class="text-xs text-egreso-600">
            {{ form.errors.exchange_changes }}
          </p>
        </div>
      </section>

      <section v-if="is('cuota_fija') || is('moneda_extranjera')" class="rounded-lg border border-stone-200 bg-white p-4">
        <h2 class="text-sm font-medium">Cuotas extras pactadas</h2>
        <div class="mt-3 space-y-2">
          <div v-for="(extra, i) in form.scheduled_extras" :key="i" class="flex items-end gap-3">
            <label class="block">
              <span class="text-xs text-stone-600">Periodo</span>
              <input v-model.number="extra.period" type="number" min="1"
                     class="cifras mt-1 w-24 rounded-md border-stone-300 text-sm" />
            </label>
            <label class="block">
              <span class="text-xs text-stone-600">Monto</span>
              <input v-model.number="extra.amount" type="number" step="any"
                     class="cifras mt-1 w-40 rounded-md border-stone-300 text-sm" />
            </label>
            <button type="button" class="pb-2 text-xs text-egreso-600"
                    @click="form.scheduled_extras.splice(i, 1)">Quitar</button>
            <p v-if="itemError('scheduled_extras', i)" class="pb-2 text-xs text-egreso-600">
              {{ itemError('scheduled_extras', i) }}
            </p>
          </div>

          <button type="button" class="text-xs font-medium text-stone-700 hover:text-stone-900"
                  @click="form.scheduled_extras.push({ period: null, amount: null })">
            Agregar cuota extra
          </button>

          <p v-if="form.errors.scheduled_extras" class="text-xs text-egreso-600">
            {{ form.errors.scheduled_extras }}
          </p>
        </div>
      </section>

      <div class="flex items-center gap-4">
        <button type="submit" :disabled="form.processing"
                class="rounded-md bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
          {{ form.processing ? 'Calculando' : 'Generar tabla' }}
        </button>
        <p v-if="form.errors.calculation" class="text-sm text-egreso-600">{{ form.errors.calculation }}</p>
        <p v-for="(message, i) in otherErrors" :key="i" class="text-sm text-egreso-600">{{ message }}</p>
      </div>
    </form>

    <section v-if="schedule" class="space-y-4">
      <div class="rounded-lg border border-stone-200 bg-white p-5">
        <h2 class="text-sm font-medium">{{ schedule.metodo }}</h2>

        <dl class="mt-3 grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
          <div>
            <dt class="text-xs text-stone-500">Capital</dt>
            <dd class="cifras">{{ money(schedule.capital) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-stone-500">Tasa por periodo</dt>
            <dd class="cifras">{{ pct(schedule.tasa_periodica) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-stone-500">Efectiva anual</dt>
            <dd class="cifras">{{ pct(summary.effective_annual, 2) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-stone-500">Total intereses</dt>
            <dd class="cifras">{{ money(schedule.total_intereses) }}</dd>
          </div>
        </dl>
      </div>

      <p v-for="(warning, i) in summary.warnings" :key="i"
         class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
        {{ warning }}
      </p>

      <div class="overflow-x-auto rounded-lg border border-stone-200 bg-white">
        <table class="w-full text-right text-sm">
          <thead class="border-b border-stone-200 text-xs text-stone-500">
            <tr>
              <th class="px-3 py-2 text-left font-normal">n</th>
              <th v-if="schedule.tiene_saldo_ajustado" class="px-3 py-2 font-normal">Saldo sin ajustar</th>
              <th v-if="schedule.tiene_saldo_ajustado" class="px-3 py-2 font-normal">Saldo ajustado</th>
              <th v-else class="px-3 py-2 font-normal">Saldo</th>
              <th class="px-3 py-2 font-normal">Interés</th>
              <th class="px-3 py-2 font-normal">Cuota</th>
              <th class="px-3 py-2 font-normal">Abono a capital</th>
            </tr>
          </thead>
          <tbody class="cifras divide-y divide-stone-100">
            <tr v-for="row in schedule.filas" :key="row.periodo"
                :class="{
                  'bg-egreso-50': row.desamortiza || row.cuota_negativa,
                  'bg-stone-50': row.nota && !row.desamortiza && !row.cuota_negativa,
                }">
              <td class="px-3 py-2 text-left">
                {{ row.periodo }}
                <span v-if="row.nota" class="ml-1 font-sans text-[10px] text-stone-500">{{ row.nota }}</span>
              </td>
              <td class="px-3 py-2">{{ money(row.saldo) }}</td>
              <td v-if="schedule.tiene_saldo_ajustado" class="px-3 py-2">{{ money(row.saldo_ajustado) }}</td>
              <td class="px-3 py-2">{{ money(row.interes) }}</td>
              <td class="px-3 py-2" :class="{ 'text-egreso-700': row.cuota_negativa }">{{ money(row.cuota) }}</td>
              <td class="px-3 py-2" :class="{ 'text-egreso-700': row.desamortiza }">{{ money(row.amortizacion) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
