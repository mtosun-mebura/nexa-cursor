#!/bin/bash
set -euo pipefail

cd /var/www/html

# .env aanwezig? (verwacht from bind mount uit project root)
if [ ! -f .env ]; then
  echo "❌ .env ontbreekt in /var/www/html. Zorg dat root .env is gemount."
  exit 1
fi

# Forceer production flags via env (app leest dit boven .env als docker envs zijn gezet)
# APP_ENV / APP_DEBUG / APP_URL komen uit docker-compose 'environment'

# APP_KEY uit env of .env (geen schrijf acties op .env)
current_key="$(grep -E '^APP_KEY=' .env 2>/dev/null | cut -d= -f2- || true)"

if [ -n "${APP_KEY:-}" ]; then
  export APP_KEY
elif [ -n "$current_key" ]; then
  export APP_KEY="$current_key"
else
  echo "❌ APP_KEY ontbreekt. Voeg APP_KEY toe aan root .env of stel APP_KEY env variabele in."
  exit 1
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
