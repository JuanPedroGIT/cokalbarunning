# Progreso - Cokalba Running

> **Última actualización:** 2026-05-31
> **Estado general:** ✅ COMPLETADO (PRD R2) + ✅ FASE 2 COMPLETADA (Post-PRD) + ✅ FASE 3 (Roles + Auditoría + Blog)

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
