#!/bin/bash

# Improved server setup script for NEXA deployment
# Handles different Ubuntu/Debian versions and PHP availability

echo "Setting up NEXA deployment server..."

# Update system
sudo apt update && sudo apt upgrade -y

# Install basic requirements
sudo apt install -y software-properties-common curl wget git

# Add PHP repository for latest PHP versions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Detect available PHP versions
echo "Detecting available PHP versions..."
AVAILABLE_PHP_VERSIONS=()

# Check for PHP 8.2
if apt list --installed 2>/dev/null | grep -q php8.2 || apt-cache policy php8.2-fpm | grep -q "Candidate:"; then
    AVAILABLE_PHP_VERSIONS+=("8.2")
fi

# Check for PHP 8.1
if apt list --installed 2>/dev/null | grep -q php8.1 || apt-cache policy php8.1-fpm | grep -q "Candidate:"; then
    AVAILABLE_PHP_VERSIONS+=("8.1")
fi

# Check for PHP 8.0
if apt list --installed 2>/dev/null | grep -q php8.0 || apt-cache policy php8.0-fpm | grep -q "Candidate:"; then
    AVAILABLE_PHP_VERSIONS+=("8.0")
fi

# If no PHP versions found, try to install PHP 8.1
if [ ${#AVAILABLE_PHP_VERSIONS[@]} -eq 0 ]; then
    echo "No PHP versions found, installing PHP 8.1..."
    sudo apt install -y php8.1-fpm php8.1-cli php8.1-mysql php8.1-xml php8.1-gd php8.1-curl php8.1-mbstring php8.1-zip php8.1-bcmath php8.1-soap php8.1-redis php8.1-intl
    PHP_VERSION="8.1"
else
    # Use the highest available version
    PHP_VERSION=${AVAILABLE_PHP_VERSIONS[0]}
    echo "Using PHP version: $PHP_VERSION"

    # Install PHP packages for the detected version
    sudo apt install -y php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-mysql php${PHP_VERSION}-xml php${PHP_VERSION}-gd php${PHP_VERSION}-curl php${PHP_VERSION}-mbstring php${PHP_VERSION}-zip php${PHP_VERSION}-bcmath php${PHP_VERSION}-soap php${PHP_VERSION}-redis php${PHP_VERSION}-intl
fi

# Install other required packages
sudo apt install -y nginx composer nodejs npm mysql-server

# Create deployment directory
sudo mkdir -p /var/www/nexa
sudo chown -R $USER:$USER /var/www/nexa

# Optional: Initialize git repository for easier updates
echo "Do you want to initialize git repository for easier updates? (y/n)"
read -r response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "Enter your GitHub repository URL (e.g., https://github.com/username/nexa-cursor.git):"
    read -r repo_url
    if [ ! -z "$repo_url" ]; then
        cd /var/www/nexa
        git init
        git remote add origin "$repo_url"
        git fetch origin
        git checkout -b main origin/main
        echo "Git repository initialized!"
    fi
fi

# Create nginx configuration with dynamic PHP version
sudo tee /etc/nginx/sites-available/nexa << EOF
server {
    listen 80;
    server_name _;
    root /var/www/nexa/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable the site
sudo ln -sf /etc/nginx/sites-available/nexa /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test nginx configuration
sudo nginx -t

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php${PHP_VERSION}-fpm

# Enable services to start on boot
sudo systemctl enable nginx
sudo systemctl enable php${PHP_VERSION}-fpm

# Create .env file template
cat > /var/www/nexa/.env.example << EOF
APP_NAME=NEXA
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://192.168.178.116

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexa
DB_USERNAME=nexa_user
DB_PASSWORD=your_secure_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="\${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="\${PUSHER_HOST}"
VITE_PUSHER_PORT="\${PUSHER_PORT}"
VITE_PUSHER_SCHEME="\${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="\${PUSHER_APP_CLUSTER}"
EOF

# Install Composer dependencies if Laravel project exists
if [ -f "/var/www/nexa/backend/composer.json" ]; then
    echo "Installing Composer dependencies..."
    cd /var/www/nexa/backend

    # Create necessary Laravel directories
    mkdir -p storage/logs
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p bootstrap/cache

    composer install --no-dev --optimize-autoloader --no-interaction

    # Generate application key
    php artisan key:generate

    # Set proper permissions
    sudo chown -R www-data:www-data /var/www/nexa
    sudo chmod -R 755 /var/www/nexa
    sudo chmod -R 775 /var/www/nexa/storage
    sudo chmod -R 775 /var/www/nexa/bootstrap/cache

    echo "Laravel setup completed!"
fi

echo "Server setup completed!"
echo "PHP Version: $PHP_VERSION"
echo "Next steps:"
echo "1. Configure your database (MySQL/MariaDB)"
echo "2. Copy .env.example to .env and configure your settings"
echo "3. Set up GitHub secrets for deployment"
echo "4. Test the deployment pipeline"
