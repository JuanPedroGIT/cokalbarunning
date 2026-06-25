# Nuevos tipos de correo: Sorteo y Últimas Indicaciones

> **Tracking:** Extender el sistema de envío de correos masivos (actualmente "info dorsal") para soportar dos nuevos tipos: **sorteo** y **últimas indicaciones**.
> **Fecha de inicio:** 2026-06-24
> **Estado:** ✅ Completado

---

## Resumen

El sistema de correos masivos se ha generalizado para soportar dos envíos operativos a los participantes:

1. **Sorteo** — informar a cada participante de su número de participación en el sorteo (su **dorsal**), premios, fecha del sorteo, etc.
2. **Últimas indicaciones** — enviar horarios, punto de encuentro, recomendaciones, etc., antes de la carrera.

Ambos usan el **mismo CSV de inscritos** (`Inscritos_Coca_de_alba_25.csv`). Al enviar el sorteo se crean/actualizan los `runners` y, además, se encolan automáticamente las últimas indicaciones para los mismos destinatarios. El envío de dorsales (`bib`) se mantiene solo como tipo legacy en el modelo; el frontend ya no lo expone como pestaña.

---

## Archivos fuente revisados

### CSV de inscritos / dorsales / últimas indicaciones

**Archivo:** `C:\Users\juanp\Documents\apps\envio-correos\Inscritos_Coca_de_alba_25.csv`

**Cabeceras:** `Dorsal;Nombre;Apellidos;Sexo;email;NIF_Pasaporte;Fecha Nacimiento;Club;Talla Camiseta;Local;Año;Años;CATEGORIA`

**Ejemplo de fila:**
```
1;VERONICA ;SANCHEZ ROMERO ;MUJER;salumvi@gmail.com;08112256H;08_08_1975;VINO DE TORO ;S;NO;1975;50;VETERANO A
```

**Campos que coinciden con la tabla `runners`:**
- `Nombre` + `Apellidos` → `first_name` + `last_name`
- `email` → `email`
- `Dorsal` → `bib_number`
- `Club` → `club`
- `Fecha Nacimiento` → `birth_date`
- `Sexo` (MUJER/HOMBRE) → `gender` (`F`/`M`)

**No se crearán nuevos campos en `runners`.** Los campos que no coincidan (`NIF_Pasaporte`, `Talla Camiseta`, `Local`, `Año`, `Años`, `CATEGORIA`) se usarán solo en el email de dorsales si es necesario, pero no se persistirán.

### CSV de sorteo (obsoleto)

**Archivo original:** `C:\Users\juanp\Documents\apps\envio-correos\sorteo-2026.csv`

**Nota:** Finalmente el sorteo usa el **mismo CSV de inscritos** (`Inscritos_Coca_de_alba_25.csv`). El dorsal de cada inscrito es su número de participación en el sorteo.

### PDF de últimas indicaciones

**Archivo:** `C:\Users\juanp\Documents\apps\envio-correos\Últimas_Instrucciones_VII_CARRERA_SOLIDARIA_UN_NUEVO_IMPULSO.pdf`

**Contenido extraído:**

