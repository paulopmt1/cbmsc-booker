import type { CSSProperties, ReactNode } from 'react'

export type BadgeTone = '' | 'off' | 'red' | 'solid' | 'dash' | 'gray'

interface BadgeProps {
  tone?: BadgeTone
  /** Mostra o ponto de status (usado em Ativo/Inativo). */
  dot?: boolean
  style?: CSSProperties
  children: ReactNode
}

export function Badge({ tone = '', dot, style, children }: BadgeProps) {
  return (
    <span className={`badge ${tone}`.trim()} style={style}>
      {dot && <i />}
      {children}
    </span>
  )
}
