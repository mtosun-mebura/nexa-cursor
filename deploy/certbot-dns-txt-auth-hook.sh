#!/usr/bin/env bash
# Certbot --manual-auth-hook: wacht tot de gevraagde TXT zichtbaar is (geen Enter meer nodig).
# Env: CERTBOT_DOMAIN, CERTBOT_VALIDATION
set -euo pipefail

domain="${CERTBOT_DOMAIN#\*\.}"
challenge_host="_acme-challenge.${domain}"
value="${CERTBOT_VALIDATION}"
interval="${DNS_TXT_POLL_INTERVAL:-10}"
max_wait="${DNS_TXT_WAIT_SEC:-900}"

echo ""
echo ">>> DNS TXT-record toevoegen bij je provider"
echo "    Host:   ${challenge_host}"
echo "    Waarde: ${value}"
echo ""
echo "    Vaak bij de provider:"
echo "      Type TXT, naam/subdomein: _acme-challenge"
echo "      Zone/domein: ${domain}"
echo "    (Twee challenges = twee TXT-records op dezelfde host, niet de vorige verwijderen.)"
echo ""
echo "    Check: dig TXT ${challenge_host} +short"
echo ""

if ! command -v dig >/dev/null 2>&1; then
  echo "ERROR: dig niet gevonden — installeer dnsutils of voeg TXT handmatig toe en wacht." >&2
  sleep 60
  exit 0
fi

deadline=$(( $(date +%s) + max_wait ))
attempt=0
while [[ $(date +%s) -lt deadline ]]; do
  attempt=$((attempt + 1))
  found=""
  while IFS= read -r line; do
    line="${line//\"/}"
    line="${line// /}"
    if [[ "$line" == *"$value"* ]]; then
      found=1
      break
    fi
  done < <(dig +short TXT "$challenge_host" 2>/dev/null || true)

  if [[ -n "$found" ]]; then
    echo ">>> DNS OK (${challenge_host} bevat de TXT-waarde)"
    exit 0
  fi

  if [[ $((attempt % 3)) -eq 1 ]]; then
    echo "    Wachten op DNS-propagatie... (${attempt}×${interval}s, max ${max_wait}s)"
    dig +short TXT "$challenge_host" 2>/dev/null | head -3 | sed 's/^/    dig: /' || echo "    dig: (nog geen antwoord)"
  fi
  sleep "$interval"
done

echo "ERROR: TXT niet zichtbaar binnen ${max_wait}s bij ${challenge_host}" >&2
echo "Controleer hostnaam bij je DNS-provider en probeer: dig TXT ${challenge_host} +short @8.8.8.8" >&2
exit 1
