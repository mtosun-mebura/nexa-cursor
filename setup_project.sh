#!/usr/bin/env bash
# =============================================================================
# Nieuwe Nexa-installatie: alleen de backend-container (Docker), database volgens
# repo-root .env (bijv. DB_HOST=192.168.178.249 op je netwerk).
#
# Vereisten: Docker met Compose-plugin; openssl; PostgreSQL bereikbaar vanaf de container.
#
# Gebruik:
#   ./setup_project.sh           — build + migrate:fresh + UserRoleSeeder + up backend
#   ./setup_project.sh --fresh   — zelfde, eerst containers van dit project stoppen
#
# Backend: http://localhost:8085/admin
# Super-admin: backend/app/Services/ModuleSchemaService.php (SUPERADMIN_*)
# =============================================================================

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

COMPOSE=(docker compose -f docker-compose.yml)
FRESH=false
for arg in "$@"; do
  case "$arg" in
    --fresh) FRESH=true ;;
    -h|--help)
      sed -n '1,22p' "$0"
      exit 0
      ;;
  esac
done

if ! docker compose version &>/dev/null; then
  echo "❌ 'docker compose' is niet beschikbaar. Installeer Docker Desktop of de Compose-plugin." >&2
  exit 1
fi

if ! command -v openssl &>/dev/null; then
  echo "❌ 'openssl' ontbreekt (nodig voor APP_KEY)." >&2
  exit 1
fi

if [[ ! -f "$ROOT/backend/.env.example" ]]; then
  echo "❌ backend/.env.example ontbreekt." >&2
  exit 1
fi

if [[ ! -f "$ROOT/.env" ]]; then
  echo "→ Kopieer backend/.env.example naar .env (repo-root, voor Docker-mount)"
  cp "$ROOT/backend/.env.example" "$ROOT/.env"
fi

# Schrijf APP_KEY direct in .env (geen `php artisan key:generate`: die faalt als de regex
# op APP_KEY= niet matcht — o.a. ontbrekende regel, BOM, of CRLF).
ensure_app_key_in_env() {
  local env_file="$1"
  if grep -qE '^APP_KEY=base64:' "$env_file" 2>/dev/null; then
    return 0
  fi
  echo "→ Genereer APP_KEY (openssl → .env)"
  local key="base64:$(openssl rand -base64 32 | tr -d '\n')"
  local tmp
  tmp="$(mktemp)"
  local replaced=false
  if [[ ! -s "$env_file" ]]; then
    echo "APP_KEY=$key" > "$env_file"
    return 0
  fi
  while IFS= read -r line || [[ -n "${line:-}" ]]; do
    line="${line%$'\r'}"
    line="${line#$'\xEF\xBB\xBF'}"
    if [[ "$line" =~ ^[[:space:]]*APP_KEY[[:space:]]*= ]]; then
      echo "APP_KEY=$key" >> "$tmp"
      replaced=true
    else
      printf '%s\n' "$line" >> "$tmp"
    fi
  done < "$env_file"
  if [[ "$replaced" == false ]]; then
    printf '\nAPP_KEY=%s\n' "$key" >> "$tmp"
  fi
  mv "$tmp" "$env_file"
}

ensure_app_key_in_env "$ROOT/.env"

if [[ "$FRESH" == true ]]; then
  echo "→ Stop bestaande containers van dit compose-project (--fresh)"
  "${COMPOSE[@]}" down --remove-orphans 2>/dev/null || true
fi

echo "→ Bouw backend-image"
"${COMPOSE[@]}" build backend

echo "→ Migraties (schone schema, geen seed)"
"${COMPOSE[@]}" run --rm --no-deps --entrypoint php backend artisan migrate:fresh --force

echo "→ UserRoleSeeder (alleen super-admin gebruiker + rol)"
"${COMPOSE[@]}" run --rm --no-deps --entrypoint php backend artisan db:seed --class=UserRoleSeeder --force

echo "→ RoleSeeder (alle standaard rollen toevoegen)"
"${COMPOSE[@]}" run --rm --no-deps --entrypoint php backend artisan db:seed --class=RoleSeeder --force

echo "→ Start backend (Laravel in container op :8000, host :8085)"
"${COMPOSE[@]}" up -d backend

echo ""
echo "✅ Klaar."
echo "   Admin:  http://localhost:8085/admin"
echo "   Database: volgens .env (DB_HOST, DB_DATABASE, …)."
echo "   Zorg dat de DB vanaf de container bereikbaar is (zelfde netwerk / firewall)."
echo ""
