# SOLID Restantes — Controllers con violaciones

> **Tracking:** Corrección de los 7 controllers que aún violan SOLID tras el ajuste inicial (`18_AJUSTE_SOLID.md`).
> **Fecha de inicio:** 2026-06-29
> **Estado:** ✅ Completado

---

## Resumen

Tras auditar los 21 controllers del proyecto, 14 están limpios y 7 tienen violaciones. Este tracking cubre la corrección de esos 7, ordenados por prioridad.

---

## Orden de implementación

| # | Controller | Severidad | Dependencias a eliminar | Esfuerzo |
|---|-----------|-----------|------------------------|----------|
| 1 | AdminResultController | CRÍTICO | EM, RaceEditionRepository, ResultRepository | Medio |
| 2 | AdminUserController | CRÍTICO | EM, UserPasswordHasher | Bajo |
| 3 | RunnerController | CRÍTICO | EM | Bajo |
| 4 | AdminBlogController | CRÍTICO | EM, StoragePort, PathGenerator, SocialPublishLogRepository | Medio |
| 5 | AdminEmailController | CRÍTICO | EM, ParseEmailCsv, 3 repos, StoragePort, PathGenerator | Alto |
| 6 | MeController | MODERADO | EM, ClubMemberRepository, StoragePort, UserPasswordHasher | Medio |
| 7 | RaceDocumentController | MENOR | RaceEditionRepository | Bajo |

---

## Decisiones técnicas

### DEC-44: Orden de implementación

Los controllers que no usan el bus para nada (Result, User, Runner) van primero porque hay que crearlo todo desde cero. Los que ya usan el bus parcialmente (Blog, Email) van después. AdminEmailController va último por ser el más complejo (300+ líneas, 7 dependencias extra).

### DEC-45: AdminResultController — un solo comando ImportResults

Todo el flujo de importación de resultados (CSV → entidades → recálculo) se mueve a `ImportResultsCommand` + `ImportResultsHandler`. Las funciones helper (`findOrCreateRunner`, `findOrCreateCategory`, `parseTime`, `recalculatePositions`) se convierten en métodos privados del handler.

### DEC-46: AdminUserController — comandos CRUD estándar

Crear `CreateUserCommand`/`UpdateUserCommand`/`DeleteUserCommand` + handlers. El `UserPasswordHasherInterface` se inyecta en los handlers que lo necesiten (create y update). El listado se convierte en `GetAllUsersQuery`.

### DEC-47: RunnerController — query + handler

`search()` pasa a ser `SearchRunnersQuery` + `SearchRunnersQueryHandler`. El handler usa el repositorio de runners (ya existente) en vez de QueryBuilder directo.

### DEC-48: AdminBlogController.uploadCover — comando de upload

Crear `UploadBlogCoverCommand` + `UploadBlogCoverHandler` (mismo patrón que `UploadSponsorLogoCommand`). `listSocialPublishes()` se convierte en query.

### DEC-49: AdminEmailController — extracción por endpoint

Cada endpoint se convierte en un comando/query independiente:
- `preview` → `PreviewEmailRecipientsCommand` + handler
- `send` → `SendEmailCampaignCommand` + handler
- `sentCounts` → `GetEmailSentCountsQuery` + handler
- `genericRecipients` → `GetGenericRecipientsQuery` + handler
- `run` → Ya existe `SendPendingEmailsCommand`, el controller solo despacha
- `getConfig`/`createConfig`/`updateConfig` → queries/commands de `EmailConfig`
- `uploadPrizeImage` → `UploadEmailImageCommand` + handler
- `upsertRunner` → método privado del handler de preview/send

### DEC-50: MeController — comandos + queries

- `clubProfile()` → `GetMyClubProfileQuery` + handler
- `updateClubProfile()` → `UpdateMyClubProfileCommand` + handler
- `changePassword()` → `ChangePasswordCommand` + handler

### DEC-51: RaceDocumentController — unificar query

Modificar `GetDocumentsByEditionQuery` para aceptar `year` como alternativa a `editionId`, eliminando la necesidad del lookup en el controller.

