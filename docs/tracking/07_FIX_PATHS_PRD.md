# Tracking: Corrección de Paths de Almacenamiento al PRD R2

> **Propósito:** Alinear la estructura de directorios de archivos subidos con la especificación del PRD original (`01_PRD_R2_STORAGE.md`).
> **Estado general:** ✅ COMPLETADO
> **Creado:** 2026-05-29

---

## Resumen del problema

Los controllers actuales generan paths planos y genéricos:
- `photos/{uuid}.{ext}` ❌
- `posters/{uuid}.{ext}` ❌
- `shirts/{uuid}.{ext}` ❌
- `docs/{uuid}.{ext}` ❌

El PRD exige una estructura jerárquica semántica:
```
un-nuevo-impulso/
├── sponsors/          # Logos de sponsors
├── docs/                    # Documentos generales (edition_id = NULL)
└── race/{YYYY}/          # Por año de edición
    ├── docs/                # Cartel, camiseta
    ├── results/          # PDFs clasificaciones
    ├── images/                # Fotos alta resolución
    └── thumbnails/          # Thumbnails WebP
```

---

## Fase 1: Análisis y Preparación

- [x] **1.1** Revisar cómo obtener el `year` de la edición en cada controller que necesita construir paths
  - `AdminPhotoController::create()` recibe `raceEditionId` en el request → inyectar `RaceEditionRepositoryInterface` y llamar `findById()` → `$edition->year()->value()`
  - `AdminRaceController::uploadPoster/uploadShirt()` ya tiene `$edition` → `$edition->year()->value()` (directo)
  - `AdminRaceDocumentController::create()` recibe `editionId` → inyectar `RaceEditionRepositoryInterface` y llamar `findById()` → `$edition->year()->value()`
- [x] **1.2** Verificar si `RaceEditionRepositoryInterface` expone `year`
  - ✅ SÍ. `findById()` devuelve `RaceEdition` que tiene `year(): EditionYear`. `EditionYear::value()` devuelve `int`.
- [x] **1.3** Revisar si el frontend admin envía `raceEditionId` al subir fotos
  - ✅ SÍ. `AdminPhotosView.vue` envía `raceEditionId` como campo del FormData, **pero es opcional** (`if (raceEditionId.value) formData.append(...)`). El select tiene opción `"Sin edicion"` con `value=""`.
- [x] **1.4** Documentar decisión: ¿qué hacer si `raceEditionId` es null al subir una foto?
  - **Decisión:** El PRD (`01_PRD_R2_STORAGE.md`) asume que toda foto pertenece a una edición (`race/{YYYY}/images/`). No contempla fotos sin edición.
  - **Opción A:** Rechazar el upload si no se selecciona edición (HTTP 400).
  - **Opción B:** Permitir fotos sin edición en un path genérico (`un-nuevo-impulso/images/`).
  - **Recomendación:** Opción A. Forzar selección de edición en el upload de fotos. Simplifica la lógica y es coherente con el PRD. El frontend debe marcar el campo como obligatorio.

---

## Fase 2: Backend - Corrección de Paths

### 2.1 AdminPhotoController
- [x] **2.1.1** Inyectar `RaceEditionRepositoryInterface` en `AdminPhotoController`
- [x] **2.1.2** Antes de generar el path, resolver el `year` desde `raceEditionId` (o lanzar error si es null)
- [x] **2.1.3** Cambiar path de original: `photos/{uuid}.{ext}` → `un-nuevo-impulso/race/{YYYY}/images/{uuid}.{ext}`
- [x] **2.1.4** Cambiar path de thumbnail: `photos/thumbs/{uuid}.webp` → `un-nuevo-impulso/race/{YYYY}/thumbnails/{uuid}.webp`
- [x] **2.1.5** En `AdminPhotoController::delete()`, asegurar que también borra de `StoragePort` → ya lo hace `DeletePhotoHandler`

### 2.2 AdminRaceController (poster y shirt)
- [x] **2.2.1** Cambiar path de poster: `posters/{uuid}.{ext}` → `un-nuevo-impulso/race/{YYYY}/docs/poster-{uuid}.{ext}`
- [x] **2.2.2** Cambiar path de shirt: `shirts/{uuid}.{ext}` → `un-nuevo-impulso/race/{YYYY}/docs/camiseta-{uuid}.{ext}`
- [x] **2.2.3** Al actualizar poster/shirt, borrar el archivo anterior de `StoragePort`

### 2.3 AdminRaceDocumentController
- [x] **2.3.1** Inyectar `RaceEditionRepositoryInterface`
- [x] **2.3.2** Si `editionId` está presente → path: `un-nuevo-impulso/race/{YYYY}/results/{uuid}.{ext}` (type=results) o `docs/` (otros tipos)
- [x] **2.3.3** Si `editionId` es null → path: `un-nuevo-impulso/docs/{uuid}.{ext}`
- [x] **2.3.4** En `AdminRaceDocumentController::delete()`, asegurar que también borra de `StoragePort` → ya lo hace `DeleteRaceDocumentHandler`

### 2.4 AdminSponsorController - NUEVO endpoint de upload
- [x] **2.4.1** Crear endpoint `POST /api/v1/admin/sponsors/{id}/logo`
- [x] **2.4.2** Path: `un-nuevo-impulso/sponsors/{uuid}.{ext}`
- [x] **2.4.3** Actualizar entidad `Sponsor` con el nuevo `logoUrl` (path relativo)
- [x] **2.4.4** Devolver URL pública en la respuesta
- [x] **2.4.5** Al actualizar logo, borrar el archivo anterior de `StoragePort`

---

