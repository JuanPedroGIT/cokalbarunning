# Envío de correos con dorsales (Bib Email Sender)

> **Tracking:** Implementación de nueva sección en el panel admin para cargar un CSV con participantes y enviarles un correo HTML con su dorsal asignado, referenciando la edición activa de la carrera.
> **Fecha de inicio:** 2026-06-13
> **Estado:** ✅ Completado

---

## Resumen

Nueva funcionalidad en el panel de administración para que el admin pueda:

1. Subir un CSV con columnas `nombre;email;dorsal`.
2. Seleccionar la edición de la carrera a la que pertenecen los participantes.
3. Ver un listado previo de participantes con validación de emails, duplicados y envíos previos por email.
4. Enviar un correo HTML (plantilla **Twig** renderizada en el backend) a cada participante informando del dorsal asignado.
5. Registrar el estado de cada envío en la tabla `email_send_logs`.
6. Visualizar estados (pendiente / enviado / error), errores, duplicados, contadores y usuario que encoló cada envío.
7. Reenviar correos de forma individual o masiva desde el historial.

---

## Decisiones técnicas

### DEC-18: Origen de datos
- **Contexto:** ¿De dónde salen los participantes a los que enviar correos?
- **Decisión:** El admin sube un CSV con las columnas `nombre;email;dorsal`. No se consultan ni modifican las entidades `Runner`/`Result`.
- **Impacto:** Nuevo endpoint `POST /api/v1/admin/bib-emails/preview` para parsear CSV y devolver listado con estados previos.
- **Reversible:** Sí.

### DEC-19: Persistencia
- **Contexto:** ¿Se deben guardar los participantes del CSV en la base de datos?
- **Decisión:** No. Solo se persiste el tracking de envíos en una nueva tabla `email_send_logs`.
- **Impacto:** Nueva entidad ORM `EmailSendLog` + migraciones Doctrine.
- **Reversible:** Sí.

### DEC-20: Transporte de envío de correos
- **Contexto:** ¿Síncrono o asíncrono? ¿Worker permanente o comando puntual?
- **Decisión:** Comando CLI `app:bib-emails:send` lanzado desde el panel admin en segundo plano con `nohup`. El envío solo ocurre una vez al año, por lo que no justifica mantener un worker consumiendo recursos permanentemente. Symfony Mailer se configura sincrónicamente dentro del comando (sin encolar en Messenger).
- **Impacto:** Nuevo `SendPendingBibEmailsCommand`, endpoint `/bib-emails/run`, `messenger.yaml` ya no enruta `SendEmailMessage` a `async`.
- **Reversible:** Sí (volver a worker con Redis si cambian los requisitos).

### DEC-21: Motor de plantillas del email
- **Contexto:** ¿Cómo se genera el HTML del correo?
- **Decisión:** **Twig**. La plantilla se renderiza en el backend (`templates/emails/bib_assigned.html.twig`), adaptada del script Python `enviar_dorsales.py`. Vue solo se usa para la interfaz de administración.
- **Impacto:** Nueva plantilla Twig; el frontend no renderiza el email.
- **Reversible:** Sí.

### DEC-22: Delay entre envíos
- **Contexto:** ¿Cómo evitar que el proveedor de email rechace mensajes por flood?
- **Decisión:** Añadir `sleep(BIB_EMAIL_DELAY_SECONDS)` en `SendBibEmailHandler` entre envíos. Valor por defecto: 3 segundos. Configurable vía `.env`.
- **Impacto:** `SendBibEmailHandler`, `.env.example`, `services.yaml`.
- **Reversible:** Sí.

### DEC-23: Auditoría de envíos
- **Contexto:** ¿Quién envió cada correo y cuántos se han enviado?
- **Decisión:** Registrar `sent_by` (userId del admin) en `email_send_logs`. El frontend calcula contadores por email y muestra el email del usuario en el historial.
- **Impacto:** Nueva columna `sent_by`, DTO, mapper, handler, frontend.
- **Reversible:** Sí.

---

## Estructura de implementación

### Backend

#### ORM
- `src/Entity/EmailSendLog.php`
  - `id` (uuid, string 36)
  - `raceEditionId` (uuid, string 36, nullable)
  - `recipientEmail` (string 255)
  - `recipientName` (string 255)
  - `bibNumber` (string 20)
  - `status` (string: `pending`, `sent`, `error`, `not_sent`)
  - `errorMessage` (text, nullable)
  - `sentAt` (datetime_immutable, nullable)
  - `sentBy` (uuid, string 36, nullable)
  - `createdAt` / `updatedAt` (datetime_immutable)
