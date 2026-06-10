# Tracking: Publicación Automática de Noticias en Redes Sociales (vía n8n)

## Contexto

Se requiere automatizar la publicación de noticias del blog de Cokalba Running en redes sociales, comenzando por Instagram, utilizando un webhook de n8n como orquestador.

---

## 1. Arquitectura y Decisiones de Diseño

### 1.1 Separación de responsabilidades (CQRS + DDD-lite)

Siguiendo el patrón existente en el proyecto:

| Capa | Responsabilidad |
|------|----------------|
| **Domain** | Entidad `SocialPublishLog`, contratos de repositorio y puertos |
| **Application** | Comandos, handlers y DTOs de respuesta |
| **Infrastructure** | Controladores HTTP, adaptadores externos (n8n), mapeadores Doctrine |

### 1.2 Entidad escalable para múltiples redes

En lugar de un campo JSON en `BlogPost`, se crea una **entidad independiente** `SocialPublishLog` con los siguientes campos:

- `id` (UUID)
- `post_id` (GUID → BlogPost)
- `network` (string, ej: 'instagram', 'facebook', 'twitter')
- `status` (string: 'pending', 'published', 'failed')
- `published_at` (datetime nullable)
- `external_url` (string nullable, enlace a la publicación en la red)
- `created_at` (datetime)

**Ventajas:**
- Historial completo de publicaciones por post
- Soporte nativo para futuras redes sociales sin modificar `BlogPost`
- Posibilidad de reintentos, trazabilidad y auditoría

### 1.3 Flujo de datos (Diagrama)

```
┌─────────────────┐     POST /admin/posts/{id}/publish-instagram      ┌──────────────────┐
│  AdminPostsView │ ──────────────────────────────────────────────────→│ AdminBlogController│
│   (Vue frontend)│                                                    │    (Symfony)       │
└─────────────────┘                                                    └────────┬─────────┘
                                                                                │
                                                                                │ dispatch
                                                                                ▼
                                                                       ┌──────────────────┐
                                                                       │PublishToNetwork  │
                                                                       │    Handler       │
                                                                       └────────┬─────────┘
                                                                                │
                                                                                │ 1. Busca post
                                                                                │ 2. Crea log (pending)
                                                                                │ 3. Llama a N8nSocialPublisher
                                                                                ▼
                                                                       ┌──────────────────┐
                                                                       │  N8nSocialPublisher│
                                                                       │   (HttpClient)    │
                                                                       └────────┬─────────┘
                                                                                │ POST JSON
                                                                                ▼
                                                                       ┌──────────────────┐
                                                                       │  n8n Webhook      │
                                                                       │ publicar-noticia  │
                                                                       └────────┬─────────┘
                                                                                │
                                                                                │ Publica en IG
                                                                                ▼
                                                                       ┌──────────────────┐
                                                                       │  POST /webhook/   │
                                                                       │  n8n/update-status│
                                                                       │  (Callback API)   │
                                                                       └──────────────────┘
```

### 1.4 Seguridad

- **Webhook n8n (salida)**: Cabecera `X-Autenticacion-Cokalba` con token configurable vía env var
- **Callback n8n (entrada)**: Cabecera `X-N8N-Callback-Token` con token diferente, configurable vía env var
- **Rutas admin**: Protegidas por JWT + ROLE_EDITOR (heredado del firewall existente)
- **Ruta callback**: Excepción en `security.yaml` para permitir acceso público con validación manual del token

---

## 2. Archivos a Crear / Modificar

### Backend — Nuevos archivos

