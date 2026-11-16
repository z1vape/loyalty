# Stage 1: Build frontend assets
FROM node:22.11-alpine AS node-builder
WORKDIR /app
COPY package.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Install PHP dependencies
FROM composer:2.8 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Stage 3: Production Runtime
FROM dunglas/frankenphp:1.2.5-php8.3-alpine AS runner
WORKDIR /app

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    bash \
    sqlite

# Install php extensions using helper script
RUN install-php-extensions gd zip opcache bcmath pcntl

# Set PHP configurations for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy application code
COPY --from=composer-builder /app/vendor ./vendor
COPY --from=node-builder /app/public/build ./public/build
COPY . .

# Setup directories and permissions
RUN mkdir -p storage/framework/cache/data \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p bootstrap/cache \
    && mkdir -p database_persistent \
    && touch database_persistent/database.sqlite \
    && chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache database_persistent

# Copy production entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Configure FrankenPHP environment
ENV SERVER_NAME=:8080
ENV USER=www-data

USER www-data

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint"]
