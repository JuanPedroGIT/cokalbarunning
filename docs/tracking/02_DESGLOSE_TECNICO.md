# Desglose Técnico del Impacto

> **Propósito:** Explicar QUÉ cambia, DÓNDE y POR QUÉ, para que cualquier agente pueda implementar una tarea sin tener que re-explorar todo el codebase.

---

## Resumen de cambios por entidad

### `RaceEdition` (Domain + ORM)
**Qué cambia:** Se agrega `shirtUrl` (diseño de camiseta). También `posterUrl` debe migrar de guardar URL completa a guardar path relativo (igual que Photo).

**Archivos a tocar:**
- `Domain/Race/Entity/RaceEdition.php` - Agregar propiedad `shirtUrl` + getter/setter
- `Entity/RaceEdition.php` - Agregar campo ORM `shirt_url`
- `Infrastructure/Persistence/Doctrine/Mapper/RaceEditionMapper.php` - Mapear `shirtUrl`
- `Application/Race/Response/RaceEditionResponseDto.php` - Incluir `shirtUrl`. **Construir URLs públicas** usando StoragePort al serializar.
- `Application/Race/Create/CreateRaceEditionHandler.php` - Recibir y persistir `shirtUrl`
- `Application/Race/Update/UpdateRaceEditionHandler.php` - Recibir y persistir `shirtUrl`
- `Infrastructure/Http/Controller/Admin/AdminRaceController.php` - Subida de camiseta (igual que poster). **Cambio crítico:** pasar path relativo, no URL completa.
- `Infrastructure/Http/Controller/RaceController.php` - Incluir `shirtUrl` en respuesta
- **Migración Doctrine** para agregar columna `shirt_url` a `race_editions`
- **Migración de datos** para `posterUrl`: convertir URLs absolutas existentes a paths relativos

### `Sponsor` (Domain + ORM)
**Qué cambia:** Se agrega `raceEditionId` opcional para permitir patrocinadores por edición. También `logoUrl` debe migrar de URL completa a path relativo.

**Archivos a tocar:**
- `Domain/Club/Entity/Sponsor.php` - Agregar `raceEditionId` opcional
- `Entity/Sponsor.php` - Agregar `ManyToOne` nullable a `RaceEdition`
- `Infrastructure/Persistence/Doctrine/Mapper/SponsorMapper.php` - Mapear relación
- `Application/Club/Response/SponsorResponseDto.php` - Incluir edición. **Construir URLs públicas** usando StoragePort al serializar.
- `Application/Club/Create/CreateSponsorHandler.php` - Aceptar edición opcional
- `Application/Club/Update/UpdateSponsorHandler.php` - Aceptar edición opcional
- `Application/Club/Query/GetActiveSponsorsQueryHandler.php` - Filtrar por edición activa / generales
- `Infrastructure/Http/Controller/Admin/AdminSponsorController.php` - CRUD con edición. **Cambio crítico:** pasar path relativo, no URL completa.
- `Infrastructure/Http/Controller/SponsorController.php` - Devolver generales + de edición activa
- **Migración Doctrine** para agregar columna `edition_id` FK nullable a `sponsors`
- **Migración de datos** para `logoUrl`: convertir URLs absolutas existentes a paths relativos

### `Photo` (Domain + ORM) - CAMBIO DE SCHEMA SIGNIFICATIVO
**Qué cambia:** 
1. Renombrado de campos para alinearse con PRD R2.
   - `filename` → `originalPath`
   - `thumbnailFilename` → `thumbPath`
   - `caption` → `altText`
2. **Cambio de comportamiento:** Ahora se guarda el path relativo en BD, no la URL completa. La URL se construye en el ResponseDTO usando `StoragePort::url()`.

