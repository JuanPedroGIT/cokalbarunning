# Plan: Roles y Gestión de Usuarios

> **Propósito:** Implementar ROLE_EDITOR, proteger endpoints por rol, y crear panel de administración de usuarios.
> **Creado:** 2026-05-31
> **Estado:** ✅ COMPLETADO

---

## Objetivo

Actualmente solo existe `ROLE_ADMIN` y `ROLE_PUBLIC`. Se necesita `ROLE_EDITOR` (acceso limitado al admin) y un panel de gestión de usuarios exclusivo para ROLE_ADMIN.

---

## Fase 1: Backend — Roles y permisos

### 1.1 Añadir ROLE_EDITOR al User entity
- [ ] Asegurar que `User.php` tiene los roles: `ROLE_ADMIN`, `ROLE_EDITOR`, `ROLE_PUBLIC`
- [ ] El comando `CreateAdminUserCommand` ya soporta `-r ROLE_EDITOR`

### 1.2 Añadir voter o expresión de seguridad por rol
- [ ] Configurar `access_control` en `config/packages/security.yaml`:
  - `/api/v1/admin/users` → ROLE_ADMIN
  - `/api/v1/admin/*` → ROLE_ADMIN + ROLE_EDITOR
  - `/api/v1/*` público → sin restricción

### 1.3 Crear AdminUserController (solo ROLE_ADMIN)
**Archivo nuevo:** `src/Infrastructure/Http/Controller/Api/Admin/AdminUserController.php`

Endpoints:
- `GET /api/v1/admin/users` — listar usuarios
- `POST /api/v1/admin/users` — crear usuario
- `PUT /api/v1/admin/users/{id}` — actualizar usuario (roles, email, password)
- `DELETE /api/v1/admin/users/{id}` — eliminar usuario

### 1.4 Commands + Handlers CQRS
**Archivos nuevos:**
- [ ] `CreateUserCommand` + `CreateUserHandler`
- [ ] `UpdateUserCommand` + `UpdateUserHandler`
- [ ] `DeleteUserCommand` + `DeleteUserHandler`
- [ ] `GetAllUsersQuery` + `GetAllUsersQueryHandler`
- [ ] `UserResponseDto`

### 1.5 Proteger endpoints existentes
- [ ] `POST/PUT/DELETE /api/v1/admin/editions/*` → ROLE_ADMIN + ROLE_EDITOR
- [ ] `POST/PUT/DELETE /api/v1/admin/photos/*` → ROLE_ADMIN + ROLE_EDITOR
- [ ] `POST/PUT/DELETE /api/v1/admin/sponsors/*` → ROLE_ADMIN + ROLE_EDITOR
- [ ] `POST/PUT/DELETE /api/v1/admin/club-members/*` → ROLE_ADMIN + ROLE_EDITOR
- [ ] `GET /api/v1/admin/users/*` → ROLE_ADMIN (solo admin gestiona usuarios)

---

## Fase 2: Frontend — Panel de usuarios

### 2.1 AdminUsersView
**Archivo nuevo:** `frontend/src/views/admin/AdminUsersView.vue`

- Tabla de usuarios: email, nombre, roles, activo
- Formulario crear/editar usuario: email, nombre, apellidos, contraseña, rol (select), activo
- Botón eliminar con confirmación

### 2.2 Ruta y menú
- [ ] Ruta: `/admin/users` (meta: requiresAuth + requiresAdmin)
- [ ] Enlace en `AdminDashboardView` solo visible para ROLE_ADMIN
- [ ] Actualizar `router.beforeEach` para verificar `requiresAdmin`

### 2.3 Auth store
- [ ] Añadir `hasRole(role: string)` al auth store
- [ ] Guardar roles del JWT al hacer login

---

## Fase 3: Protección frontend por rol

### 3.1 Router guards
- [ ] `meta.requiresAdmin` → comprueba ROLE_ADMIN
- [ ] `meta.requiresEditor` → comprueba ROLE_ADMIN o ROLE_EDITOR
- [ ] Redirigir a `/admin` si no tiene permisos

### 3.2 Ocultar enlaces en dashboard
- [ ] "Usuarios" solo visible si ROLE_ADMIN
- [ ] El resto de secciones visibles para ROLE_ADMIN + ROLE_EDITOR

---

## Fase 4: Tests

- [ ] Test funcional: AdminUserController CRUD
- [ ] Test funcional: ROLE_EDITOR no puede acceder a /admin/users
- [ ] Test funcional: ROLE_PUBLIC no puede acceder a /admin/*
- [ ] Test unitario: UserResponseDto

---

## Resumen de impacto

| Fase | Archivos nuevos | Archivos modificados | Rol |
|------|-----------------|---------------------|-----|
| F1 | ~7 (backend) | 1 (security.yaml) | ROLE_EDITOR + permisos API |
| F2 | 1 (AdminUsersView) | 2 (router + dashboard) | UI gestión usuarios |
| F3 | 0 | 2 (router + dashboard) | Guards frontend |
| F4 | ~4 tests | 0 | Cobertura |

**Prioridad:** F1 → F2 → F3 → F4
