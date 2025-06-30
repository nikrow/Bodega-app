# ====================================================================
# Etapa 1: Construcción de Assets (Node.js y frontend)
# Se usa una imagen base de Node.js para construir los assets de frontend.
# ====================================================================
FROM node:22-slim AS assets

WORKDIR /app

# Copiar solo los archivos necesarios para instalar dependencias de Node.js
# Esto optimiza el cache de Docker.
COPY package.json package-lock.json ./

# Instalar dependencias de Node.js
# Usamos --omit=dev para evitar instalar dependencias de desarrollo en el entorno final
# si alguna librería lo permite.
RUN npm install --omit=dev

# Copiar el resto de los archivos de la aplicación
COPY . .

# Construir los assets de frontend (CSS, JS, etc.)
RUN npm run build \
    && rm -rf node_modules # Limpiar node_modules después de la construcción para reducir el tamaño temporal

# ====================================================================
# Etapa 2: Construcción de la Aplicación PHP y preparación del entorno de ejecución
# Esta etapa instala PHP, extensiones, dependencias de sistema y PHP Composer.
# Aquí se preinstala RoadRunner y se prepara la aplicación para Octane.
# ====================================================================
FROM php:8.4-cli-slim AS app_builder

# Usamos la imagen CLI-slim porque RoadRunner será el que maneje el proceso,
# no PHP-FPM. Además, 'slim' es más pequeña que la versión 'fpm' por defecto.

WORKDIR /app

# Habilitar configuraciones de producción de PHP (debe ser php.ini-production)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Instalar dependencias del sistema usando apt-get.
# Incluye dependencias para PHP, Composer y las necesarias para RoadRunner/Octane.
# Eliminamos Chromium/Puppeteer aquí para mantener la imagen ligera,
# a menos que sea ABSOLUTAMENTE necesario para tu aplicación en tiempo real.
# Si lo necesitas, reintroduce la sección de 'chromium-browser' y sus libs.
RUN apt-get update && apt-get install -y --no-install-recommends \
    zip libzip-dev gnupg gosu curl ca-certificates unzip git sqlite3 libcap2-bin \
    libpng-dev libonig-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    python3 dnsutils librsvg2-bin fswatch ffmpeg nano \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Instalar extensiones PHP.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl

# Instalar RoadRunner binario (versión compatible con Octane)
# Revisa la documentación de Octane para la versión recomendada de RoadRunner.
RUN curl -sS https://raw.githubusercontent.com/spiral/roadrunner-cli/master/rr | php -- --ansi --version=2.x -n -q --install \
    && mv rr /usr/local/bin/rr \
    && chmod +x /usr/local/bin/rr \
    && rr get-binary 2.11.2 --cpu=amd64 # Ajusta la versión y CPU según sea necesario

# Copiar Composer desde su imagen oficial.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar el código fuente de la aplicación PHP (excluyendo node_modules si es posible).
COPY . /app

# Copiar los assets construidos desde la etapa 'assets'.
COPY --from=assets /app/public /app/public

# Instalar dependencias de PHP.
RUN composer install --no-dev --optimize-autoloader

# Preparar la aplicación (directorios, cache de configuración, etc.)
# Aquí incluimos 'octane:install' para que Octane configure sus 
RUN mkdir -p /app/storage/logs \
    && php artisan config:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan octane:install \
    && php artisan filament:optimize \
    && chmod -R 775 /app/storage /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache
# ====================================================================
# Etapa 3: Etapa final - Imagen de Producción para Octane
# Esta etapa toma lo esencial de la etapa 'app_builder' para una imagen final limpia.
# ====================================================================
FROM php:8.4-cli-slim

WORKDIR /app

# Copiar todos los archivos de la aplicación, incluyendo dependencias y assets,
# desde la etapa 'app_builder'.
COPY --from=app_builder /app /app

ENTRYPOINT ["sh", "-c", " php artisan octane:start --host=0.0.0.0 --port=${PORT}"]
