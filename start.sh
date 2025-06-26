#!/bin/bash
php artisan cache:clear
composer dump-autoload
php artisan migrate --force
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan filament:optimize
php artisan octane:frankenphp --port=${PORT:-8080}