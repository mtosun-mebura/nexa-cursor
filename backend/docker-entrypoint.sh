#!/bin/bash
set -e

# Controleer of .env bestaat, anders maak een basis .env aan
if [ ! -f .env ]; then
    echo "Geen .env bestand gevonden, basis .env aanmaken..."
    cp .env.example .env 2>/dev/null || echo "APP_NAME=Laravel" > .env
fi

# Als APP_KEY niet is ingesteld via environment, genereer dan een nieuwe
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "APP_KEY niet gevonden in environment, genereren nieuwe key..."
    php artisan key:generate --force --no-interaction
else
    echo "APP_KEY gevonden in environment, instellen in .env..."
    # Update of voeg APP_KEY toe aan .env
    if grep -q "^APP_KEY=" .env; then
        sed -i.bak "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
    else
        echo "APP_KEY=${APP_KEY}" >> .env
    fi
fi

# Start de Laravel server
exec php artisan serve --host=0.0.0.0 --port=8000

