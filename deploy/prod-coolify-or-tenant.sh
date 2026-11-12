#!/usr/bin/env bash
# PROD entrypoint vanaf CI (SSH): Coolify heeft voorrang; legacy deploy-tenant alleen als 5432 vrij is.
set -euo pipefail
exec 2>&1

TENANT_DIR="${APP_DIR:-${TENANT_DIR:-/home/ubuntu/nexasuite}}"
DEPLOY_USER="${SSH_DEPLOY_USER:-${DEPLOY_USER:-ubuntu}}"
GIT_REF="${GIT_REF:?GIT_REF (tag) verplicht}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
DEPLOY_SCRIPT="${DEPLOY_SCRIPT:-/tmp/nexa-deploy-tenant-ci.sh}"
COOLIFY_WEBHOOK="${COOLIFY_WEBHOOK:-}"
COOLIFY_TOKEN="${COOLIFY_TOKEN:-}"
export COMPOSE_PROJECT_NAME=nexa

echo "=== PROD deploy ==="
echo "Host: $(hostname) user=$(id -un)"
echo "TENANT_DIR: $TENANT_DIR"
echo "GIT_REF: $GIT_REF"

coolify_up=false
if docker ps --format '{{.Names}}' 2>/dev/null | grep -qiE '^coolify$|coolify-proxy'; then
  coolify_up=true
fi

port_5432_busy=false
if ss -ltn 2>/dev/null | grep -qE ':5432\b' || netstat -ltn 2>/dev/null | grep -qE ':5432\b'; then
  port_5432_busy=true
fi

echo "Coolify containers: $coolify_up | 5432 busy: $port_5432_busy"
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Ports}}' 2>/dev/null | head -30 || true

trigger_coolify_webhook() {
  if [[ -z "$COOLIFY_WEBHOOK" || -z "$COOLIFY_TOKEN" ]]; then
    return 1
  fi
  local url="$COOLIFY_WEBHOOK"
  if [[ "$url" != *force=* ]]; then
    if [[ "$url" == *\?* ]]; then
      url="${url}&force=true"
    else
      url="${url}?force=true"
    fi
  fi
  echo "==> Coolify deploy webhook (POST)…"
  # Coolify v4: /api/v1/deploy is POST-only; GET → 403/405.
  local body code
  body="$(mktemp)"
  code="$(curl --silent --show-error --output "$body" --write-out '%{http_code}' \
    --request POST "$url" \
    --header "Authorization: Bearer ${COOLIFY_TOKEN}" \
    --header "Accept: application/json" \
    --header "Content-Type: application/json" \
    || true)"
  echo "HTTP ${code}"
  head -c 2000 "$body" || true
  echo
  rm -f "$body"
  if [[ "$code" != "200" && "$code" != "201" ]]; then
    echo "ERROR: Coolify webhook faalde (HTTP ${code})." >&2
    return 1
  fi
  echo "=== Coolify webhook geaccepteerd ==="
  return 0
}

verify_whatsapp_fix_in_backend() {
  local name
  name="$(docker ps --format '{{.Names}}' | grep -E 'backend-' | grep -vi coolify | head -1 || true)"
  if [[ -z "$name" ]]; then
    echo "Geen backend-container gevonden om te verifiëren."
    return 1
  fi
  echo "==> Verify WhatsApp-fix in $name"
  if docker exec "$name" grep -q 'sanitizeTemplateParameter' /var/www/html/app/Services/WhatsAppBusinessService.php 2>/dev/null; then
    echo "OK: sanitizeTemplateParameter aanwezig in live container."
    return 0
  fi
  if docker exec "$name" grep -q 'ShouldQueue' /var/www/html/app/Modules/NexaTaxi/Jobs/NotifyNewTaxiBookingJob.php 2>/dev/null; then
    echo "WAARSCHUWING: NotifyNewTaxiBookingJob heeft nog ShouldQueue (oude code)."
    return 1
  fi
  echo "WAARSCHUWING: kon WhatsApp-fix niet bevestigen in container."
  return 1
}

if trigger_coolify_webhook; then
  sleep 5
  verify_whatsapp_fix_in_backend || true
  exit 0
fi

