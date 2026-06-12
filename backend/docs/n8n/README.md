# n8n workflows (Nexa Suite)

## Productie-URL

n8n draait op **`https://automations.nexasuite.nl`** (niet `n8n.nexasuite.nl` — dat subdomein triggert Google Safe Browsing).

Webhook taxi-assistent:

`https://automations.nexasuite.nl/webhook/nexa-taxi-assistant`

Laravel default: `NEXA_TAXI_ASSISTANT_WEBHOOK_URL` in `.env`, of per tenant in admin → AI-chat instellingen.

## Import

Import `Nexa-Taxi-RAG-PostgreSQL-Assistant.json` in n8n en activeer de workflow.

## Chrome “Gevaarlijke site” (Safe Browsing)

1. **Subdomein zonder `n8n`** — gebruik `automations.nexasuite.nl` (DNS + SSL + n8n `WEBHOOK_URL` / `N8N_HOST`).
2. **Execution-pagina’s** — geen telefoonnummers in workflow-antwoorden (al verwijderd uit export).
3. **Geen `ngrok-skip-browser-warning`** in productie.

Na DNS-migratie:

- Werk n8n bij naar de laatste stabiele versie.
- Dien false-positive in: https://safebrowsing.google.com/safebrowsing/report_error/
- Of Google Search Console → Beveiligingsproblemen → Review aanvragen.

## Server (DNS/nginx)

1. DNS A-record: `automations.nexasuite.nl` → zelfde IP als de huidige n8n-server.
2. SSL-certificaat voor het nieuwe subdomein (Let's Encrypt).
3. n8n env: `N8N_HOST=automations.nexasuite.nl`, `WEBHOOK_URL=https://automations.nexasuite.nl/`.
4. Oude `n8n.nexasuite.nl` kan redirecten of uit DNS gehaald worden na migratie.

## Laravel-koppeling

Zie `backend/docs/AI-CHAT-RBAC-ARCHITECTURE.md` — o.a. `AI_CHAT_LARAVEL_API_URL` en n8n Variable `LARAVEL_API_URL`.
