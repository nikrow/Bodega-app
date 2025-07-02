#!/bin/bash

echo "==========================================="
echo "Iniciando comandos post-despliegue"
echo "==========================================="

# PASO 2: Configuración y optimización
echo "PASO 1: Configuración y optimización"
echo "-----------------------------------"
# Ejecutamos migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# PASO 3: Crear y configurar permisos para livewire-tmp
echo "PASO 3: Crear y configurar permisos para livewire-tmp"
echo "---------------------------------------------------"
# Definir el directorio para archivos temporales de Livewire
LIVEWIRE_TMP_DIR="storage/app/public/livewire-tmp"

# Crear el directorio si no existe
if [ ! -d "$LIVEWIRE_TMP_DIR" ]; then
    echo "Creando directorio $LIVEWIRE_TMP_DIR..."
    mkdir -p "$LIVEWIRE_TMP_DIR"
else
    echo "El directorio $LIVEWIRE_TMP_DIR ya existe."
fi

# Otorgar permisos al directorio
echo "Configurando permisos para $LIVEWIRE_TMP_DIR..."
chmod -R 775 "$LIVEWIRE_TMP_DIR"

echo "==========================================="
echo "Comandos post-despliegue ejecutados con éxito"
echo "==========================================="