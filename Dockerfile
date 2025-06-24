FROM php:8.3-fpm

WORKDIR /app

# Instala dependencias (eliminado nginx)
RUN apt-get update && apt-get install -y \
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

# Verifica rutas de binarios (eliminado which nginx)
RUN which php-fpm

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

# Configura Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Crea el directorio de logs para Supervisor si no existe y asegura permisos
RUN mkdir -p /var/log/supervisor && \
    chown -R www-data:www-data /var/log/supervisor && \
    chmod -R 755 /var/log/supervisor

# Permisos para todo /app y subdirectorios
RUN chown -R www-data:www-data /app && chmod -R 775 /app

# Asegura que /var/log tenga los permisos correctos para que los workers escriban logs
RUN chown www-data:www-data /var/log && chmod 775 /var/log

EXPOSE 8000

# El comando de inicio sigue siendo supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]