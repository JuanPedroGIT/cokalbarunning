# Plan de Mejora SOLID

> **Propósito:** Refactorizar el código aplicando principios SOLID donde hay desviaciones.
> **Creado:** 2026-05-31
> **Estado:** 🔄 EN PROGRESO (F1 ✅, F2 ✅, F3 ✅, F5 ✅, F4 ⬜, F6 ⬜)

---

## Diagnóstico

El proyecto ya aplica buena parte de SOLID gracias a la arquitectura hexagonal + DDD + CQRS. Las áreas de mejora detectadas:

| Principio | Estado actual | Problema |
|-----------|---------------|----------|
| **S**ingle Responsibility | 🟡 | Controladores con lógica duplicada y comando que hace demasiadas cosas |
| **O**pen/Closed | 🟢 | OK — nuevos casos de uso son comandos sin tocar existentes |
| **L**iskov Substitution | 🟢 | OK — sin violaciones |
| **I**nterface Segregation | 🟢 | OK — interfaces ya son pequeñas y enfocadas |
| **D**ependency Inversion | 🟡 | Algunos controllers usan EntityManager en vez de bus CQRS |

---

## Fase 1: Centralizar paths de archivos (S)

### 1.1 Crear `PathGenerator` ✅
**Archivo nuevo:** `src/Domain/Media/Service/PathGenerator.php`

Centralizar toda la lógica de construcción de paths R2:
- `posterPath(int $year, string $ext): string`
- `shirtPath(int $year, string $ext): string`
- `photoPath(int $year, string $ext): string`
- `thumbnailPath(int $year): string`
- `sponsorLogoPath(string $ext): string`
- `documentPath(?int $year, string $type, string $ext): string`
- `clubMemberPhotoPath(string $ext): string`

**Archivos afectados:**
- [x] `AdminRaceController.php` — `uploadPoster`, `uploadShirt`
- [x] `AdminPhotoController.php` — `create`
- [x] `AdminRaceDocumentController.php` — `create`
- [x] `AdminSponsorController.php` — `uploadLogo`
- [x] `AdminClubMemberController.php` — `uploadPhoto`
- [x] `GenerateThumbnailsCommand.php`

### 1.2 Ventaja
Un solo punto de cambio si la estructura de directorios R2 evoluciona.

---

## Fase 2: Extraer lógica de upload a handlers CQRS (S + D)

### 2.1 `UploadRaceEditionImageHandler`
**Archivos nuevos:**
- [ ] `src/Application/Race/UploadImage/UploadRaceEditionImageCommand.php`
- [ ] `src/Application/Race/UploadImage/UploadRaceEditionImageHandler.php`

Unifica `uploadPoster` y `uploadShirt` (60 líneas duplicadas) en un solo handler con parámetro `type`.

**Archivos afectados:**
- [ ] `AdminRaceController.php` — delegar a command bus

### 2.2 `UploadSponsorLogoHandler`
**Archivos nuevos:**
- [ ] `src/Application/Club/UploadLogo/UploadSponsorLogoCommand.php`
- [ ] `src/Application/Club/UploadLogo/UploadSponsorLogoHandler.php`

Extrae la lógica de upload de logo del controller al handler.

**Archivos afectados:**
- [ ] `AdminSponsorController.php` — delegar a command bus

### 2.3 `UploadClubMemberPhotoHandler`
**Archivos nuevos:**
- [ ] `src/Application/Club/UploadPhoto/UploadClubMemberPhotoCommand.php`
- [ ] `src/Application/Club/UploadPhoto/UploadClubMemberPhotoHandler.php`

**Archivos afectados:**
- [ ] `AdminClubMemberController.php` — delegar a command bus

---

## Fase 3: Eliminar EntityManager de controllers (D) ✅

### 3.1 `DeleteClubMemberHandler`
**Archivos afectados:**
- [x] `DeleteClubMemberHandler.php` — mover borrado de archivo R2 al handler
- [x] `AdminClubMemberController.php` — quitar inyección de EntityManager + StoragePort

**Estado actual:** El controller borra el archivo en R2 antes de disparar el comando. El handler solo borra de BD.

**Estado deseado:** El handler borra archivo R2 Y registro BD. El controller solo dispara el comando.

### 3.2 `AdminClubMemberController`
- [ ] Quitar dependencia de `EntityManagerInterface`
- [ ] Quitar dependencia de `StoragePort`
- [ ] Usar exclusivamente `MessageBusInterface` para comandos y queries

---

## Fase 4: Dividir GenerateThumbnailsCommand (S)

### 4.1 Extraer `R2FileLister`
**Archivo nuevo:** `src/Infrastructure/Storage/R2FileLister.php`

Responsabilidad: listar objetos en el bucket con un prefijo dado.

### 4.2 Reutilizar `ImageProcessorInterface`
El comando no usa `GdImageProcessor`. Debería usarlo para la generación de thumbnail en lugar de tener su propia lógica GD duplicada.

### 4.3 Separar inserción en BD
La lógica de INSERT en `photos` debería usar el repositorio `PhotoRepositoryInterface::save()` en lugar de SQL crudo.

**Archivos afectados:**
- [ ] `GenerateThumbnailsCommand.php` — refactorizar para delegar en servicios
- [ ] `R2FileLister.php` — nuevo
- [ ] `ImageProcessorInterface` — verificar si necesita ajustes

---

## Fase 5: Limpiar `PhotoRepositoryInterface` ✅

### 5.1 Añadir método `findByEditionId`
El método ya existía en la interfaz y el handler ya lo usa correctamente. Verificado.

**Archivos afectados:**
- [x] `PhotoRepositoryInterface.php` — ya tiene `findByEditionId`
- [x] `DoctrinePhotoRepository.php` — ya implementado
- [x] `GetAllPhotosQueryHandler.php` — ya usa repositorio

---

## Fase 6: Tests

- [ ] Test unitario de `PathGenerator`
- [ ] Test unitario de `UploadRaceEditionImageHandler`
- [ ] Test unitario de `UploadSponsorLogoHandler`
- [ ] Test unitario de `UploadClubMemberPhotoHandler`
- [ ] Actualizar tests funcionales de controllers (delegan a command bus)
- [ ] Test de `R2FileLister`

---

## Resumen de impacto

| Fase | Archivos nuevos | Archivos modificados | Principio |
|------|-----------------|---------------------|-----------|
| F1 | 1 | ~6 | S |
| F2 | 6 | 3 | S + D |
| F3 | 0 | 2 | D |
| F4 | 1 | 1 | S |
| F5 | 0 | 3 | D |
| F6 | ~8 tests | ~4 tests existentes | — |

**Prioridad recomendada:** F1 → F3 → F2 → F5 → F4 → F6
