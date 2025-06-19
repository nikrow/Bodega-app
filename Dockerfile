# Usa la imagen oficial de FrankenPHP como base
FROM dunglas/frankenphp:latest-php8.3

# Establece el directorio de trabajo
WORKDIR /app

# Copia todos los archivos de la aplicación *primero* para establecer la base de los permisos
# Esto es crucial para aprovechar el cache de Docker.
COPY . /app

# Instalar dependencias del sistema necesarias
# Unificamos la instalación para ser más eficiente y agregamos `procps` si necesitas herramientas como `ps`.
RUN apt-get update && apt-get install -y \
    curl \
    gnupg \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libsodium-dev \
    libexif-dev \
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
    # Limpiamos el caché de APT para reducir el tamaño de la imagen
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js (versión 18.x o superior)
# Usamos un comando más robusto para la instalación de Node.js.
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
# Asegúrate de incluir 'pdo_pgsql' si usas PostgreSQL, como mencionaste en la primera parte.
RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    sodium \
    pgsql # Agrega pgsql si es necesario, ya que mencionaste postgres

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Crear y dar permisos al directorio de caché de npm
# Los permisos para www-data son importantes aquí.
RUN mkdir -p /var/www/.npm && chown -R www-data:www-data /var/www/.npm

# Crear y dar permisos al directorio de caché de Puppeteer
# Similarmente, asegura los permisos correctos.
RUN mkdir -p /var/www/.cache && chown -R www-data:www-data /var/www/.cache

# Asegurarse de que *todo* el directorio /app sea propiedad de www-data
# Esto es crucial para que la aplicación funcione correctamente como el usuario www-data.
RUN chown -R www-data:www-data /app

# Establecer permisos específicos para storage y cache
# Mantener estos permisos es vital para la operación de Laravel.
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Cambiar al usuario www-data para las instalaciones de Composer y NPM
USER www-data

# Instalar dependencias de Composer
# `composer dump-autoload --optimize` es mejor para producción.
RUN composer install --no-dev --optimize-autoloader --no-plugins

# Instalar dependencias de npm y construir assets
RUN npm ci && npm run build

# Opcional: Eliminar node_modules para reducir el tamaño de la imagen
# Esto es una buena práctica para imágenes de producción más pequeñas.
RUN rm -rf node_modules

# Vuelve al usuario root temporalmente para configurar el script post-deploy.sh
USER root
# Asegura que el script post-deploy.sh tenga permisos de ejecución y pertenezca a www-data
# Esto es esencial si ejecutas migraciones o comandos al inicio.
RUN chmod +x /app/post-deploy.sh \
    && chown www-data:www-data /app/post-deploy.sh

# Vuelve al usuario www-data para la ejecución del ENTRYPOINT
USER www-data

# ENTRYPOINT corregido para ejecutar el script y luego FrankenPHP
# Usamos `exec` para asegurar que la señal de apagado se pase correctamente a FrankenPHP.
ENTRYPOINT ["/bin/bash", "-c", "exec /app/post-deploy.sh && exec php artisan octane:frankenphp --host=0.0.0.0 --port=${PORT:-8000}"]

EXPOSE 8000