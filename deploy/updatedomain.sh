#!/usr/bin/env bash
# Wijzig het publieke domein van een Nexa-omgeving in één keer:
#   .env (APP_URL, tenancy), nginx vhost, Let's Encrypt SSL, Laravel cache.
#
# Gebruik (op de server, in de app-map of repo-root):
#   ./updatedomain nexasuite.online
#   ./updatedomain nexasuite.online --wildcard
#   TENANT_DIR=/home/nexasuite.nl/apps/saas/current ./updatedomain nexasuite.online
#
# Vereist: nginx, certbot (+ python3-certbot-nginx), docker compose, sudo voor nginx/certbot.
# DNS moet al naar deze server wijzen (A-record apex + wildcard *.domein voor tenant-subdomeinen).
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

TENANT_DIR="${TENANT_DIR:-/home/nexasuite.nl/apps/saas/current}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.deploy.yml}"
LARAVEL_SERVICE="${LARAVEL_SERVICE:-backend}"
PROXY_PORT="${PROXY_PORT:-8085}"
NGINX_SITE="${NGINX_SITE:-}"
CERTBOT_EMAIL="${CERTBOT_EMAIL:-${LETSENCRYPT_EMAIL:-}}"
DRY_RUN=0
ENV_ONLY=0
SKIP_NGINX=0
SKIP_SSL=0
WILDCARD_SSL=0
DOMAIN=""

usage() {
  cat <<'EOF'
Gebruik: updatedomain <domein> [opties]

Voorbeelden:
  ./updatedomain nexasuite.online
  ./updatedomain nexasuite.online --wildcard
  ./updatedomain nexasuite.online --email admin@example.com --dry-run

Opties:
  --wildcard          Wildcard-certificaat (*.domein) via DNS-challenge (nodig voor tenant-subdomeinen)
  --email <adres>     Let's Encrypt e-mail (of env CERTBOT_EMAIL / LETSENCRYPT_EMAIL)
  --env-only          Alleen .env + Laravel cache (geen nginx/SSL)
  --no-nginx          Sla nginx-config over (bijv. CyberPanel beheert vhost zelf)
  --no-ssl            Sla certbot over (SSL al aanwezig of extern beheerd)
  --dry-run           Toon acties zonder wijzigingen
  -h, --help          Deze help

Omgevingsvariabelen:
  TENANT_DIR          App-root (default: /home/nexasuite.nl/apps/saas/current)
  PROXY_PORT          Docker publish-poort (default: 8085)
  NGINX_SITE          sites-available naam (default: domein met punten → streepjes)
  CERTBOT_EMAIL       E-mail voor Let's Encrypt

Na wisselen van test (.41) naar nexasuite.online en productie naar Coolify (nexasuite.nl):
  - Draai dit script op de test-server voor nexasuite.online
  - Zet op Coolify apart APP_URL=https://nexasuite.nl + HTTPS daar
EOF
}

log() { echo "==> $*"; }
warn() { echo "WARN: $*" >&2; }
die() { echo "ERROR: $*" >&2; exit 1; }

run() {
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] $*"
  else
  "$@"
  fi
}

run_sudo() {
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] sudo $*"
  elif [[ "$(id -u)" -eq 0 ]]; then
    "$@"
  elif sudo -n true 2>/dev/null; then
    sudo -n "$@"
  else
    sudo "$@"
  fi
}

normalize_domain() {
  local d="${1,,}"
  d="${d#https://}"
  d="${d#http://}"
  d="${d%%/*}"
  d="${d%%:*}"
  printf '%s' "$d"
}

validate_domain() {
  local d="$1"
  [[ "$d" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ ]] || die "Ongeldig domein: $d"
  [[ "$d" == *.* ]] || die "Geef een volledig domein op (bijv. nexasuite.online), niet alleen een label."
}

parse_args() {
  while [[ $# -gt 0 ]]; do
    case "$1" in
      -h|--help) usage; exit 0 ;;
      --dry-run) DRY_RUN=1; shift ;;
      --wildcard) WILDCARD_SSL=1; shift ;;
      --env-only) ENV_ONLY=1; shift ;;
      --no-nginx) SKIP_NGINX=1; shift ;;
      --no-ssl) SKIP_SSL=1; shift ;;
      --email)
        [[ $# -ge 2 ]] || die "--email vereist een waarde"
        CERTBOT_EMAIL="$2"
        shift 2
        ;;
      -*)
        die "Onbekende optie: $1 (gebruik --help)"
        ;;
      *)
        [[ -z "$DOMAIN" ]] || die "Meerdere domeinen opgegeven; gebruik één domein."
        DOMAIN="$(normalize_domain "$1")"
        shift
        ;;
    esac
  done

  [[ -n "$DOMAIN" ]] || { usage; die "Geef een domein op, bijv. ./updatedomain nexasuite.online"; }
  validate_domain "$DOMAIN"

  if [[ -z "$NGINX_SITE" ]]; then
    NGINX_SITE="${DOMAIN//./-}"
  fi
}

