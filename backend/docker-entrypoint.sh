#!/bin/bash
set -e

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
        if grep -q "^${var_name}=" .env; then
            sed -i.bak "s|^${var_name}=.*|${var_name}=${var_value}|" .env
        else
            echo "${var_name}=${var_value}" >> .env
        fi
    fi
}

# Zet database configuratie van environment variables naar .env
if [ -n "$DB_CONNECTION" ]; then
    echo "Database configuratie instellen: DB_CONNECTION=${DB_CONNECTION}"
    set_env_var "DB_CONNECTION" "$DB_CONNECTION"
    set_env_var "DB_HOST" "$DB_HOST"
    set_env_var "DB_PORT" "$DB_PORT"
    set_env_var "DB_DATABASE" "$DB_DATABASE"
    set_env_var "DB_USERNAME" "$DB_USERNAME"
    set_env_var "DB_PASSWORD" "$DB_PASSWORD"
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

# Clear config cache zodat Laravel de nieuwe config leest
echo "Config cache legen..."
php artisan config:clear || true
php artisan cache:clear || true

# Start de Laravel server
exec php artisan serve --host=0.0.0.0 --port=8000

