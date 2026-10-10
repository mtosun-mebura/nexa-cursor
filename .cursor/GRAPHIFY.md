# Graphify — blauwdruk naslag

Project: `nexa-cursor`  
Graph-output: `graphify-out/` (lokaal, gitignored)

Zorg dat de CLI in je PATH staat:

```bash
export PATH="$HOME/.local/bin:$PATH"
cd /Users/tosun/Projecten/nexa-saas/nexa-cursor
```

---

## Blauwdruk bekijken

### Interactieve graph (beste overzicht)

```bash
open graphify-out/graph.html
```

### Tekstrapport

```bash
open graphify-out/GRAPH_REPORT.md
# of in terminal:
less graphify-out/GRAPH_REPORT.md
```

### Wiki (artikelen per community)

```bash
open graphify-out/wiki/index.md
```

### CLI-query (geen browser)

```bash
graphify query "ContractPortalController"
graphify god-nodes --top 10
graphify path "ContractPortalController" "RideStop"
graphify explain "RideRequest"
graphify affected "ContractPortalController"
```

In Cursor/Claude chat: “toon de graphify blauwdruk” of `/graphify`.

---

## Bouwen / verversen

### Code-only (geen API-key) — aanbevolen

```bash
# NexaTaxi backend
graphify extract backend/app/Modules/NexaTaxi --code-only --out .

# Mobile app (aparte extract, daarna mergen)
graphify extract mobile-app/src --code-only --out /tmp/graphify-mobile
graphify merge-graphs graphify-out/graph.json /tmp/graphify-mobile/graphify-out/graph.json --out graphify-out/graph.json
graphify cluster-only . --no-label
```

### Na codewijzigingen (goedkoop, AST)

```bash
graphify update .
```

### Hooks (auto-update na commit/checkout)

```bash
graphify hook status
graphify hook install    # eenmalig
graphify hook uninstall
```

### Met LLM (semantiek + community-namen)

Zet `ANTHROPIC_API_KEY` of `OPENAI_API_KEY`, daarna extract **zonder** `--code-only` / cluster **zonder** `--no-label`.

---

## Handige exports

```bash
graphify export wiki --graph graphify-out/graph.json
graphify export html --graph graphify-out/graph.json
graphify tree --graph graphify-out/graph.json   # collapsible tree HTML
```

---

## Meer info

Volledige AI-setup (skills, rules, Claude): [AI-SETUP.md](AI-SETUP.md)  
Agent-index: [../AGENTS.md](../AGENTS.md)
