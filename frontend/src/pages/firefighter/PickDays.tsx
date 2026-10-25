import { useState } from 'react'
import { buildPickCells } from '../../components/calendar/cells.ts'
import { CalendarGrid, CalendarLegend } from '../../components/calendar/CalendarGrid.tsx'
import { useMonth } from '../../components/calendar/useMonth.ts'
import { Icon } from '../../components/ui/Icon.tsx'
import { BOMBEIRO_ID } from '../../data/mock.ts'
import { MONTHS, daysInMonth, keyOf, longDate, monthTitle } from '../../lib/dates.ts'
import { useApp } from '../../state/context.ts'
import type { DateKey } from '../../types.ts'
import { DayModalFirefighter } from '../modals/DayModalFirefighter.tsx'

export function PickDays() {
  const { assign, interest, personById } = useApp()
  const cal = useMonth()
  const [day, setDay] = useState<DateKey | null>(null)

  const cells = buildPickCells({ year: cal.year, month: cal.month, assign, interest, personById })

  const mine: { key: DateKey; official: boolean }[] = []
  for (let d = 1; d <= daysInMonth(cal.year, cal.month); d++) {
    const key = keyOf(cal.year, cal.month, d)
    if (assign[key] === BOMBEIRO_ID) mine.push({ key, official: true })
    else if (!assign[key] && (interest[key] ?? []).includes(BOMBEIRO_ID)) mine.push({ key, official: false })
  }
  const official = mine.filter((m) => m.official).length

  return (
    <>
      <div className="note">
        <Icon name="info" />
        <span><b style={{ fontWeight: 600 }}>Manifestar interesse não confirma o plantão.</b> Você só está escalado quando a data aparece como “Escalado” — a escala oficial é definida pela administração.</span>
      </div>
      <div className="split">
        <section className="card cal" aria-label="Calendário de plantões">
          <div className="cal-head">
            <h2 className="cal-title">{monthTitle(cal.year, cal.month)}</h2>
            <div className="cal-ctl">
              <button type="button" className="ibtn" aria-label="Mês anterior" onClick={cal.prev}><Icon name="chevLeft" /></button>
              <button type="button" className="btn btn-s" onClick={cal.today}>Hoje</button>
              <button type="button" className="ibtn" aria-label="Próximo mês" onClick={cal.next}><Icon name="chevRight" /></button>
            </div>
          </div>
          <CalendarGrid cells={cells} onSelect={setDay} />
          <CalendarLegend
            items={[
              { swatch: '', label: 'Disponível' },
              { swatch: 'sw-mine', label: 'Meu interesse' },
              { swatch: 'sw-off', label: 'Escalado (você)' },
              { swatch: 'sw-taken', label: 'Escala definida' },
              { swatch: 'sw-today', label: 'Hoje' },
            ]}
          />
        </section>

        <aside className="card aside" aria-label="Resumo do mês">
          <h2>Suas datas em {MONTHS[cal.month]}</h2>
          <div className="sum-grid">
            <div className="sum-box"><div className="kpi-v red">{official}</div><div className="kpi-h">Escalado</div></div>
            <div className="sum-box"><div className="kpi-v">{mine.length - official}</div><div className="kpi-h">Interesse</div></div>
          </div>
          <div className="rows">
            {mine.length > 0 ? (
              mine.map((m) => (
                <button key={m.key} type="button" className="row-b" onClick={() => setDay(m.key)}>
                  <div className="lr-m"><div className="lr-t" style={{ fontSize: 14 }}>{longDate(m.key).replace(/, /, ' · ').replace(/ de \d{4}$/, '')}</div></div>
                  <span className={`badge ${m.official ? 'solid' : 'dash'}`}>{m.official ? 'Escalado' : 'Interesse'}</span>
                </button>
              ))
            ) : (
              <p className="lede sm">Você ainda não selecionou datas neste mês. Clique em um dia disponível do calendário.</p>
            )}
          </div>
        </aside>
      </div>

      {day && <DayModalFirefighter key={day} dayKey={day} onClose={() => setDay(null)} />}
    </>
  )
}
