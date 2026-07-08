#!/usr/bin/env bash
# Wijzig het publieke domein van een Nexa-omgeving in één keer:
#   .env (APP_URL, tenancy), nginx vhost, Let's Encrypt SSL, Laravel cache.
#
# Gebruik (op de server, in de app-map of repo-root):
#   ./updatedomain nexasuite.online              # apex + wildcard SSL + nginx + .env
#   ./updatedomain n8n.nexasuite.online        # subdomein → nginx + SSL (standaard poort 5678)
#
# Maakt /etc/nginx/sites-available/<domein> + symlink sites-enabled/<domein>
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
PROXY_PORT="${PROXY_PORT:-}"
NGINX_SITE=""
CERTBOT_EMAIL="${CERTBOT_EMAIL:-${LETSENCRYPT_EMAIL:-}}"
DRY_RUN=0
ENV_ONLY=0
SKIP_NGINX=0
SKIP_SSL=0
WILDCARD_SSL=-1
NO_WILDCARD=0
DIAGNOSE_ONLY=0
CYBERPANEL=0
IS_SUBDOMAIN=0
APEX_DOMAIN=""
SSL_CERT_NAME=""
SERVER_NAMES=""
DOMAIN=""

usage() {
  cat <<'EOF'
Gebruik: updatedomain <domein-of-subdomein> [opties]

Voorbeelden:
  ./updatedomain nexasuite.online
      Apex: .env, wildcard-SSL (DNS), nginx sites-available/nexasuite.online
  ./updatedomain n8n.nexasuite.online
      Subdomein: nginx + SSL, proxy naar 127.0.0.1:5678
  ./updatedomain automations.nexasuite.online --port 5678

Opties:
  --port <poort>      Reverse-proxy doel (default apex: 8085, n8n.*: 5678)
  --no-wildcard       Geen wildcard-certificaat (alleen apex + www + app via HTTP)
  --wildcard          Wildcard expliciet aan (standaard al aan voor apex)
  --email <adres>     Let's Encrypt e-mail (default: MAIL_FROM_ADDRESS uit .env)
  --env-only          Alleen .env + Laravel (alleen apex)
  --no-nginx          Geen nginx/SSL (bijv. alles via CyberPanel UI)
  --no-ssl            nginx wel, certbot overslaan (cert moet al bestaan)
  --dry-run           Toon acties zonder wijzigingen
  --diagnose          Routing controleren (geen wijzigingen)
  -h, --help          Deze help

Nginx:
  /etc/nginx/sites-available/<domein>  (bijv. nexasuite.nl of n8n.nexasuite.online)
  /etc/nginx/sites-enabled/<domein>    → symlink naar sites-available

CyberPanel: kan hetzelfde via Websites → Create Website → SSL → Reverse Proxy,
maar dit script schrijft direct naar nginx (schakel conflicterende CyberPanel-sites uit).

Omgevingsvariabelen:
  TENANT_DIR          App-root (default: /home/nexasuite.nl/apps/saas/current)
  PROXY_PORT          Zelfde als --port
  CERTBOT_EMAIL       Let's Encrypt e-mail
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

apex_from_host() {
  local host="$1"
  local labels
  labels="$(echo "$host" | awk -F. '{print NF}')"
  if [[ "$labels" -le 2 ]]; then
    printf '%s' "$host"
    return 0
  fi
  echo "$host" | awk -F. '{print $(NF-1)"."$NF}'
}

detect_domain_mode() {
  local labels
  labels="$(echo "$DOMAIN" | awk -F. '{print NF}')"
  NGINX_SITE="$DOMAIN"
  SSL_CERT_NAME="$DOMAIN"

  if [[ "$labels" -le 2 ]]; then
    IS_SUBDOMAIN=0
    APEX_DOMAIN="$DOMAIN"
    SERVER_NAMES="${DOMAIN} *.${DOMAIN}"
    SSL_CERT_NAME="$APEX_DOMAIN"
    if [[ -z "${PROXY_PORT:-}" ]]; then
      PROXY_PORT=8085
    fi
    if [[ "$WILDCARD_SSL" -eq -1 ]] && [[ "$NO_WILDCARD" -eq 0 ]]; then
      WILDCARD_SSL=1
    elif [[ "$WILDCARD_SSL" -eq -1 ]]; then
      WILDCARD_SSL=0
    fi
    log "Modus: apex (${DOMAIN}), wildcard-SSL=$([[ $WILDCARD_SSL -eq 1 ]] && echo aan || echo uit)"
  else
    IS_SUBDOMAIN=1
    APEX_DOMAIN="$(apex_from_host "$DOMAIN")"
    SERVER_NAMES="$DOMAIN"
    WILDCARD_SSL=0
    if [[ -z "${PROXY_PORT:-}" ]]; then
      case "$DOMAIN" in
        n8n.*|automations.*) PROXY_PORT=5678 ;;
        *) PROXY_PORT=8085 ;;
      esac
    fi
    log "Modus: subdomein (${DOMAIN} → poort ${PROXY_PORT}, apex ${APEX_DOMAIN})"
  fi
}

