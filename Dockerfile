FROM dunglas/frankenphp

# Instalar dependencias del sistema necesarias para las extensiones PHP, Node.js y Puppeteer
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    curl \
    gnupg \
    # Dependencias para Puppeteer
    libx11-xcb1 \
    libxcomposite1 \
    libxrandr2 \
    libxdamage1 \
    libxss1 \
    libxtst6 \
    libnss3 \
    libasound2 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libgbm1 \
    libpango-1.0-0 \
    libpangocairo-1.0-0 \
    libgtk-3-0 \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js (versión 16.x, ajusta según tus necesidades)
RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
    && apt-get install -y nodejs

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

# Instalar Puppeteer globalmente
RUN npm install -g puppeteer

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Establecer el directorio de trabajo
WORKDIR /app

# Copiar todos los archivos necesarios para Composer primero, incluyendo artisan
COPY . .

# Cambiar al usuario www-data para evitar ejecutar Composer como root
USER www-data

# Instalar dependencias de Composer (sin dev para producción)
RUN composer install --no-dev --optimize-autoloader --no-plugins

# Cambiar de vuelta al usuario root para las siguientes operaciones
USER root

# Instalar dependencias de npm y construir assets (si aplica)
RUN npm ci && npm run build

# Configurar permisos para storage y cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Exponer el puerto (Octane utiliza el 8000 por defecto)
EXPOSE 8000

# Establecer el punto de entrada para iniciar Octane con FrankenPHP
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]