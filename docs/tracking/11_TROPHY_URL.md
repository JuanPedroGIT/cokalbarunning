# Trophy URL — Foto del Trofeo por Edición

> **Fecha inicio:** 2026-06-04
> **Objetivo:** Añadir `trophyUrl` a las ediciones de carrera, replicando el patrón existente de `shirtUrl` (camiseta conmemorativa).

---

## Leyenda

| Icono | Significado |
|-------|-------------|
| ⬜ | Pendiente |
| 🔄 | En progreso |
| ✅ | Completado |
| ⏸️ | Bloqueado |

---

## Backend

| # | Archivo | Cambio | Estado |
|---|---------|--------|--------|
| B1 | `Domain/Race/Entity/RaceEdition.php` | Añadir propiedad `trophyUrl` + getter/setter | ✅ |
| B2 | `Entity/RaceEdition.php` (ORM) | Añadir columna `trophy_url` | ✅ |
| B3 | `migrations/Version20260604180001.php` | Nueva migración `ALTER TABLE race_editions ADD trophy_url` | ✅ |
| B4 | `Domain/Media/Service/PathGenerator.php` | Método `trophyPath(int $year, string $ext)` → `race/{year}/docs/trofeo-{hash}.{ext}` | ✅ |
| B5 | `Infrastructure/Persistence/Doctrine/Mapper/RaceEditionMapper.php` | Mapear `trophyUrl` en ambos sentidos | ✅ |
| B6 | `Application/Race/Response/RaceEditionResponseDto.php` | Campo `trophyUrl` + serialización en `fromDomain`, `fromDomainDetailed`, `toArray` | ✅ |
| B7 | `Application/Race/Create/CreateRaceEditionCommand.php` | Campo `trophyUrl` | ✅ |
| B8 | `Application/Race/Create/CreateRaceEditionHandler.php` | Pasar `trophyUrl` al constructor | ✅ |
| B9 | `Application/Race/Update/UpdateRaceEditionCommand.php` | Campo `trophyUrl` | ✅ |
| B10 | `Application/Race/Update/UpdateRaceEditionHandler.php` | Manejar `trophyUrl` en update | ✅ |
| B11 | `Application/Race/UploadImage/UploadRaceEditionImageHandler.php` | Soportar type `trophy` con `match` expression | ✅ |
| B12 | `Application/Race/Delete/DeleteRaceEditionHandler.php` | Borrar archivo trophy al eliminar edición | ✅ |
| B13 | `Application/Race/QueryHandler/GetAllEditionsQueryHandler.php` | Incluir `trophyUrl` en query | ✅ |
| B14 | `Infrastructure/Http/Controller/Api/Admin/AdminRaceController.php` | Añadir endpoint `POST /admin/editions/{id}/trophy` + fix `trophyUrl` en `create()` y `update()` | ✅ |

## Frontend

| # | Archivo | Cambio | Estado |
|---|---------|--------|--------|
| F1 | `src/stores/race.store.ts` | Añadir `trophyUrl: string \| null` a interfaz `RaceEdition` | ✅ |
| F2 | `src/views/admin/AdminEditionsView.vue` | Botón upload trofeo (🎀 color rosa), preview link, vars `trophyFile`/`uploadingTrophyId` | ✅ |
| F3 | `src/views/RaceView.vue` | Grid 1col/2cols con camiseta + trofeo, ambos con zoom | ✅ |

## QA

| # | Verificación | Estado |
|---|--------------|--------|
| Q1 | `vue-tsc --build` sin errores | ✅ |
| Q2 | Subir trofeo desde admin funciona | ⬜ (pendiente de probar en servidor) |
| Q3 | Trofeo visible en RaceView | ⬜ (pendiente de probar en servidor) |

---

## Mejoras adicionales implementadas (docs/fotos desde AdminEditionsView)

### Backend

| # | Archivo | Cambio | Estado |
|---|---------|--------|--------|
| B15 | `Infrastructure/Http/Controller/Api/Admin/AdminRaceDocumentController.php` | `GET /admin/documents` soporta `?editionId=` para listar documentos por edición | ✅ |

### Frontend

| # | Archivo | Cambio | Estado |
|---|---------|--------|--------|
| F4 | `src/views/admin/AdminEditionsView.vue` | Botón expandible "Docs/Fotos" por edición | ✅ |
| F5 | `src/views/admin/AdminEditionsView.vue` | Listar documentos adicionales (`route`, `profile`, `general`, `other`) con eliminar | ✅ |
| F6 | `src/views/admin/AdminEditionsView.vue` | Subir documentos adicionales por tipo | ✅ |
| F7 | `src/views/admin/AdminEditionsView.vue` | Listar fotos de galería por edición con miniatura y eliminar | ✅ |
