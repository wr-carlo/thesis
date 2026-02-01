# --- Node build stage ---
    FROM node:22-bookworm-slim AS nodebuild
    WORKDIR /app
    COPY package*.json ./
    RUN npm ci
    COPY . .
    RUN npm run build
    
    # --- PHP runtime stage ---
    FROM dunglas/frankenphp:php8.3-bookworm
    COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
    
    RUN apt-get update && apt-get install -y \
        libzip-dev zip unzip \
        libpng-dev libjpeg-dev libfreetype6-dev \
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install zip gd pdo pdo_mysql \
        && rm -rf /var/lib/apt/lists/*
    
    WORKDIR /app
    COPY . .
    
    # copy built Vite assets
    COPY --from=nodebuild /app/public/build /app/public/build
    
    RUN composer install --no-dev --optimize-autoloader
    
    RUN mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache \
        && chmod -R 777 storage bootstrap/cache
    
    EXPOSE 8080
    
    # keep CMD simple; Railway Start Command will handle migrate/wait
    CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
    