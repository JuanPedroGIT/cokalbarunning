# Separación: Carga de Runners y Envío de Emails

> **Tracking:** Separar la carga de runners del envío de emails en el panel admin.
> **Fecha de inicio:** 2026-07-27
> **Estado:** ⬜ Pendiente

---

## Resumen

Actualmente `AdminEmailsView.vue` (1031 líneas) y `AdminEmailController` (331 líneas) mezclan dos responsabilidades:
1. **Carga de runners** vía CSV (con upsert en BD)
2. **Envío de emails** (preview, configuración, envío, logs)

Se van a separar en dos secciones independientes del panel admin:
- **Carga de Runners:** solo subir CSV a una edición, sin nada de emails
- **Envío de Emails:** selector de tipo de email, filtros (edición, rango de dorsales), preview de destinatarios desde runners ya cargados en BD, envío

---

## Decisiones técnicas

### DEC-52: Los runners se cargan una sola vez, los emails se envían desde runners en BD

- **Antes:** El CSV se subía cada vez que se quería enviar un email (preview/send). El handler hacía upsert de runners en cada preview.
- **Ahora:** La carga de runners es una operación independiente. El envío de emails consulta los runners ya existentes en BD y aplica filtros (edición, rango de dorsales).
- **Impacto:** `PreviewEmailRecipientsHandler` y `SendEmailCampaignHandler` dejan de recibir CSV; en su lugar reciben filtros.
- **Reversible:** Sí.

### DEC-53: El preview de emails se genera desde BD, no desde CSV

- **Antes:** El preview requería subir un archivo CSV.
- **Ahora:** El preview consulta `runners` en BD con los filtros seleccionados (edición, dorsales desde-hasta) y cruza con `email_send_logs` para mostrar el estado.
- **Impacto:** Nuevo endpoint `POST /admin/emails/{type}/preview` que recibe JSON con filtros en vez de multipart file.
- **Reversible:** Sí.

### DEC-54: Nueva página independiente para carga de runners

- **Ruta:** `/admin/runners`
- **Componente:** `AdminRunnersView.vue`
- **Funcionalidad:** Selector de edición + input CSV + subida + resultado (runners creados/actualizados/saltados)
- **Backend:** Comando `ImportRunnersFromCsv` + handler (extrae lógica de upsert existente)
- **Reversible:** Sí.

### DEC-55: Crear RunnerRepositoryInterface + DoctrineRunnerRepository

- **Contexto:** `Runner` es el único agregado sin repositorio de dominio. `SearchRunnersQueryHandler`, `PreviewEmailRecipientsHandler` y `SendEmailCampaignHandler` usan `EntityManagerInterface` directo para consultar/upsert runners, lo cual viola la arquitectura hexagonal (el resto de agregados —Sponsor, Photo, BlogPost, ClubMember, RaceEdition, Result, RaceDocument, EmailSendLog— ya tienen su interfaz en Domain y su implementación Doctrine).
- **Decisión:** Crear `RunnerRepositoryInterface` en `Domain/Registration/Repository/` y `DoctrineRunnerRepository` en `Infrastructure/Persistence/Doctrine/Repository/`. Los handlers de emails y búsqueda se refactorizan para inyectar la interfaz. Se añade alias en `services.yaml`.
- **Impacto:** Nueva interfaz, nuevo repositorio, 3 handlers refactorizados, `services.yaml`.
- **Reversible:** Sí (volver a inyectar EM directo).

---

## Tareas

### Bloque 0: Prerrequisito — RunnerRepositoryInterface + DoctrineRunnerRepository

Antes de empezar los bloques de funcionalidad, hay que cerrar la brecha arquitectónica: `Runner` no tiene repositorio de dominio.

