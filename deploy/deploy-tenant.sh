#!/usr/bin/env bash
# Nexa SaaS deploy (CyberPanel / LiteSpeed, Docker).
# Standaard app-map: /home/nexasuite.nl/apps/saas/current (zie TENANT_DIR hieronder).
# CI: .github/workflows/deploy-saas.yml roept dit script aan na checkout.
#
# Draait als DEPLOY_USER (standaard nexas4479). Als je per ongeluk `sudo script.sh`
# (root) gebruikt: wordt automatisch opnieuw gestart als DEPLOY_USER — root heeft geen
# npm in PATH en git/docker horen bij de tenant-user.
# GitHub runner: zie deploy/github-runner-sudoers.example en .github/workflows/deploy-saas.yml
# Optioneel: EXTRA_DEPLOY_USERS (komma-gescheiden) — users die deploy mogen draaien zonder
# sudo (alleen als ze git/docker op TENANT_DIR mogen; bijv. repo variable in GitHub).
#
set -euo pipefail

# --- Config per tenant (pas aan op de server) ---
TENANT_DIR="${TENANT_DIR:-/home/nexasuite.nl/apps/saas/current}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-nexa-saas}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
DEPLOY_USER="${DEPLOY_USER:-nexas4479}"

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

if [[ "$(id -un)" == "root" ]]; then
  echo "==> Running as root; re-exec as ${DEPLOY_USER} (npm/docker/repo-eigenaar)"
  exec sudo -u "$DEPLOY_USER" -H -- "$0" "$@"
fi

if ! _user_allowed_for_deploy "$(id -un)"; then
  echo "ERROR: Deploy moet als ${DEPLOY_USER} draaien (nu: $(id -un))." >&2
  echo "Runner als ${DEPLOY_USER} laten draaien, passwordless sudo (zie deploy/github-runner-sudoers.example)," >&2
  echo "of zet repo-variable EXTRA_DEPLOY_USERS (komma-gescheiden) als deze user deploy mag." >&2
  exit 1
fi

if [[ ! -d "$TENANT_DIR/.git" ]]; then
  echo "ERROR: Geen git-repo in TENANT_DIR: $TENANT_DIR" >&2
  exit 1
fi

cd "$TENANT_DIR"

# Git "dubious ownership": treedt op als dit script als andere user draait dan de map-eigenaar
# (bijv. root na `sudo script.sh` i.p.v. `sudo -u nexas4479`). Zonder globale safe.directory te zetten:
_git() {
  git -c "safe.directory=$TENANT_DIR" "$@"
}

echo "==> Git fetch + reset naar ${GIT_REMOTE}/${GIT_BRANCH}"
_git fetch "$GIT_REMOTE"
_git reset --hard "${GIT_REMOTE}/${GIT_BRANCH}"

echo "==> npm build (backend)"
# Login-shell: bij Node via nvm/fnm staat npm vaak niet in het PATH van niet-interactieve scripts.
BACKEND_DIR="$TENANT_DIR/backend"
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
  # -l/-i: profile + .bashrc (nvm/fnm staat vaak in .bashrc)
  bash -lic "set -e; cd $(printf %q "$BACKEND_DIR"); if [[ -f package-lock.json ]]; then npm ci; else npm install; fi; npm run build"
fi

echo "==> Docker Compose build + up"
cd "$TENANT_DIR"
docker compose -f "$COMPOSE_FILE" build --pull
docker compose -f "$COMPOSE_FILE" up -d

echo "==> Laravel migrations + cache"
docker compose -f "$COMPOSE_FILE" exec -T backend php artisan migrate --force
docker compose -f "$COMPOSE_FILE" exec -T backend php artisan optimize

echo "==> Deploy klaar ($(date -Iseconds))"
