import type { DateKey } from '../../types.ts'
import type { CalendarCell } from './cells.ts'

const WEEKDAYS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']

interface CalendarGridProps {
  cells: CalendarCell[]
  onSelect: (key: DateKey) => void
}

export function CalendarGrid({ cells, onSelect }: CalendarGridProps) {
  return (
    <>
      <div className="wdays" aria-hidden="true">
        {WEEKDAYS.map((w) => <span key={w} className="wd">{w}</span>)}
      </div>
      <div className="grid7">
        {cells.map((c, i) => (
          <button
            key={c.key ?? `blank-${i}`}
            type="button"
            className={`cell ${c.cls}`}
            disabled={c.disabled}
            aria-label={c.aria || undefined}
            onClick={() => c.key && onSelect(c.key)}
          >
            <span className={`dn${c.today ? ' today' : ''}`}>{c.day}</span>
            <span className="lbl">{c.label}</span>
            <span className="dot" />
          </button>
        ))}
      </div>
    </>
  )
}

export interface LegendItem {
  swatch: string
  label: string
}

export function CalendarLegend({ items, extra }: { items: LegendItem[]; extra?: string }) {
  return (
    <div className="legend">
      {items.map((it) => (
        <span key={it.label} className="lg"><i className={`sw ${it.swatch}`.trim()} />{it.label}</span>
      ))}
      {extra && <span className="lg end">{extra}</span>}
    </div>
  )
}