## Fase 3: Backend - Borrado de archivos en Storage

Los controllers actuales solo borran la entidad de BD, no el archivo físico. El PRD espera limpieza completa.

- [x] **3.1** `AdminPhotoController::delete()` → `DeletePhotoHandler` ya borra original + thumb de storage
- [x] **3.2** `AdminRaceDocumentController::delete()` → `DeleteRaceDocumentHandler` ya borra de storage
- [x] **3.3** `AdminRaceController::delete()` → `DeleteRaceEditionHandler` ahora borra poster + shirt de storage
- [x] **3.4** `AdminSponsorController::delete()` → `DeleteSponsorHandler` ahora borra logo de storage

---

## Fase 4: Frontend - Adaptaciones

- [x] **4.1** `AdminPhotosView` → campo `raceEditionId` ahora es obligatorio. Se quitó opción "Sin edicion" y se añadió validación con `alert()`.
- [x] **4.2** `AdminEditionsView` → sin cambios necesarios (la API no cambió, solo los paths internos)
- [x] **4.3** `AdminSponsorsView` → campo `logoUrl` reemplazado por upload de archivo vía `POST /sponsors/{id}/logo`. Se muestra preview del logo si existe.
- [x] **4.4** `RaceDocuments.vue` → sin cambios necesarios

---

## Fase 5: Tests Backend

- [x] **5.1** Actualizar `AdminPhotoControllerTest`:
  - Verificar que el path guardado en BD contiene `un-nuevo-impulso/race/{YYYY}/images/`
  - Verificar que el thumb contiene `un-nuevo-impulso/race/{YYYY}/thumbnails/`
  - Verificar que `delete()` borra ambos archivos del storage
- [x] **5.2** Actualizar `AdminRaceControllerTest`:
  - Verificar paths de poster y shirt con `un-nuevo-impulso/race/{YYYY}/docs/`
  - Verificar que al subir nuevo poster se borra el anterior
- [x] **5.3** Crear/actualizar `AdminRaceDocumentControllerTest`:
  - Verificar path con `race/{YYYY}/results/` cuando hay edición
  - Verificar path con `un-nuevo-impulso/docs/` cuando es general
  - Verificar que `delete()` borra el archivo
- [x] **5.4** Crear `AdminSponsorControllerTest` (si no existe) o actualizar:
  - Test de upload de logo con path `un-nuevo-impulso/sponsors/`

---

## Fase 6: Migración de datos existentes (si aplica)

> ⚠️ Solo necesario si ya hay datos en producción/pre con los paths antiguos.

- [x] **6.1** En local: eliminados directorios antiguos (`photos/`, `posters/`, `sponsors/`, `blog/`, `gallery/`)
- [x] **6.2** En local: creada estructura PRD completa para 2026:
  ```
  uploads/un-nuevo-impulso/
  ├── sponsors/
  ├── docs/
  └── race/
      └── 2026/
          ├── docs/
          ├── results/
          ├── images/
          └── thumbnails/
      └── 2025/
          ├── docs/
          ├── results/
          ├── images/      
          └── thumbnails/
  ```
- [x] **6.3** Documentar la decisión en `06_DECISIONES.md`

---

## Fase 7: Limpieza y Verificación

- [x] **7.1** Ejecutar suite completa de tests: `php vendor/bin/phpunit` → **113 tests, 353 assertions — todos verdes**
- [x] **7.2** Verificado: los tests crean archivos en `backend/public/uploads/un-nuevo-impulso/race/2026/images/...`
- [x] **7.3** Verificado: las URLs públicas se construyen correctamente mediante `StoragePort::url()`
- [x] **7.4** Actualizar `05_PROGRESO.md` con el estado de esta corrección
- [x] **7.5** Actualizar `06_DECISIONES.md` con decisión sobre raceEditionId obligatorio

---

## Checklist rápido de cambios por archivo

| Archivo | Cambios |
|---------|---------|
| `AdminPhotoController.php` | Inyectar repo ediciones, construir paths con year, borrar de storage en delete |
| `AdminRaceController.php` | Paths con year en poster/shirt, borrar anterior al subir nuevo |
| `AdminRaceDocumentController.php` | Inyectar repo ediciones, paths condicionales (edición vs general), borrar en delete |
| `AdminSponsorController.php` | Nuevo endpoint `POST /sponsors/{id}/logo`, borrar logo en delete |
| `DeletePhotoHandler.php` o Controller | Borrar archivos de storage |
| `DeleteRaceDocumentHandler.php` o Controller | Borrar archivo de storage |
| `DeleteRaceEditionHandler.php` o Controller | Borrar poster/shirt de storage |
| `DeleteSponsorHandler.php` o Controller | Borrar logo de storage |
| Tests funcionales | Actualizar aserciones de paths y borrado de archivos |

---

## Notas

- **Prioridad:** Alta. La estructura actual es incompatible con el bucket R2 configurado en el PRD.
- **Riesgo:** Medio. Cambiar paths afecta a registros nuevos. Los existentes siguen funcionando porque las URLs se construyen dinámicamente desde BD.
- **Regla de oro:** Nunca hardcodear el año. Siempre obtenerlo de la entidad `RaceEdition`.

  R2_ACCOUNT_ID= c37af7cef2e32d99f4cd81882add4b97                                   
  R2_ACCESS_KEY_ID= c3fc0bc547882d1112983cbcd56ad7f2
  R2_ACCESS_KEY_SECRET= 66fa306b6671019c35ec78eb3f7d239f161a402292893e553c7e136155ae26c5
  R2_PUBLIC_URL= https://c37af7cef2e32d99f4cd81882add4b97.r2.cloudflarestorage.com
