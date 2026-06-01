import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api.service'

export interface Photo {
  id: string
  originalPath: string
  originalUrl: string
  thumbPath: string | null
  thumbUrl: string | null
  altText: string | null
  isFeatured: boolean
  sortOrder: number
  raceEditionId: string | null
}

export const usePhotoStore = defineStore('photo', () => {
  const photos = ref<Photo[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchFeaturedPhotos() {
    loading.value = true
    try {
      const response = await api.get('/photos/featured')
      photos.value = response.data.data
    } catch (e) {
      error.value = 'Error cargando fotos'
    } finally {
      loading.value = false
    }
  }

  async function fetchAllPhotos(editionId?: string) {
    loading.value = true
    try {
      const params = editionId ? { editionId } : {}
      const response = await api.get('/photos', { params })
      photos.value = response.data.data
    } catch (e) {
      error.value = 'Error cargando fotos'
    } finally {
      loading.value = false
    }
  }

  return { photos, loading, error, fetchFeaturedPhotos, fetchAllPhotos }
})
