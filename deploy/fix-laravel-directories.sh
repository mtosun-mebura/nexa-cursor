#!/bin/bash

# Fix Laravel directories script
# Run this on your server if bootstrap/cache directory is missing

echo "Creating Laravel directories..."

# Navigate to Laravel project
cd /var/www/nexa/backend

# Create necessary directories
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Set proper permissions
sudo chown -R www-data:www-data /var/www/nexa
sudo chmod -R 755 /var/www/nexa
sudo chmod -R 775 /var/www/nexa/storage
sudo chmod -R 775 /var/www/nexa/bootstrap/cache

echo "Laravel directories created successfully!"
echo "You can now run: php artisan key:generate"