parse_args() {
  while [[ $# -gt 0 ]]; do
    case "$1" in
      -h|--help) usage; exit 0 ;;
      --dry-run) DRY_RUN=1; shift ;;
      --diagnose) DIAGNOSE_ONLY=1; shift ;;
      --wildcard) WILDCARD_SSL=1; shift ;;
      --no-wildcard) NO_WILDCARD=1; WILDCARD_SSL=0; shift ;;
      --env-only) ENV_ONLY=1; shift ;;
      --no-nginx) SKIP_NGINX=1; shift ;;
      --no-ssl) SKIP_SSL=1; shift ;;
      --port)
        [[ $# -ge 2 ]] || die "--port vereist een waarde"
        PROXY_PORT="$2"
        shift 2
        ;;
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
  detect_domain_mode
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

  mv -f "$tmp" "$file"
}

read_env_value() {
  local key="$1" file="$2"
  [[ -f "$file" ]] || return 1
  grep -E "^${key}=" "$file" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '\r' | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

resolve_certbot_email() {
  if [[ -n "$CERTBOT_EMAIL" ]]; then
    return 0
  fi
  local env_file from_mail
  env_file="$(env_file_path)"
  from_mail="$(read_env_value "MAIL_FROM_ADDRESS" "$env_file" || true)"
  if [[ -n "$from_mail" && "$from_mail" == *@* ]]; then
    CERTBOT_EMAIL="$from_mail"
    log "Let's Encrypt e-mail uit .env MAIL_FROM_ADDRESS: ${CERTBOT_EMAIL}"
    return 0
  fi
  die "Geef een e-mail voor Let's Encrypt op, bijv.: ./updatedomain ${DOMAIN} --email jouw@email.nl (of zet MAIL_FROM_ADDRESS in .env)"
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
  if [[ "$IS_SUBDOMAIN" -eq 1 ]]; then
    log ".env overgeslagen (subdomein-modus; alleen nginx voor ${DOMAIN})"
    return 0
  fi

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

legacy_nginx_site_name() {
  # Oud patroon: punten → streepjes (nexasuite.online → nexasuite-online)
  printf '%s' "${1//./-}"
}

cleanup_legacy_nginx_names() {
  local legacy
  legacy="$(legacy_nginx_site_name "$DOMAIN")"
  if [[ "$legacy" == "$NGINX_SITE" ]]; then
    return 0
  fi

  local dir path
  for dir in /etc/nginx/sites-available /etc/nginx/sites-enabled; do
    path="${dir}/${legacy}"
    if [[ "$DRY_RUN" -eq 1 ]]; then
      if [[ -e "$path" || -L "$path" ]]; then
        echo "[dry-run] verwijder verouderde nginx-config: ${path}"
      fi
      continue
    fi
    if [[ -e "$path" || -L "$path" ]]; then
      run_sudo rm -f "$path"
      log "Verwijderd (oude dash-naam): ${path} → gebruik ${NGINX_SITE}"
    fi
  done
}

render_nginx_config() {
  local out="$1"
  local cert_dir="/etc/letsencrypt/live/${SSL_CERT_NAME}"
  local panel_block=""

  if [[ "$IS_SUBDOMAIN" -eq 0 ]]; then
    panel_block="
    if (\$host = panel.${APEX_DOMAIN}) {
        return 444;
    }
"
  fi

  cat >"$out" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${SERVER_NAMES};
${panel_block}
    client_max_body_size 512M;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
        allow all;
    }

    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${SERVER_NAMES};

    ssl_certificate     ${cert_dir}/fullchain.pem; # managed by Certbot
    ssl_certificate_key ${cert_dir}/privkey.pem; # managed by Certbot
${panel_block}
    client_max_body_size 512M;

    location / {
        proxy_pass http://127.0.0.1:${PROXY_PORT};
        proxy_http_version 1.1;

        proxy_redirect off;

        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_set_header X-Forwarded-Host \$host;
        proxy_set_header X-Forwarded-Port 443;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";

        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_send_timeout 300;
    }
}
EOF
}

install_nginx_config() {
  local available="/etc/nginx/sites-available/${NGINX_SITE}"
  local enabled="/etc/nginx/sites-enabled/${NGINX_SITE}"
  local tmp
  tmp="$(mktemp)"

  cleanup_legacy_nginx_names
  render_nginx_config "$tmp"

  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] nginx config → $available"
    echo "[dry-run] symlink $enabled → $available"
    head -30 "$tmp"
    rm -f "$tmp"
    return 0
  fi

  ensure_certbot_webroot
  if [[ -f "$available" ]]; then
    run_sudo cp -a "$available" "${available}.bak-$(date +%Y%m%d-%H%M%S)" 2>/dev/null || true
  fi

  run_sudo cp "$tmp" "$available"
  rm -f "$tmp"
  run_sudo ln -sf "$available" "$enabled"

  log "nginx: $available"
  log "nginx: $enabled → $available"
}

