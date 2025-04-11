#!/bin/bash
# post-deploy.sh - Script para ejecutar después de desplegar en DigitalOcean

set -e  # Detiene el script si algún comando falla

echo "==========================================="
echo "Iniciando comandos post-despliegue"
echo "==========================================="

# Verificar si mysqldump está instalado
if ! command -v mysqldump &> /dev/null; then
    echo "ERROR: mysqldump no está instalado. Asegúrate de que default-mysql-client esté instalado en el Dockerfile."
    exit 1
fi

# PASO 1: Limpieza de caché y archivos temporales
echo "PASO 1: Limpieza de caché y archivos temporales"
echo "----------------------------------------------"
# Solo limpiamos cachés específicos si es necesario
php artisan cache:clear  # Limpia el caché de datos (como Redis o Memcached), pero no el de configuración/rutas
composer dump-autoload
echo "Limpieza completada."

# PASO 2: Configuración y optimización
echo "PASO 2: Configuración y optimización"
echo "-----------------------------------"
# Ejecutamos migraciones
echo "Ejecutando migraciones..."
php artisan migrate --refresh --force

# Optimizaciones
echo "Aplicando optimizaciones..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize

# Generar enlaces simbólicos
echo "Generando enlaces simbólicos..."
php artisan storage:link

echo "==========================================="
echo "Comandos post-despliegue ejecutados con éxito"
echo "==========================================="