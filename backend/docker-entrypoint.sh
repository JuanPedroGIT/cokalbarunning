#!/bin/sh
set -e

mkdir -p var/cache var/log

# Warm up cache en runtime (cuando ya tenemos todas las env vars)
php bin/console cache:warmup

exec "$@"