cert_exists() {
  [[ -f "/etc/letsencrypt/live/${SSL_CERT_NAME}/fullchain.pem" && -f "/etc/letsencrypt/live/${SSL_CERT_NAME}/privkey.pem" ]]
}

resolve_ssl_cert_name() {
  if [[ "$IS_SUBDOMAIN" -eq 1 ]] && cert_exists_for "${APEX_DOMAIN}"; then
    SSL_CERT_NAME="$APEX_DOMAIN"
    log "Subdomein gebruikt bestaand apex/wildcard-certificaat: ${SSL_CERT_NAME}"
  fi
}

cert_exists_for() {
  local name="$1"
  [[ -f "/etc/letsencrypt/live/${name}/fullchain.pem" && -f "/etc/letsencrypt/live/${name}/privkey.pem" ]]
}

ensure_certbot_webroot() {
  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] mkdir -p /var/www/certbot/.well-known/acme-challenge"
    return 0
  fi
  run_sudo mkdir -p /var/www/certbot/.well-known/acme-challenge
  run_sudo chmod -R 755 /var/www/certbot
  local web_user="www-data"
  if id nginx >/dev/null 2>&1; then
    web_user="nginx"
  elif id www-data >/dev/null 2>&1; then
    web_user="www-data"
  fi
  run_sudo chown -R "${web_user}:${web_user}" /var/www/certbot 2>/dev/null || true
  log "ACME webroot: /var/www/certbot"
}

deploy_temp_http_vhost() {
  log "Tijdelijke HTTP-vhost voor ACME (poort 80)"
  ensure_certbot_webroot

  local http_only
  http_only="$(mktemp)"
  cat >"$http_only" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${SERVER_NAMES};

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
    echo "[dry-run] tijdelijke HTTP nginx → /etc/nginx/sites-available/${NGINX_SITE}"
    rm -f "$http_only"
    return 0
  fi

  run_sudo cp "$http_only" "/etc/nginx/sites-available/${NGINX_SITE}"
  run_sudo ln -sf "/etc/nginx/sites-available/${NGINX_SITE}" "/etc/nginx/sites-enabled/${NGINX_SITE}"
  rm -f "$http_only"
  run_sudo nginx -t
  run_sudo systemctl reload nginx
}

certbot_webroot_apex() {
  log "SSL via certbot webroot (${DOMAIN} + www + app)"
  run_sudo certbot certonly --webroot -w /var/www/certbot \
    -d "${DOMAIN}" -d "www.${DOMAIN}" -d "app.${DOMAIN}" \
    --agree-tos -m "$CERTBOT_EMAIL" \
    --non-interactive \
    --expand
}

certbot_webroot_host() {
  log "SSL via certbot webroot (${DOMAIN})"
  run_sudo certbot certonly --webroot -w /var/www/certbot \
    -d "${DOMAIN}" \
    --agree-tos -m "$CERTBOT_EMAIL" \
    --non-interactive \
    --expand
}

