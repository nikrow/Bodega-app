FROM php:8.3-fpm

WORKDIR /app

# Instalamos dependencias del sistema y configuramos
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip \
    libicu-dev default-mysql-client chromium fonts-liberation libgbm-dev libnss3 nano \
    && rm -rf /var/lib/apt/lists/*

# Instalamos extensiones PHP
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
    pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Redis y APCu para caché de alto rendimiento
RUN pecl install redis apcu \
    && docker-php-ext-enable redis apcu

# Establecemos variables de entorno
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="$PATH:/root/.composer/vendor/bin"

# Instalamos una versión específica de Node.js (22.x) y npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && rm -rf /var/lib/apt/lists/*

# Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuramos PHP para producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=512M" >> "$PHP_INI_DIR/php.ini" \
    && echo "max_execution_time=300" >> "$PHP_INI_DIR/php.ini"

# OPcache optimizado para prod
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.enable_cli=1"; \
    echo "opcache.memory_consumption=256"; \
    echo "opcache.interned_strings_buffer=16"; \
    echo "opcache.max_accelerated_files=50000"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.save_comments=1"; \
    echo "opcache.fast_shutdown=1"; \
} > $PHP_INI_DIR/conf.d/opcache.ini

# Copiamos la aplicación
COPY . /app

# Instalamos dependencias
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs \
    && npm ci \
    && npm run build \
    && rm -rf node_modules

# Instalamos Puppeteer globalmente
RUN npm install --location=global puppeteer@22.8.2

# Configuramos permisos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Aseguramos que el script post-deploy.sh tenga permisos de ejecución
RUN chmod +x /app/post-deploy.sh

# Exponemos el puerto
EXPOSE 8080

# Configuración para ejecutar el script post-deploy y luego iniciar el servidor
CMD /app/post-deploy.sh && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}