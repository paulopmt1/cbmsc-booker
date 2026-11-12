import type { ReactNode } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { AppShell } from './components/layout/AppShell.tsx'
import { HOME } from './lib/routes.ts'
import { Dashboard } from './pages/admin/Dashboard.tsx'
import { Firefighters } from './pages/admin/Firefighters.tsx'
import { Schedule } from './pages/admin/Schedule.tsx'
import { PickDays } from './pages/firefighter/PickDays.tsx'
import { Login } from './pages/Login.tsx'
import { Profile } from './pages/Profile.tsx'
import { useApp } from './state/context.ts'
import type { Role } from './types.ts'

/** Exige sessão; com `only`, restringe a rota a um perfil (outro perfil vai para a própria home). */
function Guard({ only, children }: { only?: Role; children: ReactNode }) {
  const { role } = useApp()
  if (!role) return <Navigate to="/login" replace />
  if (only && role !== only) return <Navigate to={HOME[role]} replace />
  return children
}

export default function App() {
  const { role } = useApp()

  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route element={<Guard><AppShell /></Guard>}>
        <Route path="/admin/dashboard" element={<Guard only="admin"><Dashboard /></Guard>} />
        <Route path="/admin/bombeiros" element={<Guard only="admin"><Firefighters /></Guard>} />
        <Route path="/admin/escalas" element={<Guard only="admin"><Schedule /></Guard>} />
        <Route path="/bombeiro/escolher-dias" element={<Guard only="bombeiro"><PickDays /></Guard>} />
        <Route path="/perfil" element={<Profile />} />
      </Route>
      <Route path="*" element={<Navigate to={role ? HOME[role] : '/login'} replace />} />
    </Routes>
  )
}
