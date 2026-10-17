import { BOMBEIRO_ID } from '../../data/mock.ts'
import { MONTHS, TODAY, daysInMonth, keyOf } from '../../lib/dates.ts'
import { shortName } from '../../lib/people.ts'
import type { Assignments, DateKey, Interests, Person } from '../../types.ts'

export interface CalendarCell {
  /** null = célula vazia de preenchimento da grade. */
  key: DateKey | null
  day: number | null
  cls: string
  label: string
  aria: string
  disabled: boolean
  today: boolean
}

interface BuildArgs {
  year: number
  month: number
  assign: Assignments
  interest: Interests
  personById: (id: number) => Person | undefined
}

const blank = (): CalendarCell => ({ key: null, day: null, cls: 'empty', label: '', aria: '', disabled: true, today: false })

function build(args: BuildArgs, describe: (key: DateKey, past: boolean) => Pick<CalendarCell, 'cls' | 'label' | 'disabled'> & { status: string }) {
  const { year, month } = args
  const cells: CalendarCell[] = []
  const first = new Date(year, month, 1).getDay()
  for (let i = 0; i < first; i++) cells.push(blank())
  for (let d = 1; d <= daysInMonth(year, month); d++) {
    const key = keyOf(year, month, d)
    const past = key < TODAY
    const { status, ...rest } = describe(key, past)
    cells.push({ key, day: d, today: key === TODAY, aria: `${d} de ${MONTHS[month]}: ${status}`, ...rest })
  }
  while (cells.length % 7 !== 0) cells.push(blank())
  return cells
}

/** Calendário do administrador: mostra quem está escalado ou quantos têm interesse. */
export function buildAdminCells(args: BuildArgs): CalendarCell[] {
  return build(args, (key, past) => {
    const asg = args.assign[key]
    const ints = args.interest[key] ?? []
    const dim = past ? ' c-past' : ''
    if (asg) {
      const p = args.personById(asg)
      return { cls: `a-set${dim}`, label: shortName(p?.name ?? ''), disabled: false, status: `escalado: ${p?.name ?? ''}` }
    }
    if (ints.length) {
      return {
        cls: `a-int${dim}`,
        label: `${ints.length} ${ints.length > 1 ? 'interesses' : 'interesse'}`,
        disabled: false,
        status: `${ints.length} manifestação(ões) de interesse, sem escala definida`,
      }
    }
    return { cls: `a-none${dim}`, label: 'Pendente', disabled: false, status: 'pendente de definição' }
  })
}

/** Calendário do bombeiro logado: disponível, meu interesse, escalado ou definido por outro. */
export function buildPickCells(args: BuildArgs): CalendarCell[] {
  return build(args, (key, past) => {
    const asg = args.assign[key]
    const mine = (args.interest[key] ?? []).includes(BOMBEIRO_ID)
    let cls: string
    let label = ''
    let status: string
    if (asg === BOMBEIRO_ID) { cls = 'c-official'; label = 'Escalado'; status = 'você está escalado' }
    else if (asg) { cls = 'c-taken'; label = 'Definida'; status = 'escala já definida' }
    else if (mine) { cls = 'c-mine'; label = 'Meu interesse'; status = 'interesse registrado' }
    else { cls = 'c-free'; status = 'disponível' }
    if (past) cls += ' c-past'
    return { cls, label, disabled: past, status }
  })
}
