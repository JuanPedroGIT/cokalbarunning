# Refactor: Emails en 2 Tabs — Gestión + Envío

> **Tracking:** Refactorizar `AdminEmailsView.vue` en dos tabs: gestión de emails (CRUD + preview) y envío (selector de email + filtros + runners).
> **Fecha de inicio:** 2026-07-27
> **Estado:** ⬜ Pendiente

---

## Resumen

El diseño actual tiene un tab por tipo de email (sorteo, indicaciones, agradecimiento, dorsales) con configuración + preview + envío compartidos. El nuevo diseño separa en dos tabs:

1. **Gestión de Emails** — CRUD de configuraciones (`emails_config`), vista previa del email renderizado, listado, eliminación
2. **Envío de Emails** — Selector del email a enviar, filtros (edición, dorsales desde/hasta), listado de runners, envío

---

## Entidad `EmailConfig` (tabla `emails_config`)

Campos existentes:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | GUID | PK |
| `raceEditionId` | GUID | FK a `race_editions` |
| `type` | string(20) | `bib`, `raffle`, `last_instructions`, `thanks`, `generic` |
| `subject` | string(255) | Asunto del correo (con placeholders) |
| `title` | string(255) | Título |
| `description` | text | Descripción / contenido |
| `prize` | string(255) | Premio (solo sorteo) |
| `drawDate` | string(255) | Fecha del sorteo (solo sorteo) |
| `prizeImageUrl` | string(512) | Imagen (premio o genérica) |
| `createdAt` | datetime | — |
| `updatedAt` | datetime | — |

Unique constraint: `(race_edition_id, type)` — una config por tipo y edición.

---

## Decisiones técnicas

### DEC-56: Listar configs con filtro opcional por edición

- **Endpoint:** `GET /api/v1/admin/emails/config?editionId=`
- **Handler:** `GetEmailConfigsQuery` + `GetEmailConfigsQueryHandler`
- Sin `editionId` → todas las configs de todas las ediciones
- Con `editionId` → solo las de esa edición
- **Reversible:** Sí.

### DEC-57: Nuevo endpoint DELETE para configs

- **Endpoint:** `DELETE /api/v1/admin/emails/config/{id}`
- **Handler:** `DeleteEmailConfigCommand` + `DeleteEmailConfigHandler`
- Elimina el registro de `emails_config` (no elimina imágenes de R2 para evitar borrados accidentales)
- **Reversible:** Sí.

### DEC-58: Vista previa del email renderizado

- **Endpoint:** `GET /api/v1/admin/emails/config/{id}/preview`
- **Handler:** `PreviewEmailTemplateQuery` + `PreviewEmailTemplateQueryHandler`
- Renderiza la plantilla Twig con datos de la config + datos dummy de un runner de ejemplo
- Devuelve HTML renderizado + datos usados (subject, body)
- Útil para que el admin vea cómo quedará el email antes de enviarlo
- **Reversible:** Sí.

### DEC-59: El envío referencia un emailConfigId, no un type+edition

- El endpoint `POST /admin/emails/send` recibe `emailConfigId` + `editionId` + `bibFrom`/`bibTo` + `runnerIds`
- El handler carga la config por ID para obtener `type`, metadata (subject, title, etc.)
- Ya no se asume una única config por edition+type
- **Impacto:** `SendEmailCampaignCommand`, `SendEmailCampaignHandler`, `AdminEmailController::send()`
- **Reversible:** Sí.

### DEC-60: El preview de runners también referencia un emailConfigId

- `POST /admin/emails/preview` recibe `emailConfigId` + `bibFrom`/`bibTo`
- El handler carga la config, obtiene el `type`, cruza con `email_send_logs`
- **Impacto:** `PreviewEmailRecipientsCommand`, `PreviewEmailRecipientsHandler`, `AdminEmailController::preview()`
- **Reversible:** Sí.

### DEC-61: Crear capa de dominio para EmailConfig

