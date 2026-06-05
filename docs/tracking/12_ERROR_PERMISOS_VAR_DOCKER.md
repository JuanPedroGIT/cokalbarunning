# Error Recurrente: 502/500 en API por permisos de `var/` en Docker

## Síntoma

En entorno local (Docker), las peticiones a la API (`/api/v1/*`) devuelven **502 Bad Gateway** o **500 Internal Server Error**. El frontend muestra errores de red y la aplicación no carga datos.

Ejemplo de URLs afectadas:
- `GET /api/v1/sponsors`
- `GET /api/v1/editions/active`
- `GET /api/v1/club-members`
- `GET /api/v1/posts/latest`

## Causa raíz

PHP-FPM en el contenedor `cokalbarunning-backend` ejecuta como usuario `www-data`, pero los directorios `var/cache` y `var/log` (generados o montados por Docker) pertenecen a `root:root`. Symfony no puede escribir logs ni caché, lo que provoca:

1. **502**: PHP-FPM rechaza la conexión o se cae al no poder escribir.
2. **500**: Symfony captura una excepción interna (fallo de escritura) y devuelve error.

El `docker-entrypoint.sh` del backend intenta hacer `chown -R www-data:www-data var/`, pero en entornos Windows + Docker Desktop con volúmenes anónimos o bind mounts, los permisos se pierden o no se aplican correctamente tras reinicios del contenedor.

## Por qué es recurrente

- Docker Desktop en Windows maneja los permisos de forma imperfecta con bind mounts (`./backend:/var/www/backend`).
- El volumen anónimo `/var/www/backend/var` a veces se reinicializa vacío o con permisos de root.
- El entrypoint solo corre al **crear** el contenedor, no al reiniciarlo (`docker-compose restart`).
- Cualquier operación que toque `var/` desde el host (ej. `git clean`, borrar caché manualmente) puede romper los permisos.

## Diagnóstico rápido

```bash
# 1. Verificar que PHP-FPM esté corriendo
docker exec cokalbarunning-backend sh -c "ps aux | grep php-fpm"

# 2. Verificar permisos de var/
docker exec cokalbarunning-backend sh -c "ls -la /var/www/backend/var/"

# Si muestra root:root en vez de www-data:www-data -> este es el problema.
```

## Fix inmediato

```bash
# Recuperar permisos y reiniciar
docker-compose exec cokalbarunning-backend sh -c "chown -R www-data:www-data /var/www/backend/var && chmod -R 775 /var/www/backend/var"
docker-compose restart cokalbarunning-backend
```

## Fix preventivo (opcional)

Si el problema persiste, forzar el entrypoint a correr siempre (no solo en creación) añadiendo al `docker-compose.yml` del backend:

```yaml
command: >
  sh -c "mkdir -p var/cache var/log && chown -R www-data:www-data var/ && php-fpm"
```

O bien, reconstruir el contenedor desde cero:

```bash
docker-compose down
docker-compose build --no-cache cokalbarunning-backend
docker-compose up -d
```

## Notas

- **No es un bug de código**: Symfony, nginx y PHP-FPM funcionan correctamente. Es puramente un problema de permisos de Docker en el filesystem.
- **No afecta a producción**: En producción el contenedor se construye limpio y el entrypoint se ejecuta una vez.
- Última ocurrencia documentada: 2026-06-05.
