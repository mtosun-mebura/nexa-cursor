#!/bin/bash
set -e

# EERST: Clear alle caches VOORDAT we configuratie aanpassen
echo "=== Alle caches volledig legen ==="
rm -rf bootstrap/cache/*.php 2>/dev/null || true
rm -rf storage/framework/cache/data/* 2>/dev/null || true
rm -rf storage/framework/views/*.php 2>/dev/null || true
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Controleer of .env bestaat, anders maak een basis .env aan
if [ ! -f .env ]; then
    echo "Geen .env bestand gevonden, basis .env aanmaken..."
    cp .env.example .env 2>/dev/null || echo "APP_NAME=Laravel" > .env
fi

# Functie om environment variable naar .env te schrijven
set_env_var() {
    local var_name=$1
    local var_value=$2
    if [ -n "$var_value" ]; then
        # Verwijder bestaande regel (inclusief met # comments)
        sed -i.bak "/^${var_name}=/d" .env 2>/dev/null || true
        sed -i.bak "/^#.*${var_name}=/d" .env 2>/dev/null || true
        # Voeg nieuwe regel toe
        echo "${var_name}=${var_value}" >> .env
    fi
}

# Zet database configuratie van environment variables naar .env
# FORCEER PostgreSQL - verwijder eventuele SQLite configuratie
if [ -n "$DB_CONNECTION" ]; then
    echo "=== Database configuratie FORCEREN: DB_CONNECTION=${DB_CONNECTION} ==="
    # Verwijder alle bestaande DB_* regels
    sed -i.bak '/^DB_/d' .env 2>/dev/null || true
    # Zet nieuwe PostgreSQL configuratie
    set_env_var "DB_CONNECTION" "$DB_CONNECTION"
    set_env_var "DB_HOST" "$DB_HOST"
    set_env_var "DB_PORT" "$DB_PORT"
    set_env_var "DB_DATABASE" "$DB_DATABASE"
    set_env_var "DB_USERNAME" "$DB_USERNAME"
    set_env_var "DB_PASSWORD" "$DB_PASSWORD"
    
    # Bevestig dat het correct is ingesteld
    echo "=== Database configuratie verificatie ==="
    grep "^DB_" .env || echo "GEEN DB_ VARIABELEN GEVONDEN IN .ENV!"
fi

# Als APP_KEY niet is ingesteld via environment, genereer dan een nieuwe
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "APP_KEY niet gevonden in environment, genereren nieuwe key..."
    php artisan key:generate --force --no-interaction
else
    echo "APP_KEY gevonden in environment, instellen in .env..."
    set_env_var "APP_KEY" "$APP_KEY"
fi

# Zet andere belangrijke environment variables
set_env_var "APP_ENV" "$APP_ENV"
set_env_var "APP_DEBUG" "$APP_DEBUG"
set_env_var "APP_URL" "$APP_URL"

# NADAT .env is aangepast: Clear alle caches opnieuw zodat Laravel de nieuwe config leest
echo "=== Caches opnieuw legen na configuratie wijzigingen ==="
rm -rf bootstrap/cache/*.php 2>/dev/null || true
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Verifieer dat config correct is
echo "=== Laatste configuratie check ==="
php artisan config:show database.default || echo "Config show gefaald"

# Test database connectie
if [ -n "$DB_CONNECTION" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "Database connectie testen..."
    php artisan db:show --database="$DB_CONNECTION" || echo "WAARSCHUWING: Database connectie test gefaald, maar we blijven doorgaan..."
fi

# Zorg dat storage schrijfbare permissions heeft
echo "Permissions controleren..."
chmod -R 775 storage bootstrap/cache || true
chown -R www-data:www-data storage bootstrap/cache || true

# Start de Laravel server
echo "Laravel server starten op 0.0.0.0:8000..."
exec php artisan serve --host=0.0.0.0 --port=8000

