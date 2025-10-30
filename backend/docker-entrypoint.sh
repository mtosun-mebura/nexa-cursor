#!/bin/bash
# Verwijder set -e tijdelijk zodat fouten in cache:clear niet het script stoppen
set +e

# EERST: Verwijder bootstrap cache files handmatig (zonder Laravel commands)
echo "=== Bootstrap cache verwijderen (handmatig) ==="
rm -rf bootstrap/cache/*.php 2>/dev/null || true
rm -rf storage/framework/cache/data/* 2>/dev/null || true
rm -rf storage/framework/views/*.php 2>/dev/null || true

# Controleer of .env bestaat en of het een bestand is (niet een directory!)
if [ -d .env ]; then
    echo "WAARSCHUWING: .env is een directory! Verwijderen..."
    rm -rf .env
fi

if [ ! -f .env ]; then
    echo "Geen .env bestand gevonden, basis .env aanmaken..."
    cp .env.example .env 2>/dev/null || echo "APP_NAME=Laravel" > .env
fi

# Verifieer dat .env nu een bestand is
if [ ! -f .env ]; then
    echo "FOUT: .env kon niet worden aangemaakt als bestand!"
    exit 1
fi

echo "✓ .env bestand bestaat en is een bestand (niet een directory)"

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

# EERST: Zet database configuratie CORRECT in .env VOORDAT we Laravel commands gebruiken
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
    
    # Zet ook CACHE_DRIVER naar file om SQLite te vermijden tijdens cache:clear
    set_env_var "CACHE_DRIVER" "file"
    set_env_var "SESSION_DRIVER" "file"
    
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
# Verwijder bootstrap cache opnieuw (handmatig, veiliger dan artisan)
rm -rf bootstrap/cache/*.php 2>/dev/null || true

# Nu de .env correct is, kunnen we Laravel commands gebruiken
# config:clear zou nu moeten werken omdat DB_CONNECTION correct is
echo "Config cache legen..."
php artisan config:clear 2>&1 || echo "Config clear gefaald (niet kritisch)"

# cache:clear zou nu moeten werken omdat CACHE_DRIVER=file is
echo "Application cache legen..."
php artisan cache:clear 2>&1 || echo "Cache clear gefaald (niet kritisch)"

# Andere caches
echo "Route cache legen..."
php artisan route:clear 2>&1 || echo "Route clear gefaald (niet kritisch)"

echo "View cache legen..."
php artisan view:clear 2>&1 || echo "View clear gefaald (niet kritisch)"

# Verifieer dat config correct is
echo "=== Laatste configuratie check ==="
php artisan config:show database.default 2>&1 | head -5 || echo "Config show gefaald"

# Test database connectie (alleen als PostgreSQL)
if [ -n "$DB_CONNECTION" ] && [ "$DB_CONNECTION" = "pgsql" ]; then
    echo "Database connectie testen (PostgreSQL)..."
    php artisan db:show --database="pgsql" 2>&1 | head -10 || echo "WAARSCHUWING: Database connectie test gefaald"
fi

# Zorg dat storage schrijfbare permissions heeft
echo "Permissions controleren..."
chmod -R 775 storage bootstrap/cache || true
chown -R www-data:www-data storage bootstrap/cache || true

# Zet error handling weer aan voor de server start
set -e

# Start de Laravel server
echo "Laravel server starten op 0.0.0.0:8000..."
exec php artisan serve --host=0.0.0.0 --port=8000

