FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev git unzip libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies ZONDER scripts (artisan bestaat nog niet)
RUN composer install --no-interaction --prefer-dist --no-scripts

# Copy rest of code (entrypoint script included)
COPY . /var/www/html

# Nu artisan bestaat: run composer scripts (optioneel, maar kan handig zijn)
RUN composer dump-autoload --optimize || true

# Setup entrypoint (al gekopieerd via COPY . hierboven)
RUN chmod +x /var/www/html/docker-entrypoint.sh && \
    cp /var/www/html/docker-entrypoint.sh /usr/local/bin/entrypoint.sh && \
    chmod +x /usr/local/bin/entrypoint.sh

# Create storage directories
RUN mkdir -p storage/framework/{cache,views,sessions} \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache || true

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]