- **Contexto:** `EmailConfig` solo existe como entidad ORM (`App\Entity\EmailConfig`) con un `ServiceEntityRepository` (`App\Repository\EmailConfigRepository`). 4 handlers (`CreateEmailConfigHandler`, `UpdateEmailConfigHandler`, `GetEmailConfigQueryHandler`, `UploadEmailImageHandler`) usan directamente el repositorio Doctrine y la entidad ORM, violando la arquitectura hexagonal (mismo problema que tenía `Runner` antes del Bloque 0 de `20_SEPARAR_CARGA_RUNNERS_EMAILS.md`).
- **Decisión:** Crear `EmailConfig` como entidad de dominio en `Domain\Notification\Entity\`, `EmailConfigRepositoryInterface` en `Domain\Notification\Repository\`, `EmailConfigMapper`, `DoctrineEmailConfigRepository`, y alias en `services.yaml`. Refactorizar los 4 handlers existentes para inyectar la interfaz. Los nuevos handlers (GetEmailConfigs, DeleteEmailConfig, PreviewEmailTemplate) también usarán la interfaz.
- **Impacto:** Nueva entidad de dominio, interfaz, mapper, repositorio Doctrine, alias. 4 handlers existentes refactorizados + 3 nuevos handlers limpios desde el inicio.
- **Reversible:** Sí.

---

## Tareas

### Bloque 0: Prerrequisito — Domain EmailConfig (interfaz + entidad + repositorio)

Antes de implementar el CRUD, hay que crear la capa de dominio para `EmailConfig`.

- [x] **P1** Crear `backend/src/Domain/Notification/Entity/EmailConfig.php`
  - `final class`, constructor con propiedades privadas, getters
  - Campos: `id`, `raceEditionId`, `type`, `subject`, `title`, `description`, `prize`, `drawDate`, `prizeImageUrl`, `createdAt`, `updatedAt`
  - Método `update(...)` para modificar campos
  - Sin setters — inmutable excepto vía `update()`

- [x] **P2** Crear `backend/src/Domain/Notification/Repository/EmailConfigRepositoryInterface.php`
  - Namespace: `App\Domain\Notification\Repository`
  - Métodos:
    - `save(EmailConfig $config): void`
    - `remove(EmailConfig $config): void`
    - `findById(string $id): ?EmailConfig`
    - `findByRaceEditionIdAndType(string $raceEditionId, string $type): ?EmailConfig`
    - `findByRaceEditionId(string $raceEditionId): array`
    - `findAll(): array`

- [x] **P3** Crear `backend/src/Infrastructure/Persistence/Doctrine/Mapper/EmailConfigMapper.php`
  - `toDomain(OrmEmailConfig): DomainEmailConfig`
  - `toOrm(DomainEmailConfig, ?OrmEmailConfig): OrmEmailConfig`

- [x] **P4** Crear `backend/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineEmailConfigRepository.php`
  - Implementa `EmailConfigRepositoryInterface`
  - Inyecta `EntityManagerInterface` + `EmailConfigMapper`
  - Mismo patrón que los otros 10 repositorios Doctrine

- [x] **P5** Añadir alias en `backend/config/services.yaml`:
  ```yaml
  App\Domain\Notification\Repository\EmailConfigRepositoryInterface:
      alias: App\Infrastructure\Persistence\Doctrine\Repository\DoctrineEmailConfigRepository
  ```

- [x] **P6** Refactorizar 4 handlers existentes — inyectar `EmailConfigRepositoryInterface` en vez de `EmailConfigRepository`:
  - `CreateEmailConfigHandler`
  - `UpdateEmailConfigHandler`
  - `GetEmailConfigQueryHandler`
  - `UploadEmailImageHandler`

- [x] **P7** Eliminar `backend/src/Repository/EmailConfigRepository.php` (ServiceEntityRepository — reemplazado por el de Doctrine)

### Bloque 1: Backend — CRUD de configs (listar + eliminar + preview template)

- [x] **C1** Crear `GetEmailConfigsQuery` + `GetEmailConfigsQueryHandler`
  - Lista todas las configs con filtro opcional `?editionId=`
  - Devuelve array con todos los campos de `emails_config` + nombre de la edición

- [x] **C2** Crear `DeleteEmailConfigCommand` + `DeleteEmailConfigHandler`
  - Recibe `id`, elimina el registro de `emails_config`
  - No elimina imágenes de R2

- [x] **C3** Crear `PreviewEmailTemplateQuery` + `PreviewEmailTemplateQueryHandler`
  - Recibe `emailConfigId`
  - Carga la config, obtiene un runner de ejemplo de la edición
  - Renderiza la plantilla Twig correspondiente al `type`
  - Devuelve `{ subject, html }` con el email renderizado

- [x] **C4** Endpoints en `AdminEmailController`:
  - `GET /admin/emails/config` → `GetEmailConfigsQuery`
  - `DELETE /admin/emails/config/{id}` → `DeleteEmailConfigCommand`
  - `GET /admin/emails/config/{id}/preview` → `PreviewEmailTemplateQuery`

### Bloque 2: Backend — Refactorizar preview y send para usar emailConfigId

- [x] **S1** Modificar `PreviewEmailRecipientsCommand`
  - Quitar `type`
  - Añadir `emailConfigId` (string)
  - El handler carga la config → obtiene `type` y `raceEditionId`

- [x] **S2** Modificar `PreviewEmailRecipientsHandler`
  - Cargar `EmailConfig` por ID
  - Usar `type` y `raceEditionId` de la config
  - Devolver también los datos de la config en la respuesta (subject, title, etc.)

- [x] **S3** Modificar `SendEmailCampaignCommand`
  - Quitar `type`
  - Añadir `emailConfigId` (string)
  - El handler carga la config → obtiene `type` y metadata

- [x] **S4** Modificar `SendEmailCampaignHandler`
  - Cargar `EmailConfig` por ID
  - Usar `type` de la config para el envío
  - Merge de metadata: config data + metadata adicional del request

- [x] **S5** Actualizar `AdminEmailController::preview()` — recibir `{emailConfigId, bibFrom, bibTo}`
- [x] **S6** Actualizar `AdminEmailController::send()` — recibir `{emailConfigId, runnerIds, editionId, force, metadata}`
- [x] **S7** Simplificar rutas: `POST /admin/emails/preview` y `POST /admin/emails/send` (sin `{type}` en la URL)

### Bloque 3: Frontend — Tab 1: Gestión de Emails

- [x] **G1** Crear sección "Gestión de Emails" en `AdminEmailsView.vue`:
  - Selector de edición (filtra las configs listadas)
  - Formulario con todos los campos de `emails_config`:
    - `type`: dropdown (sorteo, indicaciones, agradecimiento, dorsales)
    - `subject`, `title`, `description`, `prize` (condicional), `drawDate` (condicional)
    - `prizeImageUrl`: `ImageDropZone` para subir/eliminar imagen
  - Botones: Guardar / Cancelar

- [x] **G2** Listado de configs debajo del formulario:
  - Tabla con columnas: tipo, edición, asunto, título, fecha creación, acciones
  - Acciones: Editar (carga en formulario), Eliminar (con confirmación), Preview (abre modal con email renderizado)
  - Filtro por edición en tiempo real

- [x] **G3** Modal de vista previa del email:
  - Muestra subject + iframe/contenido HTML del email renderizado
  - Datos dinámicos (nombre runner, dorsal) con valores de ejemplo

### Bloque 4: Frontend — Tab 2: Envío de Emails

- [x] **E1** Crear sección "Envío de Emails":
  - Selector del email a enviar (dropdown con las configs existentes: "Sorteo 2026", "Indicaciones 2026", etc.)
  - Filtros: selector de edición + "Dorsal desde" + "Dorsal hasta"
  - Botón "Cargar runners"

- [x] **E2** Listado de runners debajo de los filtros:
  - Tabla con: checkbox, nombre, email, dorsal, envíos previos, estado anterior
  - Select all / deselect all
  - Stats: total, válidos, inválidos, duplicados

- [x] **E3** Acciones de envío:
  - Botón "Enviar N correo(s)" → `POST /admin/emails/send`
  - Checkbox "Forzar reenvío"
  - Sección de logs/historial con reenvío individual y por grupo
  - BCC input
  - Botón "Ejecutar envíos pendientes"

### Bloque 5: Limpieza y verificación

- [ ] **L1** Eliminar código muerto:
  - Rutas legacy con `{type}` en la URL si ya no se usan
  - Endpoints de config por type+edition si se unifican en CRUD genérico (evaluar)
  - Imports no usados en `AdminEmailController`

- [ ] **L2** `make test` — actualizar tests afectados:
  - `AdminEmailControllerTest` — nuevas firmas de endpoints
  - Nuevos tests para `GetEmailConfigs`, `DeleteEmailConfig`, `PreviewEmailTemplate`

- [ ] **L3** Frontend: `vue-tsc` + build verde

- [ ] **L4** Actualizar `docs/tracking/05_PROGRESO.md`

---

## Notas

- La tabla `emails_config` tiene unique constraint `(race_edition_id, type)`. Si el admin necesita dos configs del mismo tipo para la misma edición, habría que quitar esa restricción. Por ahora se mantiene.
- `EmailType::GENERIC` se mantiene en el value object por compatibilidad con datos existentes, pero no aparece en el UI.
- El `ImageDropZone` existente se reutiliza para la imagen del email en el formulario de gestión.
- Los endpoints de config actuales (`GET/POST /admin/emails/{type}/config`, `PUT /admin/emails/{type}/config/{id}`) se mantienen o se reemplazan por el nuevo CRUD unificado. **Decisión pendiente.**

---

## Orden de implementación

| Orden | Bloque | Depende de |
|--------|--------|------------|
| 1 | B0 — Domain EmailConfig (interfaz + entidad + repositorio) | Nada |
| 2 | B1 — Backend CRUD configs (listar + eliminar + preview template) | B0 |
| 3 | B2 — Backend refactorizar preview/send con emailConfigId | B0 |
| 4 | B3 — Frontend Tab 1: Gestión de Emails | B1 |
| 5 | B4 — Frontend Tab 2: Envío de Emails | B2 |
| 6 | B5 — Limpieza y tests | B0-B4 |
