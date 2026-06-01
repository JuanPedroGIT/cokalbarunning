# Tareas Frontend - PRD R2 Storage

> **Instrucciones:** El backend debe estar completo (Fases 1-5) antes de empezar el frontend. Marcar `[x]` solo cuando implementado y testeado.

---

## Fase 1: Dependencias y Tipos

> **Nota:** El frontend actual NO tiene carpeta `types/`. Las interfaces están inline en las Pinia stores. Se seguirá este patrón existente.

- [ ] **1.1** Instalar `photoswipe` y `@types/photoswipe` (o la librería elegida finalmente)
- [ ] **1.2** Actualizar la interfaz `Photo` en `frontend/src/stores/photo.store.ts`:
  - Renombrar `filename` → `originalPath`
  - Renombrar `thumbnailFilename` → `thumbPath`
  - Renombrar `caption` → `altText`
  - Mantener `raceEditionId`, `isFeatured`, `sortOrder`
- [ ] **1.3** Crear nuevo store `frontend/src/stores/document.store.ts` con:
  ```typescript
  export interface RaceDocument {
    id: string;
    editionId: string | null;
    name: string;
    type: 'recorrido' | 'perfil' | 'resultados' | 'general' | 'otros';
    filePath: string;
    publicUrl: string; // construido por el backend
  }
  ```
- [ ] **1.4** Actualizar la interfaz `Sponsor` en `frontend/src/stores/sponsor.store.ts` (o crearlo si no existe) para incluir `editionId: string | null`
- [ ] **1.5** Actualizar la interfaz `RaceEdition` en `frontend/src/stores/race.store.ts` (o crearlo si no existe) para incluir `shirtUrl: string | null`

---

## Fase 2: Servicios API

- [ ] **2.1** Actualizar `frontend/src/services/api.service.ts` o crear métodos:
  - `getDocumentsByEdition(year: number): Promise<RaceDocument[]>`
  - `getGeneralDocuments(): Promise<RaceDocument[]>`
  - Actualizar `getPhotos()` para reflejar nuevos nombres de campos
  - Actualizar `getSponsors()` para reflejar nueva estructura
  - Actualizar `getRaceEdition()` para incluir `shirtUrl`
- [ ] **2.2** Actualizar endpoints de admin si es necesario

---

## Fase 3: Componentes de Galería (Nuevos)

- [ ] **3.1** Crear `frontend/src/components/gallery/GalleryGrid.vue`
  - Layout tipo Masonry (CSS columns o grid masonry)
  - Consumir `thumbPath` para las miniaturas
  - Mostrar `altText` como tooltip/title
  - Al hacer clic en una miniatura, emitir evento `open-lightbox` con el índice
- [ ] **3.2** Crear `frontend/src/components/gallery/GalleryLightbox.vue`
  - Integrar PhotoSwipe
  - Recibir array de fotos con `originalPath` y `altText`
  - Cargar imagen original bajo demanda
  - Soportar gestos táctiles (swipe), zoom, navegación por teclado
- [ ] **3.3** Crear `frontend/src/components/gallery/GallerySection.vue`
  - Composición de `GalleryGrid` + `GalleryLightbox`
  - Props: `photos: Photo[]`, `title?: string`

---

## Fase 4: Vistas Públicas Actualizadas

### 4.1 HomeView
- [ ] **4.1.1** Verificar que `SponsorSection` sigue funcionando con el nuevo tipo `Sponsor` (con `editionId`)

### 4.2 RaceView (`/carrera`)
- [ ] **4.2.1** Mostrar diseño de camiseta (`shirtUrl`) si existe, junto al cartel (`posterUrl`)
- [ ] **4.2.2** Mostrar sección de documentos de la edición activa usando `RaceDocuments` component

### 4.3 GalleryView (`/galeria`)
- [ ] **4.3.1** Reemplazar implementación actual por `GallerySection`
- [ ] **4.3.2** Asegurar que carga fotos destacadas + todas las fotos

### 4.4 EditionsView (`/ediciones`)
- [ ] **4.4.1** Si muestra detalle por año, incluir documentos disponibles

---

## Fase 5: Componentes de Documentos

- [ ] **5.1** Crear `frontend/src/components/race/RaceDocuments.vue`
  - Props: `documents: RaceDocument[]`
  - Agrupar por tipo (recorrido, perfil, resultados, etc.)
  - Renderizar como lista de links con icono PDF
  - URL: `publicUrl` del documento (apunta a R2)
  - `target="_blank"` para todos los enlaces

---

## Fase 6: Panel de Administración

### 6.1 AdminEditionsView
- [ ] **6.1.1** Agregar campo/upload para "Diseño de camiseta" (`shirtUrl`)
- [ ] **6.1.2** Mostrar vista previa del cartel y camiseta si existen
- [ ] **6.1.3** Agregar sección "Documentos de la edición" con CRUD:
  - Tabla de documentos existentes
  - Formulario para subir nuevo PDF (nombre, tipo, archivo)
  - Botón eliminar documento

### 6.2 AdminSponsorsView
- [ ] **6.2.1** Agregar selector de "Edición asociada" (dropdown con ediciones, opción "General del club")
- [ ] **6.2.2** Asegurar que el upload de logo sigue funcionando (ahora va a R2)

### 6.3 AdminPhotosView
- [ ] **6.3.1** Cambiar campo "Caption" por "Texto alternativo (alt)"
- [ ] **6.3.2** Asegurar que la subida genera miniatura automáticamente (backend)
- [ ] **6.3.3** Mostrar preview de miniatura en la lista

---

## Fase 7: Tests Frontend

- [ ] **7.1** Test unitario `GalleryGrid.vue` - renderiza fotos, emite evento click
- [ ] **7.2** Test unitario `RaceDocuments.vue` - agrupa por tipo, links correctos
- [ ] **7.3** Test de tipos - verificar que TypeScript compila sin errores tras los cambios

---

## Fase 8: Integración y QA

- [ ] **8.1** Verificar flujo completo: Admin sube foto → backend genera miniatura WebP → frontend galería muestra miniatura → lightbox carga original
- [ ] **8.2** Verificar flujo de documentos: Admin sube PDF → aparece en RaceView → click abre R2 URL
- [ ] **8.3** Verificar que patrocinadores generales aparecen en Home, y los de edición en RaceView
- [ ] **8.4** Verificar responsive de galería masonry en móvil
