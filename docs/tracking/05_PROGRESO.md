# Progreso - Cokalba Running

> **Última actualización:** 2026-06-29
> **Última revisión:** Auditoría completa SOLID — 14/21 controllers limpios, 7 pendientes (tracking: `19_SOLID_RESTANTES.md`)
> **Estado general:** ✅ COMPLETADO (PRD R2) + ✅ FASE 2 COMPLETADA (Post-PRD) + ✅ FASE 3 (Roles + Auditoría + Blog) + ✅ Banner informativo (noticias tipo 2) + ✅ Envío de dorsales por email + ✅ Búsqueda de dorsal en /ediciones + ✅ Nuevos tipos de correo (sorteo + últimas indicaciones) + ✅ Imagen del premio en sorteo + ✅ Config unificada emails_config + ✅ Foto en últimas indicaciones + ✅ SOLID F4 + ✅ Tests F6 + ✅ Ajuste SOLID (18_AJUSTE_SOLID.md) + ⬜ SOLID Restantes (19_SOLID_RESTANTES.md)

---

## Ajuste SOLID — Corrección de desviaciones (2026-06-29)

Tracking: `18_AJUSTE_SOLID.md`.

| Bloque | Descripción | Estado |
|--------|-------------|--------|
| B1 | AuditSubscriber + quitar EM de 4 controllers | ✅ |
| B2 | Refactorizar UploadPhotoHandler + AdminPhotoController | ✅ |
| B3 | Crear UploadRaceDocumentHandler + limpiar AdminRaceDocumentController | ✅ |
| B4 | Mover normalizePath a UpdateRaceEditionHandler | ✅ |
| B5 | Tests + actualizar docs | ✅ (164 tests, 540 assertions) |

---

## SOLID Restantes — 7 controllers con violaciones (2026-06-29)

Tracking: `19_SOLID_RESTANTES.md`. Pendiente de ejecutar.

| # | Controller | Severidad | Estado |
|---|-----------|-----------|--------|
| 1 | AdminResultController | CRÍTICO | ✅ |
| 2 | AdminUserController | CRÍTICO | ✅ |
| 3 | RunnerController | CRÍTICO | ✅ |
| 4 | AdminBlogController | CRÍTICO | ✅ |
| 5 | AdminEmailController | CRÍTICO | ✅ |
| 6 | MeController | MODERADO | ✅ |
| 7 | RaceDocumentController | MENOR | ✅ |

---

## Imagen del premio en sorteo (2026-06-25)

| # | Cambio | Estado |
|---|--------|--------|
| IP1 | Backend: endpoint `POST /admin/emails/raffle/prize-image` sube imagen a R2 y guarda path en `RaffleConfig.prizeImageUrl` | ✅ |
| IP2 | Backend: `SendPendingEmailsCommand` incluye `prizeImageUrl` en metadata del sorteo | ✅ |
| IP3 | Backend: plantilla `raffle.html.twig` muestra la imagen del premio si existe | ✅ |
| IP4 | Frontend: campo para subir/preview/eliminar imagen del premio en `AdminEmailsView.vue` | ✅ |
| IP5 | Backend: envio de correos masivos via `BrevoMailer` (no `MailerInterface`/`MAILER_DSN`) | ✅ |
| IP6 | Backend/Frontend: mostrar `club` en buscador de runners de `/ediciones` | ✅ |
| IP7 | Backend/Frontend: guardar y mostrar `gender` y `category` en tabla `runners` y buscador | ✅ |
| IP8 | Backend: no pisar runners existentes al subir CSV con mismo dorsal en la misma edicion | ✅ |
| IP9 | Tests PHPUnit verdes (146 tests) y build frontend verde | ✅ |

---

## Generalización de config de emails + foto en últimas indicaciones (2026-06-25)

Tracking: `17_NUEVOS_TIPOS_CORREO.md` (secciones nuevas al final).

