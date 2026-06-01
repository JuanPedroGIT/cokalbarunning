# Tareas Backend - PRD R2 Storage

> **Instrucciones:** Marcar con `[x]` solo cuando la tarea está implementada Y testeada. Una tarea no está completa si los tests no pasan.

---

## Fase 1: Infraestructura de Almacenamiento

### 1.1 StoragePort y Adaptadores
- [ ] **1.1.0** Instalar `aws/aws-sdk-php` via Composer
- [ ] **1.1.1** Revisar `StoragePort.php` y agregar método `exists(string $path): bool` si es necesario
- [ ] **1.1.2** Crear `R2StorageAdapter.php` implementando `StoragePort` con SDK AWS S3
- [ ] **1.1.3** Configurar `services.yaml` para inyectar `R2StorageAdapter` o `LocalStorageAdapter` según env var `STORAGE_ADAPTER`
- [ ] **1.1.4** Actualizar `.env.example` con variables R2: `R2_ACCOUNT_ID`, `R2_ACCESS_KEY_ID`, `R2_ACCESS_KEY_SECRET`, `R2_BUCKET_NAME=cokalba-running`, `R2_PUBLIC_URL`, `R2_ENDPOINT_URL`, `STORAGE_ADAPTER`
- [ ] **1.1.5** Actualizar `docker-compose.override.yml` y `docker-compose.pre.yml` con las nuevas variables de entorno

### 1.2 Procesamiento de Imágenes
- [ ] **1.2.1** Crear `Domain/Media/Service/ImageProcessorInterface.php` con método `createThumbnail(UploadedFile $file, int $width, int $height): string` (retorna path del archivo temporal generado)
- [ ] **1.2.2** Crear `Infrastructure/Service/GdImageProcessor.php` implementando la interfaz
  - Redimensionar manteniendo aspect ratio
  - Convertir a WebP con calidad configurable (default 85)
  - Manejar errores si GD no está disponible
- [ ] **1.2.3** Asegurar que la extensión GD está disponible en el Dockerfile de PHP

### 1.3 Migración de datos existentes (URL completa → path relativo)
**Contexto:** El código actual guarda URLs completas (`$this->storage->url($path)`) en `photos.filename`, `race_editions.poster_url` y `sponsors.logo_url`. El PRD exige paths relativos.
- [ ] **1.3.1** Crear comando de consola `app:migrate-storage-urls-to-paths` que:
  - Para cada `photo`, extraiga el path relativo de `filename` (quitando el prefijo de `STORAGE_PUBLIC_URL`)
  - Para cada `race_edition`, extraiga el path relativo de `poster_url`
  - Para cada `sponsor`, extraiga el path relativo de `logo_url`
  - Actualice los registros en BD
  - Sea idempotente (si ya es un path relativo, no hacer nada)
- [ ] **1.3.2** Documentar en `06_DECISIONES.md` el trade-off de esta migración

---

## Fase 2: Migraciones de Base de Datos

### 2.1 Modificar `race_editions`
- [ ] **2.1.1** Crear migración para agregar `shirt_url VARCHAR(255) NULL` a `race_editions`

### 2.2 Modificar `sponsors`
- [ ] **2.2.1** Crear migración para agregar `edition_id VARCHAR(36) NULL` + FK a `race_editions(id)` en `sponsors`

### 2.3 Modificar `photos`
- [ ] **2.3.1** Crear migración para renombrar columnas en `photos`:
  - `filename` → `original_path`
  - `thumbnail_filename` → `thumb_path`
  - `caption` → `alt_text`

### 2.4 Crear `race_documents`
- [ ] **2.4.1** Crear migración para nueva tabla `race_documents`:
  - `id` VARCHAR(36) PK
  - `edition_id` VARCHAR(36) NULL FK → `race_editions(id)`
  - `name` VARCHAR(255)
  - `type` VARCHAR(20) (enum string)
  - `file_path` VARCHAR(500)
  - `created_at` DATETIME_IMMUTABLE

---

## Fase 3: Domain Layer

### 3.1 RaceEdition
- [ ] **3.1.1** Agregar `shirtUrl` a `Domain/Race/Entity/RaceEdition.php` (propiedad, getter, setter)
- [ ] **3.1.2** Actualizar constructor de `RaceEdition` para aceptar `?string $shirtUrl`

