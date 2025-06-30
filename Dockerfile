# ====================================================================
# Etapa 1: Construcción de Assets (Node.js y frontend)
# Se usa una imagen base de Node.js para construir los assets de frontend.
# ====================================================================
FROM node:22-slim AS assets

WORKDIR /app

# Copiar solo los archivos necesarios para instalar dependencias de Node.js
COPY package.json package-lock.json ./

# Instalar dependencias de Node.js
RUN npm install

# Copiar el resto de los archivos de la aplicación
COPY . .

# Construir los assets de frontend (CSS, JS, etc.)
RUN npm run build \
    && rm -rf node_modules # Limpiar node_modules para reducir el tamaño

# ====================================================================
# Etapa 2: Construcción de la Aplicación PHP y preparación del entorno
# Esta etapa instala PHP, extensiones, dependencias de sistema y PHP Composer.
# ====================================================================
FROM php:8.4-cli AS app_builder

WORKDIR /app

# Habilitar configuraciones de producción de PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Instalar dependencias del sistema usando apt-get
RUN apt-get update && apt-get install -y --no-install-recommends \
    zip libzip-dev gnupg gosu curl ca-certificates unzip git sqlite3 libcap2-bin \
    libpng-dev libonig-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    python3 dnsutils librsvg2-bin fswatch ffmpeg nano \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Instalar extensiones PHP, incluyendo intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl

# Copiar Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar el código fuente de la aplicación PHP
COPY . /app

# Copiar los assets construidos desde la etapa 'assets'
COPY --from=assets /app/public /app/public

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader

# Preparar la aplicación (directorios, cache de configuración, etc.)
RUN mkdir -p /app/storage/logs \
    && php artisan config:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan octane:install --server=roadrunner \
    && php artisan filament:optimize \
    && chmod -R 775 /app/storage /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

# ====================================================================
# Etapa 3: Etapa final - Imagen de Producción para Octane
# Usa php:8.4-cli-alpine para una imagen más ligera
# ====================================================================
FROM php:8.4-cli

WORKDIR /app

# Instalar dependencias necesarias para Alpine, incluyendo ICU para la extensión intl
RUN apk add --no-cache \
    curl \
    libpng \
    libzip \
    icu-libs \
    freetype \
    libjpeg-turbo \
    libwebp \
    oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl \
    && apk del .build-deps

# Copiar el binario de RoadRunner desde la imagen oficial
COPY --from=ghcr.io/roadrunner-server/roadrunner:2024.3.2 /usr/bin/rr /usr/local/bin/rr

# Copiar todos los archivos de la aplicación desde la etapa 'app_builder'
COPY --from=app_builder /app /app

# Exponer el puerto (definido por la variable de entorno PORT)
EXPOSE ${PORT}

# Iniciar RoadRunner con Laravel Octane
CMD ["rr", "serve", "-c", ".rr.yaml"]