> ULTIMAS INSTRUCCIONES VII CARRERA SOLIDARIA UN NUEVO IMPULSO
>
> 1. La carrera Absoluta dará comienzo a las 9:30 hora peninsular. Las carreras de promoción (los niños) empezarán a las 11:00 de la mañana en el siguiente orden y distancias:
>    - Infantiles y Cadetes. Salen Juntos. Circuito de 1.950 metros
>    - Chupetines 140 metros.
>    - Prebenjamines: Circuito de 315 metros. 1 vuelta al circuito.
>    - Benjamines y Alevines. Salen Juntos: Circuito de 600 metros. 2 vueltas al circuito.
> 2. Los dorsales se comenzarán a repartir a las 8:00 de la mañana. No vengáis muy tarde que se preparan colas como en el Super. Imprescindible justificante de inscripción o DNI.
> 3. Habrá cerveza, limón para la cerveza con limón, agua y fruta en meta.
> 4. Hay una ducha en el lugar donde se recogen los dorsales para que la podáis utilizar. 2 minutos por corredor y antes de que terminen las carreras de promoción estaréis todos limpitos.
> 5. Una vez terminen las carreras de promoción, alrededor de las 12.00 nos iremos a la entrega de trofeos donde os ofreceremos un ágape con bebida fresquita para los corredores.
> 6. Hay sorteo de premios, bastantes, al finalizar la entrega de trofeos, con el dorsal.
> 7. La entrega de trofeos a los campeones, osea los 3 mejores corredores de cada categoría se llevará a cabo empezando por los Benjamines que son la primera categoría competitiva, aunque a los chupetines y prebenjamines les daremos una medalla y subirán al Podium.
> 8. Venid con ganas de disfrutar, os queremos a todos en meta después de correr las 9735 Varas castellanas, que se dice pronto.
> 9. Habrá premio especial para el que bata el record masculino o femenino de la prueba. Una paleta que entregaremos a domicilio. Claro no sabemos si se va a batir, jejejejje. El record masculino está en 27:48 y el femenino en 32:32.
>
> Puntos importantes de la carrera:
> - Local entrega de dorsales y ducha.
> - Salida y meta
> - Entrega de trofeos
> - Aparcamiento

**Nota:** La plantilla usará la fecha de la carrera desde la edición activa (`editionDate`), no hardcodeada.

---

## Decisiones técnicas

### DEC-29: ¿Cómo modelar el tipo de correo?

**Decisión:** Añadir campo `type` (string) a `EmailSendLog` con valores `bib`, `raffle`, `last_instructions`. Default `'bib'` para registros existentes.

**Impacto:**
- Migración para añadir columna `type`.
- Actualizar Domain entity, ORM entity, mapper, DTO, handlers, repositorios y tests.

---

### DEC-30: ¿Cómo manejar la referencia del destinatario?

**Decisión:** Renombrar `bibNumber` → `reference` (nullable). Tanto en sorteo como en últimas indicaciones el `reference` es el **dorsal** del inscrito.

**Impacto:**
- Migración para renombrar columna.
- Actualizar todos los usos actuales de `bibNumber`.

---

### DEC-31: ¿Un comando CLI por tipo o uno genérico?

**Decisión:** Un único comando `app:emails:send` con opción `--type`. El comando delega en un `EmailTemplateResolver` que resuelve plantilla + asunto según el tipo.

**Impacto:**
- Reemplazar `SendPendingBibEmailsCommand`.
- Crear `EmailTemplateResolver`.

### DEC-39: ¿Transporte de envío: Brevo o Symfony Mailer (`MAILER_DSN`)?

**Decisión inicial (2026-06-24):** Usar `MailerInterface` con `MAILER_DSN` para enviar mediante Symfony Mailer.

**Decisión final (2026-06-25):** Volver a `BrevoMailer` para enviar directamente por la API REST de Brevo, como funcionaba el envío de dorsales originalmente. `MAILER_DSN` sigue disponible para otros usos si es necesario, pero el comando `app:emails:send` usa `BrevoMailer`.

**Impacto:**
- `SendPendingEmailsCommand` recibe `BrevoMailer` en lugar de `MailerInterface`.
- Se mantiene `BREVO_API_KEY`, `MAILER_SENDER_EMAIL` y `MAILER_SENDER_NAME` como variables necesarias.

---

### DEC-32: ¿Endpoints separados o generalizar `/bib-emails`?

**Decisión:** Generalizar a `/admin/emails/{type}/...`. Mantener `/admin/bib-emails/*` como alias legacy temporal.

---

### DEC-33: ¿Misma vista admin con pestañas o vistas separadas?

**Decisión:** Convertir `AdminBibEmailsView.vue` en `AdminEmailsView.vue` con pestañas.

---

### DEC-34: ¿Formato del CSV por tipo?

