import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { ADMIN_ID, BOMBEIRO_ID, PEOPLE, initData } from '../data/mock.ts'
import { shortDate } from '../lib/dates.ts'
import type { Person, PersonForm, Role, Toast } from '../types.ts'
import { AppContext, type AppContextValue, type AppState } from './context.ts'

const TOAST_MS = 4000

function initialState(): AppState {
  const { assign, interest } = initData()
  return { role: null, people: PEOPLE, assign, interest, toast: null }
}

export function AppStateProvider({ children }: { children: ReactNode }) {
  const [state, setState] = useState<AppState>(initialState)

  useEffect(() => {
    if (!state.toast) return
    const t = setTimeout(() => setState((s) => ({ ...s, toast: null })), TOAST_MS)
    return () => clearTimeout(t)
  }, [state.toast])

  const showToast = useCallback((kind: Toast['kind'], text: string) => {
    setState((s) => ({ ...s, toast: { kind, text } }))
  }, [])

  const value = useMemo<AppContextValue>(() => {
    const personById = (id: number) => state.people.find((p) => p.id === id)
    const me = state.role ? personById(state.role === 'admin' ? ADMIN_ID : BOMBEIRO_ID) ?? null : null

    return {
      ...state,
      me,
      personById,
      showToast,
      closeToast: () => setState((s) => ({ ...s, toast: null })),
      login: (role: Role) => setState((s) => ({ ...s, role })),
      logout: () => setState((s) => ({ ...s, role: null })),

      assignShift: (key, personId) => {
        const name = personById(personId)?.name ?? ''
        setState((s) => ({
          ...s,
          assign: { ...s.assign, [key]: personId },
          toast: { kind: 'ok', text: `Plantão de ${shortDate(key)} definido para ${name}.` },
        }))
      },

      addInterest: (key, personId) => {
        setState((s) => ({
          ...s,
          interest: { ...s.interest, [key]: [...(s.interest[key] ?? []), personId] },
          toast: { kind: 'ok', text: `Interesse registrado para ${shortDate(key)}. A data só é sua após a confirmação da escala.` },
        }))
      },

      removeInterest: (key, personId) => {
        setState((s) => ({
          ...s,
          interest: { ...s.interest, [key]: (s.interest[key] ?? []).filter((i) => i !== personId) },
          toast: { kind: 'ok', text: `Interesse em ${shortDate(key)} cancelado.` },
        }))
      },

      savePerson: (id: number | null, form: PersonForm): Person => {
        const clean: PersonForm = {
          ...form,
          name: form.name.trim(),
          matricula: form.matricula.trim(),
          email: form.email.trim(),
          phone: form.phone.trim(),
        }
        const saved: Person = { id: id ?? Math.max(...state.people.map((p) => p.id)) + 1, ...clean }
        setState((s) => ({
          ...s,
          people: id === null ? [...s.people, saved] : s.people.map((p) => (p.id === id ? saved : p)),
          toast: { kind: 'ok', text: id === null ? `${saved.name} foi cadastrado(a) com sucesso.` : `Dados de ${saved.name} atualizados.` },
        }))
        return saved
      },
    }
  }, [state, showToast])

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>
}
