# =============================================================================
# Stage 1: Build Frontend Assets
# =============================================================================
FROM node:22-alpine AS frontend-build

WORKDIR /app

# Copy package files first (better layer caching)
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci --silent

# Copy source files needed for build
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY jsconfig.json ./

# Build production assets
RUN npm run build

# =============================================================================
# Stage 2: PHP Runtime with FrankenPHP
# =============================================================================
FROM dunglas/frankenphp:php8.3-alpine AS runtime

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    zip unzip curl \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev \
    netcat-openbsd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        zip \
        gd \
        pdo \
        pdo_mysql \
        opcache \
    && rm -rf /var/cache/apk/*

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# OPcache configuration for production
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=8"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.save_comments=1"; \
    echo "opcache.jit=1255"; \
    echo "opcache.jit_buffer_size=64M"; \
} > "$PHP_INI_DIR/conf.d/opcache.ini"

# PHP production settings
RUN { \
    echo "memory_limit=256M"; \
    echo "upload_max_filesize=50M"; \
    echo "post_max_size=50M"; \
    echo "max_execution_time=120"; \
    echo "expose_php=Off"; \
} > "$PHP_INI_DIR/conf.d/production.ini"

WORKDIR /app

# Copy composer files first (better layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev, optimized)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --prefer-dist \
    --no-scripts

# Copy application code
COPY . .

# Copy built frontend assets from Stage 1
COPY --from=frontend-build /app/public/build ./public/build

# Run composer scripts after code is copied
RUN composer dump-autoload --optimize

# Create storage directories and set permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Copy and prepare entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port (Railway sets PORT env var)
EXPOSE 8080

# Default port (Railway overrides via PORT env var)
ENV PORT=8080
ENV APP_ENV=production
ENV APP_DEBUG=false

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -sf http://localhost:$PORT/ || exit 1

# Entrypoint handles migrations and caching
ENTRYPOINT ["docker-entrypoint.sh"]

# Use Laravel's built-in server (reliable for Railway)
# For high-traffic production, consider adding Laravel Octane
CMD php artisan serve --host=0.0.0.0 --port=$PORT
