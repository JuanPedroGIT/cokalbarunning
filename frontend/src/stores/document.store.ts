import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api.service'

export interface RaceDocument {
  id: string
  editionId: string | null
  name: string
  type: 'route' | 'profile' | 'results' | 'general' | 'other'
  filePath: string
  publicUrl: string
  createdAt: string
}

export const useDocumentStore = defineStore('document', () => {
  const documents = ref<RaceDocument[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchDocumentsByEdition(year: number) {
    loading.value = true
    try {
      const response = await api.get(`/editions/${year}/documents`)
      documents.value = response.data.data
    } catch (e) {
      error.value = 'Error cargando documentos'
    } finally {
      loading.value = false
    }
  }

  async function fetchGeneralDocuments() {
    loading.value = true
    try {
      const response = await api.get('/documents')
      documents.value = response.data.data
    } catch (e) {
      error.value = 'Error cargando documentos'
    } finally {
      loading.value = false
    }
  }

  return { documents, loading, error, fetchDocumentsByEdition, fetchGeneralDocuments }
})
