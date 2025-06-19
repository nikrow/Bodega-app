FROM php:8.3-fpm

WORKDIR /app

# Instala dependencias
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    zip \
    nodejs \
    npm \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring bcmath exif pcntl gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copia composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia todo el código
COPY . /app

# Configura Nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Configura Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Permisos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 8080

CMD ["/usr/bin/supervisord"]
