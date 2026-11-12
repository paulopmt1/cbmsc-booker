import { useState } from 'react'
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { initials } from '../../lib/people.ts'
import { HOME } from '../../lib/routes.ts'
import { useApp } from '../../state/context.ts'
import type { Role } from '../../types.ts'
import { Icon, type IconName } from '../ui/Icon.tsx'
import { Logo } from '../ui/Logo.tsx'
import { ToastHost } from '../ui/Toast.tsx'

interface NavItem {
  to: string
  label: string
  icon: IconName
}

const ADMIN_NAV: NavItem[] = [
  { to: '/admin/dashboard', label: 'Dashboard', icon: 'dashboard' },
  { to: '/admin/bombeiros', label: 'Bombeiros cadastrados', icon: 'users' },
  { to: '/admin/escalas', label: 'Escalas de plantão', icon: 'calendar' },
]
const BOMBEIRO_NAV: NavItem[] = [{ to: '/bombeiro/escolher-dias', label: 'Escolher dias de plantão', icon: 'calendarCheck' }]
const PROFILE_NAV: NavItem = { to: '/perfil', label: 'Meu perfil', icon: 'user' }

const TITLES: Record<string, string> = {
  '/admin/dashboard': 'Dashboard',
  '/admin/bombeiros': 'Bombeiros cadastrados',
  '/admin/escalas': 'Escalas de plantão',
  '/bombeiro/escolher-dias': 'Escolher dias de plantão',
  '/perfil': 'Meu perfil',
}

export function AppShell() {
  const { role, me, login, logout } = useApp()
  const { pathname } = useLocation()
  const navigate = useNavigate()
  const [menuOpen, setMenuOpen] = useState(false)
  const [menuPath, setMenuPath] = useState(pathname)

  // Fecha o drawer ao navegar (ajuste de estado durante a renderização).
  if (menuPath !== pathname) {
    setMenuPath(pathname)
    setMenuOpen(false)
  }

  if (!role || !me) return null
  const items = [...(role === 'admin' ? ADMIN_NAV : BOMBEIRO_NAV), PROFILE_NAV]

  const switchRole = (next: Role) => {
    login(next)
    setMenuOpen(false)
    navigate(HOME[next])
  }

  return (
    <div className="app">
      <div className={`scrim${menuOpen ? ' on' : ''}`} onClick={() => setMenuOpen(false)} />
      <aside className={`side${menuOpen ? ' open' : ''}`} aria-label="Menu principal">
        <div className="brand">
          <Logo />
          <div>
            <div className="brand-t">Sistema de Plantões</div>
            <div className="brand-s">Quartel Central</div>
          </div>
        </div>
        <nav className="navlist">
          {items.map((item) => (
            <NavLink key={item.to} to={item.to} className={({ isActive }) => `nav${isActive ? ' active' : ''}`}>
              <Icon name={item.icon} />
              {item.label}
            </NavLink>
          ))}
        </nav>
        <div className="side-foot">
          <div className="demo">
            <span className="demo-l">Protótipo · visualizar como</span>
            <div className="seg">
              <button type="button" className={`seg-b${role === 'bombeiro' ? ' on' : ''}`} onClick={() => switchRole('bombeiro')}>Bombeiro</button>
              <button type="button" className={`seg-b${role === 'admin' ? ' on' : ''}`} onClick={() => switchRole('admin')}>Administrador</button>
            </div>
          </div>
          <button type="button" className="nav" onClick={() => { logout(); navigate('/login') }}>
            <Icon name="logout" />
            Sair
          </button>
        </div>
      </aside>

      <div className="main">
        <header className="topbar">
          <button type="button" className="burger" aria-label="Abrir menu" onClick={() => setMenuOpen(true)}>
            <Icon name="menu" size={22} />
          </button>
          <h1>{TITLES[pathname] ?? ''}</h1>
          <div className="tb-user">
            <div className="nm"><b>{me.name}</b><span className="r">{me.role}</span></div>
            <div className="av">{initials(me.name)}</div>
          </div>
        </header>
        <div className="content">
          <Outlet />
        </div>
      </div>
      <ToastHost />
    </div>
  )
}
