import { useState } from 'react'
import { useLocation } from 'react-router-dom'
import { buildAdminCells } from '../../components/calendar/cells.ts'
import { CalendarGrid, CalendarLegend } from '../../components/calendar/CalendarGrid.tsx'
import { useMonth } from '../../components/calendar/useMonth.ts'
import { Icon } from '../../components/ui/Icon.tsx'
import { MAX_YEAR, MIN_YEAR, MONTHS, cap, daysInMonth, keyOf, monthTitle, parseKey } from '../../lib/dates.ts'
import type { ScheduleLocationState } from '../../lib/routes.ts'
import { useApp } from '../../state/context.ts'
import type { DateKey } from '../../types.ts'
import { DayModalAdmin } from '../modals/DayModalAdmin.tsx'

const YEARS = Array.from({ length: MAX_YEAR - MIN_YEAR + 1 }, (_, i) => MIN_YEAR + i)

export function Schedule() {
  const { assign, interest, personById } = useApp()
  const { state } = useLocation() as { state: ScheduleLocationState | null }
  const initialDay = state?.openDay ?? null
  const start = initialDay ? parseKey(initialDay) : null
  const cal = useMonth(start ? { year: start.y, month: start.m } : undefined)
  const [day, setDay] = useState<DateKey | null>(initialDay)

  const cells = buildAdminCells({ year: cal.year, month: cal.month, assign, interest, personById })

  let defined = 0
  let withInterest = 0
  let pending = 0
  for (let d = 1; d <= daysInMonth(cal.year, cal.month); d++) {
    const k = keyOf(cal.year, cal.month, d)
    if (assign[k]) defined++
    else if ((interest[k] ?? []).length) withInterest++
    else pending++
  }

  return (
    <>
      <p className="lede">Consulte a escala oficial e defina quem presta serviço em cada data. Clique em um dia para ver os detalhes.</p>
      <section className="card cal" aria-label="Calendário da escala oficial">
        <div className="cal-head">
          <h2 className="cal-title">{monthTitle(cal.year, cal.month)}</h2>
          <div className="cal-ctl">
            <button type="button" className="ibtn" aria-label="Mês anterior" onClick={cal.prev}><Icon name="chevLeft" /></button>
            <select className="sel" aria-label="Mês" value={cal.month} onChange={(e) => cal.setMonth(Number(e.target.value))}>
              {MONTHS.map((m, i) => <option key={m} value={i}>{cap(m)}</option>)}
            </select>
            <select className="sel" style={{ minWidth: 96 }} aria-label="Ano" value={cal.year} onChange={(e) => cal.setYear(Number(e.target.value))}>
              {YEARS.map((y) => <option key={y} value={y}>{y}</option>)}
            </select>
            <button type="button" className="ibtn" aria-label="Próximo mês" onClick={cal.next}><Icon name="chevRight" /></button>
            <button type="button" className="btn btn-s" onClick={cal.today}>Hoje</button>
          </div>
        </div>
        <CalendarGrid cells={cells} onSelect={setDay} />
        <CalendarLegend
          items={[
            { swatch: 'sw-off', label: 'Escala definida' },
            { swatch: 'sw-aint', label: 'Com interesse, sem definição' },
            { swatch: '', label: 'Pendente' },
            { swatch: 'sw-today', label: 'Hoje' },
          ]}
          extra={`${defined} definidas · ${withInterest} com interesse · ${pending} pendentes`}
        />
      </section>

      {day && <DayModalAdmin key={day} dayKey={day} onClose={() => setDay(null)} />}
    </>
  )
}
