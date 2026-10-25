import { useState, type FormEvent } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { Icon } from '../components/ui/Icon.tsx'
import { Logo } from '../components/ui/Logo.tsx'
import { HOME } from '../lib/routes.ts'
import { useApp } from '../state/context.ts'
import type { Role } from '../types.ts'

const DEMO_EMAIL: Record<Role, string> = {
  bombeiro: 'rafael.souza@quartel.exemplo.br',
  admin: 'marcos.andrade@quartel.exemplo.br',
}
const DEMO_PASS = 'demonstracao'

export function Login() {
  const { role: session, login } = useApp()
  const navigate = useNavigate()
  const [demoRole, setDemoRole] = useState<Role>('bombeiro')
  const [email, setEmail] = useState(DEMO_EMAIL.bombeiro)
  const [pass, setPass] = useState(DEMO_PASS)
  const [error, setError] = useState(false)

  if (session) return <Navigate to={HOME[session]} replace />

  const pickDemo = (r: Role) => {
    setDemoRole(r)
    setEmail(DEMO_EMAIL[r])
    setPass(DEMO_PASS)
    setError(false)
  }

  const submit = (e: FormEvent) => {
    e.preventDefault()
    if (!email.trim() || !pass.trim() || pass === 'errada') {
      setError(true)
      return
    }
    setError(false)
    login(demoRole)
    navigate(HOME[demoRole])
  }

  return (
    <div className="login-w">
      <div className="login">
        <div className="login-head">
          <Logo />
          <div>
            <h1>Sistema de Plantões</h1>
            <p className="lede sm" style={{ marginTop: 4 }}>Quartel Central · Corpo de Bombeiros</p>
          </div>
        </div>
        <form className="card login-card" onSubmit={submit} noValidate>
          {error && (
            <div className="alert" role="alert">
              <Icon name="alert" size={18} />
              <span>E-mail, identificação ou senha incorretos. Verifique os dados e tente novamente.</span>
            </div>
          )}
          <div className="field">
            <label htmlFor="lg-email">E-mail ou identificação</label>
            <input id="lg-email" className="inp" type="text" autoComplete="username" value={email} onChange={(e) => setEmail(e.target.value)} />
          </div>
          <div className="field">
            <label htmlFor="lg-pass">Senha</label>
            <input id="lg-pass" className="inp" type="password" autoComplete="current-password" value={pass} onChange={(e) => setPass(e.target.value)} />
          </div>
          <button type="submit" className="btn btn-p" style={{ width: '100%' }}>Entrar</button>
        </form>
        <div className="demo">
          <span className="demo-l">Protótipo · entrar como</span>
          <div className="seg">
            <button type="button" className={`seg-b${demoRole === 'bombeiro' ? ' on' : ''}`} onClick={() => pickDemo('bombeiro')}>Bombeiro</button>
            <button type="button" className={`seg-b${demoRole === 'admin' ? ' on' : ''}`} onClick={() => pickDemo('admin')}>Administrador</button>
          </div>
          <span className="demo-l n">Apague a senha e clique em Entrar para ver o estado de erro.</span>
        </div>
      </div>
    </div>
  )
}
