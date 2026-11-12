import type { DateKey } from '../types.ts'

/** "Hoje" fixo para manter os dados de exemplo coerentes. */
export const TODAY: DateKey = '2026-10-16'
export const TODAY_YEAR = 2026
export const TODAY_MONTH = 9
export const MIN_YEAR = 2026
export const MAX_YEAR = 2027

export const MONTHS = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro']
export const WDAYS = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado']
export const WSHORT = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb']

const pad = (n: number) => String(n).padStart(2, '0')

export const keyOf = (y: number, m: number, d: number): DateKey => `${y}-${pad(m + 1)}-${pad(d)}`
export const daysInMonth = (y: number, m: number) => new Date(y, m + 1, 0).getDate()
export const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1)

export function parseKey(k: DateKey) {
  const [y, m, d] = k.split('-')
  return { y: Number(y), m: Number(m) - 1, d: Number(d) }
}

export function weekdayOf(k: DateKey) {
  const { y, m, d } = parseKey(k)
  return new Date(y, m, d).getDay()
}

export function longDate(k: DateKey) {
  const { y, m, d } = parseKey(k)
  return `${cap(WDAYS[weekdayOf(k)])}, ${d} de ${MONTHS[m]} de ${y}`
}

export function shortDate(k: DateKey) {
  const { m, d } = parseKey(k)
  return `${pad(d)}/${pad(m + 1)}`
}

export function monthTitle(y: number, m: number) {
  return `${cap(MONTHS[m])} de ${y}`
}