resolve_tenant_dir() {
  if [[ -d "$TENANT_DIR/.git" ]]; then
    return 0
  fi
  if [[ -d "$REPO_ROOT/.git" && -f "$REPO_ROOT/docker-compose.deploy.yml" ]]; then
    TENANT_DIR="$REPO_ROOT"
    return 0
  fi
  die "TENANT_DIR niet gevonden (geen .git in $TENANT_DIR). Zet TENANT_DIR of start vanuit de repo."
}

env_file_path() {
  echo "$TENANT_DIR/.env"
}

_set_env_var() {
  local key="$1" value="$2" file="$3"
  local tmp
  tmp="$(mktemp)"

  if [[ ! -f "$file" ]]; then
    die "Ontbrekend: $file"
  fi

  if grep -qE "^${key}=" "$file"; then
    # shellcheck disable=SC2016
    awk -v k="$key" -v v="$value" '
      BEGIN { found=0 }
      $0 ~ "^" k "=" { print k "=" v; found=1; next }
      { print }
      END { if (!found) print k "=" v }
    ' "$file" >"$tmp"
  else
    cp "$file" "$tmp"
    echo "${key}=${value}" >>"$tmp"
  fi

  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] $file: ${key}=${value}"
    rm -f "$tmp"
    return 0
  fi

  mv "$tmp" "$file"
}

backup_file() {
  local f="$1"
  [[ -f "$f" ]] || return 0
  local ts
  ts="$(date +%Y%m%d-%H%M%S)"
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] backup $f → ${f}.bak-${ts}"
  else
    cp -a "$f" "${f}.bak-${ts}"
    log "Backup: ${f}.bak-${ts}"
  fi
}

check_dns() {
  local host="$1"
  if ! command -v dig >/dev/null 2>&1; then
    warn "dig niet gevonden — DNS-check overgeslagen"
    return 0
  fi
  local resolved
  resolved="$(dig +short "$host" A 2>/dev/null | head -1 || true)"
  if [[ -z "$resolved" ]]; then
    warn "DNS: geen A-record voor ${host} — certbot kan falen tot DNS is gepropageerd."
    return 0
  fi
  log "DNS ${host} → ${resolved}"
}

update_env_file() {
  local env_file
  env_file="$(env_file_path)"
  log "Bijwerken .env: $env_file"
  backup_file "$env_file"

  local app_url="https://${DOMAIN}"
  local central="${DOMAIN},app.${DOMAIN}"
  local parents="${DOMAIN}"

  _set_env_var "APP_URL" "$app_url" "$env_file"
  _set_env_var "TENANCY_CENTRAL_DOMAINS" "$central" "$env_file"
  _set_env_var "TENANCY_TENANT_PARENT_DOMAINS" "$parents" "$env_file"
  _set_env_var "SESSION_SECURE_COOKIE" "true" "$env_file"

  if grep -qE '^AI_CHAT_LARAVEL_API_URL=' "$env_file" 2>/dev/null; then
    _set_env_var "AI_CHAT_LARAVEL_API_URL" "$app_url" "$env_file"
  fi

  log ".env: APP_URL=${app_url}"
  log ".env: TENANCY_CENTRAL_DOMAINS=${central}"
  log ".env: TENANCY_TENANT_PARENT_DOMAINS=${parents}"
}

render_nginx_config() {
  local template="$SCRIPT_DIR/nginx-nexa-docker.conf.template"
  local out="$1"
  local cert_dir="/etc/letsencrypt/live/${DOMAIN}"

  [[ -f "$template" ]] || die "Template ontbreekt: $template"

  sed \
    -e "s|{{DOMAIN}}|${DOMAIN}|g" \
    -e "s|{{NGINX_SITE}}|${NGINX_SITE}|g" \
    -e "s|{{PROXY_PORT}}|${PROXY_PORT}|g" \
    -e "s|{{SSL_FULLCHAIN}}|${cert_dir}/fullchain.pem|g" \
    -e "s|{{SSL_PRIVKEY}}|${cert_dir}/privkey.pem|g" \
    "$template" >"$out"
}

