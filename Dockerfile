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

# Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuramos PHP para producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Optimizamos OPCache
RUN echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=60" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/conf.d/opcache.ini

# Copiamos la aplicación
COPY . /app

# Instalamos dependencias
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs \
    && npm ci \
    && npm run build \
    && rm -rf node_modules

# Configuramos permisos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Creamos el script post-deploy
RUN echo '#!/bin/bash \n\
set -e \n\
echo "===========================================" \n\
echo "Iniciando comandos post-despliegue" \n\
echo "===========================================" \n\
# Verificar si mysqldump está instalado \n\
if ! command -v mysqldump &> /dev/null; then \n\
    echo "ERROR: mysqldump no está instalado. Asegúrate de que default-mysql-client esté instalado en el Dockerfile." \n\
    exit 1 \n\
fi \n\
# PASO 1: Limpieza de caché y archivos temporales \n\
echo "PASO 1: Limpieza de caché y archivos temporales" \n\
echo "----------------------------------------------" \n\
php artisan config:clear \n\
php artisan cache:clear \n\
php artisan route:clear \n\
php artisan view:clear \n\
php artisan optimize:clear \n\
composer dump-autoload \n\
echo "Limpieza completada." \n\
# PASO 2: Configuración y optimización \n\
echo "PASO 2: Configuración y optimización" \n\
echo "-----------------------------------" \n\
# Ejecutamos migraciones \n\
echo "Ejecutando migraciones..." \n\
php artisan migrate --force \n\
# Optimizaciones \n\
echo "Aplicando optimizaciones..." \n\
php artisan config:cache \n\
php artisan event:cache \n\
php artisan route:cache \n\
php artisan view:cache \n\
php artisan filament:optimize \n\
# Generar enlaces simbólicos \n\
echo "Generando enlaces simbólicos..." \n\
php artisan storage:link \n\
echo "===========================================" \n\
echo "Comandos post-despliegue ejecutados con éxito" \n\
echo "===========================================" \n\
' > /app/post-deploy.sh \
&& chmod +x /app/post-deploy.sh

# Exponemos el puerto
EXPOSE 8080

# Configuración para ejecutar Laravel en DigitalOcean App Platform
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}