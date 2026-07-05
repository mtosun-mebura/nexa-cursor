#!/usr/bin/env bash
# Verhoog nginx uploadlimiet voor tenant-ZIP import (413 Request Entity Too Large).
#
# Gebruik op PROD (Lightsail), als root of met sudo:
#   sudo bash deploy/fix-nginx-upload-limit.sh
#   sudo bash deploy/fix-nginx-upload-limit.sh --size 512M
#
# Past site-configs in /etc/nginx/sites-enabled aan en zet desnoods http-level fallback.
set -euo pipefail

SIZE="${NGINX_CLIENT_MAX_BODY_SIZE:-512M}"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --size)
      SIZE="$2"
      shift 2
      ;;
    *)
      echo "Onbekend argument: $1" >&2
      exit 1
      ;;
  esac
done

if [[ "$(id -un)" != "root" ]]; then
  echo "Draai als root: sudo bash $0" >&2
  exit 1
fi

if ! command -v nginx >/dev/null 2>&1; then
  echo "ERROR: nginx niet gevonden." >&2
  exit 1
fi

patch_file() {
  local file="$1"
  [[ -f "$file" ]] || return 0

  if grep -qE '^\s*client_max_body_size\s+' "$file"; then
    sed -i -E "s/^\s*client_max_body_size\s+[^;]+;/    client_max_body_size ${SIZE};/" "$file"
    echo "==> Bijgewerkt: $file (client_max_body_size ${SIZE})"
  elif grep -qE '^\s*server\s*\{' "$file"; then
    sed -i -E "/^\s*server\s*\{/a\\
    client_max_body_size ${SIZE};
" "$file"
    echo "==> Toegevoegd in $file: client_max_body_size ${SIZE}"
  else
    echo "==> Overgeslagen (geen server-block): $file"
  fi
}

echo "==> Nexa: nginx uploadlimiet → ${SIZE}"
for f in /etc/nginx/sites-enabled/* /etc/nginx/conf.d/*.conf; do
  [[ -e "$f" ]] || continue
  patch_file "$f"
done

MAIN_CONF="/etc/nginx/nginx.conf"
if [[ -f "$MAIN_CONF" ]] && ! grep -qE '^\s*client_max_body_size\s+' "$MAIN_CONF"; then
  if grep -qE '^\s*http\s*\{' "$MAIN_CONF"; then
    sed -i -E "/^\s*http\s*\{/a\\
\tclient_max_body_size ${SIZE};
" "$MAIN_CONF"
    echo "==> http-level fallback in $MAIN_CONF"
  fi
fi

echo "==> nginx -t"
nginx -t

echo "==> reload nginx"
systemctl reload nginx

echo "OK: nginx accepteert uploads tot ${SIZE}."
echo "TIP: PHP/Docker-limieten staan in backend/php-upload.ini (512M). Herbouw backend na deploy-wijzigingen:"
echo "  docker compose -f docker-compose.deploy.yml build backend && docker compose -f docker-compose.deploy.yml up -d backend"