| # | Cambio | Estado |
|---|--------|--------|
| GC1 | Migración: renombrar `raffle_configs` → `emails_config`, añadir columna `type` y unique index `(race_edition_id, type)` | ✅ |
| GC2 | Entidad `RaffleConfig` → `EmailConfig` con campo `type` | ✅ |
| GC3 | Repositorio `RaffleConfigRepository` → `EmailConfigRepository` con `findByRaceEditionIdAndType()` | ✅ |
| GC4 | Endpoints de config generalizados: `/emails/{type}/config` (GET/POST) y `/{type}/config/{id}` (PUT) | ✅ |
| GC5 | `prize-image` acepta `raffle|last_instructions`; `PathGenerator::emailImagePath($type, $ext)` | ✅ |
| GC6 | `SendPendingEmailsCommand::resolveMetadata` carga config para ambos tipos; `prizeImageUrl` común | ✅ |
| GC7 | Frontend: config dinámica por pestaña (sorteo: premio/fecha/foto; indicaciones: solo título/descripción/foto) | ✅ |
| GC8 | Frontend: `PreviewItem` incluye `gender` y `birthDate`; envío no pisa datos de runners | ✅ |
| GC9 | Fix: caché de rutas Symfony requirió borrado manual de `var/cache/dev` para compilar `last_instructions` | ✅ |
| GC10 | `vue-tsc` verde; migración aplicada; schema validado | ✅ |

---

## SOLID F4: Dividir GenerateThumbnailsCommand (2026-06-26)

| # | Cambio | Estado |
|---|--------|--------|
| F4.1 | Crear `R2FileLister` (lista objetos de R2 por prefijo) | ✅ |
| F4.2 | `GenerateThumbnailsCommand` usa `R2FileLister` en vez de `S3Client` directo | ✅ |
| F4.3 | `GenerateThumbnailsCommand` usa `ImageProcessorInterface::createThumbnail()` en vez de GD crudo | ✅ |
| F4.4 | `GenerateThumbnailsCommand` usa `PhotoRepositoryInterface::save()` en vez de SQL crudo | ✅ |
| F4.5 | `GenerateThumbnailsCommand` usa `RaceEditionRepositoryInterface::findByYear()` en vez de SQL crudo | ✅ |
| F4.6 | `services.yaml`: registro de `R2FileLister` + actualización de argumentos del comando | ✅ |

## SOLID F6: Tests pendientes (2026-06-26)

| # | Cambio | Estado |
|---|--------|--------|
| F6.1 | Test unitario de `PathGenerator` (15 tests, 28 assertions) | ✅ |
| F6.2 | Test unitario de `R2FileLister` (3 tests, 10 assertions) | ✅ |
| F6.3 | Fix: caché de test con referencia a `RaffleConfigRepository` eliminada → limpiar `var/cache/test` | ✅ |
| F6.4 | Fix: aplicar migración en BD de test (`emails_config`) | ✅ |
| F6.5 | Suite completa: 164 tests, 540 assertions verdes | ✅ |

---

## Nuevos tipos de correo — Sorteo y Últimas Indicaciones (2026-06-24)

Tracking: `17_NUEVOS_TIPOS_CORREO.md`.

| # | Cambio | Estado |
|---|--------|--------|
| NC1 | Tracking y decisiones técnicas | ✅ |
| NC1b | Revisar CSV de inscritos (`Inscritos_Coca_de_alba_25.csv`) y PDF de últimas instrucciones | ✅ |
| NC1c | Unificar sorteo + indicaciones sobre el mismo CSV de inscritos; dorsal como número de participación | ✅ |
| NC2 | Campo `type` en `EmailSendLog` + migración | ✅ |
| NC2b | Campo `metadata` JSON en `EmailSendLog` + migración | ✅ |
| NC3 | Renombrar `bibNumber` → `reference` (nullable) | ✅ |
| NC4 | Generalizar parser CSV y DTO de destinatarios | ✅ |
| NC5 | `EmailTemplateResolver` + plantillas Twig por tipo | ✅ |
| NC6 | Comando CLI `app:emails:send --type` | ✅ |
| NC7 | Endpoints `/admin/emails/{type}/...` | ✅ |
| NC8 | Vista admin `AdminEmailsView.vue` con carga única arriba y pestañas (Sorteo + Últimas Indicaciones) debajo | ✅ |
| NC9 | Crear/actualizar runners automáticamente al cargar el CSV de inscritos | ✅ |
| NC10 | Al enviar sorteo se crean/actualizan runners y se encolan automáticamente las últimas indicaciones | ✅ |
| NC11 | Persistencia de configuración del sorteo en `raffle_configs` con endpoints CRUD | ✅ |
| NC12 | Comando `app:emails:send` usa `MAILER_DSN` (Symfony Mailer); `BrevoMailer` conservado | ✅ |
| NC13 | Solución definitiva de permisos de caché (`USER www-data` en Dockerfile) | ✅ |
| NC14 | Tests PHPUnit (142 tests) y `vue-tsc`/build verdes | ✅ |
| NC15 | Asunto del sorteo parametrizable con placeholders `{title}`, `{drawDate}`, `{prize}`, `{description}` | ✅ |
| NC16 | Reorganizar plantilla `raffle.html.twig`: dorsal como número de participación y datos del sorteo visibles | ✅ |