| # | Ruta | Descripción |
|---|------|-------------|
| 1 | `src/Domain/SocialPublishing/Entity/SocialPublishLog.php` | Entidad de dominio pura |
| 2 | `src/Domain/SocialPublishing/Repository/SocialPublishLogRepositoryInterface.php` | Contrato del repositorio |
| 3 | `src/Domain/SocialPublishing/Port/SocialPublisherPort.php` | Puerta de salida para publicadores externos |
| 4 | `src/Domain/SocialPublishing/Exception/SocialPublishingException.php` | Excepciones de dominio |
| 5 | `src/Application/SocialPublishing/Publish/PublishToNetworkCommand.php` | Comando para publicar en red |
| 6 | `src/Application/SocialPublishing/Publish/PublishToNetworkHandler.php` | Handler del comando anterior |
| 7 | `src/Application/SocialPublishing/UpdateStatus/UpdateSocialPublishStatusCommand.php` | Comando para actualizar estado desde callback |
| 8 | `src/Application/SocialPublishing/UpdateStatus/UpdateSocialPublishStatusHandler.php` | Handler del callback |
| 9 | `src/Application/SocialPublishing/Response/SocialPublishLogResponseDto.php` | DTO de respuesta para la API |
| 10 | `src/Entity/SocialPublishLog.php` | Entidad ORM Doctrine |
| 11 | `src/Infrastructure/Persistence/Doctrine/Mapper/SocialPublishLogMapper.php` | Mapper domain ↔ ORM |
| 12 | `src/Infrastructure/Persistence/Doctrine/Repository/DoctrineSocialPublishLogRepository.php` | Implementación del repositorio |
| 13 | `src/Infrastructure/SocialPublishing/N8n/N8nSocialPublisher.php` | Adaptador HttpClient para n8n |
| 14 | `src/Infrastructure/Http/Controller/Api/Webhook/N8nWebhookController.php` | Controlador del callback de n8n |
| 15 | `migrations/Version20260605XXXXXX.php` | Migración para crear `social_publish_logs` |

### Backend — Archivos a modificar

| # | Ruta | Cambio |
|---|------|--------|
| 16 | `config/services.yaml` | Alias del puerto, repositorio y env vars del webhook |
| 17 | `config/packages/security.yaml` | Excepción pública para `^/api/v1/webhook/n8n` |
| 18 | `src/Infrastructure/Http/Controller/Api/Admin/AdminBlogController.php` | Nuevo método `POST /admin/posts/{id}/publish-instagram` |

### Frontend — Archivos a modificar

| # | Ruta | Cambio |
|---|------|--------|
| 19 | `src/views/admin/AdminPostsView.vue` | Botón "Instagram" con estado visual; interfaz `SocialPublishLog`; fetch de logs |

---

## 3. Contratos de API

### 3.1 Publicar en Instagram (Admin → Backend → n8n)

**Request:**
```http
POST /api/v1/admin/posts/{id}/publish-instagram
Authorization: Bearer <jwt>
```

**Response 200:**
```json
{
  "data": {
    "logId": "uuid",
    "status": "pending"
  }
}
```

**Response 409 (ya publicado):**
```json
{
  "error": "Este post ya ha sido publicado en \"instagram\"."
}
```

### 3.2 Payload enviado a n8n

```json
{
  "id": "post-uuid",
  "titulo": "Título del post",
  "texto": "Resumen del post",
  "url_noticia": "https://cokalba-running.com/blog/slug-del-post",
  "url_imagen": "https://cdn.cokalba-running.com/blog/cover.jpg",
  "network": "instagram",
  "log_id": "log-uuid"
}
```

### 3.3 Callback de n8n (n8n → Backend)

**Request:**
```http
POST /api/v1/webhook/n8n/update-status-publish
X-N8N-Callback-Token: <token-secreto>
Content-Type: application/json

{
  "logId": "log-uuid",
  "status": "published",
  "externalUrl": "https://instagram.com/p/ABC123"
}
```

**Response 200:**
```json
{
  "data": { "updated": true }
}
```

### 3.4 Listar publicaciones sociales (Frontend → Backend)

**Request:**
```http
GET /api/v1/admin/social-publishes
Authorization: Bearer <jwt>
```

**Response 200:**
```json
{
  "data": [
    {
      "id": "log-uuid",
      "postId": "post-uuid",
      "network": "instagram",
      "status": "published",
      "publishedAt": "2026-06-05 14:30:00",
      "externalUrl": "https://instagram.com/p/ABC123"
    }
  ]
}
```

