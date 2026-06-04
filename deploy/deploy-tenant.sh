#!/usr/bin/env bash
# Nexa SaaS deploy (CyberPanel / nginx / Docker / Laravel).
# Standaard app-map: /home/nexasuite.nl/apps/saas/current
# CI test:  deploy-saas.yml → branch release/test (Proxmox, self-hosted runner).
# CI prod:  deploy-prod.yml → git-tag v* op main (AWS Lightsail, SSH).
#
# Artisan / Composer: draai in de backend-container, niet met `php artisan` op de host in
# TENANT_DIR/backend — daar staat geen vendor/ (die zit in de image onder /var/www/html).
# Voorbeeld: cd TENANT_DIR && docker compose -f docker-compose.deploy.yml exec backend php artisan …
#
# DEPLOY_USER moet TENANT_DIR en .git/objects kunnen schrijven. Voor git reset: storage/bootstrap/cache
# worden vóór fetch teruggechown (container root of sudo), anders blijven www-data-bestanden
# staan en faalt "unable to unlink".
#
# Docker: deploy-user moet in groep 'docker' zitten (socket). Daarna runner-service herstarten.
# compose: bij voorkeur 'docker compose' (v2), anders docker-compose v1. Laravel service: backend.
# Opruiming vóór build: alleen veilige prune (builder + dangling images + gestopte containers), géén volumes.
#
# Bind-mount TENANT_DIR/.env → container /var/www/html/.env vereist een gewoon bestand op de host.
# Als .env een map is (vaak door eerdere mislukte mount), faalt runc met "not a directory".
#
set -euo pipefail

_on_deploy_err() {
  local code=$1 line=$2
  echo "" >&2
  echo "ERROR: Deploy mislukt (exit ${code}) op regel ${line} van $0" >&2
  echo "TIP: zet DEBUG_DEPLOY=1 in GitHub repo variables voor bash -x in CI." >&2
  exit "${code}"
}
trap '_on_deploy_err $? $LINENO' ERR

if [[ "${DEPLOY_DEBUG:-}" == "1" || "${DEPLOY_DEBUG:-}" == "true" ]]; then
  set -x
fi

# --- Config per tenant ---
TENANT_DIR="${TENANT_DIR:-/home/nexasuite.nl/apps/saas/current}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-release/test}"
# Optioneel: exacte tag of commit voor productie (bijv. v1.2.3). Leeg = branch-deploy (test/CI).
GIT_REF="${GIT_REF:-}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.deploy.yml}"
DEPLOY_USER="${DEPLOY_USER:-mtosun}"
LARAVEL_SERVICE="${LARAVEL_SERVICE:-backend}"
BACKEND_DIR="${BACKEND_DIR:-$TENANT_DIR/backend}"

# Oude deploys / GitHub-variabele COMPOSE_FILE=docker-compose.prod.yml → nieuwe bestandsnaam.
_resolve_compose_file() {
  local requested="${COMPOSE_FILE:-docker-compose.deploy.yml}"
  if [[ -f "$TENANT_DIR/$requested" ]]; then
    COMPOSE_FILE="$requested"
    return 0
  fi
  for fallback in docker-compose.deploy.yml docker-compose.prod.yml; do
    if [[ -f "$TENANT_DIR/$fallback" ]]; then
      if [[ "$requested" != "$fallback" ]]; then
        echo "==> Compose: ${fallback} (was ingesteld: ${requested})"
      fi
      COMPOSE_FILE="$fallback"
      return 0
    fi
  done
  COMPOSE_FILE="$requested"
}

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

_compose_bin_label() {
  if docker compose version >/dev/null 2>&1; then
    echo "docker compose (v2 plugin)"
  elif command -v docker-compose >/dev/null 2>&1; then
    docker-compose --version 2>/dev/null | head -1 || echo "docker-compose"
  else
    echo "geen compose CLI"
  fi
}

_compose() {
  if docker compose version >/dev/null 2>&1; then
    docker compose -f "$COMPOSE_FILE" "$@"
    return
  fi
  if [[ "${REQUIRE_COMPOSE_V2:-}" == "1" || "${REQUIRE_COMPOSE_V2:-}" == "true" ]]; then
    echo "ERROR: PROD vereist 'docker compose' v2 (plugin), maar alleen v1 of geen compose gevonden." >&2
    echo "Op AWS Lightsail (eenmalig): bash $TENANT_DIR/deploy/install-docker-compose-v2.sh" >&2
    exit 1
  fi
  if command -v docker-compose >/dev/null 2>&1; then
    docker-compose -f "$COMPOSE_FILE" "$@"
    return
  fi
  echo "ERROR: Geen 'docker compose' (v2) of docker-compose (v1) in PATH." >&2
  echo "AWS prod: bash deploy/install-docker-compose-v2.sh" >&2
  echo "Proxmox test: sudo apt-get install -y docker-compose  # v1 is voldoende" >&2
  exit 1
}

