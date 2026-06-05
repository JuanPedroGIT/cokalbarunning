<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { usePhotoStore } from '@/stores/photo.store'
import { useRaceStore } from '@/stores/race.store'
import GallerySection from '@/components/gallery/GallerySection.vue'
import { usePageMeta } from '@/composables/usePageMeta'

const photoStore = usePhotoStore()
const raceStore = useRaceStore()
const filterEditionId = ref<string | null>(null)

watch(filterEditionId, (newVal) => {
  photoStore.fetchAllPhotos(newVal || undefined)
})

usePageMeta({
  title: 'Galería de Fotos',
  description: 'Revive los mejores momentos de la Carrera Solidaria Un Nuevo Impulso en Coca de Alba.',
  url: '/galeria',
})

onMounted(async () => {
  await raceStore.fetchEditions()
  // Check for edition in query params (from editions page link)
  const qEdition = new URLSearchParams(window.location.search).get('edicion')
  if (qEdition && raceStore.editions.some(e => e.id === qEdition)) {
    filterEditionId.value = qEdition
  } else if (!filterEditionId.value) {
    const active = raceStore.editions.find(e => e.isActive)
    filterEditionId.value = active?.id || raceStore.editions[0]?.id || null
  }
})
</script>

<template>
  <section class="relative z-10 pt-32 pb-20 px-6 max-w-6xl mx-auto">
    <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">
      Momentos
    </div>
    <h1 class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-8">
      GALERÍA DE FOTOS
    </h1>

    <!-- Filtro -->
    <div class="flex items-center gap-4 mb-10">
      <label class="text-sm text-white/50 whitespace-nowrap font-barlow-condensed tracking-wider uppercase">Edición:</label>
      <select v-model="filterEditionId" class="bg-[#141414] border border-white/10 rounded px-4 py-2 text-white text-sm focus:border-[#FF5C00] focus:outline-none transition min-w-[200px]">
        <option v-for="e in raceStore.editions" :key="e.id" :value="e.id">{{ e.year }} - {{ e.name }}</option>
      </select>
      <span class="text-xs text-white/30 ml-auto">{{ photoStore.photos.length }} fotos</span>
    </div>

    <div v-if="photoStore.loading" class="text-white/50 text-center py-20">Cargando...</div>

    <GallerySection v-else :photos="photoStore.photos" />
  </section>
</template>
