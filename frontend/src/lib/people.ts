import type { Person, PersonForm } from '../types.ts'

export const EMPTY_FORM: PersonForm = { name: '', matricula: '', email: '', phone: '', role: 'Bombeiro', status: 'Ativo' }

export function initials(name: string) {
  const p = name.trim().split(/\s+/)
  return (p[0].charAt(0) + (p.length > 1 ? p[p.length - 1].charAt(0) : '')).toUpperCase()
}

export function shortName(name: string) {
  const p = name.trim().split(/\s+/)
  return p.length > 1 ? `${p[0]} ${p[p.length - 1].charAt(0)}.` : p[0]
}

export type PersonErrors = Partial<Record<'name' | 'matricula' | 'email' | 'phone', string>>

export function validatePerson(form: PersonForm, people: Person[], selfId: number | null): PersonErrors {
  const e: PersonErrors = {}
  const nm = form.name.trim()
  if (nm.length < 5 || !nm.includes(' ')) e.name = 'Informe o nome completo.'
  const mat = form.matricula.trim()
  if (!mat) e.matricula = 'Informe a identificação funcional.'
  else if (people.some((p) => p.id !== selfId && p.matricula.toLowerCase() === mat.toLowerCase())) e.matricula = 'Esta identificação já está cadastrada.'
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) e.email = 'Informe um e-mail válido.'
  if (form.phone.trim() && form.phone.replace(/\D/g, '').length < 10) e.phone = 'Telefone incompleto. Use DDD e número.'
  return e
}
