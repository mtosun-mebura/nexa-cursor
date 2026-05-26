#!/usr/bin/env bash
# Eerste setup na git clone: .env, Composer, app key, migrate:fresh + ApplicationBootstrapSeeder + module-install(s).
# Gebruik: ./nexa_install.sh
#         ./nexa_install.sh taxi skillmatching
# Vereist: PHP, Composer, werkende DB-config in .env (na kopie van .env.example).

set -euo pipefail

cd "$(dirname "$0")"

echo "==> Nexa install (working dir: $(pwd))"

if [[ ! -f .env ]]; then
  echo "==> .env aanmaken vanuit .env.example"
  cp .env.example .env
fi

echo "==> composer install"
composer install --no-interaction --prefer-dist

echo "==> APP_KEY genereren (indien nodig)"
php artisan key:generate --force

install_args=(--install=taxi)
if [[ $# -gt 0 ]]; then
  install_args=()
  for m in "$@"; do
    install_args+=(--install="$m")
  done
fi

echo "==> nexa:reset-all --force ${install_args[*]}"
php artisan nexa:reset-all --force "${install_args[@]}"

echo "==> Klaar. Superadmin: zie App\\Services\\ModuleSchemaService (SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD)."
