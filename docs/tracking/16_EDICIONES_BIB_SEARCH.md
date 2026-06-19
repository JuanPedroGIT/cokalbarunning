# Búsqueda de dorsal en /ediciones

> **Tracking:** Rediseño de la página `/ediciones` para mostrar la edición actual destacada y añadir la funcionalidad de búsqueda de dorsal por nombre.
> **Fecha de inicio:** 2026-06-15
> **Estado:** ✅ Completado

---

## Resumen

Modificación de la página pública `/ediciones` para:

1. Mostrar la **edición actual** como bloque destacado en la parte superior.
2. En el bloque de la edición actual: cartel a la derecha e información + opciones a la izquierda.
3. Opciones disponibles: **Resultados**, **Galería** y, cuando proceda, **Buscar dorsal**.
4. La búsqueda de dorsales está condicionada por el flag `showBibSearch` de la edición actual.
5. Al buscar, filtrar runners por nombre (LIKE case-insensitive) y mostrar nombre completo + dorsal.
6. El término de búsqueda debe tener al menos 4 caracteres, tanto en frontend como en backend.

---

## Decisiones técnicas

### DEC-25: Endpoint de búsqueda de runners
- **Contexto:** ¿Cómo se consultan los participantes para buscar dorsal?
- **Decisión:** Nuevo endpoint público `GET /api/v1/runners?editionId=UUID&name=TERMINO`. Busca en la tabla `runners` filtrando por `race_edition_id` y haciendo `LIKE` sobre `first_name`, `last_name` y el nombre completo.
- **Impacto:** Nuevo `RunnerController::search`.
- **Reversible:** Sí.

### DEC-26: Diseño de la página /ediciones
- **Contexto:** ¿Cómo se presenta la edición actual y las anteriores?
- **Decisión:** La edición actual se muestra en un bloque horizontal (cartel derecha, info + opciones izquierda). Las ediciones anteriores mantienen el grid existente y **no** incluyen búsqueda de dorsal.
- **Impacto:** Reescritura de `EditionsView.vue`.
- **Reversible:** Sí.

### DEC-27: Control de visibilidad del buscador
- **Contexto:** ¿Cómo se decide si una edición muestra la búsqueda de dorsal?
- **Decisión:** Se añade el campo `showBibSearch` a `RaceEdition`. Es `false` por defecto, se activa automáticamente al cargar runners desde el CSV de dorsales y puede editarse manualmente desde el panel de administración.
- **Impacto:** Nuevo campo en ORM, dominio, DTO, mapper, migración, handlers y panel admin.
- **Reversible:** Sí.

### DEC-28: Longitud mínima del término de búsqueda
- **Contexto:** ¿Se debe limitar la longitud mínima del nombre para evitar consultas muy amplias o abuso del endpoint público?
- **Decisión:** El término de búsqueda debe tener **al menos 4 caracteres**. El frontend deshabilita el botón y muestra un mensaje hasta alcanzar la longitud; el backend rechaza peticiones con menos de 4 caracteres devolviendo 400.
- **Impacto:** Validación en `RunnerController::search`, ajuste en `EditionsView.vue` y nuevo test funcional.
- **Reversible:** Sí.

---

## Estructura de implementación

### Backend

- `src/Entity/RaceEdition.php`
  - Campo `showBibSearch` mapeado a `show_bib_search`.
- `src/Domain/Race/Entity/RaceEdition.php`
  - Propiedad `showBibSearch` y métodos `showBibSearch()` / `setShowBibSearch()`.
- `src/Application/Race/Response/RaceEditionResponseDto.php`
  - Expone `showBibSearch` en la respuesta y en `toArray()`.
- `src/Infrastructure/Persistence/Doctrine/Mapper/RaceEditionMapper.php`
  - Mapeo ORM ↔ dominio de `showBibSearch`.
- `src/Application/Race/Create/CreateRaceEditionCommand.php` / `CreateRaceEditionHandler.php`
  - Acepta y aplica `showBibSearch` en la creación.
- `src/Application/Race/Update/UpdateRaceEditionCommand.php` / `UpdateRaceEditionHandler.php`
  - Acepta y aplica `showBibSearch` en la actualización.
- `src/Infrastructure/Http/Controller/Api/Admin/AdminRaceController.php`
  - Recibe `showBibSearch` en `create` y `update`.
- `src/Infrastructure/Http/Controller/Api/Admin/AdminBibEmailController.php`
  - Activa `showBibSearch = true` de la edición al procesar el envío de dorsales.
- `src/Infrastructure/Http/Controller/Api/RunnerController.php`
  - `GET /api/v1/runners` — búsqueda pública por `editionId` y `name` (mínimo 4 caracteres).
- `migrations/Version20260615143450.php`
  - Añade la columna `show_bib_search` a `race_editions`.

### Frontend

- `frontend/src/stores/race.store.ts`
  - `RaceEdition` incluye `showBibSearch?: boolean`.
- `frontend/src/views/EditionsView.vue`
  - Bloque destacado de edición actual.
  - Layout cartel derecha / opciones izquierda.
  - Botones Resultados, Galería y Buscar dorsal (solo si `activeEdition.showBibSearch`).
  - Formulario de búsqueda con lista de resultados.
  - Validación de longitud mínima (4 caracteres) y mensaje de error.
- `frontend/src/views/admin/AdminEditionsView.vue`
  - Checkbox para editar `Mostrar búsqueda de dorsales`.

### Tests

- `backend/tests/Functional/Api/RunnerControllerTest.php`
  - Validación de parámetros.
  - Búsqueda por nombre y apellido.
  - Filtrado por edición.
  - Validación de longitud mínima del término de búsqueda.
- `backend/tests/Functional/Api/AdminRaceControllerTest.php`
  - Creación y actualización de `showBibSearch`.
- `backend/tests/Functional/Api/Admin/AdminBibEmailControllerTest.php`
  - Verifica que el envío de dorsales activa `showBibSearch`.

---

## Verificación

- ✅ Tests backend: 143 tests, 472 assertions.
- ✅ Build frontend verde (`vue-tsc` + Vite).

---

## Notas

- La búsqueda es case-insensitive (`LOWER(...) LIKE ...`) pero respeta tildes: buscar "lopez" no encontrará "lópez". Si se requiere búsqueda sin tildes, se puede añadir `unaccent` de PostgreSQL más adelante.
- El endpoint es público (método GET en `/api/v1`).
- `showBibSearch` es `false` por defecto; solo la edición actual puede mostrar el buscador y el admin puede desactivarlo manualmente.
- El término de búsqueda requiere al menos 4 caracteres para reducir resultados parciales y evitar consultas excesivas al endpoint público.