certbot_nginx_plugin_apex() {
  log "SSL via certbot nginx-plugin (${DOMAIN} + www + app)"
  run_sudo certbot certonly --nginx \
    -d "${DOMAIN}" -d "www.${DOMAIN}" -d "app.${DOMAIN}" \
    --agree-tos -m "$CERTBOT_EMAIL" \
    --non-interactive \
    --expand
}

certbot_nginx_plugin_host() {
  log "SSL via certbot nginx-plugin (${DOMAIN})"
  run_sudo certbot certonly --nginx \
    -d "${DOMAIN}" \
    --agree-tos -m "$CERTBOT_EMAIL" \
    --non-interactive \
    --expand
}

obtain_ssl_http_cert() {
  if cert_exists; then
    log "Certificaat bestaat al: /etc/letsencrypt/live/${SSL_CERT_NAME}/"
    return 0
  fi

  if [[ "$CYBERPANEL" -eq 1 ]]; then
    if [[ "$IS_SUBDOMAIN" -eq 1 ]]; then
      certbot_nginx_plugin_host && return 0
    else
      certbot_nginx_plugin_apex && return 0
    fi
    warn "certbot --nginx mislukt — tijdelijke webroot-vhost"
  fi

  deploy_temp_http_vhost

  if [[ "$IS_SUBDOMAIN" -eq 1 ]]; then
    certbot_webroot_host && return 0
    certbot_nginx_plugin_host
  else
    certbot_webroot_apex && return 0
    certbot_nginx_plugin_apex
  fi
}

wait_for_dns_txt_hint() {
  local challenge_host="_acme-challenge.${APEX_DOMAIN}"
  echo ""
  log "Wildcard SSL voor *.${APEX_DOMAIN}"
  echo "    1. Certbot toont TXT-waarde(n) voor: ${challenge_host}"
  echo "    2. Voeg die toe bij je DNS-provider (kan 1–15 min duren)"
  echo "    3. Check: dig TXT ${challenge_host} +short"
  echo "    4. Druk pas op Enter in certbot als de TXT zichtbaar is"
  echo "    (soms twee TXT-records op dezelfde host — beide toevoegen vóór tweede Enter)"
  echo ""
}

obtain_ssl_wildcard_cert() {
  log "Wildcard SSL (*.${APEX_DOMAIN}) — DNS TXT-challenge"
  warn "DNS: A ${APEX_DOMAIN} en A *.${APEX_DOMAIN} → dit server-IP"

  if [[ "$DRY_RUN" -eq 1 ]]; then
    echo "[dry-run] certbot certonly --manual --preferred-challenges dns -d *.${APEX_DOMAIN} -d ${APEX_DOMAIN}"
    return 0
  fi

  wait_for_dns_txt_hint

  if [[ "$(id -u)" -eq 0 ]]; then
    certbot certonly --manual --preferred-challenges dns \
      -d "*.${APEX_DOMAIN}" -d "${APEX_DOMAIN}" \
      --agree-tos -m "$CERTBOT_EMAIL" \
      --expand \
      || die "Wildcard certbot mislukt — TXT bij _acme-challenge.${APEX_DOMAIN}"
  else
    sudo certbot certonly --manual --preferred-challenges dns \
      -d "*.${APEX_DOMAIN}" -d "${APEX_DOMAIN}" \
      --agree-tos -m "$CERTBOT_EMAIL" \
      --expand \
      || die "Wildcard certbot mislukt — TXT bij _acme-challenge.${APEX_DOMAIN}"
  fi
}

