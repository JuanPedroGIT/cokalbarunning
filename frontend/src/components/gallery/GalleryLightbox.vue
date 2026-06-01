<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import PhotoSwipe from 'photoswipe'
import 'photoswipe/style.css'
import type { Photo } from '@/stores/photo.store'

const props = defineProps<{
  photos: Photo[]
  startIndex: number
  open: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const pswpRef = ref<PhotoSwipe | null>(null)

function getItems() {
  return props.photos.map((photo) => ({
    src: photo.originalUrl,
    width: 1600,
    height: 1200,
    alt: photo.altText || 'Foto',
  }))
}

function initPhotoSwipe() {
  if (!props.open || props.photos.length === 0) return

  const items = getItems()

  const pswp = new PhotoSwipe({
    dataSource: items,
    index: props.startIndex,
    bgOpacity: 0.9,
    padding: { top: 20, bottom: 20, left: 20, right: 20 },
  })

  pswp.addFilter('domItemData', (itemData, index) => {
    const photo = props.photos[index as unknown as number]
    if (photo) {
      itemData.src = photo.originalUrl
      itemData.alt = photo.altText || 'Foto'
    }
    return itemData
  })

  pswp.on('destroy', () => {
    emit('close')
  })

  pswp.init()
  pswpRef.value = pswp
}

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    initPhotoSwipe()
  } else {
    pswpRef.value?.destroy()
    pswpRef.value = null
  }
})

onMounted(() => {
  if (props.open) initPhotoSwipe()
})

onUnmounted(() => {
  pswpRef.value?.destroy()
})
</script>

<template>
  <div />
</template>
