.PHONY: help up down rebuild logs shell-backend shell-frontend \
        migrate migration-diff migration-status schema-validate \
        cache-clear cache-warm \
        test test-unit test-integration test-functional \
        install composer-require npm-install sync-vendor sync-npm \
        prod-up prod-down

# ─── Docker (desarrollo) ────────────────────────────────────────────────────

help: ## Muestra esta ayuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-22s\033[0m %s\n", $$1, $$2}'

up: ## Levanta los contenedores en desarrollo
	docker compose up -d

down: ## Detiene los contenedores
	docker compose down

rebuild: ## Reconstruye imágenes desde cero (sin caché)
	docker compose down -v
	docker compose build --no-cache
	docker compose up -d

logs: ## Logs del backend
	docker compose logs -f cokalbarunning-backend

# ─── Shells ─────────────────────────────────────────────────────────────────

shell-backend: ## Shell en el contenedor backend
	docker compose exec cokalbarunning-backend sh

shell-frontend: ## Shell en el contenedor frontend
	docker compose exec cokalbarunning-frontend sh

# ─── Symfony ────────────────────────────────────────────────────────────────

migrate: ## Ejecuta migraciones
	docker compose exec -u www-data cokalbarunning-backend php bin/console doctrine:migrations:migrate --no-interaction

migration-diff: ## Genera migración por diff
	docker compose exec -u www-data cokalbarunning-backend php bin/console doctrine:migrations:diff

migration-status: ## Estado de migraciones
	docker compose exec -u www-data cokalbarunning-backend php bin/console doctrine:migrations:status

schema-validate: ## Valida mapping Doctrine
	docker compose exec -u www-data cokalbarunning-backend php bin/console doctrine:schema:validate

cache-clear: ## Limpia caché Symfony
	docker compose exec -u www-data cokalbarunning-backend php bin/console cache:clear

cache-warm: ## Precalienta caché
	docker compose exec -u www-data cokalbarunning-backend php bin/console cache:clear
	docker compose exec -u www-data cokalbarunning-backend php bin/console cache:warmup

# ─── Tests ──────────────────────────────────────────────────────────────────

test: ## Todos los tests
	docker compose exec -u www-data cokalbarunning-backend php bin/phpunit

test-unit: ## Tests unitarios
	docker compose exec -u www-data cokalbarunning-backend php bin/phpunit tests/Unit

test-integration: ## Tests integración
	docker compose exec -u www-data cokalbarunning-backend php bin/phpunit tests/Integration

test-functional: ## Tests funcionales
	docker compose exec -u www-data cokalbarunning-backend php bin/phpunit tests/Functional

# ─── Dependencias ───────────────────────────────────────────────────────────

install: ## Instalación completa tras clonar
	docker compose exec -u www-data cokalbarunning-backend composer install
	$(MAKE) sync-vendor
	npm --prefix frontend install
	$(MAKE) sync-npm

composer-require: ## Añade paquete PHP (ej: make composer-require pkg="vendor/pkg")
	docker compose exec -u www-data cokalbarunning-backend composer require $(pkg)
	$(MAKE) sync-vendor

npm-install: ## Añade paquete npm (ej: make npm-install pkg="nombre")
	docker compose exec cokalbarunning-frontend npm install $(pkg)
	npm --prefix frontend install $(pkg)

# Copia vendor del contenedor → disco local (IDE lo necesita)
sync-vendor:
	docker cp cokalbarunning-backend:/var/www/backend/vendor ./backend/vendor

# Copia node_modules del contenedor → disco local (IDE lo necesita)
sync-npm:
	docker cp cokalbarunning-frontend:/app/node_modules ./frontend/

# ─── Utilidades ─────────────────────────────────────────────────────────────

backend-console: ## Symfony console (ej: make backend-console cmd="debug:router")
	docker compose exec -u www-data cokalbarunning-backend php bin/console $(cmd)

backend-routes: ## Lista rutas de Symfony
	docker compose exec -u www-data cokalbarunning-backend php bin/console debug:router

schema-update: ## Actualiza schema (⚠️ no usar en prod)
	docker compose exec -u www-data cokalbarunning-backend php bin/console doctrine:schema:update --force

schema-drop: ## Elimina schema (⚠️ datos perdidos)
	docker compose exec -u www-data cokalbarunning-backend php bin/console doctrine:schema:drop --force

composer-update: ## Actualiza dependencias PHP
	docker compose exec -u www-data cokalbarunning-backend composer update

composer-dump: ## Regenera autoload
	docker compose exec -u www-data cokalbarunning-backend composer dump-autoload

npm-update: ## Actualiza dependencias frontend
	docker compose exec cokalbarunning-frontend npm update

npm-build: ## Build de producción frontend
	docker compose exec cokalbarunning-frontend npx vite build

npm-lint: ## Linter frontend
	docker compose exec cokalbarunning-frontend npm run lint

jwt-generate: ## Genera claves JWT
	docker compose exec -u www-data cokalbarunning-backend php bin/console lexik:jwt:generate-keypair --overwrite

create-admin: ## Crea admin (ej: make create-admin email="a@b.com" password="s")
	docker compose exec -u www-data cokalbarunning-backend php bin/console app:user:create $(email) -p $(password)

create-editor: ## Crea editor (ej: make create-editor email="a@b.com" password="s")
	docker compose exec -u www-data cokalbarunning-backend php bin/console app:user:create $(email) -p $(password) -r ROLE_EDITOR

update-admin: ## Actualiza contraseña (ej: make update-admin email="a@b.com" password="n")
	docker compose exec -u www-data cokalbarunning-backend php bin/console app:user:create $(email) -p $(password) --update

storage-migrate: ## Migra URLs a paths relativos en BD
	docker compose exec -u www-data cokalbarunning-backend php bin/console app:migrate-storage-urls-to-paths

# ─── Producción ─────────────────────────────────────────────────────────────

prod-up: ## Levanta producción
	docker compose -f docker-compose.prod.yml up -d --build

prod-down: ## Para producción
	docker compose -f docker-compose.prod.yml down
