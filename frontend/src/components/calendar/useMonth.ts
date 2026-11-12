import { useState } from 'react'
import { MAX_YEAR, MIN_YEAR, TODAY_MONTH, TODAY_YEAR } from '../../lib/dates.ts'

/** Mês/ano exibidos no calendário, limitados a MIN_YEAR–MAX_YEAR. */
export function useMonth(initial?: { year: number; month: number }) {
  const [ym, setYm] = useState(initial ?? { year: TODAY_YEAR, month: TODAY_MONTH })

  const shift = (delta: number) =>
    setYm(({ year, month }) => {
      let y = year
      let m = month + delta
      if (m < 0) { m = 11; y-- }
      if (m > 11) { m = 0; y++ }
      return y < MIN_YEAR || y > MAX_YEAR ? { year, month } : { year: y, month: m }
    })

  return {
    ...ym,
    prev: () => shift(-1),
    next: () => shift(1),
    today: () => setYm({ year: TODAY_YEAR, month: TODAY_MONTH }),
    setMonth: (month: number) => setYm((s) => ({ ...s, month })),
    setYear: (year: number) => setYm((s) => ({ ...s, year })),
    set: (year: number, month: number) => setYm({ year, month }),
  }
}
