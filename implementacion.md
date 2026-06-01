# Implementacion - Proyecto Cokalba Running

## Resumen

Este documento recoge el estado actual de la implementacion del proyecto **Cokalba Running** (plataforma web para la Carrera Solidaria "Un Nuevo Impulso").

**Estado actual:** Backend migrado a arquitectura hexagonal completa. Todos los bounded contexts migrados. **Almacenamiento migrado a Cloudflare R2 con generación automática de miniaturas WebP.**

**Tests:** ~125 tests pasando, ~380 assertions.

---

## 1. Infraestructura y Entorno Docker

### Completado
- [x] Estructura de carpetas del proyecto (`backend/`, `frontend/`, `nginx/`, `docs/`)
- [x] `docker-compose.yml` base con servicios: `backend`, `frontend`
- [x] `docker-compose.override.yml` para desarrollo local con: `postgres`, `nginx`
- [x] `docker-compose.pre.yml` para preproduccion con postgres, nginx unificado y frontend estatico
- [x] `docker-compose.prod.yml` para produccion conectado a `shared-network`
- [x] Servicios `queue` y `redis` eliminados; Messenger configurado como `sync://`
- [x] `.env` y `.env.example` con variables de entorno documentadas
- [x] `.gitignore` configurado
- [x] Backend `Dockerfile` basado en `php:8.4-fpm-alpine`
- [x] Frontend `Dockerfile` basado en `node:22-alpine`
- [x] Frontend `Dockerfile.prod` multi-stage (node build + nginx static)
- [x] Nginx de desarrollo (`nginx/dev.conf`) configurado como proxy inverso y servidor estatico
- [x] Nginx de preproduccion (`nginx/pre.conf`) sirve frontend estatico y proxy a `/api`

### URLs de acceso en desarrollo
| Servicio | URL |
|----------|-----|
| API Backend | `http://localhost:8080/api/v1` |
| Frontend (Vite dev server) | `http://localhost:5173` |
| PostgreSQL | `localhost:5433` (host) / `postgres:5432` (interno) |

### URLs de acceso en preproduccion
| Servicio | URL |
|----------|-----|
| Aplicacion completa | `http://localhost:8080` |
| API Backend | `http://localhost:8080/api/v1` |
| Admin panel | `http://localhost:8080/admin` |
| PostgreSQL | `postgres:5432` (interno, no expuesto) |

---

## 2. Backend Symfony

### Stack tecnologico
- Symfony 7.2 + PHP 8.4-FPM Alpine
- PostgreSQL 16
- Symfony Messenger (`sync://`)
- Lexik JWT Authentication
- Nelmio CORS
- PHPUnit 13.1.11

### Entidades ORM (capa de persistencia)
| Entidad | Tabla | Descripcion |
|---------|-------|-------------|
| `RaceEdition` | `race_editions` | Ediciones anuales de la carrera |
| `Category` | `categories` | Categorias por edicion |
| `Runner` | `runners` | Corredores inscritos |
| `Result` | `results` | Clasificaciones y tiempos |
| `Photo` | `photos` | Fotos de galeria (paths relativos a R2) |
| `BlogPost` | `blog_posts` | Noticias del club |
| `Sponsor` | `sponsors` | Patrocinadores (con soporte por edicion) |
| `RaceDocument` | `race_documents` | Documentos PDF por edicion (nuevo) |
| `User` | `users` | Usuarios admin para JWT (unica entidad que permanece como Security Entity) |

### Arquitectura Hexagonal implementada

Todos los bounded contexts han sido migrados a arquitectura hexagonal:

```
Domain\Entity -> Domain\Repository\Interface
       |
       v
Infrastructure\Mapper (ORM <-> Domain)
       |
       v
Infrastructure\Repository (EntityManager + Mapper)
       |
       v
Application\Query + QueryHandler / Command + CommandHandler
       |
       v
Application\Response\Dto
       |
       v
Infrastructure\Http\Controller (QueryBus / CommandBus)
```

#### Contextos migrados
| Contexto | Estado | Tests |
|----------|--------|-------|
| Sponsor (Club) | Completo | Mapper + Repo + Funcional |
| Photo (Media) | Completo | Mapper + Repo + Funcional |
| RaceEdition (Race) | Completo | Mapper + Repo + Funcional |
| Runner + Result (Registration/Results) | Completo | Mapper + Repo + Funcional |
| Auth | Completo | Controller migrado; User permanece como Security Entity |
| BlogPost (Media) | Completo | Mapper + Repo + Funcional; write-side alineado |

