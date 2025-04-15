FROM php:8.4-fpm

WORKDIR /app

# Instalamos dependencias del sistema, incluyendo lsb-release
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
    libicu-dev \
    nano \
    wget \
    gnupg \
    lsb-release \
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
    && echo "opcache.memory_consumption=256" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=3600" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/conf.d/opcache.ini

# Agregamos el repositorio de MySQL
RUN apt-get update && apt-get install -y \
    lsb-release \
    && wget https://dev.mysql.com/get/mysql-apt-config_0.8.29-1_all.deb \
    && dpkg -i mysql-apt-config_0.8.29-1_all.deb \
    && rm -rf /var/lib/apt/lists/*

# Instalamos el cliente de MySQL
RUN apt-get install -y mysql-client

# Verificamos la versión de mysqldump (opcional, para depuración)
RUN mysqldump --version

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

# Aseguramos que el script post-deploy.sh tenga permisos de ejecución
RUN chmod +x /app/post-deploy.sh

# Exponemos el puerto
EXPOSE 8080

# Configuración para ejecutar el script post-deploy y luego iniciar el servidor
CMD /app/post-deploy.sh && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}