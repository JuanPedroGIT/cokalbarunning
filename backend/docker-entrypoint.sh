#!/bin/sh
set -e

mkdir -p var/cache var/log
chmod -R 777 var/

# Warm up cache en runtime (cuando ya tenemos todas las env vars)
php bin/console cache:warmup

exec "$@"