#### Componentes hexagonales creados
- **Domain Entities:** `Sponsor`, `Photo`, `RaceEdition`, `Category`, `Runner`, `Result`, `BlogPost`
- **Value Objects:** `RaceEditionId`, `Distance`, `EditionYear`, `FinishTime`, `Position`, `BibNumber`, `DocumentType`
- **Repository Interfaces:** `SponsorRepositoryInterface`, `PhotoRepositoryInterface`, `RaceEditionRepositoryInterface`, `RunnerRepositoryInterface`, `ResultRepositoryInterface`, `BlogPostRepositoryInterface`, `RaceDocumentRepositoryInterface`
- **Mappers Doctrine:** `SponsorMapper`, `PhotoMapper`, `RaceEditionMapper`, `CategoryMapper`, `RunnerMapper`, `ResultMapper`, `BlogPostMapper`, `RaceDocumentMapper`
- **Repositorios Doctrine:** `DoctrineSponsorRepository`, `DoctrinePhotoRepository`, `DoctrineRaceEditionRepository`, `DoctrineRunnerRepository`, `DoctrineResultRepository`, `DoctrineBlogPostRepository`, `DoctrineRaceDocumentRepository`
- **Queries/Handlers:** `GetPublishedPosts`, `GetPostBySlug`, `GetAllPosts`, `GetAllPhotos`, `GetFeaturedPhotos`, `GetActiveRace`, `GetAllRaces`, `GetRaceByYear`, `GetResultsByYear`, `GetAllSponsors`, `GetAllRunners`, `GetDocumentsByEdition`, `GetGeneralDocuments`
- **Response DTOs:** `SponsorResponseDto`, `PhotoResponseDto`, `RaceEditionResponseDto`, `RunnerResponseDto`, `ResultResponseDto`, `BlogPostResponseDto`, `RaceDocumentResponseDto`
- **StoragePort:** Interfaz agnostica + `LocalStorageAdapter` + `R2StorageAdapter` (Cloudflare R2) + `StorageAdapterFactory`
- **ImageProcessor:** `ImageProcessorInterface` + `GdImageProcessor` (miniaturas WebP 400px, calidad 85%)

### Controllers API (migrados a `Infrastructure/Http/Controller/`)
- `RaceController`: `/api/v1/editions`, `/api/v1/editions/active`, `/api/v1/editions/{year}`
- `ResultController`: `/api/v1/editions/{year}/results`
- `PhotoController`: `/api/v1/photos/featured`
- `BlogController`: `/api/v1/posts`, `/api/v1/posts/{slug}`
- `SponsorController`: `/api/v1/sponsors`
- `AuthController`: `/api/v1/auth/login`

### Admin API (migrados a `Infrastructure/Http/Controller/Api/Admin/`)
- `AdminRaceController`: `POST/PUT/DELETE /api/v1/admin/editions`, `POST /api/v1/admin/editions/{id}/poster`
- `AdminSponsorController`: `GET/POST/PUT/DELETE /api/v1/admin/sponsors`
- `AdminBlogController`: `GET/POST/PUT/DELETE /api/v1/admin/posts`
- `AdminPhotoController`: `GET/POST/PUT/DELETE /api/v1/admin/photos`
- `AdminResultController`: `POST /api/v1/admin/editions/{id}/results/import` (CSV sincrono; usa EntityManager directamente por complejidad)
- `AdminRaceDocumentController`: `GET/POST/PUT/DELETE /api/v1/admin/documents` (CRUD documentos PDF)

### CQRS (Commands + Handlers)
- Race: `Create`, `Update`, `Delete`
- Sponsor: `Create`, `Update`, `Delete`
- Photo: `Upload`, `Update`, `Delete` (genera miniatura WebP automaticamente)
- BlogPost: `Create`, `Update`, `Delete` (todos usan domain entity + repository interface)
- Result: `ClearPositionsForEdition` (via `ResultRepositoryInterface`)
- RaceDocument: `Create`, `Update`, `Delete`

### Configuracion de dependencias (`services.yaml`)
- Alias de repository interfaces a implementaciones Doctrine (publicos para test container)
- `StoragePort` -> `LocalStorageAdapter`
- Exclusiones: `src/Entity/`, `src/Kernel.php`, `src/DependencyInjection/`

### Legacy eliminado
- [x] Carpeta `src/Controller/` eliminada (controllers migrados a `Infrastructure/Http/Controller/`)
- [x] `config/routes.yaml` simplificado para escanear solo `src/Infrastructure/Http/Controller/`
- [x] `AdminResultController` ya no inyecta legacy repositories directamente (`RunnerRepository`, `CategoryRepository`)
- [x] Handlers de Blog write-side ya no usan `App\Entity\BlogPost` ni `App\Repository\BlogPostRepository`

---

## 3. Frontend Vue 3

