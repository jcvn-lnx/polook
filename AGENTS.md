# AGENTS.md — Tarkan

Guia para agentes de IA neste repositório. Abaixo do "o que um agente provavelmente erraria sem ajuda"; o resto está nos docs de planejamento (`.planning/`).

## Projeto

**Tarkan** — plataforma white-label de rastreamento GPS/frota.
- `front/` — Vue 3 SPA/PWA via **Vue CLI 4.5** (webpack). Setup: `yarn install`.
- `tarkan-api/` — Laravel 8 / PHP ^7.3|^8.0, proxy/middleware sobre Traccar.
- `tarkan.sql` — dump MySQL do banco próprio da API.

## Fatos críticos (não óbvios de filename)

- **Cuidado para nunca commitar:** `tarkan-0906.tar` (~615 MB), `tarkan.sql`, `front/dist/`, `front/test/*.umd*.js`, `tarkan-desktop/`, `html/`, `desktop.ini`.
- **Dois lockfiles no front:** `yarn.lock` + `package-lock.json` coexistem. `yarn.lock` é o source of truth (README usa `yarn install`). `package-lock.json` é resquício — não atualizar.
- **Builds mobile:** entrypoints separados (`src/main-mobile.js`, `src/main-mobile-client.js`). Comandos: `yarn mbuild` (build mobile), `yarn mserve` / `yarn mcserve` (dev server mobile).
- **Sem CI/CD:** Não há `.github/` nem workflows de CI. Nada roda automaticamente.
- **Connectors duplicados:** mesma lógica de comunicação com Traccar existe em PHP (`tarkan-api/app/Tarkan/traccarConnector.php`, 831 linhas) e JS (`front/src/tarkan/traccarConnector/traccarConnector.js`, `front/src/tarkan/tarkanConnector/tarkanConnector.js`). Alvo de dedup (Fase 3).
- **Auth delegada ao Traccar:** Laravel não tem auth própria para rotas proxy; encaminha o cookie `JSESSIONID` do cliente. Qualquer chamada sem cookie cai em Basic Auth admin — frágil.

## Setup

```bash
# Backend
cd tarkan-api
cp .env.example .env         # editar DB/Traccar vars
composer install
php artisan key:generate

# Frontend
cd front
yarn install
cp .env.example .env         # se existir; senão, criar manualmente
```

Variáveis Traccar (`TARKAN_HOST`, `TARKAN_USERNAME`, `TARKAN_PASSWORD`) não estão no `.env.example` — adicionar manualmente se for conectar a um Traccar.

## Comandos

### Backend (`tarkan-api/`)
| Comando | Descrição |
|---------|-----------|
| `php artisan test` | Laravel test runner (wraps PHPUnit 9.5) |
| `./vendor/bin/phpunit --testsuite Feature` | Feature tests apenas |
| `./vendor/bin/phpunit --coverage-text` | Coverage (requer Xdebug/PCOV) |

### Frontend (`front/`)
| Comando | Descrição |
|---------|-----------|
| `yarn serve` | Dev server desktop |
| `yarn mserve` | Dev server mobile |
| `yarn build` | Build produção desktop |
| `yarn mbuild` | Build produção mobile (`--dest=dist-mobile`) |
| `yarn lint` | ESLint (vue3-essential + eslint:recommended) — build falha se houver erro |

**Não há test runner no front ainda.** `npx vitest` só funcionará após instalação e configuração (planejado para Fase 1).

## Golden rules do milestone atual

1. **Tests-first:** nada que altere comportamento é mexido antes da rede de caracterização existir e estar verde.
2. **Preserve primeiro, corrija depois:** dedup é byte-idêntica (reproduz bugs atuais). Correções são mudanças deliberadas e isoladas (Fase 4).
3. **Upgrade incremental:** Laravel 8→9→10→11→12, um major por vez. PHP 7.3→8.1 (L8) → 8.2 (L11+). Suíte verde entre cada hop.
4. **Manter estrutura clássica do Laravel** (`app/Http/Kernel.php` etc.) — NÃO adotar slim skeleton do L11.
5. **Compatibilidade externa 100%:** não alterar rotas, status, headers ou corpos de resposta.
6. **Front toolchain fora de escopo:** não migrar Vue CLI→Vite nem bumpar libs neste milestone.

## Workflow GSD

- `/gsd-plan-phase <n>` — planejar fase
- `/gsd-execute-phase <n>` — executar fase
- Config: modo YOLO, granularidade standard, paralelização ativa, research + plan-check + verifier ativos.

## Planejamento (`.planning/`)

- `PROJECT.md` — contexto, constraints, decisões-chave
- `REQUIREMENTS.md` — requisitos v1 com IDs (TEST-01..05, DEDUP-01..02, UPG-01..06, COMPAT-01, FIX-01..03)
- `ROADMAP.md` — 5 fases em ordem rígida
- `STATE.md` — memória/estado do projeto
- `codebase/` — análise detalhada (STACK, ARCHITECTURE, STRUCTURE, CONVENTIONS, TESTING, INTEGRATIONS, CONCERNS)
