#!/bin/sh
set -e

# Cria diretórios necessários
mkdir -p /var/www/html/storage /var/www/html/api/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/api/logs

# Roda migrations na primeira execução
if [ ! -f /var/www/html/storage/.migrated ]; then
    echo "Running migrations..."
    php /var/www/html/scripts/migrate.php
    touch /var/www/html/storage/.migrated
    echo "Migrations completed!"
fi

# Inicia PHP-FPM em background
php-fpm -D

# Inicia Nginx em foreground
nginx -g "daemon off;"
