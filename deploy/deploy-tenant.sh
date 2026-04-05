#!/usr/bin/env bash
# Nexa multi-tenant deploy (CyberPanel / LiteSpeed, Docker).
# Kopieer naar /usr/local/bin/deploy-<tenant>.sh, pas de config hieronder aan, chmod +x.
#
# Vereist: draait als gebruiker nexas4479 (geen root).
# GitHub self-hosted runner: óf de runner-service draait als nexas4479 en roept dit script
# direct aan (zonder sudo), óf gebruik sudo -u nexas4479 met NOPASSWD — zie
# deploy/github-runner-sudoers.example
#
set -euo pipefail

# --- Config per tenant (pas aan op de server) ---
TENANT_DIR="${TENANT_DIR:-/home/nexasuite.nl/taxiroyaal.nexasuite.nl}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-nexa-saas}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
DEPLOY_USER="${DEPLOY_USER:-nexas4479}"

if [[ "$(id -un)" != "$DEPLOY_USER" ]]; then
  echo "ERROR: Deploy moet als ${DEPLOY_USER} draaien (nu: $(id -un))." >&2
  echo "Runner als ${DEPLOY_USER} laten draaien, of: sudo -u ${DEPLOY_USER} \$0 (met NOPASSWD in sudoers)." >&2
  exit 1
fi

if [[ ! -d "$TENANT_DIR/.git" ]]; then
  echo "ERROR: Geen git-repo in TENANT_DIR: $TENANT_DIR" >&2
  exit 1
fi

cd "$TENANT_DIR"

echo "==> Git fetch + reset naar ${GIT_REMOTE}/${GIT_BRANCH}"
git fetch "$GIT_REMOTE"
git reset --hard "${GIT_REMOTE}/${GIT_BRANCH}"

echo "==> npm build (backend)"
cd "$TENANT_DIR/backend"
if [[ -f package-lock.json ]]; then
  npm ci
else
  npm install
fi
npm run build

echo "==> Docker Compose build + up"
cd "$TENANT_DIR"
docker compose -f "$COMPOSE_FILE" build --pull
docker compose -f "$COMPOSE_FILE" up -d

echo "==> Laravel migrations + cache"
docker compose -f "$COMPOSE_FILE" exec -T backend php artisan migrate --force
docker compose -f "$COMPOSE_FILE" exec -T backend php artisan optimize

echo "==> Deploy klaar ($(date -Iseconds))"