**Decisión:** Todos los tipos usan el **mismo CSV único de inscritos** con cabeceras `Dorsal;Nombre;Apellidos;Sexo;email;...`. El tipo lo decide el endpoint, no el contenido del archivo. El dorsal se usa como referencia tanto en sorteo como en indicaciones.

---

### DEC-35: ¿Configuración del sorteo?

**Decisión:** El sorteo es configurable por edición. El frontend enviará en el request:
- `subject` (asunto parametrizable)
- `title` (título del sorteo)
- `description` (descripción/bases)
- `prize` (premio)
- `drawDate` (fecha del sorteo)

Estos datos se almacenarán temporalmente en `EmailSendLog` mediante un campo `metadata` JSON, o se enviarán directamente al comando CLI.

**Opción elegida:** Añadir campo `metadata` JSON nullable a `EmailSendLog` para guardar la configuración de la campaña. Así el comando CLI puede recuperarla al enviar.

**Impacto:**
- Migración para añadir `metadata` (JSON nullable).
- Actualizar entidad, mapper, DTO.

---

### DEC-37: ¿Asunto del sorteo parametrizable?

**Decisión:** El asunto del correo de sorteo se configura libremente desde el admin y admite placeholders. El comando reemplaza `{title}`, `{drawDate}`, `{prize}`, `{description}`, `{editionName}` y `{reference}` al renderizar el asunto. Si el asunto configurado está vacío, se usa un asunto por defecto (`Sorteo {title} - {prize}`).

**Impacto:**
- `EmailTemplateResolver` devuelve asunto por defecto con placeholders directos.
- `SendPendingEmailsCommand` aplica el reemplazo de placeholders antes de enviar.
- Frontend: campo "Asunto del correo" en la configuración del sorteo con hint de variables disponibles.

### DEC-38: ¿Foto del premio en el correo de sorteo?

**Decisión:** La foto del premio se sube desde el panel de administración y se guarda en `RaffleConfig.prizeImageUrl` (path relativo en R2). Al enviar el sorteo, el comando carga la URL pública desde la configuración si el log no tiene metadata propia, y la plantilla `raffle.html.twig` la muestra junto a los detalles del premio.

**Impacto:**
- Nuevo endpoint `POST /api/v1/admin/emails/raffle/prize-image`.
- `SendPendingEmailsCommand::resolveMetadata` incluye `prizeImageUrl`.
- Plantilla `raffle.html.twig` renderiza `<img>` condicional.
- `AdminEmailsView.vue` permite subir, previsualizar y eliminar la imagen.

---

### DEC-36: ¿Se guardan runners en últimas indicaciones y sorteo?

**Decisión:**
- `raffle`: sí, se crean/actualizan registros en `runners`.
- `last_instructions`: sí, se crean/actualizan registros en `runners`.
- `bib`: ya no se usa como envío de correo; se mantiene en el modelo por compatibilidad histórica.

---

## Estructura de implementación

### Backend

#### ORM y migraciones

- `src/Entity/EmailSendLog.php`
  - Añadir `type` (string 20, default `'bib'`).
  - Renombrar `bibNumber` → `reference` (string 50, nullable).
  - Añadir `metadata` (json, nullable).
- Migraciones Doctrine:
  - `Version20260624120000` — añadir `type` a `email_send_logs`.
  - `Version20260624130000` — renombrar `bib_number` a `reference` y hacerlo nullable.
  - `Version20260624140000` — añadir `metadata` JSON nullable.

#### Domain

- `src/Domain/Notification/Entity/EmailSendLog.php`
  - Propiedades `type`, `reference`, `metadata`.
- `src/Domain/Notification/ValueObject/EmailType.php`
  - `BIB = 'bib'`, `RAFFLE = 'raffle'`, `LAST_INSTRUCTIONS = 'last_instructions'`.
- `src/Domain/Notification/Repository/EmailSendLogRepositoryInterface.php`
  - Añadir `findByTypeAndRaceEditionId`, `findByEmailTypeAndReference`.

#### Application

