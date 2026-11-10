# Claude / Cursor — Nexa

Read **[AGENTS.md](AGENTS.md)** first. It indexes rules and skills without repeating them.

## Behavior

Follow Karpathy guidelines (think → simplicity → surgical → goal-driven). Prefer existing `.cursor/rules/` over inventing new conventions.

## Commands

- `/graphify` or skill **graphify** — knowledge graph / blueprint
- Security review → skill **security-audit**
- Browser UI automation → skill **jev-ultrafast**

## Do not

- Re-explain admin/mobile UI patterns already in `.cursor/rules/`
- Commit secrets or run destructive DB/git unless explicitly asked
- Add speculative abstractions or duplicate documentation