- Migraciones Doctrine:
  - `Version20260613094316` — tabla inicial.
  - `Version20260613114506` — columna `sent_by`.

#### Domain
- `src/Domain/Notification/Entity/EmailSendLog.php`
- `src/Domain/Notification/Repository/EmailSendLogRepositoryInterface.php`
- `src/Domain/Notification/ValueObject/EmailStatus.php`

#### Application
- `src/Application/Notification/Command/CreateEmailSendLogCommand.php` + Handler
- `src/Application/Notification/Command/UpdateEmailSendLogStatusCommand.php` + Handler
- `src/Application/Notification/Query/GetEmailSendLogsQuery.php` + Handler
- `src/Application/Notification/Response/EmailSendLogResponseDto.php`
- `src/Application/Race/BibEmail/SendBibEmailMessage.php`
- `src/Application/Race/BibEmail/SendBibEmailHandler.php`
- `src/Application/Race/BibEmail/ParseBibEmailCsv.php`
- `src/Application/Race/BibEmail/BibEmailRecipientDto.php`

#### Infrastructure
- `src/Infrastructure/Persistence/Doctrine/Repository/DoctrineEmailSendLogRepository.php`
- `src/Infrastructure/Persistence/Doctrine/Mapper/EmailSendLogMapper.php`
- `src/Infrastructure/Http/Controller/Api/Admin/AdminBibEmailController.php`
  - `POST /api/v1/admin/bib-emails/preview` — parsea CSV; acepta `editionId`.
  - `POST /api/v1/admin/bib-emails/send` — encola emails; acepta `editionId`, `force`, `items`.
  - `POST /api/v1/admin/bib-emails/run` — lanza `app:bib-emails:send` en segundo plano, pasando el `userId` del admin autenticado.
  - `GET /api/v1/admin/bib-emails` — listado filtrado por `editionId`.
  - `GET /api/v1/admin/bib-emails/sent-counts` — devuelve conteo de envíos por email agrupado por edición (opcional `editionId`).
- `src/Command/SendPendingBibEmailsCommand.php` — comando para enviar logs `pending`; acepta `--edition-id`, `--delay`, `--limit`, `--user-id`.

#### Plantilla y Mailer
- `templates/emails/bib_assigned.html.twig`
- Variables en `.env.example`:
  - `MAILER_DSN` (Gmail SMTP con SSL: `smtps://cokalbarunning@gmail.com:PASSWORD@smtp.gmail.com:465`)
  - `MAILER_SENDER_EMAIL`
  - `BIB_EMAIL_DELAY_SECONDS`

### Frontend

- `frontend/src/views/admin/AdminBibEmailsView.vue` — subida CSV, selector de edición, preview, envío, historial, estadísticas, reenvío individual y masivo.
- `frontend/src/router/index.ts` — ruta `/admin/bib-emails` (requiresAuth + requiresAdmin).
- `frontend/src/views/admin/AdminDashboardView.vue` — enlace "Envio de Dorsales".
- `frontend/src/services/api.service.ts` — se eliminó la cabecera global `Content-Type` para permitir subida de archivos.

---

## Configuración

### Variables de entorno (.env)

```env
# Mailer (Gmail SMTP con SSL en puerto 465)
MAILER_DSN=smtps://cokalbarunning@gmail.com:CONTRASEÑA@smtp.gmail.com:465
MAILER_SENDER_EMAIL=cokalbarunning@gmail.com

# Delay entre envíos (segundos)
BIB_EMAIL_DELAY_SECONDS=3
```

En desarrollo se puede usar `MAILER_DSN=null://default` para simular envíos sin llegar al destinatario. En tests se fuerza `MAILER_DSN=null://null`.

### Envío de emails

#### Opción recomendada: botón en el panel
1. Sube el CSV y pulsa **"Enviar"**. Los emails quedan en estado `pending`.
2. Pulsa **"Ejecutar envíos pendientes"**. El backend lanza el comando en segundo plano:
   ```bash
   php bin/console app:bib-emails:send --delay=3
   ```
3. El comando envía los emails uno a uno con un delay de 3 segundos y actualiza el estado.

#### Comando manual (alternativa)
```bash
docker compose exec cokalbarunning-backend php bin/console app:bib-emails:send --delay=3 --edition-id=UUID
```

Parámetros:
- `--edition-id`: filtra por edición.
- `--delay`: segundos entre envíos (por defecto 3).
- `--limit`: máximo de emails a enviar (por defecto sin límite).
- `--user-id`: UUID del admin que ejecuta el envío (se guarda en `sent_by`).