### 3.2 Sponsor
- [ ] **3.2.1** Agregar `raceEditionId` opcional a `Domain/Club/Entity/Sponsor.php`
- [ ] **3.2.2** Actualizar constructor y método `update()` de `Sponsor`

### 3.3 Photo
- [ ] **3.3.1** Renombrar propiedades en `Domain/Media/Entity/Photo.php`:
  - `filename` → `originalPath`
  - `thumbnailFilename` → `thumbPath`
  - `caption` → `altText`
- [ ] **3.3.2** Actualizar getters, setters y constructor

### 3.4 RaceDocument (NUEVO)
- [ ] **3.4.1** Crear `Domain/Race/ValueObject/DocumentType.php` (enum: recorrido, perfil, resultados, general, otros)
- [ ] **3.4.2** Crear `Domain/Race/Entity/RaceDocument.php` con propiedades: id, editionId, name, type, filePath, createdAt
- [ ] **3.4.3** Crear `Domain/Race/Repository/RaceDocumentRepositoryInterface.php`

---

## Fase 4: Application Layer

### 4.1 Response DTOs actualizados
- [ ] **4.1.1** Agregar `shirtUrl` a `Application/Race/Response/RaceEditionResponseDto.php`
- [ ] **4.1.2** Agregar `editionId` a `Application/Club/Response/SponsorResponseDto.php`
- [ ] **4.1.3** Renombrar campos en `Application/Media/Response/PhotoResponseDto.php` (originalPath, thumbPath, altText)
- [ ] **4.1.4** Crear `Application/Race/Response/RaceDocumentResponseDto.php`

### 4.2 Commands y Handlers actualizados
- [ ] **4.2.1** Actualizar `CreateRaceEditionCommand` y `Handler` para incluir `shirtUrl`
- [ ] **4.2.2** Actualizar `UpdateRaceEditionCommand` y `Handler` para incluir `shirtUrl`
- [ ] **4.2.3** Actualizar `CreateSponsorCommand` y `Handler` para incluir `editionId` opcional
- [ ] **4.2.4** Actualizar `UpdateSponsorCommand` y `Handler` para incluir `editionId` opcional
- [ ] **4.2.5** Actualizar `UploadPhotoCommand` y `Handler` para:
  - Generar miniatura WebP automáticamente usando `ImageProcessorInterface`
  - Subir original y miniatura a `StoragePort`
  - Guardar paths en nuevos campos (`originalPath`, `thumbPath`)
  - Usar `altText` en lugar de `caption`
- [ ] **4.2.6** Actualizar `UpdatePhotoCommand` y `Handler` para nuevos campos

### 4.3 Commands y Handlers nuevos (RaceDocument)
- [ ] **4.3.1** Crear `CreateRaceDocumentCommand` + `Handler` (sube PDF a R2, persiste entidad)
- [ ] **4.3.2** Crear `UpdateRaceDocumentCommand` + `Handler`
- [ ] **4.3.3** Crear `DeleteRaceDocumentCommand` + `Handler` (elimina de R2 y de BD)

### 4.4 Queries y QueryHandlers
- [ ] **4.4.1** Actualizar `GetActiveSponsorsQueryHandler` para devolver patrocinadores generales (editionId=null) + de la edición activa
- [ ] **4.4.2** Crear `GetDocumentsByEditionQuery` + `Handler` (filtra por edition_id)
- [ ] **4.4.3** Crear `GetGeneralDocumentsQuery` + `Handler` (filtra por edition_id IS NULL)

---

## Fase 5: Infrastructure Layer

### 5.1 Mappers Doctrine
- [ ] **5.1.1** Actualizar `RaceEditionMapper::toDomain()` y `::toOrm()` para `shirtUrl`
- [ ] **5.1.2** Actualizar `SponsorMapper::toDomain()` y `::toOrm()` para `raceEditionId`
- [ ] **5.1.3** Actualizar `PhotoMapper::toDomain()` y `::toOrm()` para nuevos nombres de campos
- [ ] **5.1.4** Crear `RaceDocumentMapper`

