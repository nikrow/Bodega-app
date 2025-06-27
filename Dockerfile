FROM dunglas/frankenphp:1.7.0-php8.4-bookworm

WORKDIR /app

COPY . /app

# Argumento para la versión de Node.js
ARG NODE_VERSION=22

# Configurar variables de entorno para HTTP en puerto 8080
ENV SERVER_NAME=:8080
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies including Caddy
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    zip libzip-dev gnupg gosu curl ca-certificates unzip git sqlite3 libcap2-bin \
    libpng-dev libonig-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    python3 dnsutils librsvg2-bin fswatch ffmpeg nano chromium fonts-liberation libgbm-dev libnss3 \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl
# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP para producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=512M" >> $PHP_INI_DIR/php.ini \
    && echo "upload_max_filesize=100M" >> $PHP_INI_DIR/php.ini \
    && echo "post_max_size=100M" >> $PHP_INI_DIR/php.ini \
    && echo "max_execution_time=300" >> $PHP_INI_DIR/php.ini \
    && echo "date.timezone=America/Santiago" >> $PHP_INI_DIR/php.ini

# Optimizar OPCache
RUN echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=3600" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/conf.d/opcache.ini

# Instalar Node.js y herramientas JavaScript.
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g npm@10.9.0 pnpm bun \
    && rm -rf /var/lib/apt/lists/*

# Instalar dependencias de Composer y optimizar
RUN composer install --no-dev --optimize-autoloader

# Install Puppeteer globally
RUN npm install --location=global puppeteer@22.8.2
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

RUN npm install && npm run build

RUN php artisan config:clear \
    && php artisan migrate --force \
    && php artisan octane:install \
    && php artisan storage:link \
    && php artisan optimize \
    && php artisan filament:optimize

# Configurar permisos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public \
    && chmod -R 775 /app/storage /app/bootstrap/cache /app/public \
    && find /app/storage -type d -print0 | xargs -0 chmod 2775 \
    && find /app/storage -type f -print0 | xargs -0 chmod 0664
    
# Exponer el puerto necesario
EXPOSE 8080

# Iniciar la aplicación
ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8080"]