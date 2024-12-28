FROM dunglas/frankenphp:1.2.5-php8.2-bookworm

WORKDIR /app

ENV SERVER_NAME=gjs.cl
ARG NODE_VERSION=22

# Enable PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY . /app

# Instalamos dependencias del sistema
RUN apt-get update \
    && apt-get install -y \
    zip \
    libzip-dev \
    gnupg gosu curl ca-certificates zip unzip git sqlite3 libcap2-bin \
    libpng-dev libonig-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    python3 dnsutils librsvg2-bin fswatch ffmpeg nano \
    # Dependencias para Puppeteer/Chromium
    wget gnupg libnss3 libxss1 libasound2 fonts-liberation xdg-utils libu2f-udev dbus \
    && rm -rf /var/lib/apt/lists/*

# Instalamos extensiones de PHP necesarias para Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl

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

# Instalamos Puppeteer como dependencia global de Node.js
RUN npm install -g puppeteer \
    && PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true npm install puppeteer-core

# Instalamos dependencias de PHP y Laravel Octane
RUN composer install --no-dev --optimize-autoloader

RUN mkdir -p /app/storage/logs
RUN php artisan config:clear
RUN php artisan octane:install

# Instalamos dependencias de Node.js y construimos los activos
RUN npm install \
    && npm audit fix \
    && npm run build

# Exponemos los puertos necesarios
EXPOSE 8000
EXPOSE 80
EXPOSE 443

# Comando de inicio
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]