### 5.2 Repositorios Doctrine
- [ ] **5.2.1** Actualizar `DoctrineSponsorRepository` para manejar `editionId` en queries
- [ ] **5.2.2** Actualizar `DoctrinePhotoRepository` si es necesario
- [ ] **5.2.3** Crear `DoctrineRaceDocumentRepository` implementando `RaceDocumentRepositoryInterface`

### 5.3 Controllers HTTP

#### Públicos
- [ ] **5.3.1** Actualizar `RaceController` para incluir `shirtUrl` en respuestas JSON
- [ ] **5.3.2** Actualizar `SponsorController` para devolver generales + edición activa
- [ ] **5.3.3** Actualizar `PhotoController` para adaptar a nuevos nombres de campos
- [ ] **5.3.4** Crear `RaceDocumentController` con endpoints:
  - `GET /api/v1/editions/{year}/documents`
  - `GET /api/v1/documents` (generales)

#### Admin
- [ ] **5.3.5** Actualizar `AdminRaceController`:
  - Aceptar upload de camiseta en POST/PUT
  - Devolver URLs públicas de R2 en respuesta
- [ ] **5.3.6** Actualizar `AdminSponsorController` para aceptar `editionId` opcional
- [ ] **5.3.7** Actualizar `AdminPhotoController`:
  - Adaptar a nuevos campos (`altText`, generación automática de miniatura)
  - Eliminar foto: borrar de R2 (original + thumb) y de BD
- [ ] **5.3.8** Crear `AdminRaceDocumentController` con endpoints:
  - `GET /api/v1/admin/documents`
  - `POST /api/v1/admin/documents` (upload PDF)
  - `PUT /api/v1/admin/documents/{id}`
  - `DELETE /api/v1/admin/documents/{id}`

### 5.4 Configuración de Servicios
- [ ] **5.4.1** Registrar `RaceDocumentRepositoryInterface` → `DoctrineRaceDocumentRepository` en `services.yaml`
- [ ] **5.4.2** Registrar `ImageProcessorInterface` → `GdImageProcessor` en `services.yaml`
- [ ] **5.4.3** Configurar `StoragePort` con factory: `local` (default dev) o `r2` (prod/pre)

---

## Fase 6: Tests Backend

### 6.1 Unitarios
- [ ] **6.1.1** Test `DocumentType` ValueObject
- [ ] **6.1.2** Test `RaceEdition` con `shirtUrl`
- [ ] **6.1.3** Test `Sponsor` con `raceEditionId`
- [ ] **6.1.4** Test `Photo` con campos renombrados
- [ ] **6.1.5** Test `RaceDocument` entity

### 6.2 Mappers
- [ ] **6.2.1** Test `RaceEditionMapper` incluyendo `shirtUrl`
- [ ] **6.2.2** Test `SponsorMapper` incluyendo `raceEditionId`
- [ ] **6.2.3** Test `PhotoMapper` con campos renombrados
- [ ] **6.2.4** Test `RaceDocumentMapper`

### 6.3 Repositorios (Integración)
- [ ] **6.3.1** Test `DoctrineRaceDocumentRepository`
- [ ] **6.3.2** Test `DoctrineSponsorRepository` con filtro por edición

### 6.4 Funcionales (Controllers)
- [ ] **6.4.1** Test `RaceController` - `shirtUrl` aparece en respuesta
- [ ] **6.4.2** Test `SponsorController` - devuelve generales + edición activa
- [ ] **6.4.3** Test `PhotoController` - campos renombrados en JSON
- [ ] **6.4.4** Test `RaceDocumentController` - listado por edición
- [ ] **6.4.5** Test `AdminRaceController` - upload de camiseta
- [ ] **6.4.6** Test `AdminSponsorController` - CRUD con edición
- [ ] **6.4.7** Test `AdminPhotoController` - upload genera miniatura, delete borra ambos
- [ ] **6.4.8** Test `AdminRaceDocumentController` - CRUD completo de documentos

---

## Fase 7: Limpieza y Deuda Técnica

- [ ] **7.1** Eliminar campos/código legacy que quedaron obsoletos tras la migración
- [ ] **7.2** Verificar que `AdminResultController` no se rompió con cambios de schema
- [ ] **7.3** Actualizar `IMPLEMENTACION.md` con los nuevos bounded contexts y entidades
