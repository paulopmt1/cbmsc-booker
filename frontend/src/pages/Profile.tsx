import { Badge } from '../components/ui/Badge.tsx'
import { initials } from '../lib/people.ts'
import { useApp } from '../state/context.ts'

export function Profile() {
  const { me } = useApp()
  if (!me) return null

  return (
    <section className="card prof" aria-label="Dados do perfil">
      <div className="prof-h">
        <div className="av lg">{initials(me.name)}</div>
        <div className="prof-id">
          <h2>{me.name}</h2>
          <Badge tone={me.role === 'Administrador' ? 'red' : 'gray'}>{me.role}</Badge>
        </div>
      </div>
      <dl className="dl">
        <div><dt>Nome completo</dt><dd>{me.name}</dd></div>
        <div><dt>Identificação funcional</dt><dd>{me.matricula}</dd></div>
        <div><dt>E-mail</dt><dd>{me.email}</dd></div>
        <div><dt>Telefone</dt><dd>{me.phone || 'Não informado'}</dd></div>
        <div><dt>Perfil de acesso</dt><dd>{me.role}</dd></div>
      </dl>
      <p className="lede xs">Para alterar seus dados, procure a administração do quartel.</p>
    </section>
  )
}
