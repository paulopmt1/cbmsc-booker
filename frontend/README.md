# Frontend — Sistema de Plantões

Protótipo funcional (React 19 + TypeScript + Vite) do design "Sistema de Plantões". Usa apenas dados de exemplo em memória (`src/data/mock.ts`); não consome endpoints.

## Como rodar

```bash
npm install
npm run dev      # http://localhost:5173
npm run build    # type-check + build
npm run lint
```

Na tela de login, use o seletor "Protótipo · entrar como" para entrar como Bombeiro ou Administrador.

## Estrutura (`src/`)

- `state/` — estado global (Context + dados mock), `useApp()`
- `lib/` — datas (pt-BR), regras de pessoas/validação, rotas
- `components/ui|layout|calendar/` — componentes reutilizáveis
- `pages/` — telas (`admin/`, `firefighter/`) e `modals/`
- `styles/` — tokens e CSS globais
