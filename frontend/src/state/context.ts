import { createContext, useContext } from 'react'
import type { Assignments, Interests, Person, PersonForm, Role, Toast } from '../types.ts'

export interface AppState {
  /** null = sem sessão (tela de login). */
  role: Role | null
  people: Person[]
  assign: Assignments
  interest: Interests
  toast: Toast | null
}

export interface AppActions {
  login: (role: Role) => void
  logout: () => void
  assignShift: (key: string, personId: number) => void
  addInterest: (key: string, personId: number) => void
  removeInterest: (key: string, personId: number) => void
  /** Retorna a pessoa salva. `id` null cadastra um novo bombeiro. */
  savePerson: (id: number | null, form: PersonForm) => Person
  showToast: (kind: Toast['kind'], text: string) => void
  closeToast: () => void
}

export interface AppContextValue extends AppState, AppActions {
  /** Usuário logado (null sem sessão). */
  me: Person | null
  personById: (id: number) => Person | undefined
}

export const AppContext = createContext<AppContextValue | null>(null)

export function useApp(): AppContextValue {
  const ctx = useContext(AppContext)
  if (!ctx) throw new Error('useApp deve ser usado dentro de AppStateProvider')
  return ctx
}
