<script setup lang="ts">
import type { Photo } from '@/stores/photo.store'

defineProps<{
  photos: Photo[]
}>()

defineEmits<{
  (e: 'open', index: number): void
}>()
</script>

<template>
  <div class="columns-3 md:columns-3 lg:columns-4 gap-3 space-y-3">
    <div
      v-for="(photo, index) in photos"
      :key="photo.id"
      class="break-inside-avoid relative group cursor-pointer overflow-hidden rounded-lg"
      @click="$emit('open', index)"
    >
      <img
        :src="photo.thumbUrl || photo.originalUrl"
        :alt="photo.altText || 'Foto'"
        loading="lazy"
        class="w-full h-auto object-cover transition-transform duration-300 group-hover:scale-105"
      />
      <div
        class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4"
      >
        <span class="font-barlow-condensed font-bold text-sm uppercase tracking-wider text-white">
          {{ photo.altText || 'Foto' }}
        </span>
      </div>
    </div>
  </div>
</template>