- `src/Application/Notification/Command/CreateEmailSendLogCommand.php`
  - Añadir `type` y `metadata`; cambiar `bibNumber` → `reference`.
- `src/Application/Notification/Command/CreateEmailSendLogHandler.php`
  - Usar nuevo constructor.
- `src/Application/Notification/Response/EmailSendLogResponseDto.php`
  - Incluir `type`, `reference`, `metadata`.
- `src/Application/Notification/Query/GetEmailSendLogsQuery.php` + Handler
  - Filtrar por `type`.
- `src/Application/Race/BibEmail/ParseBibEmailCsv.php` → `ParseEmailCsv.php`
  - Parsear siempre el CSV único de inscritos.
- `src/Application/Race/BibEmail/BibEmailRecipientDto.php` → `EmailRecipientDto.php`
  - Campos: `firstName`, `lastName`, `email`, `reference`, `club`, `gender`, `birthDate`, `category`, `shirtSize`, `emailValid`.

#### Infrastructure

- `src/Infrastructure/Persistence/Doctrine/Mapper/EmailSendLogMapper.php`
  - Mapear `type`, `reference`, `metadata`.
- `src/Infrastructure/Persistence/Doctrine/Repository/DoctrineEmailSendLogRepository.php`
  - Implementar nuevos métodos de filtro.
- `src/Infrastructure/Http/Controller/Api/Admin/AdminEmailController.php`
  - `GET /api/v1/admin/emails/{type}`
  - `POST /api/v1/admin/emails/{type}/preview` (también crea/actualiza runners)
  - `POST /api/v1/admin/emails/{type}/send`
  - `POST /api/v1/admin/emails/{type}/run`
  - `GET /api/v1/admin/emails/{type}/sent-counts`
  - `GET /api/v1/admin/emails/raffle/config`
  - `POST /api/v1/admin/emails/raffle/config`
  - `PUT /api/v1/admin/emails/raffle/config/{id}`
- `src/Entity/RaffleConfig.php`
  - Persistencia de la configuración del sorteo por edición.
- `src/Repository/RaffleConfigRepository.php`
  - Buscar/guardar configuración del sorteo.
- `src/Command/SendPendingEmailsCommand.php`
  - Usa `MailerInterface` con `MAILER_DSN`.
  - Para sorteos sin metadata, carga la configuración desde `raffle_configs`.
- `src/Infrastructure/Mail/EmailTemplateResolver.php`
  - Resolver plantilla y asunto según tipo. Asunto por defecto del sorteo: `Sorteo {title} - {prize}`.
- `src/Command/SendPendingEmailsCommand.php`
  - Opción `--type`; filtrar por tipo; renderizar plantilla correcta.
  - Renderiza el asunto reemplazando placeholders directos (`{title}`, `{drawDate}`, `{prize}`, `{description}`) y variables del template (`{editionName}`, `{reference}`, `{nombre}`, etc.).
  - Si el log de sorteo no tiene metadata, carga la configuración desde `raffle_configs`.

#### Plantillas Twig

- `templates/emails/bib_assigned.html.twig` — adaptada con más datos del CSV (club, categoría, talla camiseta).
- `templates/emails/last_instructions.html.twig` — basada en el PDF, con fecha de edición activa.
- `templates/emails/raffle.html.twig` — reorganizada para mostrar de forma clara: saludo personalizado, título del sorteo, número de participación (dorsal), premio, fecha del sorteo, fecha y lugar del evento, y bases del sorteo.

### Frontend

- `frontend/src/views/admin/AdminEmailsView.vue` (reemplazo de `AdminBibEmailsView.vue`)
  - Carga única del CSV de inscritos y selector de edición en la parte superior.
  - Pestañas "1. Sorteo" y "2. Últimas Indicaciones" debajo de la carga.
  - Al cargar el CSV se crean/actualizan runners automáticamente.
  - Formulario de configuración del sorteo (asunto, título, descripción, premio, fecha) con guardado/actualización.
  - El campo de asunto muestra un hint con las variables disponibles: `{title}`, `{drawDate}`, `{prize}`, `{description}`, `{editionName}`, `{reference}`.
  - Al enviar sorteo se encolan automáticamente las últimas indicaciones.
  - Historial filtrado por tipo.
  - Refresco automático de logs tras ejecutar envíos pendientes.
