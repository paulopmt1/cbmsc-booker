import { useApp } from '../../state/context.ts'
import { Icon } from './Icon.tsx'

export function ToastHost() {
  const { toast, closeToast } = useApp()
  if (!toast) return null
  return (
    <div className={`toast${toast.kind === 'bad' ? ' bad' : ''}`} role="status">
      <span style={{ flex: '0 0 20px', marginTop: 1 }}><Icon name="check" strokeWidth={2.2} /></span>
      <span style={{ flex: 1 }}>{toast.text}</span>
      <button type="button" aria-label="Fechar aviso" onClick={closeToast}>
        <Icon name="close" size={16} />
      </button>
    </div>
  )
}
