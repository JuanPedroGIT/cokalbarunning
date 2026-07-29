---
name: deploy
description: Despliega código en producción: tests locales → commit & push → pull + rebuild en media-tools
---

# Deploy

Flujo de despliegue en 3 pasos.

## Paso 1: Test local

Ejecutar los tests según lo que se haya modificado:

**Backend (PHP):**
```bash
make test
```
Si no tienes `make`, directamente:
```bash
docker compose exec -u www-data cokalbarunning-backend php bin/phpunit
```

**Frontend (Vue/TS):**
```bash
cd frontend && npm run type-check
```
O si quieres hacer build de producción para verificar que compila:
```bash
make npm-build
# equivalente a: docker compose exec cokalbarunning-frontend npx vite build
```

**Si ambos cambiaron**, ejecuta los dos.

Si algún test falla, corrige los errores antes de continuar.

## Paso 2: Commit y push

```bash
git add -A
git status                    # revisar qué se va a commitear
```

Si hay cambios en `frontend/src` o `backend/src`, el mensaje debe describir el cambio. Formato recomendado:
```
feat: descripción corta
fix: descripción corta
```

```bash
git commit -m "tipo: descripción"
git push origin master
```

## Paso 3: Pull y rebuild en producción

```bash
# Pull del código
ssh media-tools "cd ~/apps/cokalbarunning && git pull"

# Rebuild solo de los servicios que cambiaron
# Si cambió backend:
ssh media-tools "cd ~/apps/cokalbarunning && docker compose -f docker-compose.prod.yml up -d --build cokalbarunning-backend"

# Si cambió frontend:
ssh media-tools "cd ~/apps/cokalbarunning && docker compose -f docker-compose.prod.yml up -d --build cokalbarunning-frontend"

# Si cambió infra/nginx:
ssh media-tools "cd ~/apps/infra && docker compose up -d --build"

# Recargar nginx (por si las IPs de contenedores cambiaron)
ssh media-tools "docker exec infra-nginx nginx -s reload"
```

## Verificación final

```bash
# Comprobar que el backend responde
ssh media-tools "curl -s -o /dev/null -w '%{http_code}' http://localhost/api/v1/editions/active -H 'Host: cokalba-running.com'"
# Esperado: 200
```
