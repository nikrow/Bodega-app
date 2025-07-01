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

# PASO 2: Configuración y optimización
echo "PASO 1: Configuración y optimización"
echo "-----------------------------------"
# Ejecutamos migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# PASO 3: Crear y configurar permisos para livewire-tmp
#echo "PASO 3: Crear y configurar permisos para livewire-tmp"
#echo "---------------------------------------------------"
# Definir el directorio para archivos temporales de Livewire
#LIVEWIRE_TMP_DIR="storage/app/public/livewire-tmp"

# Crear el directorio si no existe
#if [ ! -d "$LIVEWIRE_TMP_DIR" ]; then
#    echo "Creando directorio $LIVEWIRE_TMP_DIR..."
#    mkdir -p "$LIVEWIRE_TMP_DIR"
#else
#    echo "El directorio $LIVEWIRE_TMP_DIR ya existe."
#fi

# Otorgar permisos al directorio
#echo "Configurando permisos para $LIVEWIRE_TMP_DIR..."
#chmod -R 775 "$LIVEWIRE_TMP_DIR"
# Intentar cambiar el propietario al usuario típico del servidor web (www-data como suposición)
# En App Platform, los permisos son manejados por el entorno, pero intentamos cubrir casos comunes
#if command -v chown &> /dev/null; then
#    chown -R www-data:www-data "$LIVEWIRE_TMP_DIR" 2>/dev/null || echo "Nota: No se pudo cambiar el propietario a www-data; App Platform puede manejar permisos automáticamente."
#else
#    echo "Nota: El comando chown no está disponible; App Platform puede manejar permisos automáticamente."
#fi


echo "==========================================="
echo "Comandos post-despliegue ejecutados con éxito"
echo "==========================================="