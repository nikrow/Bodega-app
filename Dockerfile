# Usa la imagen oficial de FrankenPHP como base
FROM dunglas/frankenphp

# Establece el directorio de trabajo
WORKDIR /app

# Copia todos los archivos de la aplicación *primero* para establecer la base de los permisos
COPY . /app

# Instalar dependencias del sistema necesarias para las extensiones PHP, Node.js, Puppeteer y MySQL
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
    # Añadimos el cliente de MySQL (o MariaDB si aplica)
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js (versión 18.x o superior)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
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

# Crear y dar permisos al directorio de caché de npm
RUN mkdir -p /var/www/.npm && chown -R 33:33 /var/www/.npm

# Crear y dar permisos al directorio de caché de Puppeteer
RUN mkdir -p /var/www/.cache && chown -R 33:33 /var/www/.cache

# Asegurarse de que *todo* el directorio /app sea propiedad de www-data
RUN chown -R www-data:www-data /app

# Establecer permisos específicos para storage y cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Cambiar al usuario www-data para las instalaciones
USER www-data

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --no-plugins

# Instalar dependencias de npm y construir assets
RUN npm ci && npm run build

# Opcional: Eliminar node_modules para reducir el tamaño de la imagen
RUN rm -rf node_modules

# Asegura que el script post-deploy.sh tenga permisos de ejecución y pertenezca a www-data
USER root
RUN chmod +x /app/post-deploy.sh \
    && chown www-data:www-data /app/post-deploy.sh

# Vuelve al usuario www-data para la ejecución del ENTRYPOINT
USER www-data

# ENTRYPOINT corregido
ENTRYPOINT ["/bin/bash", "-c", "/app/post-deploy.sh && php artisan octane:frankenphp --host=0.0.0.0 --port=${PORT:-8000}"]

EXPOSE 8000