### Completado
- [x] Proyecto Vue 3 inicializado con TypeScript, Router, Pinia, Vitest
- [x] Tailwind CSS configurado con paleta de colores del proyecto
- [x] Fuentes de Google (Barlow + Barlow Condensed) integradas
- [x] Axios instalado y configurado en `api.service.ts`

### Vistas creadas
| Vista | Ruta | Estado |
|-------|------|--------|
| `HomeView` | `/` | Completa (Hero, Club, Categorias, Patrocinadores) |
| `RaceView` | `/carrera` | Conectada a API (edicion activa) |
| `EditionsView` | `/ediciones` | Conectada a API (lista dinamica) |
| `GalleryView` | `/galeria` | Conectada a API (fotos destacadas) |
| `BlogView` | `/blog` | Conectada a API + datos fallback |
| `BlogPostView` | `/blog/:slug` | Conectada a API (detalle por slug) |
| `AdminLoginView` | `/admin/login` | Formulario funcional |
| `AdminDashboardView` | `/admin` | Panel principal con menu |
| `AdminEditionsView` | `/admin/editions` | CRUD ediciones + subida de cartel |
| `AdminResultsImportView` | `/admin/results` | Importacion CSV de clasificaciones |
| `AdminPhotosView` | `/admin/photos` | Subida y gestion de galeria |
| `AdminPostsView` | `/admin/posts` | CRUD de entradas de blog |
| `AdminSponsorsView` | `/admin/sponsors` | CRUD de patrocinadores |

---

## 4. Testing

### Estado actual
| Suite | Tests | Assertions | Estado |
|-------|-------|------------|--------|
| Unitarios (Mappers) | 7 | ~40 | OK |
| Unitarios (Domain VO) | 21 | ~60 | OK |
| Integracion (Repositories) | ~20 | ~80 | OK |
| Funcionales (API publica) | ~15 | ~50 | OK |
| Funcionales (API admin) | ~20 | ~60 | OK |
| Auth | 2 | ~6 | OK |
| **Total** | **108** | **327** | **OK** |

### Tests por contexto
- **Sponsor:** `SponsorMapperTest`, `DoctrineSponsorRepositoryTest`, `SponsorControllerTest`, `AdminSponsorControllerTest`
- **Photo:** `PhotoMapperTest`, `DoctrinePhotoRepositoryTest`, `AdminPhotoControllerTest`
- **Race:** `RaceEditionMapperTest`, `DoctrineRaceEditionRepositoryTest`, `RaceControllerTest`, `AdminRaceControllerTest`
- **Runner/Result:** `RunnerMapperTest`, `ResultMapperTest`, `DoctrineRunnerRepositoryTest`, `DoctrineResultRepositoryTest`, `ResultControllerTest`, `AdminResultControllerTest`
- **Auth:** `AuthControllerTest`
- **Blog:** `BlogPostMapperTest`, `DoctrineBlogPostRepositoryTest`, `BlogControllerTest`, `AdminBlogControllerTest`

---

## 5. Problemas conocidos / Notas tecnicas

1. **Puertos del host:** Se usan puertos alternativos por conflictos en Windows:
   - PostgreSQL expuesto en `5433` (host) en lugar de `5432`
   - Nginx expuesto en `8080` (host) en lugar de `80`
2. **Permisos de cache Symfony:** En Docker para Windows, limpiar `var/cache/*` manualmente antes de comandos criticos.
3. **Messenger sincrono:** El transporte `async` se configuro como `sync://`. Las tareas se ejecutan inmediatamente dentro de la peticion HTTP.
4. **Excepcion pragmatica - User entity:** `App\Entity\User` permanece como entidad ORM directa porque implementa `UserInterface` de Symfony Security. No se migro a domain entity.
5. **Excepcion pragmatica - CSV Import:** `AdminResultController` usa `EntityManagerInterface` directamente para la importacion CSV y recalculo de posiciones por complejidad operacional.
6. **Slugs unicos en tests:** Los tests de integracion y funcionales usan `uniqid()` en slugs/titulos para evitar violaciones de `UNIQUE CONSTRAINT` al compartir base de datos de test.
7. **Rol `ROLE_EDITOR` para Blog:** El `access_control` de `security.yaml` requiere `ROLE_EDITOR` (no `ROLE_ADMIN`) para `/api/v1/admin/posts`.
8. **Rendimiento en Docker Desktop Windows:** El bind mount `./backend:/var/www/backend` usa el filesystem `9p` de Windows, que es muy lento para operaciones masivas de I/O. Symfony en modo `dev` hace resource tracking (verifica timestamps de ~160+ archivos en cada peticion), lo que causaba **~6 segundos por peticion**. Solucion aplicada: `SYMFONY_DISABLE_RESOURCE_TRACKING=1` en `docker-compose.override.yml`. Resultado: **~1.5 segundos por peticion** (mejora del ~75%). Trade-off: despues de modificar codigo PHP/config/rutas, limpiar cache manualmente con `docker compose exec backend rm -rf var/cache/*`.

