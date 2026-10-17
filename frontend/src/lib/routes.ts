import type { Role } from '../types.ts'

export const HOME: Record<Role, string> = { admin: '/admin/dashboard', bombeiro: '/bombeiro/escolher-dias' }

/** Estado passado via navegação para abrir um dia direto na tela de escalas. */
export interface ScheduleLocationState {
  openDay?: string
}
