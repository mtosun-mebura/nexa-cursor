---
name: jev-ultrafast
description: >-
  Use Jev Ultrafast (browser-use/jev-ultrafast) for fast browser automation with a
  dynamic indexed action space. TypeSafe's Jev picks operation + target; a small LLM
  types only for TYPE_TEXT. Use when the user wants an automated browser agent, web
  UI walkthrough, form fill, search flow, or demo at http://127.0.0.1:8766. Needs
  TYPESAFE_API_KEY and TEXT_MODEL_API_KEY. Do not confuse with the old jev-code
  classifier MCP (removed).
license: MIT
compatibility: Requires uv, Chrome via Browser Harness, TYPESAFE_API_KEY, and TEXT_MODEL_API_KEY.
metadata:
  author: browser-use
  source: https://github.com/browser-use/jev-ultrafast
  version: "0.1.0"
  install_path: ~/.local/share/jev-ultrafast
---

# Jev Ultrafast

Browser agent with a dynamic, indexed action space. One natural-language goal →
TypeSafe picks `CLICK` / `TYPE_TEXT` / `SELECT` / scroll / wait / done → optional
small LLM for typed text only.

Source: https://github.com/browser-use/jev-ultrafast  
Install path: `~/.local/share/jev-ultrafast`

## When to use

- Automate a real browser flow (search, forms, navigation, UI checks).
- Run the local inspector demo.
- Prefer this over hand-written Playwright scripts when the task is goal-driven and short.

## When not to

- Classification, ranking, yes/no checks over text → not this tool (old jev-code classifier was removed).
- Pure codebase edits with no browser.
- Booking/payment flows that need human confirmation on real money.

## Setup

Keys live in `~/.local/share/jev-ultrafast/.env` (never commit):

```bash
TYPESAFE_API_KEY=...
TEXT_MODEL_API_KEY=...   # OpenRouter by default
```

Chrome via Browser Harness (`uv sync` installs it):

```bash
export PATH="$HOME/.local/bin:$PATH"
cd ~/.local/share/jev-ultrafast
uv run browser-harness --doctor
```

## Run inspector

```bash
export PATH="$HOME/.local/bin:$PATH"
cd ~/.local/share/jev-ultrafast
uv run --env-file .env jev
```

Open http://127.0.0.1:8766 → Start demo → Run automatically.

## Library usage

```bash
cd ~/.local/share/jev-ultrafast
uv run --env-file .env python examples/run.py \
  --url 'https://example.com' \
  --goal 'Describe the visible primary CTA and stop.'
```

Or in Python:

```python
from jev_ultrafast import Agent

with Agent(url, goal) as agent:
    for state in agent.run():
        print(state["elapsed_ms"], state["status"])
```

## Rules for the agent loop

- One goal; no site-specific hardcoded field values.
- Only execute the selected operation's target (observed index).
- Never emit selectors, coordinates, or executable JS from the model.
- Verify outcomes independently; `DONE` is not proof.
- Keep credentials in `.env` only.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| Missing API keys | Fill `.env` from `.env.example` |
| Chrome not connected | `uv run browser-harness --doctor` |
| `uv: command not found` | `export PATH="$HOME/.local/bin:$PATH"` |
| Package not installed | `git clone https://github.com/browser-use/jev-ultrafast.git ~/.local/share/jev-ultrafast && cd ~/.local/share/jev-ultrafast && uv sync` |
