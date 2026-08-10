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
  echo "==> Coolify deploy webhook…"
  curl --fail --show-error --silent --request GET "$url" \
    --header "Authorization: Bearer ${COOLIFY_TOKEN}"
  echo
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
  echo "    Zet GitHub secrets COOLIFY_WEBHOOK + COOLIFY_TOKEN voor automatische Coolify-deploy,"
  echo "    of klik Redeploy in Coolify (panel) op branch main / tag ${GIT_REF}."
  if verify_whatsapp_fix_in_backend; then
    echo "=== PROD lijkt al de WhatsApp-fix te draaien ==="
    exit 0
  fi
  # Laatste redmiddel: forceer Coolify rebuild door application-UUID uit containernaam.
  uuid="$(docker ps --format '{{.Names}}' | grep -E 'backend-' | grep -vi coolify | head -1 | grep -oE '[a-z0-9]{20,}' | head -1 || true)"
  echo "Afgeleide resource uuid: ${uuid:-<geen>}"
  if [[ -n "$uuid" && -d "/data/coolify/applications/${uuid}" ]]; then
    echo "==> Coolify application dir: /data/coolify/applications/${uuid}"
    ls -la "/data/coolify/applications/${uuid}" | head -20 || true
  fi
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
