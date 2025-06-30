# ====================================================================
# Etapa 1: Construcción de Assets (Node.js y frontend)
# Usa una imagen base de Node.js para construir los assets de frontend.
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
    && rm -rf node_modules

# ====================================================================
# Etapa 2: Construcción de la Aplicación PHP y preparación del entorno
# Usa php:8.4-cli (Debian) para instalar PHP, extensiones y Composer.
# ====================================================================
FROM php:8.4-cli AS app_builder

WORKDIR /app

# Habilitar configuraciones de producción de PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Instalar dependencias del sistema usando apt-get
RUN apt-get update && apt-get install -y --no-install-recommends \
    zip libzip-dev curl ca-certificates unzip git sqlite3 libcap2-bin \
    libpng-dev libonig-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Instalar extensiones PHP, incluyendo sockets e intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl sockets

# Copiar Composer desde su imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar el código fuente de la aplicación PHP
COPY . /app

# Copiar los assets construidos desde la etapa 'assets'
COPY --from=assets /app/public /app/public

# Ajustar permisos para evitar ejecutar Composer como root
RUN useradd -m -u 1000 appuser \
    && chown -R appuser:appuser /app

USER appuser

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader

USER root

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
# Etapa 3: Imagen de Producción para Octane
# ====================================================================
FROM php:8.4-cli

WORKDIR /app

# Instalar dependencias necesarias para producción
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl libpng libzip icu-devtools libjpeg62-turbo libwebp libonig \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Instalar extensiones PHP necesarias para producción
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl sockets

# Copiar el binario de RoadRunner desde la imagen oficial
COPY --from=ghcr.io/roadrunner-server/roadrunner:2024.3.2 /usr/bin/rr /usr/local/bin/rr

# Copiar todos los archivos de la aplicación desde la etapa 'app_builder'
COPY --from=app_builder /app /app

# Ajustar permisos para el usuario www-data
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Exponer el puerto definido por la variable de entorno PORT
EXPOSE ${PORT}

# Iniciar RoadRunner con Laravel Octane
CMD ["rr", "serve", "-c", ".rr.yaml"]