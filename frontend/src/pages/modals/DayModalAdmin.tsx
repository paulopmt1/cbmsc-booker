import { useState } from 'react'
import { Icon } from '../../components/ui/Icon.tsx'
import { Modal } from '../../components/ui/Modal.tsx'
import { TODAY, longDate } from '../../lib/dates.ts'
import { initials } from '../../lib/people.ts'
import { useApp } from '../../state/context.ts'
import type { DateKey, Person } from '../../types.ts'

interface DayModalAdminProps {
  dayKey: DateKey
  onClose: () => void
}

export function DayModalAdmin({ dayKey, onClose }: DayModalAdminProps) {
  const { people, assign, interest, personById, assignShift } = useApp()
  const current = assign[dayKey] ? personById(assign[dayKey]) : undefined
  const past = dayKey < TODAY
  const [sel, setSel] = useState<number | null>(current?.id ?? null)
  const [confirming, setConfirming] = useState(false)

  const interested = interest[dayKey] ?? []
  const candidates = people.filter((p) => p.role === 'Bombeiro' && p.status === 'Ativo')
  const intList = candidates.filter((p) => interested.includes(p.id))
  const otherList = candidates.filter((p) => !interested.includes(p.id))
  const selected = sel ? personById(sel) : undefined

  const apply = () => {
    if (sel === null) return
    assignShift(dayKey, sel)
    onClose()
  }

  const row = (p: Person, tag?: boolean) => (
    <button key={p.id} type="button" className={`row-b${sel === p.id ? ' sel' : ''}`} aria-pressed={sel === p.id} onClick={() => setSel(p.id)}>
      <span className="radio" />
      <div className="av">{initials(p.name)}</div>
      <div className="lr-m">
        <div className="lr-t" style={{ fontSize: 14 }}>{p.name}</div>
        <div className="lr-s">{p.matricula}{current?.id === p.id ? ' · escalado atualmente' : ''}</div>
      </div>
      {tag && <span className="badge dash">Interesse</span>}
    </button>
  )

  return (
    <>
      <Modal
        title={longDate(dayKey)}
        wide
        onClose={onClose}
        footer={
          past ? (
            <button type="button" className="btn btn-s" onClick={onClose}>Fechar</button>
          ) : (
            <>
              <button type="button" className="btn btn-s" onClick={onClose}>Cancelar</button>
              <button
                type="button"
                className="btn btn-p"
                disabled={!(sel && sel !== current?.id)}
                onClick={() => (current ? setConfirming(true) : apply())}
              >
                {current ? 'Alterar bombeiro' : 'Definir bombeiro'}
              </button>
            </>
          )
        }
      >
        <div className="m-b">
          <div className="rows">
            <span className="grp-l">Escalado atualmente</span>
            {current ? (
              <div className="cur">
                <div className="av">{initials(current.name)}</div>
                <div className="lr-m"><div className="lr-t">{current.name}</div><div className="lr-s">{current.matricula}</div></div>
                <span className="badge solid">Escalado</span>
              </div>
            ) : (
              <div className="cur">
                <div className="lr-m"><div className="lr-t" style={{ color: 'var(--muted)', fontWeight: 400 }}>Nenhum bombeiro escalado</div></div>
                <span className="badge gray">Pendente</span>
              </div>
            )}
          </div>
          {past ? (
            <div className="callout">
              <Icon name="info" />
              <span>Esta data já passou. A escala está disponível somente para consulta.</span>
            </div>
          ) : (
            <>
              <div className="rows">
                <span className="grp-l">Manifestaram interesse ({intList.length})</span>
                {intList.length > 0 ? intList.map((p) => row(p, true)) : <p className="lede sm">Ninguém manifestou interesse nesta data.</p>}
              </div>
              <div className="rows">
                <span className="grp-l">Outros bombeiros ativos</span>
                {otherList.map((p) => row(p))}
              </div>
            </>
          )}
        </div>
      </Modal>

      {confirming && current && selected && (
        <Modal
          alert
          title="Alterar o bombeiro deste plantão?"
          onClose={() => setConfirming(false)}
          footer={
            <>
              <button type="button" className="btn btn-s" onClick={() => setConfirming(false)}>Voltar</button>
              <button type="button" className="btn btn-p" onClick={apply}>Sim, alterar plantão</button>
            </>
          }
        >
          <div className="m-b">
            <p className="lede sm">{longDate(dayKey)}</p>
            <div className="compare">
              <div className="cmp"><span className="grp-l">Atual</span><b>{current.name}</b></div>
              <div className="cmp new"><span className="grp-l">Novo</span><b>{selected.name}</b></div>
            </div>
            <p className="lede sm">A escala oficial será atualizada imediatamente.</p>
          </div>
        </Modal>
      )}
    </>
  )
}