install_nginx_config() {
  local available="/etc/nginx/sites-available/${NGINX_SITE}"
  local enabled="/etc/nginx/sites-enabled/${NGINX_SITE}"
  local tmp
  tmp="$(mktemp)"

  render_nginx_config "$tmp"

  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] nginx config → $available"
    head -20 "$tmp"
    rm -f "$tmp"
    return 0
  fi

  run_sudo mkdir -p /var/www/certbot
  if [[ -f "$available" ]]; then
    backup_file "$available"
    run_sudo cp -a "$available" "${available}.pre-updatedomain-$(date +%Y%m%d-%H%M%S)" 2>/dev/null || true
  fi

  run_sudo cp "$tmp" "$available"
  rm -f "$tmp"

  if [[ ! -L "$enabled" && ! -f "$enabled" ]]; then
    run_sudo ln -sf "$available" "$enabled"
  fi

  log "nginx site: $available"
}

cert_exists() {
  [[ -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" && -f "/etc/letsencrypt/live/${DOMAIN}/privkey.pem" ]]
}

obtain_ssl_cert() {
  if [[ "$SKIP_SSL" -eq 1 ]]; then
    log "SSL overgeslagen (--no-ssl)"
    return 0
  fi

  command -v certbot >/dev/null 2>&1 || die "certbot niet gevonden. Installeer: sudo apt install certbot python3-certbot-nginx"

  if cert_exists; then
    log "Certificaat bestaat al voor ${DOMAIN} — certbot renew/expand"
  fi

  [[ -n "$CERTBOT_EMAIL" ]] || die "Geef --email op of zet CERTBOT_EMAIL / LETSENCRYPT_EMAIL"

  # Eerst HTTP-only vhost (zonder geldige ssl_* directives) als cert nog ontbreekt
  if ! cert_exists; then
    log "Tijdelijke HTTP-vhost voor ACME (poort 80)"
    local http_only
    http_only="$(mktemp)"
    cat >"$http_only" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN} app.${DOMAIN} *.${DOMAIN};

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
        allow all;
    }

    location / {
        proxy_pass http://127.0.0.1:${PROXY_PORT};
        proxy_set_header Host \$host;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    }
}
EOF
    if [[ "$DRY_RUN" -eq 1 ]]; then
      echo "[dry-run] tijdelijke HTTP nginx config"
    else
      run_sudo cp "$http_only" "/etc/nginx/sites-available/${NGINX_SITE}"
      run_sudo ln -sf "/etc/nginx/sites-available/${NGINX_SITE}" "/etc/nginx/sites-enabled/${NGINX_SITE}"
      run_sudo nginx -t
      run_sudo systemctl reload nginx
    fi
    rm -f "$http_only"
  fi

  if [[ "$WILDCARD_SSL" -eq 1 ]]; then
    log "Wildcard SSL (*.${DOMAIN}) — DNS TXT-challenge (handmatig bevestigen in certbot)"
    warn "Zorg voor DNS: A ${DOMAIN}, A app.${DOMAIN}, A *.${DOMAIN} (wildcard) → deze server"
    if [[ "$DRY_RUN" -eq 1 ]]; then
      echo "[dry-run] certbot certonly --manual --preferred-challenges dns -d *.${DOMAIN} -d ${DOMAIN}"
      return 0
    fi
    run_sudo certbot certonly --manual --preferred-challenges dns \
      -d "*.${DOMAIN}" -d "${DOMAIN}" \
      --agree-tos -m "$CERTBOT_EMAIL" \
      --expand \
      || die "Wildcard certbot mislukt"
  else
    log "SSL via certbot (apex + www + app)"
    if [[ "$DRY_RUN" -eq 1 ]]; then
      echo "[dry-run] certbot certonly --webroot -w /var/www/certbot -d ${DOMAIN} -d www.${DOMAIN} -d app.${DOMAIN}"
      return 0
    fi
    run_sudo certbot certonly --webroot -w /var/www/certbot \
      -d "${DOMAIN}" -d "www.${DOMAIN}" -d "app.${DOMAIN}" \
      --agree-tos -m "$CERTBOT_EMAIL" \
      --non-interactive \
      --expand \
      || die "certbot mislukt — controleer DNS en poort 80"
  fi
}

