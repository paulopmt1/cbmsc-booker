import type { Assignments, Interests, Person } from '../types.ts'
import { keyOf } from '../lib/dates.ts'

export const BOMBEIRO_ID = 1
export const ADMIN_ID = 12

export const PEOPLE: Person[] = [
  { id: 1, name: 'Rafael Souza', matricula: 'BM-20418', email: 'rafael.souza@quartel.exemplo.br', phone: '(61) 98123-4567', role: 'Bombeiro', status: 'Ativo' },
  { id: 2, name: 'Camila Ferreira', matricula: 'BM-20233', email: 'camila.ferreira@quartel.exemplo.br', phone: '(61) 99811-2034', role: 'Bombeiro', status: 'Ativo' },
  { id: 3, name: 'Thiago Almeida', matricula: 'BM-19876', email: 'thiago.almeida@quartel.exemplo.br', phone: '', role: 'Bombeiro', status: 'Ativo' },
  { id: 4, name: 'Beatriz Nogueira', matricula: 'BM-21102', email: 'beatriz.nogueira@quartel.exemplo.br', phone: '(61) 99650-7712', role: 'Bombeiro', status: 'Ativo' },
  { id: 5, name: 'Lucas Pereira', matricula: 'BM-20571', email: 'lucas.pereira@quartel.exemplo.br', phone: '(61) 98544-0198', role: 'Bombeiro', status: 'Ativo' },
  { id: 6, name: 'Fernanda Lima', matricula: 'BM-19944', email: 'fernanda.lima@quartel.exemplo.br', phone: '', role: 'Bombeiro', status: 'Ativo' },
  { id: 7, name: 'Gustavo Barbosa', matricula: 'BM-20810', email: 'gustavo.barbosa@quartel.exemplo.br', phone: '(61) 99277-3340', role: 'Bombeiro', status: 'Ativo' },
  { id: 8, name: 'Juliana Castro', matricula: 'BM-21045', email: 'juliana.castro@quartel.exemplo.br', phone: '(61) 98702-5521', role: 'Bombeiro', status: 'Ativo' },
  { id: 9, name: 'André Martins', matricula: 'BM-18732', email: 'andre.martins@quartel.exemplo.br', phone: '', role: 'Bombeiro', status: 'Inativo' },
  { id: 10, name: 'Patrícia Rocha', matricula: 'BM-20127', email: 'patricia.rocha@quartel.exemplo.br', phone: '(61) 99403-8860', role: 'Bombeiro', status: 'Ativo' },
  { id: 11, name: 'Daniel Teixeira', matricula: 'BM-21230', email: 'daniel.teixeira@quartel.exemplo.br', phone: '', role: 'Bombeiro', status: 'Ativo' },
  { id: 12, name: 'Marcos Andrade', matricula: 'ADM-0007', email: 'marcos.andrade@quartel.exemplo.br', phone: '(61) 99234-5678', role: 'Administrador', status: 'Ativo' },
  { id: 13, name: 'Helena Duarte', matricula: 'ADM-0012', email: 'helena.duarte@quartel.exemplo.br', phone: '(61) 98866-1204', role: 'Administrador', status: 'Ativo' },
]

const hash = (n: number, s: number) => {
  let x = (Math.imul(n + 1, 374761393) + Math.imul(s + 1, 668265263)) >>> 0
  x = Math.imul(x ^ (x >>> 13), 1274126177) >>> 0
  return (x ^ (x >>> 16)) >>> 0
}

/** Gera escala e interesses de exemplo (determinístico) para 2026–2027. */
export function initData(): { assign: Assignments; interest: Interests } {
  const assign: Assignments = {}
  const interest: Interests = {}
  const bomb = [1, 2, 3, 4, 5, 6, 7, 8, 10, 11]
  const t0 = new Date(2026, 9, 16).getTime()
  let n = 0
  for (const dt = new Date(2026, 0, 1); dt <= new Date(2027, 11, 31); dt.setDate(dt.getDate() + 1), n++) {
    const k = keyOf(dt.getFullYear(), dt.getMonth(), dt.getDate())
    const ahead = Math.round((dt.getTime() - t0) / 86400000)
    let asg: number | null = null
    if (ahead <= 0) {
      asg = bomb[n % 10]
    } else {
      const p = Math.max(5, Math.min(80, 75 - ahead * 0.4))
      if (hash(n, 1) % 100 < p) asg = bomb[(n * 3 + 1) % 10]
    }
    if (asg) assign[k] = asg
    if (ahead > 0) {
      const r = hash(n, 2) % 100
      let c = r < 30 ? 0 : r < 60 ? 1 : r < 85 ? 2 : 3
      if (asg) c = c > 0 && hash(n, 3) % 100 < 35 ? 1 : 0
      const list: number[] = []
      for (let i = 0; i < c; i++) {
        const id = bomb[hash(n, 10 + i) % 10]
        if (id !== asg && !list.includes(id)) list.push(id)
      }
      if (list.length) interest[k] = list
    }
  }
  delete assign['2026-10-21']; interest['2026-10-21'] = [1, 3, 6]
  assign['2026-10-24'] = 1; delete interest['2026-10-24']
  delete assign['2026-10-27']; interest['2026-10-27'] = [2, 5]
  delete assign['2026-10-30']; delete interest['2026-10-30']
  delete assign['2026-11-03']; interest['2026-11-03'] = [1, 8]
  assign['2026-10-18'] = 4
  return { assign, interest }
}
