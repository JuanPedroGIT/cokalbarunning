# Plan: Carga Masiva de Fotos desde ZIP (Google Photos)

## Contexto
El usuario quiere poder descargar un álbum de Google Photos como ZIP y subirlo al panel de administración para importar todas las fotos de golpe a una edición de la carrera.

## Estado Actual
- Las fotos se suben una a una vía `POST /api/v1/admin/photos` (multipart/form-data).
- El backend: recibe `UploadedFile`, genera thumbnail WebP con GD, sube original + thumb a R2 (Cloudflare), y crea registro en tabla `photos`.
- El frontend (`AdminPhotosView.vue`) tiene selector de edición, dropzone para 1 foto, y grid de fotos existentes.
- Symfony Messenger está configurado como síncrono (`sync://`).

## Opciones Consideradas

| Opción | Pros | Cons |
|--------|------|------|
| **A. Endpoint HTTP síncrono** | Simple, feedback inmediato al usuario | Riesgo de timeout con ZIPs grandes (>50-100 fotos) |
| **B. Comando CLI** | Sin límites de tiempo, ideal para miles de fotos | Requiere acceso SSH al servidor |

**Recomendación: Opción A (endpoint síncrono)** como MVP, con manejo de tiempo de ejecución y límite de tamaño razonable (~100MB ZIP / ~200 fotos). Si en el futuro hay álbumes más grandes, se migra a Opción B.

## Implementación

### 1. Backend — Nuevo endpoint `POST /api/v1/admin/photos/bulk-zip`

**Archivo:** `backend/src/Infrastructure/Http/Controller/Api/Admin/AdminPhotoController.php`

- Añadir método `bulkUploadFromZip(Request $request)` con ruta `/photos/bulk-zip`.
- Parámetros: `file` (ZIP), `raceEditionId` (requerido).
- Validaciones: ZIP válido, edición existe.
- Procesamiento paso a paso:
  1. Extraer ZIP a directorio temporal (`sys_get_temp_dir()`).
  2. Iterar archivos con extensión `jpg`, `jpeg`, `png`, `webp`, `gif`, `heic`, `heif`.
  3. Para cada imagen:
     - Generar nombre de ruta con `PathGenerator::photoPath($year, $ext)`.
     - Generar thumbnail con `ImageProcessorInterface::createThumbnail()`.
     - Subir original + thumbnail a R2 vía `StoragePort::store()`.
     - Crear registro en BD vía `UploadPhotoCommand`.
  4. Limpiar archivos temporales (original + thumb).
  5. Devolver resumen JSON: `{ data: { imported: 42, skipped: 3, errors: [...] } }`.
- Prevenir timeout: `set_time_limit(300)` o `ini_set('max_execution_time', 300)`.
- Limitar tamaño de ZIP: verificar contra `upload_max_filesize` (recomendado aumentar a 100M en `php.ini` o `.htaccess` del contenedor).

**Archivos a crear/modificar:**
- `AdminPhotoController.php` — añadir método
- `docker-compose.prod.yml` o `backend/Dockerfile` — aumentar `upload_max_filesize` y `post_max_size` a 100M si es necesario

### 2. Frontend — Sección de importación masiva en `AdminPhotosView.vue`

**Archivo:** `frontend/src/views/admin/AdminPhotosView.vue`

- Añadir debajo del formulario de subida individual una nueva sección "Importar desde ZIP".
- Componente: input file nativo (no drag-and-drop, ya que un ZIP no es una imagen) con estilo consistente al tema oscuro.
- Estado: `zipFile`, `importing`, `importResult`.
- Al seleccionar ZIP y pulsar "Importar":
  - `POST /admin/photos/bulk-zip` con `multipart/form-data`.
  - Mostrar spinner/progreso ("Importando X fotos...").
  - Al finalizar, mostrar resumen (importadas / saltadas / errores).
  - Refrescar grid de fotos.
- Responsive: input y botón en una fila en desktop, apilados en móvil.

### 3. Consideraciones Técnicas

| Tema | Decisión |
|------|----------|
| **HEIC/HEIF** | GD no soporta HEIC por defecto. Filtrar y saltar estos archivos con mensaje informativo. |
| **Duplicados** | No verificar duplicados por hash en MVP (podría añadirse luego). |
| **Memoria** | Procesar una foto a la vez, liberando recursos entre iteraciones. |
| **Thumbnails** | Reutilizar `GdImageProcessor` existente (400px WebP, calidad 85). |
| **Paths** | Reutilizar `PathGenerator` existente para mantener consistencia con subidas manuales. |
| **Rollback** | Si una foto falla, continuar con las demás. Loguear errores en la respuesta. |

### 4. Estimación de Tiempos

| Fase | Archivos | Complejidad |
|------|----------|-------------|
| Backend endpoint + procesamiento ZIP | 1 controller, posible ajuste Docker/PHP | Media |
| Frontend sección import ZIP | 1 view (AdminPhotosView.vue) | Baja |
| Testing local + deploy prod | — | Baja |

**Total estimado: 2-3 horas de trabajo.**

### 5. Flujo de Uso Final

1. El fotógrafo/admin comparte un álbum de Google Photos.
2. Desde Google Photos web, se descarga el álbum como ZIP.
3. El admin entra a `/admin/photos`, selecciona la edición.
4. En la sección "Importar desde ZIP", selecciona el archivo ZIP descargado.
5. Pulsa "Importar". Espera el resumen.
6. Revisa las fotos importadas en el grid y puede eliminar las que no quiera.
