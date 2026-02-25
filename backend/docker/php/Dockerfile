FROM php:8.4-fpm-alpine

# Install system dependencies needed by PHP extensions and Composer
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    # oniguruma-dev is required by the mbstring extension
    oniguruma-dev

# Install PHP extensions required by Laravel 12
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    mbstring \
    bcmath \
    opcache

# Install Composer from its official image (avoids curl install quirks)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer manifests first so Docker can cache the vendor layer.
# The vendor layer only rebuilds when composer.json / composer.lock changes,
# not on every source file change — keeps rebuilds fast.
COPY composer.json composer.lock* ./

# Install dependencies without running scripts (no artisan yet, no autoloader)
RUN composer install \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist

# Copy the full application source
COPY . .

# Generate the optimised autoloader now that all source files are present
RUN composer dump-autoload --optimize

# Create storage directories Laravel expects (they are gitignored so won't exist
# in a fresh clone) and set ownership to www-data (the php-fpm user)
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
