<script setup lang="ts">
import { ref } from 'vue'
import type { Photo } from '@/stores/photo.store'
import GalleryGrid from './GalleryGrid.vue'
import GalleryLightbox from './GalleryLightbox.vue'

const props = defineProps<{
  photos: Photo[]
  title?: string
}>()

const lightboxOpen = ref(false)
const lightboxIndex = ref(0)

function openLightbox(index: number) {
  lightboxIndex.value = index
  lightboxOpen.value = true
}

function closeLightbox() {
  lightboxOpen.value = false
}
</script>

<template>
  <section class="pt-32 pb-20 px-6 max-w-6xl mx-auto">
    <div v-if="title" class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">
      Momentos
    </div>
    <h1 v-if="title" class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-12">
      {{ title }}
    </h1>

    <div v-if="photos.length === 0" class="text-white/50 text-center py-20">
      No hay fotos disponibles.
    </div>

    <GalleryGrid v-else :photos="photos" @open="openLightbox" />

    <GalleryLightbox
      :photos="photos"
      :start-index="lightboxIndex"
      :open="lightboxOpen"
      @close="closeLightbox"
    />
  </section>
</template>
