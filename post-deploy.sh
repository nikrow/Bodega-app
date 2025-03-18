#!/bin/bash
# post-deploy.sh - Script para ejecutar después de desplegar en DigitalOcean

echo "==========================================="
echo "Iniciando comandos post-despliegue"
echo "==========================================="

# PASO 1: Limpieza de caché y archivos temporales
echo "PASO 1: Limpieza de caché y archivos temporales"
echo "----------------------------------------------"
php artisan config:clear
php artisan cache:clear 
php artisan route:clear 
php artisan view:clear
php artisan optimize:clear 
composer dump-autoload
echo "Limpieza completada."

# PASO 2: Configuración y optimización
echo "PASO 2: Configuración y optimización"
echo "-----------------------------------"
# Ejecutamos migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# Optimizaciones
echo "Aplicando optimizaciones..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache 
php -d memory_limit=256M artisan optimize
php artisan filament:optimize

# Generar enlaces simbólicos
echo "Generando enlaces simbólicos..."
php artisan storage:link

echo "==========================================="
echo "Comandos post-despliegue ejecutados con éxito"
echo "==========================================="