---

## Banner Informativo — Noticias Tipo 2 (2026-06-12)

Tracking: `13_BANNER_NOTICIA_TIPO2.md`.

| # | Cambio | Estado |
|---|--------|--------|
| BN1 | Campo `type` en `BlogPost` + 5 tipos + migración | ✅ |
| BN2 | Endpoint público `GET /api/v1/banner` con rango de fechas | ✅ |
| BN3 | Excluir tipo 2 de blog, destacada Home e Instagram | ✅ |
| BN4 | Componente `InfoBanner.vue` en `App.vue` | ✅ |
| BN5 | Selector de tipo + fecha fin del banner + bloqueo Instagram en admin | ✅ |
| BN6 | `vue-tsc` verde; migraciones aplicadas | ✅ |

---

## Envío de dorsales por email (2026-06-13)

Tracking: `15_BIB_EMAILS.md`.

| # | Cambio | Estado |
|---|--------|--------|
| BE1 | Backend: entidad `EmailSendLog` + migración | ✅ |
| BE2 | Backend: domain, repository, CQRS, CSV parser | ✅ |
| BE3 | Backend: plantilla Twig + comando CLI + controller | ✅ |
| BE4 | Backend: tests PHPUnit verdes (134 tests) | ✅ |
| BE5 | Frontend: vista `AdminBibEmailsView.vue` | ✅ |
| BE6 | Frontend: ruta `/admin/bib-emails` + enlace dashboard | ✅ |
| BE7 | Frontend: `vue-tsc` y build verdes | ✅ |
| BE8 | Backend: endpoint `/bib-emails/sent-counts` + contador global | ✅ |
| BE9 | Backend: registrar `sent_by` desde el comando CLI | ✅ |

---

## Búsqueda de dorsal en /ediciones (2026-06-15)

Tracking: `16_EDICIONES_BIB_SEARCH.md`.

| # | Cambio | Estado |
|---|--------|--------|
| ED1 | Backend: endpoint público `GET /api/v1/runners` | ✅ |
| ED2 | Frontend: edición actual destacada arriba | ✅ |
| ED3 | Frontend: layout cartel derecha / opciones izquierda | ✅ |
| ED4 | Frontend: botones Resultados, Galería y Buscar dorsal | ✅ |
| ED5 | Frontend: búsqueda por nombre con lista de resultados | ✅ |
| ED6 | Tests PHPUnit verdes (139 tests) y build frontend verde | ✅ |
| ED7 | Backend: campo `showBibSearch` en `RaceEdition` + migración | ✅ |
| ED8 | Backend: activar `showBibSearch` al cargar CSV de dorsales | ✅ |
| ED9 | Backend: crear/actualizar edición con `showBibSearch` | ✅ |
| ED10 | Frontend: panel admin con checkbox `Mostrar búsqueda de dorsales` | ✅ |
| ED11 | Frontend: mostrar buscador solo si `activeEdition.showBibSearch` | ✅ |
| ED12 | Tests PHPUnit verdes (142 tests) y build frontend verde | ✅ |

---

## Edición foto miembros del club (2026-06-12)

Tracking: `14_CLUB_MEMBER_PHOTO.md`.

