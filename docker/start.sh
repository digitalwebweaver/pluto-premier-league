#!/bin/sh
set -e

cd /var/www/html

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan migrate --force
php artisan teams:sync-logos

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec /usr/bin/supervisord -c /etc/supervisord.conf
