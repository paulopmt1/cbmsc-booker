import { useEffect, useId, useRef, type ReactNode } from 'react'
import { Icon } from './Icon.tsx'

interface ModalProps {
  title: string
  onClose: () => void
  children: ReactNode
  footer: ReactNode
  wide?: boolean
  /** Diálogo de confirmação: sem botão X, sobreposto a outros modais. */
  alert?: boolean
}

export function Modal({ title, onClose, children, footer, wide, alert }: ModalProps) {
  const titleId = useId()
  const ref = useRef<HTMLDivElement>(null)
  const onCloseRef = useRef(onClose)
  useEffect(() => {
    onCloseRef.current = onClose
  })

  useEffect(() => {
    const previous = document.activeElement as HTMLElement | null
    ref.current?.focus()
    const onKey = (e: KeyboardEvent) => {
      if (e.key !== 'Escape') return
      e.stopPropagation()
      onCloseRef.current()
    }
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('keydown', onKey)
      previous?.focus?.()
    }
  }, [])

  return (
    <div className={`overlay${alert ? ' top' : ''}`} onClick={onClose}>
      <div
        ref={ref}
        tabIndex={-1}
        className={`modal${wide ? ' w' : ''}`}
        role={alert ? 'alertdialog' : 'dialog'}
        aria-modal="true"
        aria-labelledby={titleId}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="m-h">
          <h2 id={titleId}>{title}</h2>
          {!alert && (
            <button type="button" className="xbtn" aria-label="Fechar" onClick={onClose}>
              <Icon name="close" />
            </button>
          )}
        </div>
        {children}
        <div className="m-f">{footer}</div>
      </div>
    </div>
  )
}
