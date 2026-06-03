#!/bin/sh
set -e

mkdir -p var/cache var/log
chmod -R 777 var/

# Warm up cache en runtime (cuando ya tenemos todas las env vars)
php bin/console cache:warmup

# Asegurar que PHP-FPM pueda escribir la caché en caliente
chown -R www-data:www-data var/

exec "$@"
