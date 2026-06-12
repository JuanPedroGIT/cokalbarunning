# Sistema de Tracking - PRD: Almacenamiento y Galería R2

## Propósito

Este directorio contiene el sistema de tracking para implementar la especificación del PRD de Almacenamiento y Galería R2 (`docs/prd_documentos.md`).

El objetivo es poder trabajar en esta feature larga sin saturar el contexto de la IA, dividiendo el trabajo en bloques independientes y trackeando el progreso de forma explícita.

## Cómo usar este sistema

### Para el usuario (humano)
1. Revisa `05_PROGRESO.md` para ver en qué fase estamos.
2. Pide a la IA: *"Implementa la tarea [X.Y] del tracking"*.
3. Al finalizar cada sesión, la IA actualiza `05_PROGRESO.md`.

### Para la IA (agente)
1. **SIEMPRE** lee `05_PROGRESO.md` al inicio de cada sesión para saber dónde estamos.
2. **SIEMPRE** lee `02_DESGLOSE_TECNICO.md` para entender el impacto técnico de la tarea actual.
3. Implementa UNA tarea a la vez (o un grupo de tareas muy relacionadas).
4. **SIEMPRE** actualiza `05_PROGRESO.md` al finalizar.
5. Si surge una decisión técnica importante, anótala en `06_DECISIONES.md`.

## Archivos del sistema

| Archivo | Propósito |
|---------|-----------|
| `01_PRD_R2_STORAGE.md` | Copia de referencia del PRD original |
| `02_DESGLOSE_TECNICO.md` | Análisis de impacto técnico: qué cambia, dónde, por qué |
| `03_TAREAS_BACKEND.md` | Checklist detallado de todas las tareas del backend |
| `04_TAREAS_FRONTEND.md` | Checklist detallado de todas las tareas del frontend |
| `05_PROGRESO.md` | **ESTADO EN TIEMPO REAL**: qué está hecho, qué se está haciendo, qué falta |
| `06_DECISIONES.md` | Decisiones técnicas, trade-offs, notas importantes |
| `07_FIX_PATHS_PRD.md` | Corrección de paths post-implementación (alineación con PRD + inglés) |
| `08_PLAN_SOLID.md` | Plan de mejora aplicando principios SOLID (6 fases) |
| `09_PLAN_ROLES.md` | Plan de roles: ROLE_EDITOR, panel de usuarios, protección por rol |
| `10_FIX_FRONTEND_MOBILE.md` | Fix de responsive en frontend: hero, countdown, grid de fotos |
| `11_TROPHY_URL.md` | Fix de URL de trofeo y camiseta en ediciones |
| `12_ERROR_PERMISOS_VAR_DOCKER.md` | Error recurrente: 502/500 en API por permisos de `var/` en Docker |
| `13_BANNER_NOTICIA_TIPO2.md` | Banner informativo global: noticias tipo 2, exclusiones y admin |
| `14_CLUB_MEMBER_PHOTO.md` | Edición de foto de miembros del club igual que sponsors |

## Reglas de oro

- **Una tarea a la vez.** No saltes a la siguiente hasta que la actual esté completa y testeada.
- **Tests antes que frontend.** El backend debe estar testeado antes de tocar el frontend que lo consume.
- **Migraciones explícitas.** Cada cambio de schema debe ir acompañado de su migración Doctrine.
- **No acumular deuda.** Si algo del PRD no encaja con la arquitectura actual, se discute en `06_DECISIONES.md` antes de implementar un hack.
