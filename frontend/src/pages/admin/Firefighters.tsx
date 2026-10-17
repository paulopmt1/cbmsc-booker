import { useState } from 'react'
import { Badge } from '../../components/ui/Badge.tsx'
import { Icon } from '../../components/ui/Icon.tsx'
import { initials } from '../../lib/people.ts'
import { useApp } from '../../state/context.ts'
import type { Person, PersonRole, PersonStatus } from '../../types.ts'
import { PersonModal, type PersonModalMode } from '../modals/PersonModal.tsx'

export function Firefighters() {
  const { people } = useApp()
  const [search, setSearch] = useState('')
  const [fStatus, setFStatus] = useState<'todos' | PersonStatus>('todos')
  const [fRole, setFRole] = useState<'todos' | PersonRole>('todos')
  const [modal, setModal] = useState<{ mode: PersonModalMode; person: Person | null } | null>(null)

  const q = search.trim().toLowerCase()
  const rows = people.filter(
    (p) =>
      (!q || p.name.toLowerCase().includes(q) || p.matricula.toLowerCase().includes(q)) &&
      (fStatus === 'todos' || p.status === fStatus) &&
      (fRole === 'todos' || p.role === fRole),
  )

  return (
    <>
      <div className="toolbar">
        <div className="sbox">
          <Icon name="search" />
          <input className="inp" type="search" aria-label="Pesquisar por nome ou identificação" placeholder="Pesquisar por nome ou identificação" value={search} onChange={(e) => setSearch(e.target.value)} />
        </div>
        <select className="sel" aria-label="Filtrar por status" value={fStatus} onChange={(e) => setFStatus(e.target.value as 'todos' | PersonStatus)}>
          <option value="todos">Todos os status</option>
          <option value="Ativo">Ativo</option>
          <option value="Inativo">Inativo</option>
        </select>
        <select className="sel" aria-label="Filtrar por perfil" value={fRole} onChange={(e) => setFRole(e.target.value as 'todos' | PersonRole)}>
          <option value="todos">Todos os perfis</option>
          <option value="Bombeiro">Bombeiro</option>
          <option value="Administrador">Administrador</option>
        </select>
        <button type="button" className="btn btn-p" onClick={() => setModal({ mode: 'new', person: null })}>
          <Icon name="plus" size={18} strokeWidth={2.2} />
          Cadastrar bombeiro
        </button>
      </div>

      <section className="card" style={{ overflow: 'hidden' }} aria-label="Lista de bombeiros">
        <div className="thead" aria-hidden="true">
          <span>Nome</span><span>Identificação</span><span>E-mail</span><span>Perfil</span><span>Status</span><span>Ações</span>
        </div>
        <div>
          {rows.map((p) => (
            <div key={p.id} className="trow">
              <div className="tcell tname"><div className="av">{initials(p.name)}</div><b>{p.name}</b></div>
              <div className="tcell mlab" data-label="Identificação">{p.matricula}</div>
              <div className="tcell mlab" data-label="E-mail">{p.email}</div>
              <div className="tcell"><Badge tone={p.role === 'Administrador' ? 'red' : 'gray'}>{p.role}</Badge></div>
              <div className="tcell"><Badge tone={p.status === 'Ativo' ? '' : 'off'} dot>{p.status}</Badge></div>
              <div className="tcell tact">
                <button type="button" className="btn btn-s btn-sm" aria-label={`Visualizar ${p.name}`} onClick={() => setModal({ mode: 'view', person: p })}>Ver</button>
                <button type="button" className="btn btn-s btn-sm" aria-label={`Editar ${p.name}`} onClick={() => setModal({ mode: 'edit', person: p })}>Editar</button>
              </div>
            </div>
          ))}
        </div>
        {rows.length === 0 && <div className="empty-s">Nenhum bombeiro encontrado para esta pesquisa.</div>}
      </section>
      <p className="lede xs">{rows.length} de {people.length} bombeiros</p>

      {modal && <PersonModal key={`${modal.mode}-${modal.person?.id ?? 'new'}`} mode={modal.mode} person={modal.person} onClose={() => setModal(null)} />}
    </>
  )
}