---

## 6. Historial de fases

### Fase 0 - Infraestructura hexagonal
- [x] Estructura de directorios `Domain/`, `Application/`, `Infrastructure/`
- [x] Aliases de servicios en `services.yaml`
- [x] Scaffolding de controllers en `Infrastructure/Http/Controller/`
- [x] Baseline: 28 tests

### Fase 1 - Sponsor (Club)
- [x] `SponsorMapper`, `DoctrineSponsorRepository`, CQRS handlers, DTO, controller migration
- [x] 42 tests

### Fase 2 - Photo (Media)
- [x] `PhotoMapper`, `DoctrinePhotoRepository`, Upload/Update/Delete commands + Queries, DTO, controller migration
- [x] 56 tests

### Fase 3 - Race (RaceEdition + Category)
- [x] `RaceEditionMapper`, `CategoryMapper`, `DoctrineRaceEditionRepository`, Queries, `RaceEditionResponseDto`, controller migration
- [x] 75 tests

### Fase 4 - Runner/Results
- [x] `RunnerMapper`, `ResultMapper`, `DoctrineRunnerRepository`, `DoctrineResultRepository`, `GetResultsByYear`, `ResultResponseDto`, controller migration
- [x] 89 tests

### Fase 5 - Auth
- [x] `AuthController` migrado a `Infrastructure/Http/Controller/Auth/`
- [x] `AuthControllerTest` (valid/invalid JWT)
- [x] 91 tests

### Fase 6 - Blog (Completa)
- [x] Read-side: `BlogPostMapper`, `DoctrineBlogPostRepository`, `GetPublishedPosts`, `GetPostBySlug`, `GetAllPosts`, `BlogPostResponseDto`
- [x] Write-side: `CreateBlogPostHandler`, `UpdateBlogPostHandler` reescritos con domain entity + repository interface
- [x] Controllers migrados: `BlogController`, `AdminBlogController`
- [x] Tests: `BlogPostMapperTest`, `DoctrineBlogPostRepositoryTest`, `BlogControllerTest`, `AdminBlogControllerTest`
- [x] 108 tests, 327 assertions

### Fase 7 - Cleanup
- [x] Alineacion write-side Blog (handlers hexagonales)
- [x] `updatePublishedAt()` agregado a domain `BlogPost`
- [x] `setCreatedAt()` agregado a ORM `BlogPost`
- [x] `BlogPostMapper::toOrm()` maneja `createdAt`
- [x] `AdminResultController` limpiado de inyecciones directas a legacy repositories
- [x] Eliminada carpeta vacia `src/Controller/`
- [x] Simplificado `config/routes.yaml`
- [x] `StoragePort` soporta R2 via factory pattern
- [x] Comando `app:migrate-storage-urls-to-paths` para migrar URLs a paths relativos

### Fase 8 - Diagnostico y optimizacion de rendimiento
- [x] Identificada causa de lentitud: Symfony resource tracking en bind mount Windows (`9p`)
- [x] Aplicada variable `SYMFONY_DISABLE_RESOURCE_TRACKING=1` en `docker-compose.override.yml`
- [x] Reduccion de ~6s a ~1.5s por peticion en desarrollo
- [x] Documentado trade-off (limpieza manual de cache tras cambios de codigo)

---

## 7. Siguientes pasos

- [ ] Configurar cobertura de codigo con PHPUnit
- [ ] Documentar guia de despliegue en `docs/deployment.md`
- [ ] Migrar datos historicos de ediciones anteriores
- [ ] Subir carteles y fotos reales
- [ ] Crear posts de blog con contenido real

---

## 8. Credenciales

| Entorno | Usuario | Contraseña | Rol |
|---------|---------|------------|-----|
| Admin (JWT) | `admin@cokalba.es` | `admin123` | `ROLE_ADMIN` |

### Crear usuarios desde consola

```bash
# Crear admin
make create-admin email="nuevo@admin.es" password="secreto"

# Crear editor (solo blog)
make create-editor email="editor@blog.es" password="secreto"

# Actualizar contraseña de usuario existente
make update-admin email="admin@cokalba.es" password="nueva123"
```

Comando directo:
```bash
docker compose exec backend php bin/console app:user:create <email> -p <password> [-r ROLE_ADMIN|ROLE_EDITOR] [--update]
```

---

*Documento actualizado el 2026-05-26*
*Proyecto: Cokalba Running - Club de Atletismo Coca de Alba*
*Arquitectura: Hexagonal completa (6 bounded contexts migrados)*
*Tests: 108 pasando, 327 assertions*
