import { ref } from 'vue'

export const blankItem = (overrides = {}) => ({
  kind: 'single',
  direction: 'egreso',
  period: 0,
  unknown: false,
  amount: null,
  coefficient: 1,
  constant: 0,
  count: 1,
  step: 1,
  variation: null,
  growth: null,
  gradient_direction: 'creciente',
  ...overrides,
})

export function useFlowItems(initial = []) {
  const items = ref(
    initial.length
      ? initial.map((item) => ({ ...blankItem(), ...item }))
      : [
          blankItem({ direction: 'ingreso', period: 0 }),
          blankItem({ direction: 'egreso', period: 1 }),
        ]
  )

  const add = (overrides) => items.value.push(blankItem(overrides))

  const remove = (index) => {
    if (items.value.length > 2) items.value.splice(index, 1)
  }

  const duplicate = (index) =>
    items.value.splice(index + 1, 0, { ...items.value[index] })

  // Al marcar incógnita el monto deja de aplicar, y al revés
  const toggleUnknown = (index) => {
    const item = items.value[index]
    item.unknown = !item.unknown

    if (item.unknown) {
      item.amount = null
      item.coefficient = item.coefficient || 1
    } else {
      item.coefficient = 1
      item.constant = 0
    }
  }

  return { items, add, remove, duplicate, toggleUnknown }
}
