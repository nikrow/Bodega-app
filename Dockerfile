# --- Etapa 1: Construcción de assets con Node.js ---
FROM node:22-bookworm AS build-env

WORKDIR /app

# 1. Copiar solo los archivos necesarios para npm primero
COPY package*.json ./
COPY package-lock.json ./

# 2. Instalar dependencias de Node.js
RUN npm ci \
    && npm audit fix

# 3. Copiar el resto del código relacionado con Node.js
COPY vite.config.js ./
COPY resources ./resources

# 4. Construir los activos
RUN npm run build

# --- Etapa 2: Instalación de Puppeteer ---
FROM node:22-bookworm AS puppeteer-install

# WORKDIR para evitar conflictos con node_modules de la etapa anterior
WORKDIR /puppeteer-install

# Establecer la ruta de descarga y del ejecutable de Chromium
ENV PUPPETEER_DOWNLOAD_PATH=/puppeteer-chromium
ENV PUPPETEER_EXECUTABLE_PATH=/puppeteer-chromium/chrome/linux-122.0.6261.57/chrome-linux/chrome
ENV PUPPETEER_CHROMIUM_REVISION=122.0.6261.57

# Instalamos dependencias del sistema para Puppeteer
RUN apt-get update && apt-get install -y \
    gconf-service libasound2 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget libgbm-dev libxshmfence-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar chromium
RUN apt-get update && apt-get install -y chromium

# Instalamos Puppeteer v22.8.2 globalmente
RUN npm install --location=global --unsafe-perm puppeteer@24.0.0

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

# 6. Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 7. Copiamos el resto de la aplicación
COPY . /app

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