- [x] **P1** Crear `backend/src/Domain/Registration/Repository/RunnerRepositoryInterface.php`
  - Namespace: `App\Domain\Registration\Repository`
  - Métodos:
    - `save(Runner $runner): void`
    - `remove(Runner $runner): void`
    - `findById(string $id): ?Runner`
    - `findByBibNumberAndEditionId(string $bibNumber, string $raceEditionId): ?Runner`
    - `findByEmailAndEditionId(string $email, string $raceEditionId): ?Runner`
    - `findByEditionId(string $raceEditionId): array`
    - `findByEditionIdAndBibRange(string $raceEditionId, ?string $bibFrom, ?string $bibTo): array`

- [x] **P2** Crear `backend/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineRunnerRepository.php`
  - Implementa `RunnerRepositoryInterface`
  - Inyecta `EntityManagerInterface` + `RunnerMapper` (ya existe)
  - Patrón: mismo que `DoctrineSponsorRepository`, `DoctrinePhotoRepository`, etc.

- [x] **P3** Añadir alias en `backend/config/services.yaml`:
  ```yaml
  App\Domain\Registration\Repository\RunnerRepositoryInterface:
      alias: App\Infrastructure\Persistence\Doctrine\Repository\DoctrineRunnerRepository
  ```

- [x] **P4** Refactorizar `SearchRunnersQueryHandler` — quitar `EntityManagerInterface`, inyectar `RunnerRepositoryInterface`
  - Usar `$this->runnerRepository->findByEditionId()` o método con filtros combinados
  - Eliminar QueryBuilder directo sobre `App\Entity\Runner`

---

### Bloque 1: Backend — Carga de runners independiente

- [x] **R1** Crear `backend/src/Application/Registration/ImportRunners/ImportRunnersFromCsvCommand.php`
  - `final class`, propiedades: `public string $raceEditionId`, `public string $csvContent`

- [x] **R2** Crear `backend/src/Application/Registration/ImportRunners/ImportRunnersFromCsvHandler.php`
  - `#[AsMessageHandler] final class`
  - Constructor: `RunnerRepositoryInterface $runnerRepository`, `RaceEditionRepositoryInterface $raceEditionRepository`, `ParseEmailCsv $csvParser`
  - Extraer la lógica de upsert desde `PreviewEmailRecipientsHandler` y `SendEmailCampaignHandler`
  - Para cada fila del CSV: buscar runner por `bibNumber+editionId`, luego por `email+editionId`, crear o actualizar
  - Saltar runners con `bibNumber` vacío, `0`, `00`, `000`, o sin email válido (misma lógica actual)
  - Response DTO: `ImportRunnersResultDto` con `created`, `updated`, `skipped`, `total`

- [x] **R3** Crear endpoint `POST /api/v1/admin/editions/{id}/runners/import`
  - **Decisión:** Opción B — `AdminRunnerController` con `#[Route('/api/v1/admin')]`
  - Constructor: `MessageBusInterface $commandBus`
  - Método `import(string $id, Request $request)`: valida archivo CSV, despacha `ImportRunnersFromCsvCommand`, devuelve JSON con `ImportRunnersResultDto`

---

### Bloque 2: Backend — Refactorizar envío de emails (sin CSV)

- [x] **E1** Modificar `PreviewEmailRecipientsCommand`
  - Quitar: `public string $csvContent`
  - Añadir: `public string $editionId`, `public ?string $bibFrom = null`, `public ?string $bibTo = null`

- [x] **E2** Reescribir `PreviewEmailRecipientsHandler`
  - Quitar dependencias: `ParseEmailCsv`, `EntityManagerInterface`
  - Añadir dependencias: `RunnerRepositoryInterface`, `EmailSendLogRepositoryInterface`
  - `__invoke`: consulta runners con `findByEditionIdAndBibRange()`, cruza con `email_send_logs` para estado
  - Ya no hace upsert de runners (eso es responsabilidad del Bloque 1)
  - Devuelve mismo formato de preview (items con status) para no romper el frontend

- [x] **E3** Modificar `SendEmailCampaignCommand`
  - Quitar: array de items con datos del CSV
  - Añadir: `public string $type`, `public string $editionId`, `public array $runnerIds`, `public ?string $bibFrom = null`, `public ?string $bibTo = null`, `public bool $forceResend = false`, `public array $metadata = []`

