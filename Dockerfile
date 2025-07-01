FROM php:8.3-fpm

WORKDIR /app

# Instalamos dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    # Dependencias para intl
    libicu-dev \
    # Agregamos default-mysql-client para mysqldump
    default-mysql-client \
    # Agregamos nano
    nano \
    chromium \
    fonts-liberation libgbm-dev libnss3 \
    && rm -rf /var/lib/apt/lists/*

# Instalamos extensiones PHP incluyendo intl
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

# Establecemos variables de entorno
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="$PATH:/root/.composer/vendor/bin"
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar PHP para producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit=512M" >> "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize=100M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size=100M" >> "$PHP_INI_DIR/php.ini" \
    && echo "max_execution_time=300" >> "$PHP_INI_DIR/php.ini"

# Optimizamos OPCache
RUN echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=3600" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/conf.d/opcache.ini

# Instalar Node.js y herramientas JavaScript
ARG NODE_VERSION=22
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@10.9.0 \
    && npm install -g pnpm \
    && npm install -g bun \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor -o /usr/share/keyrings/yarnkey.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y yarn \
    && rm -rf /var/lib/apt/lists/*

    # Instalamos Puppeteer globalmente
RUN npm install --location=global puppeteer@22.8.2

# Copiamos la aplicación
COPY . /app

# Instalamos dependencias
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs \
    && npm ci \
    && npm run build \
    && rm -rf node_modules

RUN mkdir -p /app/storage/logs
RUN php artisan config:clear \
    && php artisan storage:link \
    && php artisan optimize \
    && php artisan filament:optimize


# Configuramos permisos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Aseguramos que el script post-deploy.sh tenga permisos de ejecución
RUN chmod +x /app/post-deploy.sh

# Exponemos el puerto
EXPOSE 8080

ENTRYPOINT ["php", "artisan", "serve", "--host=0.0.0.0"]