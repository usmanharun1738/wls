# ──────────────────────────────────────────────
# Docker build for WLS — uses pre-built assets
# ──────────────────────────────────────────────
# Run locally first:  npm install && npm run build
# Then:               docker build -t registry.africastalking.dev/z1wcnco/wildlife-support:latest .

FROM php:8.5-fpm AS base

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    default-mysql-client nginx supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy app code (excluding vendor, node_modules via .dockerignore)
COPY . .

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader

# Copy nginx + supervisor configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Laravel setup
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions \
    storage/framework/views bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