- [x] **E4** Reescribir `SendEmailCampaignHandler`
  - Quitar dependencias: `EntityManagerInterface`, `ParseEmailCsv`
  - Añadir dependencias: `RunnerRepositoryInterface`, `EmailSendLogRepositoryInterface`, `MessageBusInterface`
  - `__invoke`: itera `runnerIds`, busca cada runner en BD, crea/actualiza `EmailSendLog`
  - Quitar upsert de runners (ya no es su responsabilidad)
  - Mantener auto-encolado de `last_instructions` al enviar `raffle`
  - Mantener `showBibSearch = true` al enviar `bib`

- [x] **E5** Refactorizar `AdminEmailController::preview()` — recibir JSON body con `{editionId, bibFrom, bibTo}` en vez de multipart file

- [x] **E6** Refactorizar `AdminEmailController::send()` — recibir `{type, editionId, runnerIds, bibFrom, bibTo, forceResend, metadata}` en vez de items del CSV

- [x] **E7** Evaluar endpoints legacy `/bib-emails`
  - Si ya no se usan → eliminar
  - Si se usan → mantener redirect a `/admin/emails/bib`

- [x] **E8** Limpiar `AdminEmailController`:
  - Quitar `ParseEmailCsv` del constructor si ya no se usa
  - Verificar que solo inyecta `MessageBusInterface $commandBus`, `MessageBusInterface $queryBus`, `EmailSendLogRepositoryInterface` (para BCC en `run()`)
  - Eliminar método `genericRecipients()` y endpoint asociado (la pestaña "genérico" se cubre con el filtro de edición "todas" o desaparece)

---

### Bloque 3: Frontend — Nueva página de carga de runners

- [x] **F1** Crear `frontend/src/views/admin/AdminRunnersView.vue`
  - `<script setup lang="ts">` — mismo estilo que el resto de vistas admin
  - Estado: `editions`, `selectedEditionId`, `csvFile`, `importing`, `result`
  - Al cargar: `fetchEditions()` desde `GET /api/v1/admin/editions` (selector con edición activa por defecto)
  - Input file nativo para CSV (no drag & drop — es un CSV, no imagen)
  - Botón "Importar" → `POST /api/v1/admin/editions/{id}/runners/import` (multipart)
  - Mostrar resumen: tarjetas con contadores (creados, actualizados, saltados, total)
  - Diseño consistente con tema oscuro del admin

- [x] **F2** Añadir ruta en `frontend/src/router/index.ts`:
  ```ts
  {
    path: '/admin/runners',
    name: 'admin-runners',
    component: () => import('@/views/admin/AdminRunnersView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  }
  ```

- [x] **F3** Añadir tarjeta "Carga de Runners" en `AdminDashboardView.vue`
  - `adminOnly: true`
  - Entre "Envio de Correos" y "Usuarios" (o al final)

---

### Bloque 4: Frontend — Refactorizar página de envío de emails

- [x] **G1** Refactorizar `AdminEmailsView.vue`:
  - **Quitar:**
    - Input file de CSV y toda la lógica de `handleFileChange()`
    - Lógica de parseo de CSV en frontend
    - Pestaña "genérico" (se cubre con filtro sin edición o edición "todas")
    - `loadGenericRecipients()`
  - **Añadir:**
    - Selector de tipo de email (dropdown: `raffle`, `last_instructions`, `thanks`, `bib`)
    - Filtros en una fila: selector de edición + input "Dorsal desde" + input "Dorsal hasta"
    - Botón "Cargar preview" → `POST /api/v1/admin/emails/{type}/preview` con body JSON `{editionId, bibFrom, bibTo}`
  - **Mantener:**
    - Pestañas de tipo de email (ahora controlan qué tipo se envía, no qué CSV se carga)
    - Configuración del email (subject, title, description, prize, fecha sorteo, imagen)
    - Tabla de preview con checkboxes, stats (válidos/inválidos/duplicados/enviados)
    - Logs de envío agrupados con contadores
    - Botón "Ejecutar envíos pendientes"
    - BCC input
    - Reenvío individual y por grupo