obtain_ssl_cert() {
  if [[ "$SKIP_SSL" -eq 1 ]]; then
    log "SSL overgeslagen (--no-ssl)"
    return 0
  fi

  command -v certbot >/dev/null 2>&1 || die "certbot niet gevonden. Installeer: sudo apt install certbot python3-certbot-nginx"

  [[ -n "$CERTBOT_EMAIL" ]] || resolve_certbot_email

  if [[ "$IS_SUBDOMAIN" -eq 0 && "$WILDCARD_SSL" -eq 1 ]]; then
    obtain_ssl_wildcard_cert
  else
    if [[ "$DRY_RUN" -eq 1 ]]; then
      echo "[dry-run] obtain_ssl_http_cert"
      return 0
    fi
    obtain_ssl_http_cert || die "certbot mislukt — controleer DNS en poort 80"
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

  local check_url="https://${DOMAIN}/"
  if [[ "$IS_SUBDOMAIN" -eq 0 ]]; then
    check_url="https://${DOMAIN}/admin/login"
  fi

  local headers code location
  headers="$(curl -sS -D - -o /dev/null "$check_url" --max-time 15 2>/dev/null || true)"
  code="$(printf '%s' "$headers" | awk 'toupper($1) ~ /^HTTP/ {print $2; exit}')"
  location="$(printf '%s' "$headers" | awk 'tolower($1)=="location:" {print $2; exit}' | tr -d '\r')"

  if [[ "$location" == *"/signin"* ]]; then
    die "https://${DOMAIN} wijst naar n8n (/signin). Controleer proxy_pass poort (${PROXY_PORT})."
  fi

  if [[ "$code" == "200" || "$code" == "302" || "$code" == "301" ]]; then
    log "HTTPS OK: ${check_url} (HTTP ${code})"
  else
    warn "HTTPS-check gaf HTTP ${code} voor ${check_url}"
  fi
}

diagnose_domain_routing() {
  log "Diagnose routing voor ${DOMAIN}"

  check_dns "$DOMAIN"

  if command -v curl >/dev/null 2>&1; then
    log "Docker/Laravel direct (127.0.0.1:${PROXY_PORT}, Host: ${DOMAIN}):"
    local direct_code direct_body
    direct_code="$(curl -sS -o /tmp/updatedomain-direct.html -w '%{http_code}' \
      -H "Host: ${DOMAIN}" "http://127.0.0.1:${PROXY_PORT}/admin/login" --max-time 8 2>/dev/null || echo "000")"
    direct_body="$(head -c 200 /tmp/updatedomain-direct.html 2>/dev/null || true)"
    echo "    HTTP ${direct_code} — $(echo "$direct_body" | tr '\n' ' ' | head -c 120)"
    if [[ "$direct_code" == "200" ]] && [[ "$direct_body" == *"Inloggen"* || "$direct_body" == *"admin"* ]]; then
      log "    OK: Laravel reageert op poort ${PROXY_PORT}"
    elif [[ "$direct_code" == "000" ]]; then
      warn "    Geen antwoord op 127.0.0.1:${PROXY_PORT} — draait Docker backend? (docker compose ps)"
    else
      warn "    Onverwacht antwoord op ${PROXY_PORT}"
    fi

    log "Publiek HTTPS https://${DOMAIN}/ :"
    local pub_headers pub_code pub_loc pub_server
    pub_headers="$(curl -sS -D - -o /tmp/updatedomain-pub.html "https://${DOMAIN}/" --max-time 12 2>/dev/null || true)"
    pub_code="$(printf '%s' "$pub_headers" | awk 'toupper($1) ~ /^HTTP/ {print $2; exit}')"
    pub_loc="$(printf '%s' "$pub_headers" | awk 'tolower($1)=="location:" {print $2; exit}' | tr -d '\r')"
    pub_server="$(printf '%s' "$pub_headers" | awk 'tolower($1)=="server:" {$1=""; sub(/^ /,""); print; exit}')"
    echo "    HTTP ${pub_code}  Server: ${pub_server:-?}"
    [[ -n "$pub_loc" ]] && echo "    Location: ${pub_loc}"

    if [[ "$pub_loc" == *"/signin"* ]]; then
      echo ""
      warn "PROBLEEM: publiek domein wijst naar n8n (pad /signin), niet naar Nexa/Laravel."
      echo "    Oplossing:"
      echo "    1. CyberPanel → Websites → ${DOMAIN} → reverse proxy naar http://127.0.0.1:${PROXY_PORT}"
      echo "    2. Verplaats n8n naar subdomein automations.${DOMAIN} → poort 5678"
      echo "    3. Of: sudo grep -r '${DOMAIN}' /etc/nginx/ en verwijder proxy_pass naar 5678"
    elif [[ "$direct_code" == "200" && "$pub_code" != "200" && "$pub_code" != "302" ]]; then
      warn "Laravel op ${PROXY_PORT} werkt, maar publiek HTTPS niet — nginx/CyberPanel vhost of SSL controleren."
    fi
  fi

  if [[ -d /usr/local/CyberCP ]]; then
    log "CyberPanel gedetecteerd — controleer in het panel welke backend aan ${DOMAIN} hangt."
  fi

  if command -v ss >/dev/null 2>&1; then
    log "Luisterende poorten (80/443/5678/${PROXY_PORT}):"
    ss -tlnp 2>/dev/null | grep -E ':80 |:443 |:5678 |:'"${PROXY_PORT}"' ' || ss -tlnp 2>/dev/null | grep -E ':80|:443|:5678|:'"${PROXY_PORT}" || true
  fi

  echo ""
  log "nginx configs met ${DOMAIN} of poort 5678:"
  if sudo test -r /etc/nginx/sites-enabled 2>/dev/null; then
    run_sudo grep -RIn "${DOMAIN}\|5678\|${PROXY_PORT}" /etc/nginx/sites-enabled /etc/nginx/sites-available 2>/dev/null | head -30 || true
  else
    warn "Geen leesrechten op /etc/nginx — draai: sudo grep -r '${DOMAIN}' /etc/nginx/"
  fi
}

