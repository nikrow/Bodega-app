FROM dunglas/frankenphp:1.7.0-php8.4-bookworm

WORKDIR /app

COPY . /app

# Make post-deploy.sh executable
RUN chmod +x /app/post-deploy.sh

# Install system dependencies
RUN apt-get update \
    && apt-get install -y \
    zip libzip-dev gnupg gosu curl ca-certificates unzip git sqlite3 libcap2-bin \
    libpng-dev libonig-dev libicu-dev libjpeg-dev libfreetype6-dev libwebp-dev \
    python3 dnsutils librsvg2-bin fswatch ffmpeg nano chromium fonts-liberation libgbm-dev libnss3 \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions pdo_mysql mbstring opcache exif pcntl bcmath gd zip intl pdo_mysql

# Establecemos variables de entorno
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="$PATH:/root/.composer/vendor/bin"

# Copy Composer
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

# Install Node.js and JavaScript tools
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@10.9.0 pnpm bun \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor -o /usr/share/keyrings/yarnkey.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y yarn \
    && rm -rf /var/lib/apt/lists/*

# Install Puppeteer globally
RUN npm install --location=global puppeteer@22.8.2

# Set Puppeteer executable path
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Install PHP dependencies and optimize
RUN composer install --no-dev --optimize-autoloader

# Prepare application
RUN mkdir -p /app/storage/logs
RUN php artisan config:clear
RUN php artisan octane:install

# Install Node.js dependencies and build assets
RUN npm install && npm run build

# Configuramos permisos
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Aseguramos que el script post-deploy.sh tenga permisos de ejecución
RUN chmod +x /app/post-deploy.sh

# Expose necessary port
EXPOSE 8080

# Start the application
ENTRYPOINT ["sh", "-c", "/app/post-deploy.sh && php artisan octane:frankenphp --host=0.0.0.0 --port=${PORT:-8080}"]