FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev git unzip libzip-dev libicu-dev libonig-dev \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql intl zip gd \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies ZONDER scripts (artisan bestaat nog niet)
RUN composer install --no-interaction --prefer-dist --no-scripts

# PHP upload limits (voor php artisan serve)
COPY php-upload.ini /usr/local/etc/php/conf.d/99-upload.ini

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