main() {
  parse_args "$@"
  resolve_tenant_dir

  if [[ "$DIAGNOSE_ONLY" -eq 1 ]]; then
    diagnose_domain_routing
    exit 0
  fi

  log "Domein wijzigen naar: ${DOMAIN}"
  log "TENANT_DIR=${TENANT_DIR}"
  log "nginx sites-available/${NGINX_SITE}  PROXY_PORT=${PROXY_PORT}"

  if [[ -d /usr/local/CyberCP ]]; then
    CYBERPANEL=1
    if [[ "$SKIP_NGINX" -eq 0 ]]; then
      warn "CyberPanel actief — schakel conflicterende websites voor ${DOMAIN} uit in het panel."
      warn "Dit script schrijft direct naar /etc/nginx/sites-{available,enabled}/${NGINX_SITE}"
      warn "Of gebruik alleen CyberPanel: ./updatedomain ${DOMAIN} --no-nginx --env-only"
    fi
  fi

  check_dns "$DOMAIN"
  if [[ "$IS_SUBDOMAIN" -eq 0 ]]; then
    check_dns "app.${DOMAIN}"
    if [[ "$WILDCARD_SSL" -eq 1 ]]; then
      warn "Wildcard: DNS A *.${DOMAIN} → server-IP (naast apex)"
    fi
  fi

  resolve_certbot_email
  update_env_file

  if [[ "$ENV_ONLY" -eq 1 ]]; then
    restart_laravel
    log "Klaar (--env-only)."
    exit 0
  fi

  if [[ "$SKIP_NGINX" -eq 0 ]]; then
    resolve_ssl_cert_name
    if [[ "$SKIP_SSL" -eq 1 ]] && ! cert_exists; then
      die "Geen certificaat in /etc/letsencrypt/live/${SSL_CERT_NAME}/ — verwijder --no-ssl of haal SSL eerst op"
    fi
    if [[ "$SKIP_SSL" -eq 0 ]]; then
      obtain_ssl_cert
    fi
    install_nginx_config
    reload_nginx
  fi

  if [[ "$IS_SUBDOMAIN" -eq 0 ]]; then
    restart_laravel
    verify_https
  else
    verify_https
    log "Subdomein klaar — geen .env/Laravel-wijzigingen"
  fi

  echo ""
  log "Klaar: https://${DOMAIN}"
  if [[ "$IS_SUBDOMAIN" -eq 0 ]]; then
    echo "    Admin:     https://${DOMAIN}/admin/login"
    echo "    Tenants:   https://{slug}.${DOMAIN}"
    echo "    nginx:     /etc/nginx/sites-available/${NGINX_SITE}"
    echo ""
    echo "Subdomeinen (n8n, enz.): ./updatedomain n8n.${DOMAIN} --port 5678"
  else
    echo "    Proxy:     127.0.0.1:${PROXY_PORT}"
    echo "    nginx:     /etc/nginx/sites-available/${NGINX_SITE}"
  fi
  echo ""
  if [[ "$IS_SUBDOMAIN" -eq 0 ]]; then
    echo "Optioneel: company_domains in DB controleren op oude hosts."
  fi
}

main "$@"
