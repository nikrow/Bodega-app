# ... (etapas anteriores) ...

# --- Etapa 3: Construcción de la imagen final con FrankenPHP ---
FROM dunglas/frankenphp:1.2.5-php8.2-bookworm AS final

WORKDIR /app

ENV SERVER_NAME=gjs.cl

# 1. Habilitar PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 2. Instalamos dependencias del sistema
RUN apt-get update \
    && apt-get install -y \
    zip \
    libzip-dev \
    gnupg gosu curl ca-certificates zip unzip git sqlite3 libcap2-bin \
    libpng-dev libonig-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    python3 dnsutils librsvg2-bin fswatch ffmpeg nano \
    && rm -rf /var/lib/apt/lists/*

# 3. Instalamos extensiones de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl

# 4. Copiamos composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Copiar archivos de configuración de Composer primero
COPY composer.* /app/

# 7. Copiamos el resto de la aplicación
COPY . /app

# 6. Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 8. Configuramos Laravel
RUN mkdir -p /app/storage/logs \
    && php artisan config:clear \
    && php artisan octane:install

# --- Copiamos los artefactos de las etapas anteriores ---

# Copiamos los assets construidos desde la etapa `build-env`
COPY --from=build-env /app/public/build /app/public/build

# Copiamos la instalación global de Puppeteer, chromium y node_modules desde la etapa `puppeteer-install`
COPY --from=puppeteer-install /puppeteer-install/node_modules /app/node_modules
COPY --from=puppeteer-install /usr/local/bin/ /usr/local/bin/
COPY --from=puppeteer-install /usr/lib/node_modules/ /usr/lib/node_modules/
COPY --from=puppeteer-install /root/.npm /root/.npm
COPY --from=puppeteer-install /puppeteer-chromium /puppeteer-chromium
COPY --from=puppeteer-install /usr/bin/chromium /usr/bin/chromium

# Exponemos los puertos
EXPOSE 8000
EXPOSE 80
EXPOSE 443

# Comando de inicio
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
