import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api.service'

export interface RaceEdition {
  id: string
  year: number
  name: string
  description: string | null
  date: string
  location: string
  isActive: boolean
  posterUrl: string | null
  registrationUrl: string | null
  shirtUrl: string | null
  resultsUrl: string | null
  inscriptionInfo: string | null
  solidarityCause: string | null
  solidarityUrl: string | null
}

export const useRaceStore = defineStore('race', () => {
  const editions = ref<RaceEdition[]>([])
  const activeEdition = ref<RaceEdition | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchEditions() {
    loading.value = true
    try {
      const response = await api.get('/editions')
      editions.value = response.data.data
    } catch (e) {
      error.value = 'Error cargando ediciones'
    } finally {
      loading.value = false
    }
  }

  async function fetchActiveEdition() {
    loading.value = true
    try {
      const response = await api.get('/editions/active')
      activeEdition.value = response.data.data
    } catch (e) {
      error.value = 'Error cargando edicion activa'
    } finally {
      loading.value = false
    }
  }

  async function fetchLatestEdition() {
    loading.value = true
    try {
      const response = await api.get('/editions/latest')
      activeEdition.value = response.data.data
    } catch (e) {
      error.value = 'Error cargando ultima edicion'
    } finally {
      loading.value = false
    }
  }

  return { editions, activeEdition, loading, error, fetchEditions, fetchActiveEdition, fetchLatestEdition }
})
