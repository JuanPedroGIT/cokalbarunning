# Ajuste SOLID — Corrección de desviaciones

> **Tracking:** Corrección de desviaciones detectadas en auditoría del plan `08_PLAN_SOLID.md`.
> **Fecha de inicio:** 2026-06-29
> **Estado:** ✅ Completado

---

## Resumen

El plan `08_PLAN_SOLID.md` definió 6 fases. `05_PROGRESO.md` las marca todas como completadas, pero la auditoría del código real revela que varias fases están parcialmente implementadas. Este tracking corrige las 4 desviaciones encontradas.

---

## Hallazgos de la auditoría

### Fase 1 (PathGenerator): Parcialmente completo

| Controller | ¿Usa PathGenerator? | ¿Problema? |
|---|---|---|
| AdminRaceController | Sí, vía UploadRaceEditionImageHandler | OK |
| AdminPhotoController | Sí, pero **inline en el controller** | El controller genera paths y almacena archivos directamente |
| AdminRaceDocumentController | Sí, pero **inline en el controller** | El controller genera paths y almacena archivos directamente |
| AdminSponsorController | Sí, vía UploadSponsorLogoHandler | OK |
| AdminClubMemberController | Sí, vía UploadClubMemberPhotoHandler | OK |

### Fase 2 (Upload Handlers CQRS): Implementado pero doc desactualizado

Los 3 handlers existen y funcionan. El doc tiene checkboxes `[ ]` sin marcar. Pero faltan handlers para Photos y Documents:
- `UploadPhotoHandler` actual solo inserta en BD; el controller hace el upload real
- No existe `UploadRaceDocumentHandler`; `CreateRaceDocumentHandler` solo inserta en BD

### Fase 3 (Sin EntityManager/StoragePort en controllers): INCOMPLETO

**EntityManager inyectado para `createdBy`/`updatedBy`:** AdminRaceController, AdminSponsorController, AdminClubMemberController, AdminBlogController

**StoragePort inyectado para storage inline:** AdminPhotoController, AdminRaceDocumentController, AdminRaceController (normalizePath), AdminBlogController (uploadCover)

### Fase 4, 5, 6: COMPLETO

GenerateThumbnailsCommand, PhotoRepositoryInterface.findByEditionId, y tests OK.

---

## Decisiones técnicas

### DEC-40: AuditSubscriber vía Doctrine events

- **Contexto:** 4 controllers inyectan EntityManager solo para setear `createdBy`/`updatedBy`.
- **Decisión:** Crear `AuditSubscriber` con `#[AsDoctrineListener]` en `prePersist`/`preUpdate`. Usa `Security::getUser()` y `method_exists()` para ser genérico.
- **Impacto:** Nuevo `AuditSubscriber.php`, eliminar EM de 4 controllers.
- **Reversible:** Sí.

### DEC-41: UploadPhotoHandler recibe tmpPath

- **Contexto:** `AdminPhotoController.create()` hace path generation + thumbnail + storage inline (29 líneas). `UploadPhotoHandler` solo inserta en BD.
- **Decisión:** Cambiar `UploadPhotoCommand` para recibir `tmpPath`/`originalName`/`mimeType` (como los demás handlers). El handler hace todo el trabajo: buscar edición, generar paths, crear thumbnail, almacenar, persistir.
- **Impacto:** Command, Handler, Controller.
- **Reversible:** Sí.

### DEC-42: UploadRaceDocumentHandler reemplaza CreateRaceDocumentHandler

- **Contexto:** `AdminRaceDocumentController.create()` hace path generation + storage inline. `CreateRaceDocumentHandler` solo inserta en BD.
- **Decisión:** Crear `UploadRaceDocumentCommand` + `UploadRaceDocumentHandler` con la lógica completa. Eliminar `CreateRaceDocumentCommand`/`CreateRaceDocumentHandler` (solo los usa el controller).
- **Impacto:** 2 archivos nuevos, 2 eliminados, 1 modificado.
- **Reversible:** Sí (re-crear los archivos eliminados desde git).

### DEC-43: normalizePath se mueve a UpdateRaceEditionHandler

- **Contexto:** `AdminRaceController` inyecta `StoragePort` solo para `normalizePath()`.
- **Decisión:** Mover el helper a `UpdateRaceEditionHandler`, que ya es responsable de actualizar la entidad. Normaliza `posterUrl`/`shirtUrl`/`trophyUrl` antes de aplicarlos.
- **Impacto:** Handler y Controller.
- **Reversible:** Sí.

---

## Tareas

### Bloque 1: AuditSubscriber — eliminar EntityManager de 4 controllers

- [x] **A1.1** Crear `backend/src/Infrastructure/Persistence/Doctrine/EventSubscriber/AuditSubscriber.php`
- [x] **A1.2** `AdminRaceController.php` — quitar EM, quitar bloques setCreatedBy/setUpdatedBy
- [x] **A1.3** `AdminSponsorController.php` — quitar EM, quitar bloques setCreatedBy/setUpdatedBy
- [x] **A1.4** `AdminClubMemberController.php` — quitar EM, quitar bloques setCreatedBy/setUpdatedBy
- [x] **A1.5** `AdminBlogController.php` — quitar EM, quitar bloques setCreatedBy/setUpdatedBy (EM conservado solo para uploadCover)

### Bloque 2: Refactorizar UploadPhotoHandler

- [x] **A2.1** `UploadPhotoCommand.php` — cambiar campos a `tmpPath`/`originalName`/`mimeType`
- [x] **A2.2** `UploadPhotoHandler.php` — añadir StoragePort, ImageProcessorInterface, PathGenerator, RaceEditionRepositoryInterface; mover lógica de upload
- [x] **A2.3** `AdminPhotoController.php` — simplificar create(), quitar StoragePort/ImageProcessor/RaceEditionRepository/PathGenerator del constructor

### Bloque 3: Crear UploadRaceDocumentHandler

- [x] **A3.1** Crear `backend/src/Application/Race/UploadDocument/UploadRaceDocumentCommand.php`
- [x] **A3.2** Crear `backend/src/Application/Race/UploadDocument/UploadRaceDocumentHandler.php`
- [x] **A3.3** Eliminar `CreateRaceDocumentCommand.php` y `CreateRaceDocumentHandler.php`
- [x] **A3.4** `AdminRaceDocumentController.php` — simplificar create(), quitar StoragePort/RaceEditionRepository/PathGenerator

### Bloque 4: Mover normalizePath al handler

- [x] **A4.1** `UpdateRaceEditionHandler.php` — añadir StoragePort, añadir normalizePath(), aplicarlo a posterUrl/shirtUrl/trophyUrl
- [x] **A4.2** `AdminRaceController.php` — quitar StoragePort, quitar normalizePath(), pasar valores crudos en update()

### Bloque 5: Verificación y docs

- [x] **A5.1** Ejecutar `php bin/phpunit` — ✅ 164 tests, 540 assertions verdes
- [x] **A5.2** Actualizar `docs/tracking/08_PLAN_SOLID.md` — marcar checkboxes Fase 2
- [x] **A5.3** Actualizar `docs/tracking/05_PROGRESO.md`

---

## Verificación

```bash
cd backend && php bin/phpunit
```

Resultado esperado: 164 tests, 540 assertions verdes.
