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

# Aantal workers voor de PHP built-in server (php artisan serve). Default 8 als niet via compose gezet,
# zodat requests parallel worden afgehandeld i.p.v. single-threaded (1 trage request blokkeert anders alles).
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-8}"

# Caches: eerst clear (verse staat), daarna Blade-views vooraf compileren.
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Blade-views vooraf compileren zodat de EERSTE weergave van een (admin)pagina niet hoeft te
# compileren tijdens de request. Scheelt merkbaar bij het navigeren in de admin na een herstart.
php artisan view:cache || true

# (Optioneel) storage symlink
php artisan storage:link || true

# Wacht op PostgreSQL (compose service `db` met healthcheck)
if [ -n "${DB_HOST:-}" ] && [ "${DB_HOST}" != "127.0.0.1" ] && [ "${DB_HOST}" != "localhost" ]; then
  echo "Wachten op database (${DB_HOST}:5432)..."
  for i in $(seq 1 60); do
    if php -r "
      \$h = getenv('DB_HOST') ?: 'db';
      \$p = (int) (getenv('DB_PORT') ?: 5432);
      \$errno = 0; \$err = '';
      \$s = @fsockopen(\$h, \$p, \$errno, \$err, 2);
      if (\$s) { fclose(\$s); exit(0); }
      exit(1);
    "; then
      echo "Database bereikbaar."
      break
    fi
    if [ "$i" -eq 60 ]; then
      echo "⚠️  Database niet bereikbaar na 60 pogingen; migraties worden overgeslagen."
    fi
    sleep 2
  done
fi

# Migraties + minimale seed (rollen, super admin, branches, thema's, …) als DB-variabelen aanwezig zijn
if [ -n "${DB_CONNECTION:-}" ] && [ -n "${DB_HOST:-}" ]; then
  php artisan migrate --force || true
  # Idempotent: veilig bij elke container-start; eerste deployment krijgt altijd basisdata
  php artisan db:seed --class=Database\\Seeders\\ApplicationBootstrapSeeder --force || true
fi

echo "Start Laravel op 0.0.0.0:8000 (workers: ${PHP_CLI_SERVER_WORKERS})"
# --no-reload is verplicht voor PHP_CLI_SERVER_WORKERS: zonder vlag forceert Laravel 1 worker (hot-reload).
# Met meerdere workers kan n8n terugbellen naar /integrations/n8n/ai-chat/live-query terwijl de
# website-chat nog op het n8n-webhook-antwoord wacht (anders 30s timeout / deadlock).
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