---

## 4. Variables de Entorno Necesarias

Añadir al `.env` y `.env.example` del backend:

```env
# n8n Webhook (salida)
N8N_WEBHOOK_URL=https://n8n.dulziasalamanca.es/webhook/publicar-noticia
N8N_WEBHOOK_AUTH_HEADER=X-Autenticacion-Cokalba
N8N_WEBHOOK_AUTH_TOKEN=zS6FU4qWy4UkF5d9sNpgiSg5usTZoXtkyCJjYHiA1XRFVOzo

# n8n Callback (entrada)
N8N_CALLBACK_TOKEN=cambiar_por_token_secreto_aleatorio

# URL pública del sitio (para construir url_noticia)
APP_PUBLIC_URL=https://cokalba-running.com
```

---

## 5. Consideraciones de UI/UX (Frontend)

- **Botón "Instagram"** en cada fila de la tabla de posts (columna de acciones), junto a "Editar" y "Eliminar"
- **Estados visuales:**
  - Normal (gris/rosa): Aún no publicado → clic para publicar
  - Publicado (verde): Ya está en Instagram → botón deshabilitado con badge/check
  - Pendiente (amarillo): Envío en curso → texto "Publicando..."
- **Confirmación:** `confirm('¿Publicar esta noticia en Instagram?')` para evitar clics accidentales
- **Optimistic update:** El frontend puede marcar como "pendiente" inmediatamente tras el envío, y refrescar el estado al recargar

---

## 6. Futuras Extensiones (manteniendo la arquitectura)

- Añadir `facebook`, `twitter`, `linkedin` como valores válidos de `network`
- Crear un endpoint `POST /admin/posts/{id}/publish/{network}` genérico
- Programar publicaciones futuras añadiendo `scheduled_at` a `SocialPublishLog`
- Reintentos automáticos con contador `retry_count`

---

## 7. Checklist de Implementación

- [x] Crear entidad de dominio `SocialPublishLog`
- [x] Crear entidad ORM y migración
- [x] Crear repositorio + mapper
- [x] Crear puerto + adaptador N8n
- [x] Crear comandos y handlers
- [x] Crear DTO de respuesta
- [x] Modificar `AdminBlogController` (endpoint publish + list social publishes)
- [x] Crear `N8nWebhookController` (endpoint callback)
- [x] Configurar `services.yaml` y `security.yaml`
- [x] Actualizar `AdminPostsView.vue`
- [x] Probar flujo completo localmente

## 8. Estado Actual (08/06/2026)

### ✅ Funciona
- Listado de posts con botón "Instagram"
- Creación de registro `pending` en `social_publish_logs`
- Envío de payload a n8n (sin autenticación)
- Reutilización de logs fallidos (evita duplicate key)
- Endpoint de callback `/webhook/n8n/update-status-publish`

### ⚠️ Pendiente / En pruebas
- **Autenticación con n8n**: El token con `ñ` da problemas. Se sugiere cambiar a un token alfanumérico sin caracteres especiales.
- **URL de producción**: Actualmente usa `/webhook-test/` (modo escucha del editor). Para producción real, cambiar a `/webhook/`.
- **Callback de n8n**: Configurar en n8n para que, tras publicar con éxito, llame a `POST /api/v1/webhook/n8n/update-status-publish` con header `X-N8N-Callback-Token`.

### 🔧 Deploy a producción
1. Asegurar variables de entorno en `.env` del servidor:
   ```env
   N8N_WEBHOOK_URL=https://n8n.dulziasalamanca.es/webhook/publicar-noticia
   N8N_WEBHOOK_AUTH_HEADER=X-Autenticacion-Cokalba
   N8N_WEBHOOK_AUTH_TOKEN=<token-sin-caracteres-especiales>
   N8N_CALLBACK_TOKEN=<token-secreto-aleatorio>
   APP_PUBLIC_URL=https://cokalba-running.com
   ```
2. `make prod-up`
3. `php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260606000000' --no-interaction`
4. `php bin/console cache:clear` (como www-data)
