export type Role = 'bombeiro' | 'admin'
export type PersonRole = 'Bombeiro' | 'Administrador'
export type PersonStatus = 'Ativo' | 'Inativo'

export interface Person {
  id: number
  name: string
  matricula: string
  email: string
  phone: string
  role: PersonRole
  status: PersonStatus
}

export type PersonForm = Omit<Person, 'id'>

/** Data no formato AAAA-MM-DD. */
export type DateKey = string
export type Assignments = Record<DateKey, number>
export type Interests = Record<DateKey, number[]>

export interface Toast {
  kind: 'ok' | 'bad'
  text: string
}