---

## Tareas

### 1. AdminResultController (CRÍTICO)

- [ ] **R1** Crear `src/Application/Result/ImportResultsCommand.php`
- [ ] **R2** Crear `src/Application/Result/ImportResultsHandler.php` (mover CSV parsing, findOrCreateRunner, findOrCreateCategory, recalculatePositions)
- [ ] **R3** Simplificar `AdminResultController.php` — solo validar archivo, despachar comando

### 2. AdminUserController (CRÍTICO)

- [ ] **U1** Crear `src/Application/User/Create/CreateUserCommand.php` + `CreateUserHandler.php`
- [ ] **U2** Crear `src/Application/User/Update/UpdateUserCommand.php` + `UpdateUserHandler.php`
- [ ] **U3** Crear `src/Application/User/Delete/DeleteUserCommand.php` + `DeleteUserHandler.php`
- [ ] **U4** Crear `src/Application/User/Query/GetAllUsersQuery.php` + handler
- [ ] **U5** Simplificar `AdminUserController.php` — solo bus

### 3. RunnerController (CRÍTICO)

- [ ] **RN1** Crear `src/Application/Runner/SearchRunnersQuery.php` + `SearchRunnersQueryHandler.php`
- [ ] **RN2** Simplificar `RunnerController.php` — solo bus

### 4. AdminBlogController (CRÍTICO)

- [ ] **B1** Crear `src/Application/Media/UploadCover/UploadBlogCoverCommand.php` + `UploadBlogCoverHandler.php`
- [ ] **B2** Crear `src/Application/SocialPublishing/Query/GetSocialPublishLogsQuery.php` + handler
- [ ] **B3** Simplificar `AdminBlogController.php` — quitar EM, StoragePort, PathGenerator, SocialPublishLogRepository; delegar uploadCover y listSocialPublishes

### 5. AdminEmailController (CRÍTICO)

- [ ] **E1** Crear `src/Application/Notification/PreviewEmailRecipientsCommand.php` + handler (mover CSV parsing, upsertRunner)
- [ ] **E2** Crear `src/Application/Notification/SendEmailCampaignCommand.php` + handler (mover createOrUpdateLog, asignación de sentBy)
- [ ] **E3** Crear `src/Application/Notification/GetEmailSentCountsQuery.php` + handler
- [ ] **E4** Crear `src/Application/Notification/GetGenericRecipientsQuery.php` + handler
- [ ] **E5** Crear `src/Application/Notification/UploadEmailImageCommand.php` + handler
- [ ] **E6** Simplificar `AdminEmailController.php` — solo bus en constructor, cada endpoint despacha su comando/query

### 6. MeController (MODERADO)

- [ ] **M1** Crear `src/Application/Club/GetMyClubProfileQuery.php` + handler
- [ ] **M2** Crear `src/Application/Club/UpdateMyClubProfileCommand.php` + handler
- [ ] **M3** Crear `src/Application/User/ChangePasswordCommand.php` + handler
- [ ] **M4** Simplificar `MeController.php` — solo bus

### 7. RaceDocumentController (MENOR)

- [ ] **RD1** Modificar `GetDocumentsByEditionQuery` para aceptar `?int $year` como alternativa
- [ ] **RD2** Actualizar handler para resolver año → editionId internamente
- [ ] **RD3** Simplificar `RaceDocumentController.php` — quitar RaceEditionRepository

### 8. Verificación

- [ ] **V1** Ejecutar `make test` — 164 tests actuales deben seguir pasando
- [ ] **V2** Actualizar `docs/tracking/05_PROGRESO.md`

---

## Notas

- AdminEmailController es el más grande y delicado. Se recomienda implementarlo en sesiones separadas, un endpoint a la vez.
- Los comandos de usuario (Create/Update/Delete) deben validar que solo ROLE_ADMIN pueda asignar rol admin a otro usuario.
- `ImportResultsHandler` debe seguir el patrón de `SendPendingEmailsCommand`: recibir la ruta del archivo temporal y procesarlo.