reload_nginx() {
  if [[ "$SKIP_NGINX" -eq 1 ]]; then
    return 0
  fi
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] nginx -t && systemctl reload nginx"
    return 0
  fi
  run_sudo nginx -t
  run_sudo systemctl reload nginx
  log "nginx herladen"
}

_compose() {
  if docker compose version >/dev/null 2>&1; then
    docker compose -f "$COMPOSE_FILE" "$@"
  else
    docker-compose -f "$COMPOSE_FILE" "$@"
  fi
}

restart_laravel() {
  local env_file
  env_file="$(env_file_path)"
  cd "$TENANT_DIR"

  [[ -f "$COMPOSE_FILE" ]] || die "Compose-bestand ontbreekt: $TENANT_DIR/$COMPOSE_FILE"

  log "Docker backend herstarten (pakt .env APP_URL op)"
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] docker compose up -d ${LARAVEL_SERVICE}"
    echo "[dry-run] artisan config:clear && optimize"
    return 0
  fi

  _compose up -d "$LARAVEL_SERVICE"
  sleep 2
  _compose exec -T "$LARAVEL_SERVICE" php artisan config:clear
  _compose exec -T "$LARAVEL_SERVICE" php artisan cache:clear
  _compose exec -T "$LARAVEL_SERVICE" php artisan route:clear
  _compose exec -T "$LARAVEL_SERVICE" php artisan view:clear
  _compose exec -T "$LARAVEL_SERVICE" php artisan optimize
  log "Laravel cache geleegd"
}

verify_https() {
  if [[ "$DRY_RUN" -eq 1 ]]; then
    return 0
  fi
  if ! command -v curl >/dev/null 2>&1; then
    return 0
  fi
  local code
  code="$(curl -sS -o /dev/null -w '%{http_code}' "https://${DOMAIN}/admin/login" --max-time 15 || echo "000")"
  if [[ "$code" == "200" || "$code" == "302" ]]; then
    log "HTTPS OK: https://${DOMAIN}/admin/login (HTTP ${code})"
  else
    warn "HTTPS-check gaf HTTP ${code} voor https://${DOMAIN}/admin/login — controleer nginx/Docker handmatig."
  fi
}

main() {
  parse_args "$@"
  resolve_tenant_dir

  log "Domein wijzigen naar: ${DOMAIN}"
  log "TENANT_DIR=${TENANT_DIR}"
  log "NGINX_SITE=${NGINX_SITE}  PROXY_PORT=${PROXY_PORT}"

  if [[ -d /usr/local/CyberCP && "$SKIP_NGINX" -eq 0 ]]; then
    warn "CyberPanel gedetecteerd — nginx wordt mogelijk door het panel beheerd."
    warn "Gebruik --no-nginx als je vhost/SSL via CyberPanel zet, en alleen .env via --env-only niet nodig."
  fi

  check_dns "$DOMAIN"
  check_dns "app.${DOMAIN}"
  if [[ "$WILDCARD_SSL" -eq 1 ]]; then
    warn "Wildcard: zet DNS A-record *.${DOMAIN} → dit server-IP (of CNAME naar apex)"
  fi

  update_env_file

  if [[ "$ENV_ONLY" -eq 1 ]]; then
    restart_laravel
    log "Klaar (--env-only)."
    exit 0
  fi

  if [[ "$SKIP_NGINX" -eq 0 ]]; then
    if [[ "$SKIP_SSL" -eq 1 ]] && ! cert_exists; then
      die "Geen certificaat voor ${DOMAIN} en --no-ssl gezet. Verwijder --no-ssl of plaats certificaat handmatig in /etc/letsencrypt/live/${DOMAIN}/"
    fi
    if [[ "$SKIP_SSL" -eq 0 ]]; then
      obtain_ssl_cert
    fi
    install_nginx_config
    reload_nginx
  fi

  restart_laravel
  verify_https

  echo ""
  log "Domein ingesteld op https://${DOMAIN}"
  echo "    Admin:     https://${DOMAIN}/admin/login"
  echo "    Tenants:   https://{slug}.${DOMAIN}"
  if [[ "$WILDCARD_SSL" -eq 0 ]]; then
    echo ""
    warn "Tenant-subdomeinen (taxi.${DOMAIN}) vereisen een wildcard-certificaat."
    warn "Draai opnieuw met: ./updatedomain ${DOMAIN} --wildcard --email ${CERTBOT_EMAIL:-jouw@email.nl}"
  fi
  echo ""
  echo "Optioneel: controleer company_domains in de DB als oude hosts (nexasuite.nl) nog gekoppeld zijn."
}

main "$@"
