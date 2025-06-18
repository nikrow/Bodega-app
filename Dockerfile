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

# Instalar Node.js (actualizamos a la versión 18.x o superior para compatibilidad con Puppeteer)
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

# --- CAMBIO CRÍTICO AQUÍ ---
# Asegura que *todo* el directorio /app sea propiedad de www-data
# Esto debe hacerse DESPUÉS de COPY . /app y ANTES de USER www-data
RUN chown -R www-data:www-data /app
# --- FIN CAMBIO CRÍTICO ---

# Configuramos permisos específicos para storage y cache (aunque ya cubierto por el chown de /app,
# mantenerlo es una buena práctica para asegurar permisos adecuados, ej. 775 para grupos)
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Cambiar al usuario www-data para ejecutar Composer e npm ci/npm run build
USER www-data

# Instalar dependencias de Composer (ahora debería tener permisos de escritura en /app/vendor)
RUN composer install --no-dev --optimize-autoloader --no-plugins

# Instalar dependencias de npm y construir assets
RUN npm ci && npm run build

# Opcional: Eliminar node_modules para reducir el tamaño de la imagen
RUN rm -rf node_modules

# Asegura que el script post-deploy.sh tenga permisos de ejecución
# Y que pertenezca a www-data si se va a ejecutar bajo ese usuario.
# Si el script `post-deploy.sh` necesita ejecutar comandos como `php artisan migrate` o `php artisan storage:link`,
# y esos comandos se ejecutan bajo `www-data` (lo cual es una buena práctica),
# este chmod y chown son importantes.
USER root # Vuelve a root temporalmente para asegurar el chown si fuera necesario.
RUN chmod +x /app/post-deploy.sh \
    && chown www-data:www-data /app/post-deploy.sh
USER www-data # Vuelve a www-data para el ENTRYPOINT si quieres que se ejecute como este usuario.


# Exponer el puerto
EXPOSE 8000

# Establecer el punto de entrada para iniciar Octane con FrankenPHP
# Aseguramos que Octane escuche en 0.0.0.0 y el puerto definido por la plataforma o 8000 por defecto.
ENTRYPOINT ["/app/post-deploy.sh", "&&", "php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=${PORT:-8000}"]