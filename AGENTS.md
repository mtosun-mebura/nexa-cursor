# Nexa Cursor — agent index

Token-efficient map for Cursor and Claude. **Do not duplicate** these rules in chat; follow them.

## Always-on behavior

- Karpathy guidelines → `.cursor/rules/karpathy-guidelines.mdc`
- AI OS / token discipline → `~/.cursor/rules/ai-operating-system.mdc`
- Graphify (if `graphify-out/` present) → `.cursor/rules/graphify.mdc`

## Skills (on demand)

| Skill | When |
|-------|------|
| `nexa-taxi-app-design` | Mobile app UX: design map, heroes, header/footer, CTAs, tokens, Exact Pack, new-ride sounds, archive/restore motions (all roles) |
| `graphify` | Architecture blueprint, cross-file impact, `/graphify` |
| `security-audit` | Security audit / vulnerability hunt |
| `jev-ultrafast` | Browser automation / UI walkthroughs |
| `karpathy-guidelines` | Explicit behavioral refresh |

Personal copies: `~/.cursor/skills/` and `~/.claude/skills/` (symlinked).  
Project skills: `.cursor/skills/` (incl. `nexa-taxi-app-design`).

## Project rules (scoped — open matching files)

Admin UI: `admin-*.mdc`, `foto-upload-patroon.mdc`, `scroll-zonder-achtergrond.mdc`  
Mobile: `mobile-app-*.mdc`, `nexa-taxi-app-design-map.mdc` → skill **`nexa-taxi-app-design`** + `mobile-app/app-design-map/DESIGN_MAP.md`  
Safety: `geen-destructieve-db-commandos.mdc`

## Stack map

| Path | Stack |
|------|--------|
| `backend/` | Laravel, Blade admin, NexaTaxi module |
| `mobile-app/` | Expo React Native (iOS/Android) |
| `deploy/` | TEST/PROD deploy scripts |

## New feature checklist

1. State assumptions / success criteria (Karpathy).
2. If cross-cutting: `graphify query "…"`.
3. Touch only required files; match existing patterns in-folder.
4. Security-sensitive? Run or schedule `security-audit`.
5. No new always-apply rules unless globally true and under ~40 lines — prefer globs or a skill.

## Graphify status

Local blueprint: `graphify-out/` (gitignored). NexaTaxi + `mobile-app/src` merged (~3600 nodes). Query with `graphify query "…"`. Rebuild: see `.cursor/AI-SETUP.md`.
