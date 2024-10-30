FROM dunglas/frankenphp:1.2.5-php8.2-bookworm

WORKDIR /app

ARG NODE_VERSION=22

# Enable PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY . .

# Instalamos dependencias del sistema
RUN apt-get update \
    && apt-get install -y \
    zip \
    libzip-dev \
    gnupg gosu curl ca-certificates zip unzip git sqlite3 libcap2-bin \
    libpng-dev libonig-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    python3 dnsutils librsvg2-bin fswatch ffmpeg nano \
    && rm -rf /var/lib/apt/lists/*

# Instalamos extensiones de PHP necesarias para Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

RUN curl -sS https://getcomposer.org/installer | php -- \
     --install-dir=/usr/local/bin --filename=composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalamos Node.js y otras herramientas de JavaScript
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

# Instalamos dependencias de PHP y Laravel Octane
RUN composer update && composer install \

RUN composer require laravel/octane --server=frankenphp \

RUN mkdir -p /app/storage/logs

RUN php artisan cache:clear
RUN php artisan view:clear
RUN php artisan config:clear
RUN php artisan octane:install --server=frankenphp

# Instalamos dependencias de Node.js y construimos los activos
RUN npm install \
    && npm run build

# Configuramos permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
COPY .env.example .env
# Exponemos los puertos necesarios
EXPOSE 8000
EXPOSE 80
EXPOSE 443

# Comando de inicio
CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]