# Proxmox-test en AWS-prod gebruiken docker-compose.deploy.yml; oude compose v1 kent geen `include:`.
_preflight_compose_file() {
  local compose_path="$TENANT_DIR/$COMPOSE_FILE"
  if [[ ! -f "$compose_path" ]]; then
    echo "ERROR: Compose-bestand ontbreekt: $compose_path" >&2
    exit 1
  fi
  echo "==> Docker Compose CLI: $(_compose_bin_label)"
  echo "==> Compose-bestand: $compose_path"
  if grep -qE '^[[:space:]]*include:' "$compose_path" 2>/dev/null; then
    echo "ERROR: $COMPOSE_FILE gebruikt 'include:' — niet ondersteund op deze server (alleen docker compose v2)." >&2
    echo "Oplossing: pull/reset naar release/test met inline db in docker-compose.deploy.yml (geen include-blok)." >&2
    exit 1
  fi
  echo "==> Valideren compose-config..."
  _compose config >/dev/null
  echo "==> Compose-config OK"
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

# docker-compose 1.29.x faalt soms met KeyError 'ContainerConfig' bij `up -d` + recreate.
# down + up (zonder --volumes) maakt nieuwe containers; named volumes (Postgres-data) blijven.
_compose_up_deploy() {
  echo "==> Compose down (remove-orphans, volumes blijven behouden)"
  _compose down --remove-orphans 2>/dev/null || true
  echo "==> Compose up -d"
  _compose up -d
}

# docker-compose.deploy: ./.env moet een regulier bestand zijn (geen directory).
_ensure_compose_env_mount() {
  local env_path="$TENANT_DIR/.env"
  if [[ -d "$env_path" ]]; then
    echo "ERROR: $env_path is een map, geen bestand. Docker kan die niet bind-mounten op /var/www/html/.env." >&2
    echo "  (runc: not a directory / directory onto file)" >&2
    echo "Oplossing op de server (inhoud map meestal leeg of fout):" >&2
    echo "  rm -rf $(printf %q "$env_path")" >&2
    if [[ -f "$TENANT_DIR/.env.example" ]]; then
      echo "  cp $(printf %q "$TENANT_DIR/.env.example") $(printf %q "$env_path")" >&2
    fi
    echo "  # vul APP_KEY, DB_*, secrets; daarna deploy opnieuw." >&2
    exit 1
  fi
  if [[ ! -f "$env_path" ]]; then
    echo "ERROR: Ontbrekend bestand voor compose-mount: $(printf %q "$env_path")" >&2
    if [[ -f "$TENANT_DIR/.env.example" ]]; then
      echo "Maak aan met: cp $(printf %q "$TENANT_DIR/.env.example") $(printf %q "$env_path")" >&2
    fi
    echo "Vul daarna secrets; in CI kun je .env vóór deploy-tenant.sh schrijven." >&2
    exit 1
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

cd "$TENANT_DIR"

DEPLOY_LOG="${DEPLOY_LOG:-$TENANT_DIR/storage/logs/deploy-latest.log}"
if mkdir -p "$(dirname "$DEPLOY_LOG")" 2>/dev/null; then
  : >"$DEPLOY_LOG"
  exec > >(tee -a "$DEPLOY_LOG") 2>&1
fi

echo "==> Deploy gestart $(date -Iseconds)"
if [[ -n "$GIT_REF" ]]; then
  echo "    user=$(id -un) host=$(hostname) TENANT_DIR=$TENANT_DIR ref=${GIT_REF}"
else
  echo "    user=$(id -un) host=$(hostname) TENANT_DIR=$TENANT_DIR branch=${GIT_BRANCH}"
fi
echo "    log=${DEPLOY_LOG:-<geen logbestand>}"

_require_docker_socket
# Vóór elke `compose exec` / `compose run` (chown): anders faalt mount ./.env als dat een map is.
_ensure_compose_env_mount

_git() {
  git -c "safe.directory=$TENANT_DIR" "$@"
}

# Draaiende backend + restart-loop: entrypoint.sh chown't storage naar www-data bij elke start.
# Daardoor kan chown via compose run direct weer teniet worden gedaan vóór git reset → eerst stoppen.
_stop_backend_for_git_reset() {
  echo "==> Stop ${LARAVEL_SERVICE} (geen entrypoint-chown naar www-data tijdens git reset)"
  if ! _compose stop -t 60 "$LARAVEL_SERVICE"; then
    echo "==> compose stop gaf een waarschuwing (container was mogelijk al gestopt)"
  fi
  echo "==> Wacht 3s tot container gestopt is..."
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
  local uid gid fix_cmd d
  uid=$(id -u)
  gid=$(id -g)
  # Eén regel voor exec/run -c (paden = volume-mounts in docker-compose.deploy)
  fix_cmd="for d in /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build; do [ ! -e \"\$d\" ] && continue; chown -R ${uid}:${gid} \"\$d\" 2>/dev/null || true; chmod -R ug+rwX \"\$d\" 2>/dev/null || true; done"

  echo "==> Laravel writable dirs → ${uid}:${gid} + ug+rwX"
  if _compose ps -q "$LARAVEL_SERVICE" 2>/dev/null | grep -q .; then
    echo "==> Container draait nog; probeer compose exec -u root"
    if _compose exec -T -u root "$LARAVEL_SERVICE" sh -c "$fix_cmd"; then
      return 0
    fi
    echo "==> compose exec mislukt"
  else
    echo "==> Container gestopt; sla exec over"
  fi

  echo "==> compose run --rm (entrypoint /bin/sh) voor chown op volumes"
  if _compose run --rm --no-deps -T -u root --entrypoint /bin/sh "$LARAVEL_SERVICE" -c "$fix_cmd"; then
    return 0
  fi

  echo "==> compose run mislukt; host-paden direct (zonder container)"
  for d in "$BACKEND_DIR/storage" "$BACKEND_DIR/bootstrap/cache" "$BACKEND_DIR/public/build"; do
    [[ -e "$d" ]] || continue
    chown -R "${uid}:${gid}" "$d" 2>/dev/null || true
    chmod -R ug+rwX "$d" 2>/dev/null || true
  done
  if [[ -w "$BACKEND_DIR/storage" ]]; then
    echo "==> Host-chown op storage gelukt"
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
  echo "Als compose run faalde met mount .env: $(printf %q "$TENANT_DIR/.env") moet een bestand zijn, geen map (rm -rf + cp .env.example .env)." >&2
  echo "TIP: image bouwen: docker compose -f $COMPOSE_FILE build ${LARAVEL_SERVICE}" >&2
  echo "TIP: bij Permission denied op de socket eerst: usermod -aG docker $(id -un) + runner herstarten." >&2
  exit 1
}

_stop_backend_for_git_reset
_fix_backend_tree_for_git_reset

# fetch schrijft naar .git/objects; pack-bestanden kunnen root zijn terwijl .git/objects zelf van mtosun is.
_fix_git_dir_ownership() {
  local uid gid test_file alien
  uid=$(id -u)
  gid=$(id -g)
  test_file="$TENANT_DIR/.git/objects/.deploy-write-test-$$"

  _git_objects_writable() {
    touch "$test_file" 2>/dev/null && rm -f "$test_file" 2>/dev/null
  }

  # Niet vertrouwen op eigenaar van .git alleen: objects/pack/* is vaak nog root na docker compose run.
  alien=""
  if command -v find >/dev/null 2>&1; then
    alien="$(find "$TENANT_DIR/.git" ! -uid "$uid" -print -quit 2>/dev/null || true)"
  fi

  if [[ -z "$alien" ]] && _git_objects_writable; then
    return 0
  fi

  if [[ -n "$alien" ]]; then
    echo "==> .git bevat bestanden van een andere user (voorbeeld: ${alien})"
  else
    echo "==> .git/objects lijkt schrijfbaar maar git fetch faalde eerder — rechten herstellen"
  fi
  echo "==> chown -R $(id -un):$(id -gn) $TENANT_DIR/.git"

  if chown -R "${uid}:${gid}" "$TENANT_DIR/.git" 2>/dev/null; then
    :
  elif command -v sudo >/dev/null 2>&1 && sudo -n chown -R "${uid}:${gid}" "$TENANT_DIR/.git" 2>/dev/null; then
    :
  else
    echo "ERROR: chown op $TENANT_DIR/.git mislukt (root-owned pack/objecten?)." >&2
    echo "Eenmalig op de server (als root):" >&2
    echo "  sudo chown -R $(id -un):$(id -gn) $TENANT_DIR/.git" >&2
    echo "Of: sudo bash $TENANT_DIR/deploy/fix-git-ownership.sh" >&2
    exit 1
  fi

  alien="$(find "$TENANT_DIR/.git" ! -uid "$uid" -print -quit 2>/dev/null || true)"
  if [[ -n "$alien" ]]; then
    echo "ERROR: Na chown nog steeds vreemde eigenaar: ${alien}" >&2
    echo "Gebruik root: sudo chown -R $(id -un):$(id -gn) $TENANT_DIR/.git" >&2
    exit 1
  fi

  if ! _git_objects_writable; then
    echo "ERROR: Kan nog steeds niet schrijven in $TENANT_DIR/.git/objects." >&2
    exit 1
  fi
}

_fix_git_dir_ownership

if [[ -n "$GIT_REF" ]]; then
  echo "==> Git fetch (tags) + checkout ${GIT_REF}"
  _git fetch "$GIT_REMOTE" --tags --force
  if ! _git rev-parse --verify "${GIT_REF}^{commit}" >/dev/null 2>&1; then
    echo "ERROR: Git-ref niet gevonden na fetch: ${GIT_REF}" >&2
    echo "TIP: push de tag naar ${GIT_REMOTE} (git push origin ${GIT_REF})" >&2
    exit 1
  fi
  _git checkout --force "$GIT_REF"
else
  echo "==> Git fetch + reset naar ${GIT_REMOTE}/${GIT_BRANCH}"
  _git fetch "$GIT_REMOTE"
  _git reset --hard "${GIT_REMOTE}/${GIT_BRANCH}"
fi

if [[ ! -d "$BACKEND_DIR" ]]; then
  echo "ERROR: Backend directory niet gevonden na git checkout: $BACKEND_DIR" >&2
  echo "TIP: controleer GIT_REF/GIT_BRANCH en of origin de volledige monorepo bevat (map backend/)." >&2
  exit 1
fi

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
_resolve_compose_file
_ensure_compose_env_mount
_preflight_compose_file
_compose pull || true
_docker_safe_prune
_compose build --pull
_compose_up_deploy

_read_dotenv_db_credentials() {
  DB_DEPLOY_USER="${DB_USERNAME:-nexa}"
  DB_DEPLOY_NAME="${DB_DATABASE:-nexa}"
  if [[ -f "$TENANT_DIR/.env" ]]; then
    DB_DEPLOY_USER="$(grep -E '^DB_USERNAME=' "$TENANT_DIR/.env" | cut -d= -f2- | tr -d '\r' || true)"
    DB_DEPLOY_NAME="$(grep -E '^DB_DATABASE=' "$TENANT_DIR/.env" | cut -d= -f2- | tr -d '\r' || true)"
    DB_DEPLOY_USER="${DB_DEPLOY_USER:-nexa}"
    DB_DEPLOY_NAME="${DB_DEPLOY_NAME:-nexa}"
  fi
}

# Laravel migrate maakt geen PostgreSQL-rol/database aan. Admin = postgres OF POSTGRES_USER uit .env (vaak nexa).
PSQL_SUPERUSER=""

_detect_psql_superuser() {
  _read_dotenv_db_credentials
  local su
  for su in postgres "$DB_DEPLOY_USER"; do
    [[ -z "$su" ]] && continue
    if _compose exec -T db psql -U "$su" -d template1 -tAc "SELECT 1" >/dev/null 2>&1; then
      PSQL_SUPERUSER="$su"
      echo "==> Postgres admin-user: ${PSQL_SUPERUSER}"
      return 0
    fi
    if _compose exec -T db psql -U "$su" -d postgres -tAc "SELECT 1" >/dev/null 2>&1; then
      PSQL_SUPERUSER="$su"
      echo "==> Postgres admin-user: ${PSQL_SUPERUSER}"
      return 0
    fi
  done
  echo "ERROR: Geen psql-toegang als postgres of ${DB_DEPLOY_USER} in db-container." >&2
  echo "TIP: controleer DB_PASSWORD in .env en of het volume bij eerste start met POSTGRES_USER is geïnitialiseerd." >&2
  return 1
}

_db_psql_admin() {
  local maint_db="${1:-template1}"
  shift
  _compose exec -T db psql -U "$PSQL_SUPERUSER" -d "$maint_db" "$@"
}

_ensure_main_database_exists() {
  _read_dotenv_db_credentials
  local user="$DB_DEPLOY_USER" dbname="$DB_DEPLOY_NAME"
  local pw=""
  if [[ -f "$TENANT_DIR/.env" ]]; then
    pw="$(grep -E '^DB_PASSWORD=' "$TENANT_DIR/.env" | cut -d= -f2- | tr -d '\r' || true)"
    pw="${pw%\"}"
    pw="${pw#\"}"
    pw="${pw%\'}"
    pw="${pw#\'}"
  fi
  if [[ -z "$pw" ]]; then
    echo "ERROR: DB_PASSWORD ontbreekt in $TENANT_DIR/.env (vereist voor Postgres-bootstrap)." >&2
    exit 1
  fi
  _detect_psql_superuser

  if [[ "$PSQL_SUPERUSER" != "$user" ]] \
    && ! _db_psql_admin template1 -tAc "SELECT 1 FROM pg_roles WHERE rolname='${user}'" | grep -q 1; then
    echo "==> Postgres-rol ${user} ontbreekt — aanmaken"
    _db_psql_admin template1 -v ON_ERROR_STOP=1 -c \
      "CREATE ROLE \"${user//\"/\"\"}\" WITH LOGIN PASSWORD '${pw//\'/\'\'}' CREATEDB;"
  fi
  if _db_psql_admin template1 -tAc "SELECT 1 FROM pg_database WHERE datname='${dbname}'" | grep -q 1; then
    echo "==> Hoofddatabase ${dbname} bestaat al"
    return 0
  fi
  echo "==> Hoofddatabase ${dbname} ontbreekt — aanmaken (OWNER ${user})"
  _db_psql_admin template1 -v ON_ERROR_STOP=1 -c \
    "CREATE DATABASE \"${dbname//\"/\"\"}\" OWNER \"${user//\"/\"\"}\";"
}

_wait_for_postgres() {
  local user dbname tries=0
  _read_dotenv_db_credentials
  user="$DB_DEPLOY_USER"
  dbname="$DB_DEPLOY_NAME"
  echo "==> Wachten tot PostgreSQL-server bereikbaar is (user=${user})"
  until _compose exec -T db pg_isready -U "$user" -d template1 >/dev/null 2>&1 \
    || _compose exec -T db pg_isready -U "$user" >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [[ "$tries" -ge 30 ]]; then
      echo "ERROR: PostgreSQL-server reageert niet." >&2
      _compose logs --tail=60 db || true
      exit 1
    fi
    sleep 2
  done
  _ensure_main_database_exists
  echo "==> Wachten tot database ${dbname} bereikbaar is"
  tries=0
  until _compose exec -T db pg_isready -U "$user" -d "$dbname" >/dev/null 2>&1; do
    tries=$((tries + 1))
    if [[ "$tries" -ge 15 ]]; then
      echo "ERROR: Database ${dbname} niet bereikbaar via pg_isready." >&2
      _compose logs --tail=60 db || true
      exit 1
    fi
    sleep 2
  done
  echo "==> PostgreSQL OK (${dbname})"
}

_wait_for_postgres

echo "==> Wachten tot Laravel service beschikbaar is"
sleep 3

echo "==> Laravel migrations + basis seed + module-schema/DB's"
_compose exec -T "$LARAVEL_SERVICE" php artisan migrate --force
_compose exec -T "$LARAVEL_SERVICE" php artisan db:seed --class=Database\\Seeders\\ApplicationBootstrapSeeder --force
_compose exec -T "$LARAVEL_SERVICE" php artisan modules:ensure-databases || true
_compose exec -T "$LARAVEL_SERVICE" php artisan config:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan cache:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan route:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan view:clear
_compose exec -T "$LARAVEL_SERVICE" php artisan optimize

echo "==> Deploy klaar ($(date -Iseconds))"
echo ""
echo "TIP: Geen 'php artisan' op de host in ${BACKEND_DIR} (geen vendor daar)."
echo "    Voorbeeld: cd $(printf %q "$TENANT_DIR") && docker-compose -f ${COMPOSE_FILE} exec -T ${LARAVEL_SERVICE} php artisan config:clear"
echo "    (zonder -T voor een TTY: laat -T weg)"