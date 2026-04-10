#!/usr/bin/env bash
# Nexa SaaS deploy (CyberPanel / nginx / Docker / Laravel).
# Standaard app-map: /home/nexasuite.nl/apps/saas/current
# CI: .github/workflows/deploy-saas.yml roept dit script aan na checkout.
#
# DEPLOY_USER moet de Unix-user zijn die TENANT_DIR (incl. .git) mag schrijven — meestal de
# eigenaar van die map. Bij "cannot open .git/FETCH_HEAD: Permission denied" klopt DEPLOY_USER
# niet bij de bestandseigenaar (chown map of zet DEPLOY_USER via env / GitHub variable).
#
# compose: docker-compose met fallback naar docker compose; Laravel service: backend.
#
set -euo pipefail

# --- Config per tenant ---
TENANT_DIR="${TENANT_DIR:-/home/nexasuite.nl/apps/saas/current}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-nexa-saas}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
DEPLOY_USER="${DEPLOY_USER:-mtosun}"
LARAVEL_SERVICE="${LARAVEL_SERVICE:-backend}"
BACKEND_DIR="${BACKEND_DIR:-$TENANT_DIR/backend}"

_user_allowed_for_deploy() {
  local u="$1" entry
  [[ "$u" == "$DEPLOY_USER" ]] && return 0
  [[ -z "${EXTRA_DEPLOY_USERS:-}" ]] && return 1
  local IFS=,
  for entry in $EXTRA_DEPLOY_USERS; do
    entry="${entry#"${entry%%[![:space:]]*}"}"
    entry="${entry%"${entry##*[![:space:]]}"}"
    [[ -n "$entry" && "$entry" == "$u" ]] && return 0
  done
  return 1
}

_compose() {
  if command -v docker-compose >/dev/null 2>&1; then
    docker-compose -f "$COMPOSE_FILE" "$@"
  else
    docker compose -f "$COMPOSE_FILE" "$@"
  fi
}

if [[ "$(id -un)" == "root" ]]; then
  echo "==> Running as root; re-exec as ${DEPLOY_USER}"
  exec sudo -u "$DEPLOY_USER" -H -- "$0" "$@"
fi

if ! _user_allowed_for_deploy "$(id -un)"; then
  echo "ERROR: Deploy moet als ${DEPLOY_USER} draaien (nu: $(id -un))." >&2
  echo "Zet DEPLOY_USER goed, laat de runner als ${DEPLOY_USER} draaien," >&2
  echo "of zet EXTRA_DEPLOY_USERS als deze user deploy mag." >&2
  exit 1
fi

if [[ ! -d "$TENANT_DIR/.git" ]]; then
  echo "ERROR: Geen git-repo in TENANT_DIR: $TENANT_DIR" >&2
  exit 1
fi

if [[ ! -d "$BACKEND_DIR" ]]; then
  echo "ERROR: Backend directory niet gevonden: $BACKEND_DIR" >&2
  exit 1
fi

cd "$TENANT_DIR"

_git() {
  git -c "safe.directory=$TENANT_DIR" "$@"
}

echo "==> Git fetch + reset naar ${GIT_REMOTE}/${GIT_BRANCH}"
_git fetch "$GIT_REMOTE"
_git reset --hard "${GIT_REMOTE}/${GIT_BRANCH}"

echo "==> Frontend build (Vite in backend/)"
if [[ -f "$BACKEND_DIR/package.json" ]]; then
  if command -v npm >/dev/null 2>&1; then
    (
      cd "$BACKEND_DIR"
      if [[ -f package-lock.json ]]; then
        npm ci
      else
        npm install
      fi
      npm run build
    )
  else
    bash -lic "set -e; cd $(printf %q "$BACKEND_DIR"); if [[ -f package-lock.json ]]; then npm ci; else npm install; fi; npm run build"
  fi
else
  echo "==> Geen package.json in $BACKEND_DIR, frontend build wordt overgeslagen"
fi

echo "==> Docker Compose pull/build/up"
cd "$TENANT_DIR"
_compose pull || true
_compose build --pull
_compose up -d

echo "==> Wachten tot Laravel service beschikbaar is"
sleep 5

echo "==> Laravel migrations + cache"
_compose exec -T "$LARAVEL_SERVICE" php artisan migrate --force
_compose exec -T "$LARAVEL_SERVICE" php artisan config:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan cache:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan route:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan view:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan optimize

echo "==> Deploy klaar ($(date -Iseconds))"