| # | Cambio | Estado |
|---|--------|--------|
| CM1 | Backend: permitir borrar foto con `photoUrl: ''` | ✅ |
| CM2 | Frontend: foto solo editable en edición, igual que sponsors | ✅ |
| CM3 | `vue-tsc` verde | ✅ |

---

## Leyenda

| Icono | Significado |
|-------|-------------|
| ⬜ | Pendiente |
| 🔄 | En progreso |
| ✅ | Completado |
| ⏸️ | Bloqueado |
| ❌ | Cancelado |

---

## PRD R2 Storage & Gallery (Fases 1-8)

**Completado 2026-05-28.** Ver detalle original abajo.

113 tests, 353 assertions, todo verde.

---

## Corrección de Paths (2026-05-29)

Tracking: `07_FIX_PATHS_PRD.md`. Alineado con estructura PRD (`carrera/{YYYY}/imgs/` → `race/{YYYY}/images/` en inglés).

---

## Fase Post-PRD (2026-05-29 a 2026-05-31)

### Almacenamiento y Configuración

| # | Cambio | Estado |
|---|--------|--------|
| S1 | Eliminar LocalStorageAdapter — solo R2 | ✅ |
| S2 | Eliminar StorageAdapterFactory | ✅ |
| S3 | R2_PUBLIC_URL → media.cokalba-running.com | ✅ |
| S4 | Eliminar STORAGE_DRIVER / STORAGE_LOCAL_PATH | ✅ |
| S5 | Unificar .env: solo raíz, eliminar backend/.env* y frontend/.env* | ✅ |
| S6 | Variables de test en phpunit.dist.xml | ✅ |
| S7 | Montar .env raíz en contenedor backend | ✅ |
| S8 | CORS: permitir app.localhost | ✅ |
| S9 | DEFAULT_URI en docker-compose | ✅ |

### Paths en inglés

| # | Cambio | Estado |
|---|--------|--------|
| P1 | carrera/ → race/ | ✅ |
| P2 | patrocinadores/ → sponsors/ | ✅ |
| P3 | imgs/ → images/ | ✅ |
| P4 | miniaturas/ → thumbnails/ | ✅ |
| P5 | resultados/ → results/ | ✅ |
| P6 | DocumentType valores inglés (route, profile, results, other) | ✅ |

### Patrocinadores

| # | Cambio | Estado |
|---|--------|--------|
| SP1 | Eliminar edition_id de tabla sponsors | ✅ |
| SP2 | Eliminar raceEditionId de Domain/ORM/Mapper/DTO | ✅ |
| SP3 | Añadir campo message (TEXT, HTML) | ✅ |
| SP4 | Simplificar GetActiveSponsors → sin filtro por edición | ✅ |
| SP5 | Quitar filtro edición del admin UI | ✅ |
| SP6 | Textarea HTML para mensaje en admin | ✅ |
| SP7 | Resaltar fila editada en tabla admin | ✅ |
| SP8 | Logo upload en formulario crear/editar | ✅ |
| SP9 | HomeView: sponsor principal con mensaje + grid resto con grayscale hover | ✅ |
| SP10 | Fix PUT sponsors (eliminar editionId del controller) | ✅ |
| SP11 | Fix POST sponsors/logo (raceEditionId → message) | ✅ |

### Admin Ediciones

| # | Cambio | Estado |
|---|--------|--------|
| E1 | Botones visibles: Cartel, Camiseta, Resultados | ✅ |
| E2 | Subida directa con file picker (sin panel) | ✅ |
| E3 | Indicadores de archivos subidos con enlaces | ✅ |
| E4 | Resaltar fila editada | ✅ |
| E5 | Resultados: auto-reemplaza si ya existe | ✅ |

### Admin Fotos

| # | Cambio | Estado |
|---|--------|--------|
| F1 | Filtro por edición (carga por defecto activa) | ✅ |
| F2 | Consulta servidor al cambiar filtro (backend ?editionId=) | ✅ |
| F3 | Endpoint público GET /api/v1/photos con filtro | ✅ |

### Galería Pública