---

## Cómo verificar envíos

### Frontend
- Panel **Historial de envíos** agrupado por edición + nombre + dorsal + email:
  - Último estado y última fecha de envío.
  - Contador de envíos dentro de la edición seleccionada.
  - Columna "Enviado por" con el email del admin del último envío.
  - Estadísticas globales: Total / Enviados / Pendientes / Errores.

### Base de datos
```bash
docker exec shared-postgres-db psql -U cokalba -d cokalba_running -c "
  SELECT recipient_email, bib_number, status, sent_at, sent_by
  FROM email_send_logs
  ORDER BY created_at DESC
  LIMIT 10;
"
```

### Logs del comando
El comando escribe su salida en `/var/www/backend/var/log/bib-emails-runner.log` (cuando se lanza desde el panel) o en consola si se ejecuta manualmente. Con `BIB_EMAIL_DELAY_SECONDS=3` se aprecia el retardo entre envíos.

---

## Seguridad

- La contraseña de Gmail está en `.env` en texto plano. Proteger el archivo:
  ```bash
  chmod 600 .env
  ```
- No subir `.env` a Git (ya está en `.gitignore`).
- En producción usar **contraseña de aplicación** de Gmail en lugar de la contraseña principal.

---

## Tareas

### Backend
- [x] **B1** Crear entidad ORM `EmailSendLog` y migraciones.
- [x] **B2** Crear domain entity, value object `EmailStatus`, repository interface.
- [x] **B3** Crear `DoctrineEmailSendLogRepository` + alias en `services.yaml`.
- [x] **B4** Crear commands/handlers para crear/actualizar/listar `EmailSendLog`.
- [x] **B5** Crear `ParseBibEmailCsv` y `BibEmailRecipientDto`.
- [x] **B6** Crear plantilla Twig `templates/emails/bib_assigned.html.twig`.
- [x] **B7** Crear `SendBibEmailMessage` + `SendBibEmailHandler` (legacy; ya no se usa para el envío anual).
- [x] **B8** Configurar transporte Messenger con Redis (solo para otros mensajes; el mailer es sincrónico).
- [x] **B9** Crear `AdminBibEmailController` con endpoints preview/send/list/run/sent-counts.
- [x] **B10** Añadir variables de mailer/delay a `.env.example`.
- [x] **B11** Tests backend (134 tests verdes).
- [x] **B12** Añadir `sent_by` y delay entre envíos.
- [x] **B13** Crear comando CLI `app:bib-emails:send` con `--edition-id`, `--delay`, `--limit`, `--user-id`.
- [x] **B14** Endpoint `/bib-emails/sent-counts` para conteo global de envíos por email.

### Frontend
- [x] **F1** Crear `AdminBibEmailsView.vue`.
- [x] **F2** Añadir ruta `/admin/bib-emails`.
- [x] **F3** Añadir enlace en dashboard.
- [x] **F4** `vue-tsc` y build verdes.
- [x] **F5** Selector de edición.
- [x] **F6** Estadísticas, contador individual y columna "Enviado por".
- [x] **F7** Reenvío individual y masivo.
- [x] **F8** Consumir endpoint `/bib-emails/sent-counts` para contador global de envíos.

### Documentación
- [x] **D1** Crear `docs/tracking/15_BIB_EMAILS.md`.
- [x] **D2** Actualizar `docs/tracking/05_PROGRESO.md`.

---

## Notas

- No se modificaron las entidades `Runner`, `Result` ni `RaceEdition`.
- El envío queda en estado `pending` hasta que el comando CLI `app:bib-emails:send` lo procese.
- El comando se lanza desde el panel con `nohup` en segundo plano.
- Si el admin pulsa "Enviar" y algunos registros ya están `sent`, se omiten a menos que se marque `force=true`.
- Se corrigió `AdminClubMemberControllerTest::testCreateMemberWithUserId` para buscar el miembro recién creado por ID y evitar fallos por datos residuales en la BD de test.
- Se forzó `MESSENGER_TRANSPORT_DSN=sync://`, `MAILER_DSN=null://null` y `BIB_EMAIL_DELAY_SECONDS=0` en `tests/bootstrap.php` para que los tests funcionales sean deterministas.
- Se corrigió `api.service.ts` eliminando la cabecera global `Content-Type`, para que Axios genere automáticamente `multipart/form-data` con boundary al subir archivos.
- Se añadió soporte BOM en `ParseBibEmailCsv` para CSV exportados desde Excel.
