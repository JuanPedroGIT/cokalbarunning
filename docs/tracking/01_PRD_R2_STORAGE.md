# PRD: Gestión de Almacenamiento y Galería R2

> **Documento fuente:** `docs/prd_documentos.md`
> **Estado:** Pendiente de implementación

---

## 1. Introducción y Objetivos

El objetivo es definir la arquitectura de almacenamiento, la estructura de persistencia y el comportamiento del frontend para la gestión de recursos multimedia de la plataforma. Todo el contenido estático (imágenes, carteles y documentos PDF) se centralizará en un bucket de Cloudflare R2 para optimizar el rendimiento y costes, manteniendo únicamente las referencias analíticas (nombres de archivo y metadatos) en la base de datos relacional.

---

## 2. Configuración del Almacenamiento (Cloudflare R2)

### 2.1. Credenciales y Endpoint
- **Provider:** Cloudflare R2 (S3 Compatible API)
- **Bucket Name:** `cokalba-running` (variable de entorno)
- **Subdominio público:** No configurado aún. Se usará URL pública de R2 directamente o se configura CNAME posteriormente.

### 2.2. Estructura de Directorios (Árbol de Objetos)

```
cokalba-running/
└── un-nuevo-impulso/
    ├── sponsors/          # Logos de sponsors del club/evento
    ├── docs/                    # Documentos generales (route global, profile general)
    └── race/
        └── {YYYY}/              # Directorio dinámico por año de edición
            ├── docs/            # Cartel oficial, diseño de camisetas, etc.
            ├── results/      # PDFs de clasificaciones y tiempos
            ├── images/            # Fotografías en alta resolución de la carrera
            └── thumbnails/      # Versiones optimizadas (WebP/JPEG ligero) para la galería
```

---

## 3. Modelo de Persistencia (Base de Datos)

**Regla de oro:** Nunca se guardará la URL completa en las tablas. Solo el path relativo, construyendo la URL pública en backend/frontend mediante variables de entorno.

### 3.1. Propuesta de Entidades

#### Tabla: `race_editions` (modificar existente)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `year` | INT | Año de la edición (ej: 2026) |
| `poster_path` | VARCHAR | Ruta relativa del cartel (ej: `race/2026/docs/cartel.webp`) |
| `shirt_path` | VARCHAR | Ruta relativa de la camiseta (ej: `race/2026/docs/camiseta.webp`) |

#### Tabla: `race_documents` (nueva)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `edition_id` | INT (FK) | Relación con la edición (NULL si es documento general común) |
| `name` | VARCHAR | Nombre legible (ej: "Recorrido Niños", "Clasificación General") |
| `type` | ENUM | `['route', 'profile', 'results', 'general', 'other']` |
| `file_path` | VARCHAR | Ruta en el bucket (ej: `race/2026/results/clasificacion.pdf`) |

#### Tabla: `sponsors` (modificar existente)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `edition_id` | INT (FK) | Relación con el año (NULL si es patrocinador general) |
| `name` | VARCHAR | Nombre de la empresa |
| `logo_path` | VARCHAR | Ruta al logo en el bucket |

#### Tabla: `photos` (modificar existente)
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `edition_id` | INT (FK) | Relación con el año de la carrera |
| `original_path` | VARCHAR | Ruta a la imagen real (`race/2026/images/foto_01.jpg`) |
| `thumb_path` | VARCHAR | Ruta a la miniatura (`race/2026/thumbnails/foto_01.webp`) |
| `alt_text` | VARCHAR | Texto alternativo para accesibilidad y SEO |

---

## 4. Integración del Frontend

### 4.1. Visualización de Documentos
- PDFs se enlazan directamente a URL pública de R2.
- `target="_blank"` para forzar descarga/apertura nativa.
- Imágenes informativas (cartel, profilees, rutas) con `<img loading="lazy">`.

### 4.2. Componente de Galería de Imágenes (UI)
- **Librerías recomendadas:** Lightgallery.js / PhotoSwipe / Swiper
- **Diseño del Grid:** Masonry o Justificado, consumiendo thumbnails de `thumbnails/`.
- **Lightbox:** Al hacer clic, visor a pantalla completa que carga la imagen en alta resolución de `images/`.
- **Features nativas:** Gestos táctiles (swipe), zoom, navegación por teclado.

---

## 5. Consideraciones de Seguridad y Rendimiento

### Acceso Público (Lectura)
- Subdominio propio al bucket: `media.cokalba-running.com`
- URLs limpias optimizadas por la red de Cloudflare.

### Acceso Privado (Escritura)
- Credenciales S3 (Access Key, Secret Key) estrictamente en `.env` del servidor.
- El frontend NUNCA sube archivos directamente al bucket.
- Siempre pasa por API intermedia que valida sesión JWT.

### Procesamiento de Imágenes
- Al subir imágenes de galería, generar automáticamente miniatura correspondiente.
- Redimensionar y convertir a `.webp` optimizado antes de guardar en `thumbnails/`.
