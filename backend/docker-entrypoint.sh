#!/bin/bash
set -euo pipefail

cd /var/www/html

# .env aanwezig? Zo niet: kopieer voorbeeld
# Opgelet: .env kan uit root gemount zijn (docker-compose.yml of docker-compose.prod.yml)
if [ ! -f .env ]; then
  # Probeer eerst .env.example uit backend
  cp .env.example .env 2>/dev/null || touch .env
  echo "⚠️  .env aangemaakt - vul waarden in!"
fi

# Forceer production flags via env (app leest dit boven .env als docker envs zijn gezet)
# APP_ENV / APP_DEBUG / APP_URL komen uit docker-compose 'environment'

# APP_KEY uit env of .env (leeg = ook genereren)
current_key="$(grep -E '^APP_KEY=' .env 2>/dev/null | cut -d= -f2- || true)"
env_writable=true
if [ ! -w .env ]; then
  env_writable=false
fi

if [ -n "${APP_KEY:-}" ]; then
  export APP_KEY
  if $env_writable; then
    if grep -q '^APP_KEY=' .env; then
      tmp_file=$(mktemp)
      grep -v '^APP_KEY=' .env > "$tmp_file"
      echo "APP_KEY=${APP_KEY}" >> "$tmp_file"
      cat "$tmp_file" > .env
      rm -f "$tmp_file"
    else
      echo "APP_KEY=${APP_KEY}" >> .env
    fi
  else
    echo "⚠️  .env is niet schrijfbaar; gebruik APP_KEY uit environment variabele"
  fi
elif [ -z "$current_key" ] || [ "$current_key" = "" ]; then
  if $env_writable; then
    echo "Geen geldige APP_KEY gevonden; genereren..."
    newkey=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
    if grep -q '^APP_KEY=' .env; then
      tmp_file=$(mktemp)
      grep -v '^APP_KEY=' .env > "$tmp_file"
      echo "APP_KEY=$newkey" >> "$tmp_file"
      cat "$tmp_file" > .env
      rm -f "$tmp_file"
    else
      echo "APP_KEY=$newkey" >> .env
    fi
    export APP_KEY="$newkey"
  else
    echo "❌ APP_KEY ontbreekt en .env is niet schrijfbaar. Voeg APP_KEY toe aan root .env."
    exit 1
  fi
else
  export APP_KEY="$current_key"
fi

# Rechten (veilig & stil)
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Caches: eerst clear, dan cache
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# (Optioneel) storage symlink
php artisan storage:link || true

# (Optioneel) migraties alleen als DB-variabelen aanwezig zijn
if [ -n "${DB_CONNECTION:-}" ] && [ -n "${DB_HOST:-}" ]; then
  php artisan migrate --force || true
fi

echo "Start Laravel op 0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
