FROM php:8.4-fpm-bookworm

WORKDIR /app

# Install system dependencies, including gnupg and chromium
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
    # Dependencies for intl
    libicu-dev \
    # Add default-mysql-client for mysqldump
    default-mysql-client \
    # Add nano
    nano \
    # Add gnupg for gpg command (required for Yarn)
    gnupg \
    # Add chromium for Puppeteer
    chromium \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions including intl
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

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="$PATH:/root/.composer/vendor/bin"

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN echo "memory_limit=512M" >> $PHP_INI_DIR/php.ini
RUN echo "upload_max_filesize=100M" >> $PHP_INI_DIR/php.ini \
    && echo "post_max_size=100M" >> $PHP_INI_DIR/php.ini \
    && echo "max_execution_time=300" >> $PHP_INI_DIR/php.ini \
    && echo "date.timezone=America/Santiago" >> $PHP_INI_DIR/php.ini

# Optimize OPCache
RUN echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=3600" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/conf.d/opcache.ini

# Copy the application
COPY . /app

# Install JavaScript tools (removed redundant Node.js installation from nodesource)
RUN npm install -g npm@10.9.0 pnpm bun \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor -o /usr/share/keyrings/yarnkey.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y yarn \
    && rm -rf /var/lib/apt/lists/*

# Install Puppeteer globally
RUN npm install --location=global puppeteer@22.8.2

# Set Puppeteer executable path
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Install application dependencies
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs \
    && npm ci \
    && npm run build \
    && rm -rf node_modules

# Configure permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Expose the port
EXPOSE 8080

# Configuration to run post-deploy script and start the server
CMD /app/post-deploy.sh && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}