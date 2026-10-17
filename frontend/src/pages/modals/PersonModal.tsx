import { useState } from 'react'
import { Modal } from '../../components/ui/Modal.tsx'
import { EMPTY_FORM, validatePerson } from '../../lib/people.ts'
import { useApp } from '../../state/context.ts'
import type { Person, PersonForm, PersonRole, PersonStatus } from '../../types.ts'

export type PersonModalMode = 'new' | 'edit' | 'view'

interface PersonModalProps {
  mode: PersonModalMode
  person: Person | null
  onClose: () => void
}

export function PersonModal({ mode: initialMode, person, onClose }: PersonModalProps) {
  const { people, savePerson } = useApp()
  const [mode, setMode] = useState(initialMode)
  const [form, setForm] = useState<PersonForm>(person ? { ...person } : { ...EMPTY_FORM })
  const [submitted, setSubmitted] = useState(false)

  const errors = mode !== 'view' && submitted ? validatePerson(form, people, person?.id ?? null) : {}
  const set = <K extends keyof PersonForm>(field: K, value: PersonForm[K]) => setForm((f) => ({ ...f, [field]: value }))

  const save = () => {
    setSubmitted(true)
    if (Object.keys(validatePerson(form, people, person?.id ?? null)).length) return
    savePerson(person?.id ?? null, form)
    onClose()
  }

  if (mode === 'view') {
    return (
      <Modal
        title="Dados do bombeiro"
        wide
        onClose={onClose}
        footer={
          <>
            <button type="button" className="btn btn-s" onClick={onClose}>Fechar</button>
            <button type="button" className="btn btn-p" onClick={() => setMode('edit')}>Editar</button>
          </>
        }
      >
        <div className="m-b">
          <dl className="dl">
            <div><dt>Nome completo</dt><dd>{form.name}</dd></div>
            <div><dt>Identificação funcional</dt><dd>{form.matricula}</dd></div>
            <div><dt>E-mail</dt><dd>{form.email}</dd></div>
            <div><dt>Telefone</dt><dd>{form.phone || 'Não informado'}</dd></div>
            <div><dt>Perfil de acesso</dt><dd>{form.role}</dd></div>
            <div><dt>Status</dt><dd>{form.status}</dd></div>
          </dl>
        </div>
      </Modal>
    )
  }

  return (
    <Modal
      title={mode === 'new' ? 'Cadastrar bombeiro' : 'Editar bombeiro'}
      wide
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn btn-s" onClick={onClose}>Cancelar</button>
          <button type="button" className="btn btn-p" onClick={save}>Salvar</button>
        </>
      }
    >
      <div className="m-b">
        <div className="form">
          <div className="field full">
            <label htmlFor="f-name">Nome completo</label>
            <input id="f-name" className={`inp${errors.name ? ' inv' : ''}`} type="text" value={form.name} onChange={(e) => set('name', e.target.value)} />
            {errors.name && <p className="err">{errors.name}</p>}
          </div>
          <div className="field">
            <label htmlFor="f-mat">Identificação funcional</label>
            <input id="f-mat" className={`inp${errors.matricula ? ' inv' : ''}`} type="text" placeholder="BM-00000" value={form.matricula} onChange={(e) => set('matricula', e.target.value)} />
            {errors.matricula && <p className="err">{errors.matricula}</p>}
          </div>
          <div className="field">
            <label htmlFor="f-role">Perfil de acesso</label>
            <select id="f-role" className="inp" value={form.role} onChange={(e) => set('role', e.target.value as PersonRole)}>
              <option value="Bombeiro">Bombeiro</option>
              <option value="Administrador">Administrador</option>
            </select>
          </div>
          <div className="field full">
            <label htmlFor="f-mail">E-mail</label>
            <input id="f-mail" className={`inp${errors.email ? ' inv' : ''}`} type="email" value={form.email} onChange={(e) => set('email', e.target.value)} />
            {errors.email && <p className="err">{errors.email}</p>}
          </div>
          <div className="field">
            <label htmlFor="f-tel">Telefone <span className="opt">(opcional)</span></label>
            <input id="f-tel" className={`inp${errors.phone ? ' inv' : ''}`} type="tel" placeholder="(00) 00000-0000" value={form.phone} onChange={(e) => set('phone', e.target.value)} />
            {errors.phone && <p className="err">{errors.phone}</p>}
          </div>
          <div className="field">
            <label htmlFor="f-st">Status</label>
            <select id="f-st" className="inp" value={form.status} onChange={(e) => set('status', e.target.value as PersonStatus)}>
              <option value="Ativo">Ativo</option>
              <option value="Inativo">Inativo</option>
            </select>
          </div>
        </div>
      </div>
    </Modal>
  )
}
