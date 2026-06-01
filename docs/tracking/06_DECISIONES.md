# Decisiones Técnicas y Notas

> **Propósito:** Registrar decisiones arquitectónicas, trade-offs y descubrimientos importantes durante la implementación del PRD R2 Storage.
> **Regla:** Cualquier agente que tome una decisión técnica no trivial DEBE anotarla aquí.

---

## Plantilla de decisión

```
### DEC-[NN]: Título corto
- **Fecha:** YYYY-MM-DD
- **Contexto:** ¿Qué problema estábamos resolviendo?
- **Opciones consideradas:**
  1. Opción A
  2. Opción B
- **Decisión:** Opción elegida y por qué
- **Impacto:** Qué archivos/código se ven afectados
- **Reversible:** Sí / No (y en qué condiciones)
```

---

## Decisiones registradas

### DEC-01: Bucket name y configuración R2
- **Fecha:** 2026-05-28
- **Contexto:** Definir configuración concreta de Cloudflare R2 antes de implementar adaptador
- **Opciones consideradas:**
  1. Bucket genérico `cokalba-media`
  2. Bucket específico `cokalba-running`
- **Decisión:** Usar bucket `cokalba-running` (proporcionado por el usuario)
- **Subdominio público:** No está configurado aún. Se usará la URL pública directa de R2 (`https://pub-...r2.dev`) o se configura CNAME posteriormente. De momento el backend construye URLs con `R2_PUBLIC_URL` desde `.env`.
- **Impacto:** `R2StorageAdapter.php`, `.env.example`, variables de entorno en Docker
- **Reversible:** Sí (cambiar variable de entorno)

### DEC-02: Procesamiento de imágenes (miniaturas)
- **Fecha:** 2026-05-28
- **Contexto:** Definir parámetros del generador de miniaturas WebP
- **Decisión:**
  - Calidad WebP: **85%**
  - Ancho máximo miniatura: **400px** (altura proporcional manteniendo aspect ratio)
  - Librería: GD (nativa en PHP, sin dependencias externas)
- **Impacto:** `GdImageProcessor.php`, `Dockerfile` (asegurar extensión gd)
- **Reversible:** Sí (cambiar constantes/configuración)

### DEC-03: Migración de URLs completas a paths relativos
- **Fecha:** 2026-05-28
- **Contexto:** Al auditar el código real se descubrió que el sistema actual guarda URLs completas en BD (`$this->storage->url($path)`) en `photos.filename`, `race_editions.poster_url` y `sponsors.logo_url`. El PRD exige guardar solo paths relativos.
- **Opciones consideradas:**
  1. Dejar las URLs existentes como están y solo aplicar paths relativos a registros nuevos (más fácil, más deuda técnica)
  2. Migrar todas las URLs existentes a paths relativos + implementar DTOs que construyan URLs dinámicamente (más trabajo, limpio)
- **Decisión:** Opción 2. Crear un comando de consola `app:migrate-storage-urls-to-paths` que convierta las URLs existentes a paths relativos. Los ResponseDTOs usarán `StoragePort::url($path)` para construir la URL pública al serializar.
- **Impacto:** Admin controllers, ResponseDTOs, comando de migración, todos los registros existentes en BD
- **Reversible:** Sí (el comando podría hacerse con backup, o reconstruir URLs desde paths)
- **Nota:** Esta decisión afecta a las tareas 1.3.1, 4.1.x, 5.3.x del backend.

### DEC-04: Orden de operaciones en upload de fotos (thumbnail vs store)
- **Fecha:** 2026-05-28
- **Contexto:** `AdminPhotoController::create()` generaba el thumbnail **después** de `$this->storage->store($file, ...)`. `LocalStorageAdapter::store()` usa `$file->move()`, que mueve y elimina el archivo temporal de `UploadedFile`. Al intentar `$file->getMimeType()` después de `move()`, Symfony lanzaba `FileinfoMimeTypeGuesser` error porque el archivo ya no existía.
- **Opciones consideradas:**
  1. Cambiar `LocalStorageAdapter` para que use `copy()` en vez de `move()` (deja basura temporal)
  2. Generar el thumbnail **antes** de `store()` del original, y subir el thumb primero
  3. Obtener `mimeType` antes de `store()` y pasar un path clonado al image processor
- **Decisión:** Opción 2. El controller ahora genera y sube el thumbnail antes de subir la imagen original. El `unlink()` del thumb temporal está protegido con `file_exists()` porque `move()` ya lo eliminó.
- **Impacto:** `AdminPhotoController.php`, lógica de cualquier controller que procese uploads con thumbnails
- **Reversible:** Sí

### DEC-05: Tests funcionales con archivos de imagen reales
- **Fecha:** 2026-05-28
- **Contexto:** Los tests funcionales de `AdminPhotoController` creaban archivos temporales con `file_put_contents($tempFile, 'fake image content')`. `GdImageProcessor::createThumbnail()` usa `getimagesize()` y `imagecreatefromjpeg()`, que fallan con contenido no-imagen. Además `FileinfoMimeTypeGuesser` no detectaba MIME type válido.
- **Decisión:** Los tests ahora generan una imagen JPEG real de 100x100 píxeles con `imagecreatetruecolor()` + `imagejpeg()`.
- **Impacto:** `AdminPhotoControllerTest.php`
- **Reversible:** Sí

### DEC-06: Memory limit en PHPUnit para tests funcionales
- **Fecha:** 2026-05-28
- **Contexto:** Al ejecutar la suite completa de tests funcionales, PHP agotaba el límite de memoria por defecto (128MB) mostrando `Fatal error: Allowed memory size of 134217728 bytes exhausted`.
- **Decisión:** Añadir `<ini name="memory_limit" value="512M" />` en `phpunit.dist.xml` dentro de la sección `<php>`.
- **Impacto:** `phpunit.dist.xml`
- **Reversible:** Sí

