import { useNavigate } from 'react-router-dom'
import { Badge, type BadgeTone } from '../../components/ui/Badge.tsx'
import { MONTHS, TODAY, TODAY_MONTH, TODAY_YEAR, WSHORT, daysInMonth, keyOf, parseKey, weekdayOf } from '../../lib/dates.ts'
import type { ScheduleLocationState } from '../../lib/routes.ts'
import { useApp } from '../../state/context.ts'
import type { DateKey } from '../../types.ts'

const plural = (n: number, one: string, many: string) => `${n} ${n > 1 ? many : one}`

export function Dashboard() {
  const { people, assign, interest, personById } = useApp()
  const navigate = useNavigate()
  const openDay = (key: DateKey) => navigate('/admin/escalas', { state: { openDay: key } satisfies ScheduleLocationState })

  const total = daysInMonth(TODAY_YEAR, TODAY_MONTH)
  let defined = 0
  let intTotal = 0
  let intDates = 0
  const pending: { key: DateKey; count: number }[] = []
  for (let d = 1; d <= total; d++) {
    const key = keyOf(TODAY_YEAR, TODAY_MONTH, d)
    if (assign[key]) { defined++; continue }
    const count = (interest[key] ?? []).length
    if (count) {
      intTotal += count
      intDates++
      if (key >= TODAY) pending.push({ key, count })
    }
  }

  const kpis = [
    { label: 'Bombeiros cadastrados', value: people.length, hint: `${people.filter((p) => p.status === 'Ativo').length} ativos`, cls: '' },
    { label: 'Plantões definidos no mês', value: defined, hint: `de ${total} datas`, cls: '' },
    { label: 'Datas a definir', value: total - defined, hint: 'sem bombeiro escalado', cls: 'red' },
    { label: 'Manifestações de interesse', value: intTotal, hint: `em ${intDates} datas sem escala`, cls: '' },
  ]

  const upcoming = Array.from({ length: 7 }, (_, i) => {
    const dt = new Date(TODAY_YEAR, TODAY_MONTH, 16 + i)
    const key = keyOf(dt.getFullYear(), dt.getMonth(), dt.getDate())
    const asg = assign[key]
    const ints = (interest[key] ?? []).length
    const tone: BadgeTone = asg ? 'solid' : ints ? 'dash' : 'gray'
    return {
      key,
      day: dt.getDate(),
      wd: WSHORT[dt.getDay()],
      who: asg ? personById(asg)?.name ?? '' : 'Sem bombeiro escalado',
      sub: key === TODAY ? 'Hoje' : asg ? 'Escala definida' : ints ? plural(ints, 'bombeiro interessado', 'bombeiros interessados') : 'Aguardando definição',
      badge: asg ? 'Definido' : ints ? 'Interesse' : 'Pendente',
      tone,
    }
  })

  const month = `${MONTHS[TODAY_MONTH]} de ${TODAY_YEAR}`

  return (
    <>
      <p className="lede">Resumo da escala de {month}.</p>
      <div className="kpis">
        {kpis.map((k) => (
          <div key={k.label} className="card kpi">
            <span className="kpi-l">{k.label}</span>
            <span className={`kpi-v ${k.cls}`.trim()}>{k.value}</span>
            <span className="kpi-h">{k.hint}</span>
          </div>
        ))}
      </div>
      <div className="twocol">
        <section className="card" aria-labelledby="h-prox">
          <div className="sec-h">
            <h2 id="h-prox">Próximos plantões</h2>
            <button type="button" className="btn btn-s btn-sm" onClick={() => navigate('/admin/escalas')}>Abrir escala</button>
          </div>
          <div>
            {upcoming.map((u) => (
              <button key={u.key} type="button" className="lrow" onClick={() => openDay(u.key)}>
                <div className="dbox"><b>{u.day}</b><span>{u.wd}</span></div>
                <div className="lr-m"><div className="lr-t">{u.who}</div><div className="lr-s">{u.sub}</div></div>
                <Badge tone={u.tone}>{u.badge}</Badge>
              </button>
            ))}
          </div>
        </section>
        <section className="card" aria-labelledby="h-pend">
          <div className="sec-h"><h2 id="h-pend">Aguardando definição</h2></div>
          <div className="prog-box">
            <div className="prog-row"><span>Datas definidas em {month}</span><b>{defined} de {total}</b></div>
            <progress className="prog" value={defined} max={total} aria-label="Datas definidas no mês" />
          </div>
          <div>
            {pending.length > 0 ? (
              pending.slice(0, 5).map((p) => {
                const { d } = parseKey(p.key)
                return (
                  <button key={p.key} type="button" className="lrow" onClick={() => openDay(p.key)}>
                    <div className="dbox"><b>{d}</b><span>{WSHORT[weekdayOf(p.key)]}</span></div>
                    <div className="lr-m">
                      <div className="lr-t">{plural(p.count, 'bombeiro interessado', 'bombeiros interessados')}</div>
                      <div className="lr-s">Sem bombeiro escalado</div>
                    </div>
                    <Badge tone="dash">Definir</Badge>
                  </button>
                )
              })
            ) : (
              <div className="empty-s">Nenhuma data com interesse pendente neste mês.</div>
            )}
          </div>
        </section>
      </div>
    </>
  )
}