- [x] **G2** Actualizar `router/index.ts`:
  - Mantener `/admin/emails` → `AdminEmailsView.vue`
  - Evaluar si quitar redirect de `/admin/bib-emails`

---

### Bloque 5: Limpieza y verificación

- [x] **L1** Eliminar código muerto en backend:
  - `PreviewEmailRecipientsHandler` — quitar referencias a `ParseEmailCsv` y `EntityManager`
  - `SendEmailCampaignHandler` — quitar referencias a `ParseEmailCsv` y `EntityManager`
  - `SearchRunnersQueryHandler` — quitar `EntityManagerInterface`, usar `RunnerRepositoryInterface`
  - `AdminEmailController` — quitar `ParseEmailCsv`, quitar endpoint `genericRecipients`
  - Evaluar si `ParseEmailCsv` se mueve a `Application/Registration/` (solo lo usa el handler de import)

- [ ] **L2** Ejecutar `make test` en backend **(requiere servidor/Docker)**
  - `AdminEmailControllerTest` (8 tests) necesita actualizarse para las nuevas firmas JSON
  - Tests de handlers no existen (no hay tests unitarios de `PreviewEmailRecipientsHandler` ni `SendEmailCampaignHandler`)

- [ ] **L3** Tests nuevos **(requiere servidor/Docker)**:
  - Test unitario de `DoctrineRunnerRepository`
  - Test unitario de `ImportRunnersFromCsvHandler`
  - Test funcional de `AdminRunnerController::import()`

- [ ] **L4** Frontend **(requiere node)**:
  - `vue-tsc` verde
  - Build de producción verde

- [x] **L5** Actualizar `docs/tracking/05_PROGRESO.md`

---

## Notas

### Arquitectura a respetar

- **Controllers** solo inyectan `MessageBusInterface` (`$commandBus` / `$queryBus`), nunca `EntityManager`, `StoragePort`, repositorios ni `ParseEmailCsv`
- **Handlers** inyectan interfaces del dominio (`RunnerRepositoryInterface`, `RaceEditionRepositoryInterface`, etc.), nunca `EntityManager` ni clases concretas
- **Commands/Queries** son `final class` con propiedades públicas (commands) o `final readonly class` (queries)
- **Handlers** son `#[AsMessageHandler] final class` con `__invoke(CommandType $cmd)`
- **Response DTOs** en `src/Application/<Context>/Response/` para devolver datos estructurados
- **Nuevos endpoints** = nuevo comando/query + handler. El controller solo valida entrada y despacha
- **Messenger** es síncrono (`sync://`) — los handlers se ejecutan inline

### Formato del CSV de runners

```
Dorsal;Nombre;Apellidos;Sexo;email;NIF_Pasaporte;Fecha Nacimiento;Club;Talla Camiseta;Local;Ano;Anos;CATEGORIA
```

`ParseEmailCsv` ya maneja este formato. Se reutiliza tal cual en el handler de import.

### Dependencias entre handlers (a corregir en esta refactorización)

Actualmente estos handlers violan la arquitectura inyectando `EntityManagerInterface`:
- `SearchRunnersQueryHandler` → P4 lo corrige con `RunnerRepositoryInterface`
- `PreviewEmailRecipientsHandler` → E2 lo corrige con `RunnerRepositoryInterface`
- `SendEmailCampaignHandler` → E4 lo corrige con `RunnerRepositoryInterface`

---

## Orden de implementación

| Orden | Bloque | Depende de |
|--------|--------|------------|
| 1 | Bloque 0 — RunnerRepositoryInterface + DoctrineRunnerRepository | Nada |
| 2 | Bloque 1 — Backend carga runners | Bloque 0 |
| 3 | Bloque 3 — Frontend carga runners | Bloque 1 |
| 4 | Bloque 2 — Backend emails (sin CSV) | Bloque 0 |
| 5 | Bloque 4 — Frontend emails (refactor) | Bloques 2, 3 |
| 6 | Bloque 5 — Limpieza y tests | Bloques 1-4 |
