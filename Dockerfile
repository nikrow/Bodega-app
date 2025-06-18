# Usa la imagen oficial de FrankenPHP como base
FROM dunglas/frankenphp

# Establece el directorio de trabajo
WORKDIR /app

# Copia todos los archivos de la aplicación *primero* para establecer la base de los permisos
COPY . /app

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

# Instalar Node.js (actualizamos a la versión 18.x o superior)
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

# Instalar Puppeteer globalmente
RUN npm install -g puppeteer

# --- INICIO CAMBIOS PARA NPM ---

# Crear y dar permisos al directorio de caché de npm antes de cualquier operación npm
# Esto resuelve el error EACCES en /var/www/.npm
# Utilizamos www-data:www-data (GID:UID 33:33) para que coincida con el usuario www-data
RUN mkdir -p /var/www/.npm && chown -R 33:33 /var/www/.npm

# Asegurarse de que *todo* el directorio /app sea propiedad de www-data
# Esto es crucial para Composer y cualquier otra operación de escritura en la aplicación.
RUN chown -R www-data:www-data /app

# Establecer permisos específicos para storage y cache (siempre buena práctica)
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Cambiar al usuario www-data para instalar dependencias de Composer
USER www-data
RUN composer install --no-dev --optimize-autoloader --no-plugins

# Vuelve a root temporalmente para las operaciones de npm si hay problemas
# con la instalación como www-data, aunque `chown /var/www/.npm` debería ayudar.
# Si el problema persiste, considera mover npm ci/npm run build a un usuario root.
# Por ahora, lo mantenemos como www-data para mayor seguridad.

# Instalar dependencias de npm y construir assets
# Con el chown de /var/www/.npm, esto debería funcionar como www-data
RUN npm ci && npm run build

# Opcional: Eliminar node_modules para reducir el tamaño de la imagen
RUN rm -rf node_modules

# --- FIN CAMBIOS PARA NPM ---


# Asegura que el script post-deploy.sh tenga permisos de ejecución y pertenezca a www-data
# Vuelve a root para esta operación, para garantizar que se apliquen los permisos.
USER root
RUN chmod +x /app/post-deploy.sh \
    && chown www-data:www-data /app/post-deploy.sh

# Vuelve al usuario www-data para la ejecución del ENTRYPOINT.
# Esta es una buena práctica de seguridad en producción.
USER www-data

# ENTRYPOINT corregido
ENTRYPOINT ["/bin/bash", "-c", "/app/post-deploy.sh && php artisan octane:frankenphp --host=0.0.0.0 --port=${PORT:-8000}"]

EXPOSE 8000
