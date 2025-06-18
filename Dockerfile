FROM dunglas/frankenphp

# Instalar dependencias del sistema necesarias para las extensiones PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer el directorio de trabajo
WORKDIR /app

# Copiar composer.json y composer.lock para instalar dependencias
COPY composer.json composer.lock ./

# Instalar dependencias de Composer (sin dev para producción)
RUN composer install --no-dev --optimize-autoloader

# Copiar la aplicación completa
COPY . .

# Instalar dependencias de npm y construir assets (si aplica)
RUN npm ci && npm run build
# Install Puppeteer globally
RUN npm install -g puppeteer
# Configurar permisos para storage y cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Exponer el puerto (Octane utiliza el 8000 por defecto)
EXPOSE 8000

# Establecer el punto de entrada para iniciar Octane con FrankenPHP
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]