| # | Cambio | Estado |
|---|--------|--------|
| G1 | Filtro por edición (default activa) | ✅ |
| G2 | Contador de fotos | ✅ |
| G3 | Pre-filtro vía query param ?edicion=ID | ✅ |
| G4 | Enlace "Galería" en /ediciones por año | ✅ |

### Ediciones Públicas

| # | Cambio | Estado |
|---|--------|--------|
| ED1 | Mostrar cartel en tarjeta (si existe en R2) | ✅ |
| ED2 | Zoom al hacer click (useImageZoom) | ✅ |
| ED3 | Botón "Ver Resultados" → descarga PDF bajo demanda | ✅ |
| ED4 | Insertar ediciones 2016-2025 en BD | ✅ |
| ED5 | Insertar race_documents de resultados 2016-2025 | ✅ |
| ED6 | Fix RaceDocumentController (year → edition lookup) | ✅ |

### Home

| # | Cambio | Estado |
|---|--------|--------|
| H1 | Cartel real desde API (no placeholder) | ✅ |
| H2 | Zoom al hacer click en cartel | ✅ |
| H3 | Responsive: centrado en móvil, texto arriba | ✅ |
| H4 | Quitar sección categorías | ✅ |
| H5 | Sección patrocinadores rediseñada (principal + grid) | ✅ |
| H6 | Sección miembros del club (4 col, responsive) | ✅ |

### Comando Miniaturas

| # | Cambio | Estado |
|---|--------|--------|
| T1 | Crear app:generate-thumbnails {year} | ✅ |
| T2 | Listar objetos con S3 SDK (sin adivinar nombres) | ✅ |
| T3 | WebP 400px ancho, calidad 85% | ✅ |
| T4 | memory_limit 512M + gc_collect_cycles | ✅ |
| T5 | Fotos 2023 procesadas (55) | ✅ |
| T6 | Fotos 2024 procesadas (220) | ✅ |
| T7 | Fotos 2025 procesadas (191) | ✅ |

### Miembros del Club

| # | Cambio | Estado |
|---|--------|--------|
| M1 | Migración: tabla club_members | ✅ |
| M2 | Domain/ORM Entity + Mapper + Repository | ✅ |
| M3 | CQRS: Create/Update/Delete commands + handlers | ✅ |
| M4 | Query GetClubMembers + Handler + DTO | ✅ |
| M5 | Admin controller CRUD + upload photo | ✅ |
| M6 | Public controller GET /club-members | ✅ |
| M7 | Admin UI: CRUD con subida de foto | ✅ |
| M8 | Sección en HomeView (grid 4 cols, responsive) | ✅ |
| M9 | Enlace en dashboard admin | ✅ |

### Varios

| # | Cambio | Estado |
|---|--------|--------|
| V1 | Composable useImageZoom (overlay fullscreen) | ✅ |
| V2 | Página 404 (catch-all route + NotFoundView) | ✅ |
| V3 | Makefile: rebuild (sin -v) + reset (con -v) | ✅ |
| V4 | Makefile: storage-migrate, limpiar npm-dev | ✅ |
| V5 | Quitar fix-permissions public/uploads | ✅ |
| V6 | S3ClientFactory + registro en services.yaml | ✅ |

---

## Tests

| Fecha | Resultado |
|-------|-----------|
| 2026-05-28 | 113 tests, 353 assertions ✅ |
| 2026-05-30 | 113 tests, 353 assertions ✅ (tras limpiar edition_id de sponsors) |
| 2026-06-13 | 134 tests, 441 assertions ✅ (Bib Email Sender + fix club-member test) |
| 2026-06-15 | 142 tests, 470 assertions ✅ (Bib Search por edición + showBibSearch) |
| 2026-06-26 | 164 tests, 540 assertions ✅ (SOLID F4 + tests F6) |

---

## Fase 3: Roles, Auditoría y Blog (2026-05-31)

### Roles y permisos