- `frontend/src/router/index.ts`
  - Ruta `/admin/emails`.
- `frontend/src/views/admin/AdminDashboardView.vue`
  - Actualizar enlace.

---

## Verificación / QA

- `phpunit` verde.
- `vue-tsc --build --force` sin errores.
- Migraciones aplicadas en desarrollo.
- `doctrine:schema:validate` sin discrepancias.
- Flujo de sorteo: subir CSV de inscritos → se crean runners → configurar/guardar sorteo (asunto, título, premio, fecha, bases) → enviar → se encolan sorteo + indicaciones → ejecutar pendientes.
- Flujo de últimas indicaciones manual: subir CSV de inscritos → se crean runners → enviar → ejecutar pendientes.
- Configuración del sorteo persistente: crear, editar y reutilizar antes del envío masivo.
- Asunto del sorteo parametrizable: verificar que `{title}`, `{prize}`, `{drawDate}`, etc. se reemplazan correctamente.
- Imagen del premio: subir desde admin, guardar en `RaffleConfig`, comprobar que aparece en el email enviado.
- Envío de emails a través de `BrevoMailer` (API REST de Brevo).

---

## Tareas

### Backend

- [x] **B1** Migración: añadir `type` a `email_send_logs`.
- [x] **B2** Migración: renombrar `bib_number` → `reference` (nullable).
- [x] **B3** Migración: añadir `metadata` JSON nullable.
- [x] **B4** Domain `EmailSendLog`: propiedades `type`, `reference`, `metadata`.
- [x] **B5** Crear `EmailType` ValueObject.
- [x] **B6** Actualizar `EmailSendLog` ORM y mapper.
- [x] **B7** Actualizar `CreateEmailSendLogCommand` + Handler.
- [x] **B8** Actualizar `EmailSendLogResponseDto`.
- [x] **B9** Renombrar `ParseBibEmailCsv` → `ParseEmailCsv` y `BibEmailRecipientDto` → `EmailRecipientDto`.
- [x] **B10** Actualizar `GetEmailSendLogsQuery` + Handler para filtrar por tipo.
- [x] **B11** Actualizar `EmailSendLogRepositoryInterface` y `DoctrineEmailSendLogRepository`.
- [x] **B12** Crear `EmailTemplateResolver`.
- [x] **B13** Crear plantillas Twig `last_instructions.html.twig` y `raffle.html.twig`; adaptar `bib_assigned.html.twig`.
- [x] **B14** Crear/reemplazar `SendPendingEmailsCommand` con `--type`.
- [x] **B15** Crear `AdminEmailController` con endpoints `/admin/emails/{type}/...` y `/admin/emails/raffle/config`.
- [x] **B16** Entidad y repositorio `RaffleConfig`.
- [x] **B17** Crear runners al cargar CSV en `/preview`.
- [x] **B18** Cambiar `SendPendingEmailsCommand` a `MailerInterface` (`MAILER_DSN`); `BrevoMailer` conservado.
- [x] **B18b** Revertir B18: `SendPendingEmailsCommand` vuelve a usar `BrevoMailer` para envío por API REST de Brevo.
- [x] **B19** Cargar configuración de sorteo desde `raffle_configs` cuando el log no tiene metadata.
- [x] **B20** Actualizar `services.yaml`.
- [x] **B21** Tests backend verdes.
- [x] **B22** Asunto parametrizable del sorteo con placeholders `{title}`, `{drawDate}`, `{prize}`, `{description}`.
- [x] **B23** Reorganizar plantilla `raffle.html.twig`: dorsal como número de participación y datos del sorteo visibles.
- [x] **B24** Endpoint `POST /admin/emails/raffle/prize-image` para subir foto del premio a R2.
- [x] **B25** Incluir `prizeImageUrl` en metadata del sorteo y plantilla.

