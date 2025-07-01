FROM php:8.4-fpm

WORKDIR /app

# Instalar dependencias del sistema y herramientas de compilación
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    default-mysql-client \
    nano \
    # Dependencias para Puppeteer si no están en la base
    chromium-browser \
    libnss3 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libgdk-pixbuf2.0-0 \
    libglib2.0-0 \
    libnspr4 \
    libxcomposite1 \
    libxdamage1 \
    libxext6 \
    libxrandr2 \
    libxshmfence6 \
    libexpat1 \
    libfontconfig1 \
    libjpeg-dev \
    libwebp-dev \
    libxkbcommon0 \
    libxmuu1 \
    libgbm1 \
    libasound2 \
    libatspi2.0-0 \
    libgtk-3-0 \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP incluyendo intl
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Establecer variables de entorno
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="$PATH:/root/.composer/vendor/bin"

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP para producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=512M" >> $PHP_INI_DIR/php.ini \
    && echo "upload_max_filesize=100M" >> $PHP_INI_DIR/php.ini \
    && echo "post_max_size=100M" >> $PHP_INI_DIR/php.ini \
    && echo "max_execution_time=300" >> $PHP_INI_DIR/php.ini

# Optimizar OPCache
RUN echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=3600" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/conf.d/opcache.ini

# Instalar Node.js y herramientas JavaScript.

ARG NODE_VERSION=22
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g pnpm bun \
    && rm -rf /var/lib/apt/lists/*

# Copiamos la aplicación
COPY . /app
COPY my.cnf /etc/mysql/conf.d/my.cnf

# Instalar dependencias de Composer y optimizar
RUN composer install --no-dev --optimize-autoloader

# Instalar dependencias de Node.js y construir assets
RUN npm install \
    && npm run build

# Install Puppeteer globally y configura la ruta
RUN npm install --location=global puppeteer@22.8.2
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Ejecutar comandos de optimización de Laravel
RUN php artisan config:clear \
    && php artisan octane:install \
    && php artisan storage:link \
    && php artisan optimize \
    && php artisan filament:optimize

# Configurar permisos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public \
    && chmod -R 775 /app/storage /app/bootstrap/cache /app/public \
    && find /app/storage -type d -print0 | xargs -0 chmod 2775 \
    && find /app/storage -type f -print0 | xargs -0 chmod 0664

# Exponemos el puerto
EXPOSE 8080

# Configuración para ejecutar el script post-deploy y luego iniciar el servidor
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}