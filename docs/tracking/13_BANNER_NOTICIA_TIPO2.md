# Banner Informativo — Noticias Tipo 2

> **Fecha inicio:** 2026-06-12
> **Objetivo:** Añadir 5 tipos de noticia, donde el tipo 2 es un banner informativo global que se muestra en todas las páginas, justo debajo del header. El banner se muestra entre la fecha de publicación y la fecha de fin (`bannerEndAt`). Si no hay noticia tipo 2 publicada y dentro de rango, el banner no se renderiza. Las noticias tipo 2 no aparecen en el blog ni se publican en Instagram.

---

## Leyenda

| Icono | Significado |
|-------|-------------|
| ⬜ | Pendiente |
| 🔄 | En progreso |
| ✅ | Completado |
| ⏸️ | Bloqueado |

---

## Decisiones técnicas

- Se añade un campo `type` (INT, NOT NULL, default 1) a `blog_posts`.
- Se añade un campo `banner_end_at` (DATETIME nullable) para definir la fecha de fin del banner.
- Se definen 5 tipos mediante constantes en dominio (ej: `TYPE_NEWS=1`, `TYPE_BANNER=2`, `TYPE_RACE=3`, `TYPE_CLUB=4`, `TYPE_OTHER=5`).
- El banner se obtiene desde un endpoint público que devuelve la noticia tipo 2 cuyo rango incluya la fecha actual (`published_at <= now <= banner_end_at`), o `null` si no hay ninguna.
- El banner se renderiza en `App.vue` justo debajo de `<NavBar />` para que aparezca en todas las páginas.
- Las noticias tipo 2 se excluyen de:
  - Listado de blog (`/blog`).
  - Noticia destacada en Home.
  - Endpoint de publicación en Instagram.
- En el admin se añade un selector de tipo en el formulario de creación/edición de noticias.

---

## Backend

| # | Archivo | Cambio | Estado |
|---|---------|--------|--------|
| B1 | `backend/src/Domain/Media/Entity/BlogPost.php` | Añadir constantes de tipo + propiedad/métodos `type` | ✅ |
| B2 | `backend/src/Entity/BlogPost.php` (ORM) | Añadir columna `type` INT NOT NULL DEFAULT 1 | ✅ |
| B3 | `backend/migrations/Version20260612120001.php` | Migración `ALTER TABLE blog_posts ADD type INT NOT NULL DEFAULT 1` | ✅ |
| B3b | `backend/migrations/Version20260612130001.php` | Migración `ALTER TABLE blog_posts ADD banner_end_at` | ✅ |
| B4 | `backend/src/Infrastructure/Persistence/Doctrine/Mapper/BlogPostMapper.php` | Mapear `type` y `bannerEndAt` en ambos sentidos | ✅ |
| B5 | `backend/src/Application/Media/Response/BlogPostResponseDto.php` | Incluir `type` en serialización | ✅ |
| B6 | `backend/src/Application/Media/Create/CreateBlogPostCommand.php` | Campo `type` | ✅ |
| B7 | `backend/src/Application/Media/Create/CreateBlogPostHandler.php` | Pasar `type` al constructor | ✅ |
| B8 | `backend/src/Application/Media/Update/UpdateBlogPostCommand.php` | Campo `type` | ✅ |
| B9 | `backend/src/Application/Media/Update/UpdateBlogPostHandler.php` | Manejar `type` en update | ✅ |
| B10 | `backend/src/Domain/Media/Repository/BlogPostRepositoryInterface.php` | Método `findLatestPublishedByType(int $type): ?BlogPost` | ✅ |
| B11 | `backend/src/Infrastructure/Persistence/Doctrine/Repository/DoctrineBlogPostRepository.php` | Implementar `findLatestPublishedByType` con filtro de rango de fechas; excluir tipo 2 de `findPublished`, `findLatestPublished` y `findFeatured` | ✅ |
| B12 | `backend/src/Application/Media/QueryHandler/GetActiveBannerQueryHandler.php` | Handler `GetActiveBanner` → devuelve DTO o null | ✅ |
| B13 | `backend/src/Infrastructure/Http/Controller/Api/BlogController.php` | Endpoint `GET /api/v1/banner` → noticia tipo 2 activa | ✅ |
| B14 | `backend/src/Application/Media/QueryHandler/GetAllPostsQueryHandler.php` | Excluir tipo 2 del listado público de blog | ✅ |
| B15 | `backend/src/Application/Media/QueryHandler/GetLatestPostQueryHandler.php` | Excluir tipo 2 de la noticia destacada | ✅ |
| B16 | `backend/src/Infrastructure/Http/Controller/Api/Admin/AdminBlogController.php` | Recibir `type` en create/update | ✅ |
| B17 | `backend/src/Application/SocialPublishing/Publish/PublishToNetworkHandler.php` + `SocialPublishingException` | Rechazar publicación en redes si tipo = banner | ✅ |
| B18 | `backend/tests/Unit/Infrastructure/Persistence/Doctrine/Mapper/BlogPostMapperTest.php` | Tests unitarios del mapper para `type` | ✅ |

---

## Frontend

| # | Archivo | Cambio | Estado |
|---|---------|--------|--------|
| F1 | `frontend/src/components/layout/InfoBanner.vue` | Nuevo componente: muestra título + resumen completos, link a detalle, botón cerrar | ✅ |
| F2 | `frontend/src/App.vue` | Insertar `<InfoBanner />` debajo de `<NavBar />`; ocultar si no hay banner | ✅ |
| F3 | `frontend/src/views/admin/AdminPostsView.vue` | Selector de tipo en formulario crear/editar noticia | ✅ |
| F3b | `frontend/src/views/admin/AdminPostsView.vue` | Campo "Fecha fin del banner" visible solo para tipo 2 | ✅ |
| F4 | `frontend/src/views/admin/AdminPostsView.vue` | Mostrar tipo en tabla admin | ✅ |
| F5 | `frontend/src/views/admin/AdminPostsView.vue` | Deshabilitar botón "Publicar en Instagram" cuando tipo = 2 | ✅ |

---

## QA

| # | Verificación | Estado |
|---|--------------|--------|
| Q1 | `phpunit tests/Unit` verde (salvo fallo preexistente `ClubMemberMapperTest::testToOrmPreservesExistingUserIdWhenDomainHasNone`) | ✅ |
| Q2 | `vue-tsc --build --force` sin errores | ✅ |
| Q3 | Migración ejecutada en entorno de desarrollo | ✅ |
| Q4 | `doctrine:schema:validate` sin discrepancias sobre `blog_posts.type` | ✅ |
| Q5 | Endpoint `GET /api/v1/banner` registrado en router | ✅ |

---

## Notas

- Las noticias tipo 2 se excluyen del blog y de Home en el backend (`findPublished`, `findLatestPublished`, `findFeatured`), por lo que el frontend no necesita filtrarlas explícitamente.
- El banner se muestra en todas las páginas porque vive en `App.vue`. Se cierra por sesión de navegación (no se persiste en `localStorage`).
