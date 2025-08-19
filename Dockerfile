FROM php:8.3-fpm

WORKDIR /app

# Dependencias base + ca-certificates (importante para TLS)
RUN apt-get update && apt-get install -y \
    gnupg lsb-release ca-certificates curl git zip unzip nano \
    libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev chromium fonts-liberation libgbm-dev libnss3 \
    && rm -rf /var/lib/apt/lists/*

# MySQL APT repo (no interactivo) e instalación del cliente oficial
RUN curl -fsSL https://repo.mysql.com/RPM-GPG-KEY-mysql-2022 | gpg --dearmor -o /usr/share/keyrings/mysql.gpg && \
    echo "deb [signed-by=/usr/share/keyrings/mysql.gpg] https://repo.mysql.com/apt/debian $(. /etc/os-release && echo $VERSION_CODENAME) mysql-8.0" \
    > /etc/apt/sources.list.d/mysql.list && \
    apt-get update && apt-get install -y mysql-client && \
    rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-configure intl \
 && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Node.js 22 + npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
 && apt-get install -y nodejs && npm i -g npm@latest \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# php.ini y opcache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && { echo "memory_limit=512M"; echo "max_execution_time=300"; } >> "$PHP_INI_DIR/php.ini" \
 && { echo "opcache.enable=1"; echo "opcache.memory_consumption=256"; \
      echo "opcache.interned_strings_buffer=8"; echo "opcache.max_accelerated_files=10000"; \
      echo "opcache.revalidate_freq=3600"; echo "opcache.enable_cli=1"; } > $PHP_INI_DIR/conf.d/opcache.ini

# Copiamos la app
COPY . /app

# Registrar el CA de tu DB (opción 1: como trust del sistema)
# Si tu cert está en /app/storage/app/cert/ca-certificatecampo.crt:
RUN mkdir -p /usr/local/share/ca-certificates/custom && \
    cp /app/storage/app/cert/ca-certificatecampo.crt /usr/local/share/ca-certificates/custom/icc-db-ca.crt && \
    update-ca-certificates

# Dependencias app
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs \
 && npm ci && npm run build && rm -rf node_modules

# Puppeteer
RUN npm install --location=global puppeteer@22.8.2

# Permisos + script
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
 && chmod -R 775 /app/storage /app/bootstrap/cache \
 && chmod +x /app/post-deploy.sh

EXPOSE 8080
CMD /app/post-deploy.sh && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}