### Frontend

- [x] **F1** Crear `AdminEmailsView.vue` con carga única arriba y pestañas (Sorteo + Últimas Indicaciones) debajo.
- [x] **F2** No limpiar destinatarios al cambiar de pestaña.
- [x] **F3** Añadir ruta `/admin/emails`.
- [x] **F4** Actualizar enlace en dashboard.
- [x] **F5** Actualizar tipos TypeScript.
- [x] **F6** Formulario de configuración del sorteo con guardado/actualización.
- [x] **F7** Cargar configuración del sorteo al entrar en la pestaña o cambiar edición.
- [x] **F8** Refrescar logs automáticamente tras ejecutar envíos pendientes.
- [x] **F9** `vue-tsc` y build verdes.
- [x] **F10** Campo de asunto parametrizable en la configuración del sorteo con hint de variables disponibles.

### Documentación

- [x] **D1** Actualizar `docs/tracking/17_NUEVOS_TIPOS_CORREO.md`.
- [x] **D2** Actualizar `docs/tracking/05_PROGRESO.md`.

---

## Generalización de la tabla de configuración (2026-06-25)

### Objetivo

Unificar la configuración de ambos tipos de correo (sorteo y últimas indicaciones) en una sola tabla `emails_config` con un campo `type` para distinguirlos. Añadir también foto para últimas indicaciones.

### Cambios realizados

#### Base de datos
- **Migración `Version20260625100000`**: renombra `raffle_configs` → `emails_config`, añade columna `type VARCHAR(20) NOT NULL DEFAULT 'raffle'` e índice único `(race_edition_id, type)`.

#### Backend
- **`EmailConfig.php`** (nuevo, reemplaza a `RaffleConfig.php`): entidad ORM con campo `type`, tabla `emails_config`.
- **`EmailConfigRepository.php`** (nuevo, reemplaza a `RaffleConfigRepository.php`): método `findByRaceEditionIdAndType(string $raceEditionId, string $type)`.
- **`AdminEmailController.php`**:
  - Endpoints generalizados: `GET/POST /admin/emails/{type}/config`, `PUT /admin/emails/{type}/config/{id}`, `POST /admin/emails/{type}/prize-image`.
  - `type` acepta `raffle|last_instructions` en todas las rutas de config.
- **`SendPendingEmailsCommand.php`**: `resolveMetadata()` carga config para ambos tipos desde `emails_config`; `prizeImageUrl` incluido en metadata común.
- **`PathGenerator.php`**: `rafflePrizeImagePath($ext)` → `emailImagePath($type, $ext)`, guarda en `un-nuevo-impulso/{type}/random.png`.

#### Frontend
- **`AdminEmailsView.vue`**:
  - Interfaz `RaffleConfig` → `EmailConfigData`.
  - Sección de configuración dinámica: sorteo muestra premio/fecha/foto; últimas indicaciones muestra título/descripción/foto.
  - `ImageDropZone` visible en ambas pestañas.
  - `PreviewItem` incluye `gender` y `birthDate`; el envío no pisa datos de runners.
  - Funciones `loadEmailConfig()`, `saveEmailConfig()`, `uploadPrizeImage()` unificadas.

### Fix: caché de rutas Symfony
El comando `cache:clear` no fue suficiente para regenerar la caché de rutas. La ruta compilada retenía `(raffle)` en lugar de `(raffle|last_instructions)` para `prize-image`. Se solucionó borrando manualmente `var/cache/dev` y regenerando. Sin este fix, `last_instructions/prize-image` devolvía 404 desde el host (Docker Desktop).

### Notas
- La tabla `emails_config` usa unique index en `(race_edition_id, type)` — una config por edición y tipo.
- Los campos `prize`, `drawDate`, `prizeImageUrl` son específicos de sorteo pero existen en la misma tabla (nullable).
- `prizeImageUrl` se incluye en metadata para ambos tipos; la plantilla `last_instructions.html.twig` puede usarlo si se añade el markup.

