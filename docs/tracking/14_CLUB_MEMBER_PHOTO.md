# Edición de foto de miembros del club igual que sponsors

> **Fecha inicio:** 2026-06-12
> **Objetivo:** Unificar la edición de foto de los miembros del club con la de sponsors: solo se edita en modo edición, muestra la foto actual con botón eliminar, y si no hay foto muestra un `ImageDropZone`.

---

## Leyenda

| Icono | Significado |
|-------|-------------|
| ⬜ | Pendiente |
| 🔄 | En progreso |
| ✅ | Completado |
| ⏸️ | Bloqueado |

---

## Cambios realizados

### Backend

| # | Archivo | Cambio | Estado |
|---|---------|--------|--------|
| B1 | `backend/src/Domain/Club/Entity/ClubMember.php` | `update()` permite borrar `photoPath` al recibir string vacío | ✅ |
| B2 | `backend/src/Infrastructure/Http/Controller/Api/Admin/AdminClubMemberController.php` | `update()` recibe `photoUrl` y lo pasa al command | ✅ |
| B3 | `backend/src/Application/Club/Response/ClubMemberResponseDto.php` | `buildUrl()` evita duplicar el dominio cuando `photoPath` ya es URL completa | ✅ |

### Frontend

| # | Archivo | Cambio | Estado |
|---|---------|--------|--------|
| F1 | `frontend/src/views/admin/AdminClubMembersView.vue` | Foto solo editable en modo edición, igual que sponsors | ✅ |
| F2 | `frontend/src/views/admin/AdminClubMembersView.vue` | Añadidas funciones `uploadPhoto()` y `deletePhoto()` | ✅ |
| F3 | `frontend/src/views/admin/AdminClubMembersView.vue` | Eliminada subida de foto en creación y miniatura interactiva en tabla | ✅ |
| F4 | `frontend/src/views/admin/AdminClubMembersView.vue` | Ajustados espacios y bordes para fotos cuadradas (formulario y tabla) | ✅ |

---

## QA

| # | Verificación | Estado |
|---|--------------|--------|
| Q1 | `vue-tsc --build --force` sin errores | ✅ |
| Q2 | `phpunit tests/Unit` verde (salvo fallo preexistente `ClubMemberMapperTest`) | ✅ |
