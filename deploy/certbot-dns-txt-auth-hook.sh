#!/usr/bin/env bash
# Certbot --manual-auth-hook: toont TXT-waarde en wacht tot die zichtbaar is via dig.
# Env: CERTBOT_DOMAIN, CERTBOT_VALIDATION
set -euo pipefail

CHALLENGE_LOG="/tmp/nexa-acme-dns-challenge.txt"

log_both() {
  # Certbot vangt stdout vaak op — altijd naar stderr + vast logbestand.
  printf '%s\n' "$*" | tee -a "$CHALLENGE_LOG" >&2
}

domain="${CERTBOT_DOMAIN#\*\.}"
challenge_host="_acme-challenge.${domain}"
value="${CERTBOT_VALIDATION}"
interval="${DNS_TXT_POLL_INTERVAL:-10}"
max_wait="${DNS_TXT_WAIT_SEC:-900}"

{
  echo ""
  echo "========================================"
  echo "$(date -Iseconds)"
  log_both ">>> ACME DNS TXT toevoegen (Cloudflare: DNS → Add record)"
  log_both "    Type:    TXT"
  log_both "    Name:    _acme-challenge"
  log_both "    Content: ${value}"
  log_both "    Host FQDN: ${challenge_host}"
  log_both ""
  log_both "    Check: dig TXT ${challenge_host} +short @kai.ns.cloudflare.com"
  log_both "    Log:   tail -f ${CHALLENGE_LOG}"
  log_both ""
} >&2

_dns_query() {
  local host="$1"
  dig +short TXT "$host" 2>/dev/null || true
  local ns
  for ns in $(dig +short NS "$domain" 2>/dev/null | head -3); do
    dig +short TXT "$host" "@${ns}" 2>/dev/null || true
  done
}

_value_visible() {
  local line
  while IFS= read -r line; do
    line="${line//\"/}"
    line="${line// /}"
    if [[ "$line" == *"$value"* ]]; then
      return 0
    fi
  done < <(_dns_query "$challenge_host")
  return 1
}

if ! command -v dig >/dev/null 2>&1; then
  log_both "ERROR: dig niet gevonden — installeer dnsutils"
  exit 1
fi

deadline=$(( $(date +%s) + max_wait ))
attempt=0
while [[ $(date +%s) -lt deadline ]]; do
  attempt=$((attempt + 1))

  if _value_visible; then
    log_both ">>> DNS OK — TXT zichtbaar voor ${challenge_host}"
    exit 0
  fi

  if [[ $((attempt % 3)) -eq 1 ]]; then
    log_both "    Wachten op DNS… (${attempt}×${interval}s, max ${max_wait}s) — voeg TXT toe in Cloudflare als je dat nog niet deed"
    _dns_query "$challenge_host" | head -5 | sed 's/^/    dig: /' | tee -a "$CHALLENGE_LOG" >&2 || log_both "    dig: (nog geen TXT)"
  fi
  sleep "$interval"
done

log_both "ERROR: TXT niet zichtbaar binnen ${max_wait}s bij ${challenge_host}"
log_both "Cloudflare: Name moet _acme-challenge zijn (niet het volledige domein dubbel)"
exit 1