if [[ "$coolify_up" == true || "$port_5432_busy" == true ]]; then
  echo "==> Coolify/legacy-poortconflict: géén tweede compose-stack op 5432."
  echo "    Zet Coolify Settings → Advanced → API Access = Enabled + secrets COOLIFY_WEBHOOK/TOKEN,"
  echo "    of klik Redeploy in Coolify (panel) op branch main / tag ${GIT_REF}."

  # Alleen Coolify-backends (backend-<uuid>-…); skip legacy nexa-backend-*.
  backends="$(docker ps -a --format '{{.Names}}' | grep -E '^backend-[a-z0-9]+-' | grep -vi coolify || true)"
  primary=""
  if [[ -n "$backends" ]]; then
    echo "==> Coolify backend containers:"
    echo "$backends"
    while IFS= read -r name; do
      [[ -z "$name" ]] && continue
      [[ -z "$primary" ]] && primary="$name"
      status="$(docker inspect -f '{{.State.Status}} {{if .State.Health}}{{.State.Health.Status}}{{else}}nohealth{{end}}' "$name" 2>/dev/null || echo 'missing')"
      echo "    $name → $status"
      nets="$(docker inspect -f '{{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$name" 2>/dev/null || true)"
      echo "    networks: ${nets:-<geen>}"
      # Traefik moet op hetzelfde UUID-app-netwerk zitten als de backend (niet alleen 'coolify').
      app_net="$(echo "$nets" | tr ' ' '\n' | grep -E '^[a-z0-9]{20,}$' | head -1 || true)"
      if [[ -n "$app_net" ]] && docker ps --format '{{.Names}}' | grep -qx 'coolify-proxy'; then
        proxy_nets="$(docker inspect -f '{{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}' coolify-proxy 2>/dev/null || true)"
        echo "    coolify-proxy networks: ${proxy_nets:-<geen>}"
        if [[ "$proxy_nets" != *"$app_net"* ]]; then
          echo "==> docker network connect $app_net coolify-proxy"
          docker network connect "$app_net" coolify-proxy 2>/dev/null || true
        fi
      fi
      echo "==> docker restart $name"
      docker restart "$name" || true
    done <<< "$backends"
  else
    echo "Geen Coolify backend-* containers gevonden (docker ps -a)."
  fi

  if docker ps --format '{{.Names}}' | grep -qx 'coolify-proxy'; then
    echo "==> docker restart coolify-proxy"
    docker restart coolify-proxy || true
  fi

  echo "==> Lokale backend-check (host :8085)…"
  local8085="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 10 http://127.0.0.1:8085/up 2>/dev/null || echo '000')"
  echo "    http://127.0.0.1:8085/up → HTTP ${local8085}"

  echo "==> Wacht 25s op Traefik/backend…"
  sleep 25
  for host in nexasuite.nl www.nexasuite.nl nexataxi.nl; do
    code="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 15 "https://${host}/" || echo '000')"
    echo "    https://${host}/ → HTTP ${code}"
  done

  uuid="$(echo "$primary" | grep -oE '[a-z0-9]{20,}' | head -1 || true)"
  echo "Afgeleide resource uuid: ${uuid:-<geen>}"
  if [[ -n "$uuid" && -d "/data/coolify/applications/${uuid}" ]]; then
    echo "==> Coolify application dir: /data/coolify/applications/${uuid}"
    ls -la "/data/coolify/applications/${uuid}" | head -20 || true
  fi

  verify_whatsapp_fix_in_backend || true

  ns="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 15 https://nexasuite.nl/ || echo '000')"
  if [[ "$ns" =~ ^(200|301|302)$ ]]; then
    echo "=== PROD bereikbaar na Traefik/backend-herstel (HTTP ${ns}) ==="
    echo "    Voor CI-deploys: Coolify → Settings → Advanced → API Access = Enabled"
    exit 0
  fi

  echo "ERROR: Coolify API is disabled én sites blijven 503 (nexasuite.nl HTTP ${ns})." >&2
  echo "FIX NU in Coolify panel:" >&2
  echo "  1) Settings → Advanced → API Access = Enabled (Save)" >&2
  echo "  2) Open de Nexa Suite app → Redeploy (force)" >&2
  echo "  3) Domains: https://nexasuite.nl:8000,https://www.nexasuite.nl:8000" >&2
  echo "  4) Daarna Deploy PROD opnieuw in GitHub Actions" >&2
  exit 1
fi

# Legacy pad: Hostinger zonder Coolify
if [[ ! -d "$TENANT_DIR/.git" ]]; then
  echo "ERROR: Geen git-repo in TENANT_DIR ($TENANT_DIR)" >&2
  exit 1
fi

SCRIPT=""
if [[ -n "${DEPLOY_SCRIPT:-}" && -f "$DEPLOY_SCRIPT" ]]; then
  SCRIPT="$DEPLOY_SCRIPT"
elif [[ -f "$TENANT_DIR/deploy/deploy-tenant.sh" ]]; then
  SCRIPT="$TENANT_DIR/deploy/deploy-tenant.sh"
fi
if [[ -z "$SCRIPT" ]]; then
  echo "ERROR: Geen deploy-script" >&2
  exit 1
fi

echo "==> Legacy deploy-tenant: $SCRIPT"
TRACE_LOG="/tmp/nexa-deploy-trace-${GIT_REF//[^a-zA-Z0-9_.-]/_}.log"
DEPLOY_ENV=(
  TENANT_DIR="$TENANT_DIR"
  GIT_REF="$GIT_REF"
  GIT_REMOTE="$GIT_REMOTE"
  COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.deploy.yml}"
  LARAVEL_SERVICE="${LARAVEL_SERVICE:-backend}"
  DEPLOY_USER="$DEPLOY_USER"
  REQUIRE_COMPOSE_V2=1
  DEPLOY_DEBUG=1
  DEPLOY_NO_TEE=1
  COMPOSE_PROJECT_NAME=nexa
)

set +e
if [[ "$(id -un)" != "$DEPLOY_USER" ]] && sudo -n -u "$DEPLOY_USER" true 2>/dev/null; then
  sudo -u "$DEPLOY_USER" -H env "${DEPLOY_ENV[@]}" bash -x "$SCRIPT" 2>&1 | tee "$TRACE_LOG"
else
  env "${DEPLOY_ENV[@]}" bash -x "$SCRIPT" 2>&1 | tee "$TRACE_LOG"
fi
code="${PIPESTATUS[0]}"
set -e
if [[ "$code" != "0" ]]; then
  echo "ERROR: deploy-tenant.sh exit ${code}"
  tail -n 80 "$TRACE_LOG" 2>/dev/null || true
  exit "$code"
fi
echo "=== PROD deploy afgerond (legacy) ==="