**Archivos a tocar:**
- `Domain/Media/Entity/Photo.php` - Renombrar propiedades, getters, setters
- `Entity/Photo.php` - Renombrar columnas ORM
- `Infrastructure/Persistence/Doctrine/Mapper/PhotoMapper.php` - Mapear nuevos nombres
- `Application/Media/Response/PhotoResponseDto.php` - Adaptar nombres. **IMPORTANTE:** El DTO debe construir URLs públicas usando `StoragePort::url($path)` al serializar, no devolver el path crudo.
- `Application/Media/Upload/UploadPhotoHandler.php` - Generar miniatura WebP automáticamente
- `Application/Media/Update/UpdatePhotoHandler.php` - Adaptar a nuevos campos
- `Application/Media/QueryHandler/GetAllPhotosQueryHandler.php` - Adaptar
- `Application/Media/QueryHandler/GetFeaturedPhotosQueryHandler.php` - Adaptar
- `Infrastructure/Http/Controller/PhotoController.php` - Adaptar respuesta
- `Infrastructure/Http/Controller/Admin/AdminPhotoController.php` - **Cambio crítico:** en lugar de pasar `$this->storage->url($path)` al command, pasar el path relativo. El controller ya no construye la URL antes de persistir.
- **Migración Doctrine** para renombrar columnas en `photos`
- **Migración de datos** para convertir URLs absolutas existentes en `filename` a paths relativos

### `RaceDocument` (Domain + ORM) - NUEVA ENTIDAD
**Qué es:** Documentos asociados a una edición o generales (PDFs de recorrido, clasificaciones, etc.)

**Archivos a CREAR:**
- `Domain/Race/Entity/RaceDocument.php`
- `Domain/Race/ValueObject/DocumentType.php` (enum)
- `Domain/Race/Repository/RaceDocumentRepositoryInterface.php`
- `Entity/RaceDocument.php` (ORM)
- `Infrastructure/Persistence/Doctrine/Mapper/RaceDocumentMapper.php`
- `Infrastructure/Persistence/Doctrine/Repository/DoctrineRaceDocumentRepository.php`
- `Application/Race/Create/CreateRaceDocumentCommand.php` + `Handler.php`
- `Application/Race/Update/UpdateRaceDocumentCommand.php` + `Handler.php`
- `Application/Race/Delete/DeleteRaceDocumentCommand.php` + `Handler.php`
- `Application/Race/Query/GetDocumentsByEditionQuery.php` + `Handler.php`
- `Application/Race/Query/GetGeneralDocumentsQuery.php` + `Handler.php`
- `Application/Race/Response/RaceDocumentResponseDto.php`
- `Infrastructure/Http/Controller/Admin/AdminRaceDocumentController.php`
- `Infrastructure/Http/Controller/RaceDocumentController.php` (público)
- **Migración Doctrine** para crear tabla `race_documents`

### `StoragePort` + Adaptadores
**Qué cambia:** El sistema actual usa `LocalStorageAdapter` guardando en disco local. El PRD requiere subir a Cloudflare R2 (S3-compatible).

**Decisiones arquitectónicas:**
- `StoragePort` ya está bien diseñado (interfaz agnóstica). No necesita cambiar.
- Se creará `R2StorageAdapter` implementando `StoragePort`.
- Se usará `aws/aws-sdk-php` (SDK oficial de AWS, compatible con R2 S3 API).
- La URL pública se construye con `R2_PUBLIC_URL`.
- En desarrollo local puede seguirse usando `LocalStorageAdapter` para no depender de R2.
- Se usará una variable de entorno `STORAGE_ADAPTER=local|r2` para decidir cuál inyectar.
- **IMPORTANTE:** El código actual guarda URLs completas en BD (`$this->storage->url($path)`). El PRD exige guardar solo paths relativos. Se necesita una **fase de migración de datos** para convertir URLs existentes a paths.

**Archivos a tocar/crear:**
- `Domain/Media/Port/StoragePort.php` - Revisar si necesita método adicional (ej: `exists()`)
- `Infrastructure/Storage/R2StorageAdapter.php` - NUEVO, implementa StoragePort con S3 SDK
- `config/services.yaml` - Registrar nuevo adapter con factory o condicional
- `.env.example` - Variables: `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_ACCESS_KEY_SECRET`, `R2_BUCKET_NAME`, `R2_PUBLIC_URL`, `STORAGE_ADAPTER`

