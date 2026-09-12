const moneyFormatter = new Intl.NumberFormat('es-CO', {
  style: 'currency',
  currency: 'COP',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

const plainFormatter = new Intl.NumberFormat('es-CO', {
  maximumFractionDigits: 0,
})

export function useFormat() {
  const money = (value) => (value === null || value === undefined ? '—' : moneyFormatter.format(value))

  const plain = (value) => (value === null || value === undefined ? '—' : plainFormatter.format(value))

  const pct = (value, decimals = 4) =>
    value === null || value === undefined ? '—' : `${(value * 100).toFixed(decimals)} %`

  return { money, plain, pct }
}
