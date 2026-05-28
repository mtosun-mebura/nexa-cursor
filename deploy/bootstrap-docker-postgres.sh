#!/usr/bin/env bash
# Eenmalig op TEST (Proxmox) of PROD: Postgres-rol + database aanmaken uit .env (docker-compose v1).
# Gebruik als deploy faalt met "role nexa does not exist" of "database nexa does not exist".
#
#   cd /home/nexasuite.nl/apps/saas/current
#   bash deploy/bootstrap-docker-postgres.sh
set -euo pipefail

TENANT_DIR="${TENANT_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$TENANT_DIR"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.deploy.yml}"
if [[ ! -f "$COMPOSE_FILE" && -f docker-compose.deploy.yml ]]; then
  COMPOSE_FILE=docker-compose.deploy.yml
elif [[ ! -f "$COMPOSE_FILE" && -f docker-compose.prod.yml ]]; then
  COMPOSE_FILE=docker-compose.prod.yml
fi

if [[ ! -f .env ]]; then
  echo "ERROR: $TENANT_DIR/.env ontbreekt" >&2
  exit 1
fi

if docker compose version >/dev/null 2>&1; then
  DC=(docker compose -f "$COMPOSE_FILE")
elif command -v docker-compose >/dev/null 2>&1; then
  DC=(docker-compose -f "$COMPOSE_FILE")
else
  echo "ERROR: docker-compose of docker compose niet gevonden" >&2
  exit 1
fi

DB_USER="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '\r')"
DB_NAME="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '\r')"
DB_PASS="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '\r')"
DB_USER="${DB_USER:-nexa}"
DB_NAME="${DB_NAME:-nexa}"
DB_PASS="${DB_PASS%\"}"
DB_PASS="${DB_PASS#\"}"

if [[ -z "$DB_PASS" ]]; then
  echo "ERROR: Zet DB_PASSWORD in .env" >&2
  exit 1
fi

echo "==> Bootstrap Postgres (user=$DB_USER db=$DB_NAME)"
"${DC[@]}" up -d db
sleep 3

PSQL_SU=""
for su in postgres "$DB_USER"; do
  if "${DC[@]}" exec -T db psql -U "$su" -d template1 -tAc "SELECT 1" >/dev/null 2>&1; then
    PSQL_SU="$su"
    break
  fi
done
if [[ -z "$PSQL_SU" ]]; then
  echo "ERROR: geen psql als postgres of $DB_USER" >&2
  exit 1
fi
echo "==> Admin-user: $PSQL_SU"

if [[ "$PSQL_SU" != "$DB_USER" ]]; then
  "${DC[@]}" exec -T db psql -U "$PSQL_SU" -d template1 -v ON_ERROR_STOP=1 -c \
    "DO \$\$ BEGIN
       IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '${DB_USER}') THEN
         CREATE ROLE \"${DB_USER//\"/\"\"}\" WITH LOGIN PASSWORD '${DB_PASS//\'/\'\'}' CREATEDB;
       END IF;
     END \$\$;"
fi

"${DC[@]}" exec -T db psql -U "$PSQL_SU" -d template1 -tAc \
  "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'" | grep -q 1 \
  || "${DC[@]}" exec -T db psql -U "$PSQL_SU" -d template1 -v ON_ERROR_STOP=1 -c \
  "CREATE DATABASE \"${DB_NAME//\"/\"\"}\" OWNER \"${DB_USER//\"/\"\"}\";"

"${DC[@]}" exec -T db pg_isready -U "$DB_USER" -d "$DB_NAME"
echo "==> OK. Daarna:"
echo "    ${DC[*]} exec -T backend php artisan migrate --force"
echo "    ${DC[*]} exec -T backend php artisan config:clear"
