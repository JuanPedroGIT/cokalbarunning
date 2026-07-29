# Formulario de Contacto para Patrocinadores

> **Tracking:** Reemplazar el botón mailto "Conviértete en patrocinador" por un formulario que guarda los datos en BD.
> **Fecha de inicio:** 2026-07-28
> **Estado:** ⬜ Pendiente

---

## Resumen

Actualmente en `HomeView.vue` hay un enlace `mailto:jabautistah@gmail.com` con el texto "Conviértete en patrocinador". Se va a reemplazar por un botón que abre un modal con un formulario de contacto. Los datos se guardan en una nueva tabla `sponsorship_contacts`.

---

## Campos del formulario

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `name` | string(255) | Sí | Nombre de la persona de contacto |
| `company` | string(255) | Sí | Nombre de la empresa |
| `email` | string(255) | Sí | Email de contacto |
| `phone` | string(50) | Sí | Teléfono de contacto |
| `message` | text | No | Notas adicionales (interés, presupuesto, etc.) |

---

## Decisiones técnicas

### DEC-62: Nueva tabla `sponsorship_contacts`

- Tabla simple, sin FK a otras tablas (es un formulario público)
- Sin autenticación requerida para el endpoint
- Se guarda `createdAt` para seguimiento
- **Reversible:** Sí.

### DEC-63: Endpoint público sin autenticación

- `POST /api/v1/sponsorship/contact` — sin token JWT
- Validación básica: campos requeridos, email válido
- Rate limiting recomendado (pero no implementado en MVP)
- **Reversible:** Sí.

### DEC-64: Arquitectura hexagonal estándar

- Domain entity + Repository interface + ORM entity + Mapper + Doctrine repository
- CQRS: `SubmitSponsorshipContactCommand` + Handler
- Controller público (sin `#[Route('/api/v1/admin')]`)
- **Reversible:** Sí.

---

## Tareas

### Bloque 1: Backend — Entidad, migración y repositorio

- [ ] **B1.1** Crear migración Doctrine para tabla `sponsorship_contacts`:
  - `id` (GUID), `name` (string 255), `company` (string 255), `email` (string 255), `phone` (string 50), `message` (text nullable), `created_at` (datetime)

- [ ] **B1.2** Crear `App\Entity\SponsorshipContact` (ORM entity)

- [ ] **B1.3** Crear `App\Domain\Sponsorship\Contact\Entity\SponsorshipContact` (domain entity)

- [ ] **B1.4** Crear `App\Domain\Sponsorship\Contact\Repository\SponsorshipContactRepositoryInterface`
  - `save(SponsorshipContact): void`

- [ ] **B1.5** Crear `App\Infrastructure\Persistence\Doctrine\Mapper\SponsorshipContactMapper`

- [ ] **B1.6** Crear `App\Infrastructure\Persistence\Doctrine\Repository\DoctrineSponsorshipContactRepository`

- [ ] **B1.7** Añadir alias en `services.yaml`

### Bloque 2: Backend — CQRS y endpoint

- [ ] **B2.1** Crear `SubmitSponsorshipContactCommand` + Handler
  - Valida campos requeridos y formato email
  - Crea la entidad de dominio y la guarda

- [ ] **B2.2** Crear `SponsorshipContactController` con endpoint `POST /api/v1/sponsorship/contact`
  - Sin autenticación (ruta fuera de `/api/v1/admin`)
  - Recibe JSON `{name, company, email, phone, message}`
  - Devuelve `{ success: true }` o error 422

### Bloque 3: Frontend — Modal y formulario

- [ ] **B3.1** Crear componente `SponsorContactModal.vue`:
  - Modal con fondo oscuro y cierre al hacer clic fuera
  - Campos: nombre*, empresa*, email*, teléfono*, mensaje
  - Validación client-side (requeridos, formato email)
  - Botón "Enviar" con estado loading
  - Mensajes de éxito/error
  - Diseño consistente con el tema de la web

- [ ] **B3.2** Modificar `HomeView.vue`:
  - Reemplazar `<a href="mailto:...">` por un botón que abre el modal
  - Misma sección, mismo texto "Conviértete en patrocinador"

### Bloque 4: Frontend — Panel admin para revisar contactos

- [ ] **B4.1** Backend: `GetSponsorshipContactsQuery` + Handler
  - Lista todos los contactos ordenados por fecha (más reciente primero)
  - Endpoint `GET /api/v1/admin/sponsorship/contacts` (requiere auth admin)

- [ ] **B4.2** Frontend: Añadir sección en el panel admin
  - Opción A: Nueva página `AdminSponsorshipContactsView.vue` + ruta `/admin/sponsorship-contacts`
  - Opción B: Añadir un accordion/sección dentro del dashboard o de otra página existente
  - **Decisión:** Opción A — página dedicada con tabla de contactos

- [ ] **B4.3** Frontend: Tabla con columnas
  - Nombre, Empresa, Email, Teléfono, Mensaje, Fecha
  - Sin acciones (solo consulta)
  - Diseño consistente con el tema oscuro del admin

- [ ] **B4.4** Frontend: Añadir tarjeta en `AdminDashboardView.vue` y ruta en `router/index.ts`

### Bloque 5: Limpieza y tests

- [ ] **B5.1** `make test` — tests existentes deben seguir pasando
- [ ] **B5.2** Test funcional del endpoint público
- [ ] **B5.3** `vue-tsc` y build frontend verdes
- [ ] **B5.4** Actualizar `docs/tracking/05_PROGRESO.md`

---

## Orden de implementación

| Orden | Bloque | Depende de |
|--------|--------|------------|
| 1 | B1 — Backend entidad + migración + repositorio | Nada |
| 2 | B2 — Backend CQRS + endpoint | B1 |
| 3 | B3 — Frontend modal + formulario | B2 |
| 4 | B4 — Panel admin contactos | B1 |
| 5 | B5 — Limpieza y tests | B1-B4 |
