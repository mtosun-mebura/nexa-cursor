#!/usr/bin/env bash
# Nexa SaaS deploy (CyberPanel / nginx / Docker / Laravel).
# Standaard app-map: /home/nexasuite.nl/apps/saas/current
# CI: .github/workflows/deploy-saas.yml roept dit script aan na checkout.
#
# DEPLOY_USER moet TENANT_DIR en .git/objects kunnen schrijven. Voor git reset: storage/bootstrap/cache
# worden vóór fetch teruggechown (container root of sudo), anders blijven www-data-bestanden
# staan en faalt "unable to unlink".
#
# Docker: deploy-user moet in groep 'docker' zitten (socket). Daarna runner-service herstarten.
# compose: bij voorkeur 'docker compose' (v2), anders docker-compose v1. Laravel service: backend.
# Opruiming vóór build: alleen veilige prune (builder + dangling images + gestopte containers), géén volumes.
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
  if docker compose version >/dev/null 2>&1; then
    docker compose -f "$COMPOSE_FILE" "$@"
  elif command -v docker-compose >/dev/null 2>&1; then
    docker-compose -f "$COMPOSE_FILE" "$@"
  else
    echo "ERROR: Geen 'docker compose' (plugin) of docker-compose in PATH." >&2
    exit 1
  fi
}

# Zonder socket-rechten faalt alles stil of met Python-stacktraces — vroeg en duidelijk stoppen.
_require_docker_socket() {
  if docker info >/dev/null 2>&1; then
    return 0
  fi
  echo "" >&2
  echo "ERROR: Docker daemon niet bereikbaar voor user $(id -un) (meestal Permission denied op /var/run/docker.sock)." >&2
  echo "Oplossing op de server (eenmalig), daarna runner opnieuw starten zodat de groep actief is:" >&2
  echo "  sudo usermod -aG docker $(id -un)" >&2
  echo "  id $(id -un)    # moet 'docker' tonen in groepen" >&2
  echo "  # herstart de Actions-runner (anders ziet hij groep 'docker' niet):" >&2
  echo "  #   sudo systemctl restart <actions.runner.*.service>   # of ./svc.sh stop && ./svc.sh start in runner-map" >&2
  echo "" >&2
  exit 1
}

# Geen `docker system prune --volumes` / geen `image prune -a`: named volumes en getagde images blijven intact.
_docker_safe_prune() {
  echo "==> Docker: veilige schijfruimte (dangling build-cache, dangling images, gestopte containers — géén volumes)"
  docker builder prune -f 2>/dev/null || true
  docker image prune -f 2>/dev/null || true
  docker container prune -f 2>/dev/null || true
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

_require_docker_socket

_git() {
  git -c "safe.directory=$TENANT_DIR" "$@"
}

# Draaiende backend + restart-loop: entrypoint.sh chown't storage naar www-data bij elke start.
# Daardoor kan chown via compose run direct weer teniet worden gedaan vóór git reset → eerst stoppen.
_stop_backend_for_git_reset() {
  echo "==> Stop ${LARAVEL_SERVICE} (geen entrypoint-chown naar www-data tijdens git reset)"
  _compose stop -t 60 "$LARAVEL_SERVICE" 2>/dev/null || true
  sleep 3
  if _compose ps "$LARAVEL_SERVICE" 2>/dev/null | grep -qiE 'restarting|starting'; then
    echo "==> ${LARAVEL_SERVICE} blijft herstarten; compose kill"
    _compose kill "$LARAVEL_SERVICE" 2>/dev/null || true
    sleep 2
  fi
}

# Laravel/Docker: storage/bootstrap/cache zijn vaak www-data. Git reset moet als deploy-user kunnen
# unlinken — chown/chmod als root in een compose run (bind mounts), ná stop hierboven.
_fix_backend_tree_for_git_reset() {
  local uid gid fix_cmd
  uid=$(id -u)
  gid=$(id -g)
  # Eén regel voor exec/run -c (paden = volume-mounts in docker-compose.prod)
  fix_cmd="for d in /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build; do [ ! -e \"\$d\" ] && continue; chown -R ${uid}:${gid} \"\$d\" 2>/dev/null || true; chmod -R ug+rwX \"\$d\" 2>/dev/null || true; done"

  echo "==> Laravel writable dirs → ${uid}:${gid} + ug+rwX (Docker root; geen TTY-sudo)"
  if _compose exec -T -u root "$LARAVEL_SERVICE" sh -c "$fix_cmd"; then
    return 0
  fi

  echo "==> exec mislukte of container uit; compose run --rm met /bin/sh (geen entrypoint.sh → geen www-data reset vóór chown)"
  if _compose run --rm --no-deps -T -u root --entrypoint /bin/sh "$LARAVEL_SERVICE" -c "$fix_cmd"; then
    return 0
  fi

  echo "==> Fallback: passwordless host-sudo (sudo -n)"
  if command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
    for d in "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache" "$BACKEND_DIR/public/build"; do
      [[ -e "$d" ]] || continue
      sudo -n chown -R "${uid}:${gid}" "$d" || true
      sudo -n chmod -R ug+rwX "$d" || true
    done
    return 0
  fi

  echo "ERROR: Kon storage/bootstrap/cache niet vrijmaken voor git (docker exec/run faalde; geen sudo -n)." >&2
  echo "TIP: image bouwen: docker compose -f $COMPOSE_FILE build ${LARAVEL_SERVICE}" >&2
  echo "TIP: bij Permission denied op de socket eerst: usermod -aG docker $(id -un) + runner herstarten." >&2
  exit 1
}

_stop_backend_for_git_reset
_fix_backend_tree_for_git_reset

# fetch schrijft naar .git/objects; na eerdere root/docker-deploys kan .git nog root:www-data zijn.
_fix_git_dir_ownership() {
  local uid gid
  uid=$(id -u)
  gid=$(id -g)
  if [[ -w "$TENANT_DIR/.git/objects" ]] 2>/dev/null; then
    return 0
  fi
  echo "==> Eigenaar $TENANT_DIR/.git → ${uid}:${gid} (fix insufficient permission voor .git/objects)"
  if command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
    sudo -n chown -R "${uid}:${gid}" "$TENANT_DIR/.git" || true
  else
    chown -R "${uid}:${gid}" "$TENANT_DIR/.git" 2>/dev/null || true
  fi
  if [[ ! -w "$TENANT_DIR/.git/objects" ]]; then
    echo "ERROR: Kan niet schrijven in $TENANT_DIR/.git/objects (git fetch faalt)." >&2
    echo "Eenmalig op de server: sudo chown -R $(id -un):$(id -gn) $TENANT_DIR/.git" >&2
    exit 1
  fi
}

_fix_git_dir_ownership

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
_docker_safe_prune
_compose build --pull
_compose up -d

echo "==> Wachten tot Laravel service beschikbaar is"
sleep 5

echo "==> Laravel migrations + basis seed + cache"
_compose exec -T "$LARAVEL_SERVICE" php artisan migrate --force
_compose exec -T "$LARAVEL_SERVICE" php artisan db:seed --class=Database\\Seeders\\ApplicationBootstrapSeeder --force
_compose exec -T "$LARAVEL_SERVICE" php artisan config:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan cache:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan route:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan view:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan optimize

echo "==> Deploy klaar ($(date -Iseconds))"