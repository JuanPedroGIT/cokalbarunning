Documento de Especificación Técnica: Gestión de Almacenamiento y Galería R2
1. Introducción y Objetivos
El objetivo de esta especificación es definir la arquitectura de almacenamiento, la estructura de persistencia y el comportamiento del frontend para la gestión de recursos multimedia de la plataforma. Todo el contenido estático (imágenes, carteles y documentos PDF) se centralizará en un bucket de Cloudflare R2 para optimizar el rendimiento y costes, manteniendo únicamente las referencias analíticas (nombres de archivo y metadatos) en la base de datos relacional.

2. Configuración del Almacenamiento (Cloudflare R2)
2.1. Credenciales y Endpoint
Provider: Cloudflare R2 (S3 Compatible API)

Account ID: c37af7cef2e32d99f4cd81882add4b97

Endpoint URL: https://c37af7cef2e32d99f4cd81882add4b97.r2.cloudflarestorage.com

Bucket Name: (A definir en variables de entorno, ej: cokalba-media)

2.2. Estructura de Directorios (Árbol de Objetos)
Dado que R2/S3 es un almacenamiento de clave-valor plano, la jerarquía se simula mediante prefijos (/) en el nombre del objeto:

Plaintext
cokalba-running/
└── un-nuevo-impulso/
    ├── patrocinadores/          # Logos de patrocinadores del club/evento
    ├── docs/                    # Documentos generales (recorrido global, perfil general)
    └── carrera/
        └── 2026/                # Directorio dinámico por año de edición
            ├── docs/            # Cartel oficial, diseño de camisetas, etc.
            ├── resultados/      # PDFs de clasificaciones y tiempos
            ├── imgs/            # Fotografías en alta resolución de la carrera
            └── miniaturas/      # Versiones optimizadas (WebP/JPEG ligero) para la galería

3. Modelo de Persistencia (Base de Datos)
Para evitar acoplar la base de datos al proveedor de almacenamiento, nunca se guardará la URL completa en las tablas. Se almacenará únicamente el identificador único o el path relativo, construyendo la URL pública en el backend o mediante variables de entorno en el frontend. Los nombres se guardan en base de datos y los archivos físicos están en el bucket.

3.1. Propuesta de Entidades Básicas
Tabla: ediciones_carrera
Campo	Tipo	Descripción
id	INT (PK)	Identificador único.
ano	INT	Año de la edición (ej: 2026).
cartel_path	VARCHAR	Ruta relativa (ej: carrera/2026/docs/cartel.webp).
camiseta_path	VARCHAR	Ruta relativa (ej: carrera/2026/docs/camiseta.webp).
Tabla: documentos_carrera
Campo	Tipo	Descripción
id	INT (PK)	Identificador único.
edicion_id	INT (FK)	Relación con la edición (NULL si es un documento general común).
nombre	VARCHAR	Nombre legible (ej: "Recorrido Niños", "Clasificación General").
tipo	ENUM	['recorrido', 'perfil', 'resultados', 'general', 'otros']
file_path	VARCHAR	Ruta en el bucket (ej: carrera/2026/resultados/clasificacion.pdf o docs/recorrido_ninos.pdf).
Tabla: patrocinadores
Campo	Tipo	Descripción
id	INT (PK)	Identificador único.
edicion_id	INT (FK)	Relación con el año (NULL si es patrocinador general de un-nuevo-impulso).
nombre	VARCHAR	Nombre de la empresa patrocinadora.
logo_path	VARCHAR	Ruta al logo en el bucket (ej: patrocinadores/logo_global.png o carrera/2026/patrocinadores/logo_local.png).
Tabla: galeria_fotos
Campo	Tipo	Descripción
id	INT (PK)	Identificador único.
edicion_id	INT (FK)	Relación con el año de la carrera.
img_original_path	VARCHAR	Ruta a la imagen real (carrera/2026/imgs/foto_01.jpg).
img_thumb_path	VARCHAR	Ruta a la miniatura (carrera/2026/miniaturas/foto_01.webp).
alt_text	VARCHAR	Texto alternativo para accesibilidad y SEO.
4. Integración del Frontend
4.1. Visualización de Perfiles, Rutas y Documentos
Documentos y Tracks: Los PDFs (tanto los comunes/generales de la raíz del proyecto como los de resultados de cada año) se enlazarán directamente apuntando a su URL pública de R2. Se configurará el navegador para forzar la descarga o apertura nativa en pestaña nueva (target="_blank").

Imágenes Informativas: El cartel, las fotos de perfiles y rutas de la carrera se renderizarán mediante etiquetas <img> estándar con carga diferida (loading="lazy").

4.2. Componente de Galería de Imágenes (UI)
Para garantizar un acabado profesional, fluido y sin "inventar la rueda", se optará por librerías estándar de mercado (componentes ya listos e integrados).

Librerías Recomendadas: Lightgallery.js / PhotoSwipe / Swiper
Diseño del Grid: Se implementará un componente de Grid tipo Masonry (estilo Pinterest) o Justificado que consumirá directamente las imágenes optimizadas de la carpeta miniaturas/ para asegurar que el Front cargue de forma instantánea.

Comportamiento Lightbox: Al hacer clic en cualquier miniatura, el componente abrirá un visor a pantalla completa animado (lightbox), el cual cargará de forma asíncrona y bajo demanda la imagen en alta resolución alojada en la carpeta imgs/.

Características nativas del componente: Soporte para gestos táctiles (deslizar/swipe en móviles), zoom y navegación fluida por teclado.

5. Consideraciones de Seguridad y Rendimiento
Acceso Público (Lectura desde el Front): Se recomienda asociar un subdominio propio al bucket de Cloudflare R2 (ej: media.cokalba-running.com). Esto permite que el frontend pinte los carteles, documentos e imágenes de la galería usando URLs limpias y optimizadas a través de la red de Cloudflare.

Acceso Privado (Escritura): Las credenciales de la API S3 (Access Key y Secret Key) se almacenarán estrictamente en las variables de entorno del servidor (.env). El frontend nunca subirá archivos directamente sin pasar por una API intermedio que valide la sesión.

Procesamiento de Imágenes: Al subir imágenes de galería, el sistema debe generar automáticamente la miniatura correspondiente (redimensionando y convirtiendo a formato optimizado .webp) antes de guardarla en la carpeta miniaturas/.


quiero que crees un sisteme de tracking como el que has propuesto siguiendo implementacion.md y teniendo en cuenta lo que quiero hacer: apps/cokalbarunning/docs/prd_documentos.md