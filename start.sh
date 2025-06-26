#!/bin/bash
php artisan cache:clear
composer dump-autoloader
php artisan migrate --force
php artisan config:cache
php artisan event:cache
php artisan route:cache
php -d memory_limit=256M artisan view:cache
php -d memory_limit=256M artisan optimize
php artisan filament:optimize
php artisan octane:frankenphp --host=0.0.0.0 --port=${PORT:-8080}