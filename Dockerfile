FROM php:8.3-fpm

WORKDIR /app

# Instala dependencias
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    zip \
    nodejs \
    npm \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring bcmath exif pcntl gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Verifica rutas de binarios
RUN which php-fpm && which nginx

# Copia composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia todo el código (incluyendo artisan)
COPY . /app

# Verifica que artisan exista
RUN ls -la /app/artisan

# Copia composer.json y composer.lock para aprovechar el caché
COPY composer.json composer.lock /app/

# Verifica que composer.json y composer.lock existan
RUN ls -la /app/composer.json /app/composer.lock

# Instala dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && ls -la /app/vendor/autoload.php

# Verifica que index.php exista
RUN ls -la /app/public/index.php

# Configura Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Configura Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Permisos para todo /app y subdirectorios
RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]