| # | Cambio | Estado |
|---|--------|--------|
| R1 | ROLE_ADMIN hereda ROLE_EDITOR | ✅ |
| R2 | `/api/v1/admin/users` solo ROLE_ADMIN | ✅ |
| R3 | `/api/v1/admin/posts` + `/admin/photos` → ROLE_EDITOR | ✅ |
| R4 | Resto `/api/v1/admin` → ROLE_ADMIN | ✅ |
| R5 | AdminUserController CRUD (GET/POST/PUT/DELETE) | ✅ |
| R6 | AdminUsersView frontend (tabla + formulario) | ✅ |
| R7 | Ruta `/admin/users` protegida requiresAdmin | ✅ |
| R8 | Dashboard: "Usuarios" solo visible para admin | ✅ |
| R9 | NavBar: botón Admin + Salir cuando autenticado | ✅ |
| R10 | Editor solo ve Galería y Blog en dashboard | ✅ |
| R11 | Router guard requiresAdmin | ✅ |

### Auditoría (created_by / updated_by)

| # | Cambio | Estado |
|---|--------|--------|
| A1 | Migración: +created_by, +updated_by en 5 tablas | ✅ |
| A2 | AdminRaceController: createdBy/updatedBy | ✅ |
| A3 | AdminSponsorController: createdBy/updatedBy | ✅ |
| A4 | AdminBlogController: createdBy/updatedBy | ✅ |
| A5 | AdminClubMemberController: createdBy/updatedBy | ✅ |

### Blog — imágenes

| # | Cambio | Estado |
|---|--------|--------|
| B1 | Endpoint `POST /admin/posts/{id}/cover` sube a R2 | ✅ |
| B2 | PathGenerator::blogCoverPath() | ✅ |
| B3 | BlogPostResponseDto construye URLs completas | ✅ |
| B4 | AdminPostsView: drag & drop para imagen | ✅ |
| B5 | BlogView + BlogPostView muestran coverImage | ✅ |
| B6 | SponsorResponseDto con buildUrl guard | ✅ |

### Varios

| # | Cambio | Estado |
|---|--------|--------|
| V7 | BG global en index.html (bg-fixed + bg-lines) | ✅ |
| V8 | z-10 en todas las vistas públicas y admin | ✅ |
| V9 | NavBar con estado de autenticación | ✅ |
| V10 | LoginView con z-10 | ✅ |

### SOLID — Fases completadas

| Fase | Descripción | Estado |
|------|-------------|--------|
| F1 | PathGenerator centralizado | ✅ |
| F2 | Upload handlers CQRS (RaceEdition, Sponsor, ClubMember) | ✅ |
| F3 | DeleteClubMemberHandler borra archivo R2 | ✅ |
| F5 | PhotoRepository con findByEditionId | ✅ |

---

## Datos en R2

| Carpeta | Archivos |
|---------|----------|
| sponsors/ | 12 logos |
| docs/ | 4 documentos generales |
| race/2016/results/ | CLASIFICACION_2016.pdf |
| race/2017/results/ | CLASIFICACION_2017.pdf |
| race/2018/results/ | CLASIFICACION_2018.pdf |
| race/2019/results/ | CLASIFICACION_2019.pdf |
| race/2022/results/ | CLASIFICACION_2022.pdf |
| race/2023/images/ + thumbnails/ | 55 originales + 55 miniaturas |
| race/2023/results/ | CLASIFICACION_2023.pdf |
| race/2024/images/ + thumbnails/ | 220 originales + 220 miniaturas |
| race/2024/results/ | CLASIFICACION_2024.pdf |
| race/2025/images/ + thumbnails/ | 191 originales + 191 miniaturas |
| race/2025/results/ | CLASIFICACION_2025.pdf |
| race/2026/docs/ | cartel-2026.jpg, camiseta-2026.jpeg |

**Total miniaturas generadas:** 466
**Total fotos en BD:** 466

---

## PRD R2 Storage — Resumen de fases originales

| Fase | Descripción | Estado |
|------|-------------|--------|
| Fase 1 | Infraestructura R2 | ✅ |
| Fase 2 | Migraciones Doctrine | ✅ |
| Fase 3 | Domain Layer | ✅ |
| Fase 4 | Application Layer | ✅ |
| Fase 5 | Infrastructure Layer | ✅ |
| Fase 6 | Tests Backend | ✅ |
| Fase 7 | Limpieza | ✅ |
| Fase 8 | Frontend | ✅ |