### Procesamiento de imágenes (Miniaturas WebP)
**Qué es:** Al subir una foto a la galería, el backend debe generar automáticamente una miniatura WebP optimizada.

**Enfoque recomendado:**
- Servicio de dominio: `Domain/Media/Service/ImageProcessorInterface.php`
- Implementación: `Infrastructure/Service/GdImageProcessor.php` (usa extensión GD de PHP, nativa)
- El `UploadPhotoHandler` o `AdminPhotoController` usará este servicio.
- Flujo: Upload original → procesar miniatura WebP → subir ambos a StoragePort → persistir paths.

**Archivos a crear:**
- `Domain/Media/Service/ImageProcessorInterface.php`
- `Infrastructure/Service/GdImageProcessor.php`

### Frontend - Galería
**Qué es:** Reemplazar/crear el componente de galería con Masonry + Lightbox profesional.

**Librería elegida:** PhotoSwipe (es la más madura, ligera, buen soporte Vue, masonry built-in via CSS columns o complementos).

**Componentes a crear/modificar:**
- `frontend/src/components/gallery/GalleryGrid.vue` - NUEVO. Grid masonry usando miniaturas
- `frontend/src/components/gallery/GalleryLightbox.vue` - NUEVO. Visor PhotoSwipe
- `frontend/src/components/gallery/GallerySection.vue` - NUEVO. Composición Grid + Lightbox
- `frontend/src/views/GalleryView.vue` - Integrar nuevo componente
- `frontend/src/stores/photo.store.ts` - Actualizar interfaz `Photo` (altText, originalPath, thumbPath)
- Instalar `photoswipe` via npm

### Frontend - Documentos
**Qué es:** Mostrar documentos (PDFs) asociados a una edición o generales.

**Archivos a tocar/crear:**
- `frontend/src/stores/document.store.ts` - NUEVO store con tipo RaceDocument
- `frontend/src/components/race/RaceDocuments.vue` - Lista de documentos con links
- `frontend/src/views/RaceView.vue` - Incluir sección de documentos
- `frontend/src/views/admin/AdminEditionsView.vue` - CRUD de documentos por edición

---

## Dependencias entre tareas

```
Fase 1: Infraestructura R2
  ├── StoragePort (sin cambios, solo revisión)
  ├── R2StorageAdapter
  ├── ImageProcessorInterface + GdImageProcessor
  └── Variables de entorno

Fase 2: Schema Base (migraciones doctrine)
  ├── RaceEdition: +shirt_url
  ├── Sponsor: +edition_id
  ├── Photo: renombrar campos
  └── RaceDocument: nueva tabla

Fase 3: Domain + Application (Backend)
  ├── RaceEdition domain/entity actualizado
  ├── Sponsor domain/entity actualizado
  ├── Photo domain/entity actualizado
  ├── RaceDocument domain completo (entity, VO, repository interface)
  ├── Commands/Handlers/Queries/DTOs para RaceDocument
  └── Response DTOs actualizados para RaceEdition, Sponsor, Photo

Fase 4: Infrastructure (Backend)
  ├── Mappers actualizados
  ├── Repositorios Doctrine actualizados/nuevos
  └── Controllers Admin y Públicos

Fase 5: Tests Backend
  ├── Tests unitarios (VO, Mappers)
  ├── Tests integración (Repositories)
  └── Tests funcionales (Controllers API)

Fase 6: Frontend
  ├── Tipos actualizados/nuevos
  ├── Componente Galería (Masonry + Lightbox)
  ├── Componente Documentos
  └── Vistas Admin actualizadas

Fase 7: Tests Frontend
  └── Tests unitarios/componentes Vue

Fase 8: Integración y QA
  ├── Verificar flujo completo: subida → procesamiento → visualización
  └── Verificar URLs públicas de R2
```