---

## Notas técnicas descubiertas

- **Base de datos de test separada:** El entorno `test` usa `cokalba_running_test` (definido en `.env.test`), no la misma BD que dev. Las migraciones deben ejecutarse explícitamente con `APP_ENV=test`.
- `UploadedFile::move()` invalida el archivo original. No se puede llamar `$file->getMimeType()` ni generar thumbnails **después** de `storage->store()` cuando el adapter usa `move()` (como `LocalStorageAdapter`).
- `tempnam()` crea archivos que PHP no limpia automáticamente al final de la request. Los thumbs temporales deben eliminarse manualmente, pero solo si aún existen ( LocalStorageAdapter ya los movió).
- Los tests funcionales que suben archivos deben crear imágenes reales (con GD) para que `getimagesize()`, `guessExtension()` y `GdImageProcessor` funcionen correctamente.

---

### DEC-07: Fotos sin edición asociada
- **Fecha:** 2026-05-29
- **Contexto:** Al implementar paths del PRD (`carrera/{YYYY}/imgs/`), surgió la duda de qué hacer si se sube una foto sin `raceEditionId`.
- **Opciones consideradas:**
  1. Permitir fotos sin edición en path genérico (`un-nuevo-impulso/imgs/`)
  2. Rechazar el upload si no se proporciona `raceEditionId` (HTTP 400)
- **Decisión:** Opción 2. El PRD no contempla fotos sin edición. Forzar `raceEditionId` obligatorio simplifica la lógica y mantiene coherencia con la estructura de directorios definida.
- **Impacto:** `AdminPhotoController.php`, `AdminPhotosView.vue`
- **Reversible:** Sí (cambiar validación en controller y frontend)

---

### DEC-08: R2 como único storage (sin fallback local)
- **Fecha:** 2026-05-30
- **Contexto:** Simplificar configuración eliminando LocalStorageAdapter y StorageAdapterFactory
- **Decisión:** R2 es el único storage en todos los entornos (dev, pre, prod). Se elimina STORAGE_DRIVER, STORAGE_LOCAL_PATH, STORAGE_PUBLIC_URL. El StoragePort se cablea directamente a R2StorageAdapter.
- **Impacto:** services.yaml, .env, docker-compose, LocalStorageAdapter.php (eliminado), StorageAdapterFactory.php (eliminado)
- **Reversible:** Sí (re-crear adaptador local y factory)

### DEC-09: Paths en inglés
- **Fecha:** 2026-05-30
- **Contexto:** El bucket R2 ya tenía datos con nombres en inglés. El PRD original usaba español.
- **Decisión:** Migrar todo a inglés: race/ (no carrera/), sponsors/ (no patrocinadores/), images/ (no imgs/), thumbnails/ (no miniaturas/), results/ (no resultados/). DocumentType valores también en inglés.
- **Impacto:** 5 controllers, 5 tests, 3 docs, 3 archivos frontend
- **Reversible:** Sí (cambiar strings de vuelta)

### DEC-10: Patrocinadores generales del club (sin edición)
- **Fecha:** 2026-05-30
- **Contexto:** Los patrocinadores patrocinan al club, no a una edición específica.
- **Decisión:** Eliminar columna edition_id de sponsors. Todos los sponsors son generales. Añadir campo message (TEXT) para el sponsor principal con soporte HTML.
- **Impacto:** Migración, Domain/ORM/Mapper/DTO, handlers, controllers, admin UI, HomeView
- **Reversible:** No trivial (requiere re-migración de datos)

### DEC-11: Un solo .env en raíz del proyecto
- **Fecha:** 2026-05-30
- **Contexto:** Había .env duplicados en raíz, backend/ (Symfony) y frontend/ (Vite).
- **Decisión:** Un solo .env en raíz. Docker compose inyecta variables. Symfony lee el .env montado como volumen. Variables de test en phpunit.dist.xml. VITE_API_URL como build ARG en Dockerfile.prod.
- **Impacto:** 5 .env eliminados, docker-compose.yml, phpunit.dist.xml, Dockerfile.prod
- **Reversible:** Sí

### DEC-12: Miniaturas bajo demanda con comando de consola
- **Fecha:** 2026-05-30
- **Contexto:** Fotos históricas (2016-2025) necesitan miniaturas generadas masivamente.
- **Decisión:** Crear comando `app:generate-thumbnails {year}` que lista objetos del bucket con S3 SDK, descarga cada imagen vía URL pública, genera miniatura WebP 400px ancho con GD, sube a thumbnails/ y crea registro en BD. Usa 512M memory limit + gc_collect_cycles.
- **Impacto:** GenerateThumbnailsCommand.php, S3ClientFactory.php, services.yaml
- **Reversible:** N/A (comando de utilidad)

### DEC-13: Fotos de ediciones sin filtro de año en DB
- **Fecha:** 2026-05-30
- **Contexto:** GET /admin/photos y GET /photos necesitan filtrar por edición.
- **Decisión:** Añadir parámetro opcional ?editionId= al endpoint. El handler GetAllPhotosQuery usa el repositorio para filtrar. Sin parámetro = todas las fotos.
- **Impacto:** GetAllPhotosQuery, GetAllPhotosQueryHandler, AdminPhotoController, PhotoController
- **Reversible:** Sí

---

## Dudas pendientes de resolver

*(Todas resueltas)*
