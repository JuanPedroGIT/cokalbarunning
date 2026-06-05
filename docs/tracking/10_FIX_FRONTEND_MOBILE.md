# Fix Frontend + Mobile Responsive — Tracking

> **Fecha inicio:** 2026-06-04
> **Objetivo:** Corregir errores de TypeScript, limpiar code smells y hacer las tablas del panel admin usables en móvil.

---

## Leyenda

| Icono | Significado |
|-------|-------------|
| ⬜ | Pendiente |
| 🔄 | En progreso |
| ✅ | Completado |
| ⏸️ | Bloqueado |

---

## Errores TypeScript

| # | Archivo | Error | Fix aplicado | Estado |
|---|---------|-------|--------------|--------|
| TS1 | `RaceDocuments.vue:28` | `Object is possibly 'undefined'` | Usar `;(groups[doc.type] ??= []).push(doc)` | ✅ |
| TS2 | `HomeView.vue:127` | `publishedAt` no existe en `LatestPost` | Añadir `publishedAt: string` a la interfaz | ✅ |

## Code Smells

| # | Archivo | Problema | Fix aplicado | Estado |
|---|---------|----------|--------------|--------|
| CS1 | `RaceView.vue` | Countdown duplicado (lógica manual en vez de `useCountdown`) | Refactorizar a `useCountdown(raceDate)`; eliminar `tick`, `timer`, `pad` | ✅ |
| CS2 | `BlogView.vue` | Fallback data con faltas de ortografía (`ano`, `batio`) | Corregir a `año`, `batió`, `más`, `categorías`, `espíritu`, `edición`, `récords` | ✅ |
| CS3 | `BlogPostView.vue:60` | `v-html="post.content"` sin sanitización | Añadir comentario de advertencia: el backend debe sanitizar el HTML | ✅ |
| CS4 | `RaceDocuments.vue` | `<script>` separado con `import { computed }` | Mover import al `<script setup>`; eliminar segundo bloque script | ✅ |

## Responsive — Tablas Admin

Estrategia: wrapper `overflow-x-auto` + `min-w-*` en la tabla + paddings reducidos en móvil (`p-2 md:p-3`) + columnas secundarias ocultas en móvil (`hidden md:table-cell`).

| # | Vista | Tabla | Cambios | Estado |
|---|-------|-------|---------|--------|
| R1 | `AdminClubMembersView.vue` | Miembros (7 cols) | Scroll horizontal, paddings reducidos, ocultar Bio y Usuario en móvil | ✅ |
| R2 | `AdminEditionsView.vue` | Ediciones (5 cols) | Scroll horizontal, paddings reducidos | ✅ |
| R3 | `AdminPostsView.vue` | Posts (4 cols) | Scroll horizontal, paddings reducidos | ✅ |
| R4 | `AdminSponsorsView.vue` | Patrocinadores (6 cols) | Scroll horizontal, paddings reducidos, ocultar Mensaje en móvil | ✅ |
| R5 | `AdminUsersView.vue` | Usuarios (4 cols) | Scroll horizontal, paddings reducidos | ✅ |

## QA Final

| # | Verificación | Resultado | Estado |
|---|--------------|-----------|--------|
| Q1 | `vue-tsc --build` sin errores | ✅ 0 errores | ✅ |
| Q2 | Tablas admin navegables en viewport 375px | Scroll horizontal habilitado, columnas secundarias ocultas | ✅ |
| Q3 | Countdown sigue funcionando en RaceView | Usa `useCountdown`, mismos valores `days/hours/minutes/seconds/isExpired` | ✅ |
