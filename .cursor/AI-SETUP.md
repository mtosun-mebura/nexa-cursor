# AI setup — Cursor + Claude (Mac)

Geoptimaliseerd voor Laravel backend, admin frontend, Expo iOS/Android. Focus: **token-efficiëntie**, **veiligheid**, **geen herhalingen**.

## Wat er geïnstalleerd is

### CLI

- `graphify` via `uv tool install graphifyy` → `~/.local/bin/graphify`

### Personal skills (`~/.cursor/skills/` ↔ `~/.claude/skills/`)

| Skill | Bron |
|-------|------|
| karpathy-guidelines | [andrej-karpathy-skills](https://github.com/multica-ai/andrej-karpathy-skills) |
| graphify | [Graphify](https://github.com/safishamsi/graphify) / Graphify-Labs |
| security-audit | [cloudflare/security-audit-skill](https://github.com/cloudflare/security-audit-skill) |
| jev-ultrafast | [browser-use/jev-ultrafast](https://github.com/browser-use/jev-ultrafast) |

### Always-apply rules (kort houden!)

- `~/.cursor/rules/karpathy-guidelines.mdc`
- `~/.cursor/rules/ai-operating-system.mdc`
- `~/.cursor/rules/graphify.mdc` (alleen als graph bestaat / architecture)
- Project: `.cursor/rules/*.mdc` (UI patterns = **globs**, niet alles always)

### Claude app

- `~/.claude/CLAUDE.md` — globale index
- Project `CLAUDE.md` + `AGENTS.md`

## Dagelijks gebruik

```bash
cd /Users/tosun/Projecten/nexa-saas/nexa-cursor
export PATH="$HOME/.local/bin:$PATH"

# Code-only (geen API-key) — aanbevolen standaard
graphify extract backend/app/Modules/NexaTaxi --code-only --out .
graphify extract mobile-app/src --code-only --out /tmp/graphify-mobile
graphify merge-graphs graphify-out/graph.json /tmp/graphify-mobile/graphify-out/graph.json --out graphify-out/graph.json
graphify cluster-only . --no-label

# Met LLM (semantiek + community-namen): zet ANTHROPIC_API_KEY of OPENAI_API_KEY, dan zonder --code-only / --no-label

# Query zonder files te dumpen
graphify query "waar wordt contract today gebouwd?"
graphify path "ContractPortalController" "RideStop"
graphify god-nodes --top 10
graphify update .   # na commits (AST, goedkoop)

# Auto-refresh na commits (al geïnstalleerd in dit repo)
graphify hook status
```

In Cursor/Claude chat:

- `/graphify .` of “bouw de graphify blauwdruk”
- “security audit this codebase” → skill security-audit
- “automatiseer deze UI in de browser” → jev-ultrafast

## Architectuur (waarom zo)

```
alwaysApply rules  →  klein (gedrag + router)     = weinig tokens elke turn
globs rules        →  UI/API alleen bij match     = geen herhaling
skills             →  zware workflows on-demand   = laden alleen als nodig
graphify-out/      →  10–70x minder tokens voor arch-vragen
AGENTS.md          →  index, geen tweede kopie van regels
```

## Onderhoud

| Actie | Commando |
|-------|----------|
| Graphify updaten | `uv tool upgrade graphifyy` |
| Security skill updaten | `npx skills add https://github.com/cloudflare/security-audit-skill --skill security-audit --global -y` |
| Nieuwe UI-conventie | nieuwe `.mdc` met **globs**, niet alwaysApply |
| Nieuwe workflow | skill in `~/.cursor/skills/` + symlink naar `~/.claude/skills/` |

## .gitignore

Zorg dat `graphify-out/` genegeerd wordt (lokaal artefact). Zie project `.gitignore`.
