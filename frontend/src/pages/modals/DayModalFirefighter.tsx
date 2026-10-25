import { Icon } from '../../components/ui/Icon.tsx'
import { Modal } from '../../components/ui/Modal.tsx'
import { BOMBEIRO_ID } from '../../data/mock.ts'
import { longDate } from '../../lib/dates.ts'
import { useApp } from '../../state/context.ts'
import type { DateKey } from '../../types.ts'

interface DayModalFirefighterProps {
  dayKey: DateKey
  onClose: () => void
}

export function DayModalFirefighter({ dayKey, onClose }: DayModalFirefighterProps) {
  const { assign, interest, addInterest, removeInterest } = useApp()
  const asg = assign[dayKey]
  const ints = interest[dayKey] ?? []
  const isMine = ints.includes(BOMBEIRO_ID)
  const official = asg === BOMBEIRO_ID
  const taken = !!asg && !official
  const others = ints.filter((i) => i !== BOMBEIRO_ID).length

  const badge = official
    ? { tone: 'solid', text: 'Escalado oficialmente' }
    : taken
      ? { tone: 'gray', text: 'Escala já definida' }
      : isMine
        ? { tone: 'dash', text: 'Interesse registrado' }
        : { tone: '', text: 'Disponível' }

  const othersText = others
    ? `${others} ${others > 1 ? 'outros bombeiros já manifestaram' : 'outro bombeiro já manifestou'} interesse nesta data.`
    : 'Nenhum outro bombeiro manifestou interesse nesta data até agora.'

  let footer
  if (asg) {
    footer = <button type="button" className="btn btn-s" onClick={onClose}>Fechar</button>
  } else if (isMine) {
    footer = (
      <>
        <button type="button" className="btn btn-s" onClick={onClose}>Manter</button>
        <button type="button" className="btn btn-d" onClick={() => { removeInterest(dayKey, BOMBEIRO_ID); onClose() }}>Cancelar interesse</button>
      </>
    )
  } else {
    footer = (
      <>
        <button type="button" className="btn btn-s" onClick={onClose}>Cancelar</button>
        <button type="button" className="btn btn-p" onClick={() => { addInterest(dayKey, BOMBEIRO_ID); onClose() }}>Manifestar interesse</button>
      </>
    )
  }

  return (
    <Modal title={longDate(dayKey)} onClose={onClose} footer={footer}>
      <div className="m-b">
        <div><span className={`badge ${badge.tone}`.trim()}>{badge.text}</span></div>
        {official && (
          <div className="callout">
            <Icon name="check" />
            <span>Você está <b style={{ fontWeight: 600 }}>oficialmente escalado</b> para este plantão. Para trocar ou cancelar, fale com a administração.</span>
          </div>
        )}
        {taken && (
          <div className="callout">
            <Icon name="info" />
            <span>A escala oficial desta data já foi definida. Não é possível manifestar interesse.</span>
          </div>
        )}
        {!asg && (
          <>
            <div className="compare">
              <div className="cmp"><span className="badge dash" style={{ alignSelf: 'flex-start' }}>Interesse</span>Sua preferência por esta data. Pode ser cancelada a qualquer momento.</div>
              <div className="cmp"><span className="badge solid" style={{ alignSelf: 'flex-start' }}>Escalado</span>Confirmação oficial feita pela administração.</div>
            </div>
            <p className="lede sm">{othersText}</p>
          </>
        )}
      </div>
    </Modal>
  )
}
