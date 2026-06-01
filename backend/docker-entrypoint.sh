#!/bin/sh
set -e

mkdir -p var/cache var/log
chmod -R 777 var/

exec "$@"
