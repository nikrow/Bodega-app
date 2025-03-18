# Usamos una imagen base con PHP 8.4
FROM php:8.4-cli-bookworm

WORKDIR /app

ENV SERVER_NAME=gjs.cl

# Instalamos dependencias del sistema (solo las esenciales)
RUN apt-get update && apt-get install -y \
    zip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libicu-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    nodejs \
    npm \
    git \
    curl \
    gnupg \
    lsb-release \
    && rm -rf /var/lib/apt/lists/*

# Instalamos FrankenPHP
RUN curl -fsSL https://dl.frankenphp.dev/frankenphp/apt/gpg.key | gpg --dearmor -o /usr/share/keyrings/frankenphp-archive-keyring.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/frankenphp-archive-keyring.gpg] https://dl.frankenphp.dev/frankenphp/apt $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/frankenphp.list \
    && apt-get update \
    && apt-get install -y frankenphp \
    && rm -rf /var/lib/apt/lists/*

# Configuramos extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl

# Configuramos PHP para producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiamos los archivos de la aplicación
COPY . .

# Instalamos dependencias y optimizamos
RUN composer install --no-dev --optimize-autoloader \
    && php artisan config:clear \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan octane:install

# Instalamos dependencias de frontend y compilamos
RUN npm ci && npm run build

# Configuramos permisos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Limpiamos archivos no necesarios en producción
RUN rm -rf node_modules tests

# Exponemos puertos
EXPOSE 8000 80 443

# Comando de inicio
CMD ["php", "artisan", "octane:frankenphp"]