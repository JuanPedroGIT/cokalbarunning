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
  showBibSearch: boolean
  posterUrl: string | null
  registrationUrl: string | null
  shirtUrl: string | null
  trophyUrl: string | null
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

  // Deduplicación: si ya hay una petición en vuelo, reutilizamos la misma promesa
  let editionsPromise: Promise<void> | null = null
  let activeEditionPromise: Promise<void> | null = null
  let latestEditionPromise: Promise<void> | null = null

  async function fetchEditions() {
    if (editions.value.length > 0) return
    if (editionsPromise) return editionsPromise
    loading.value = true
    editionsPromise = (async () => {
      try {
        const response = await api.get('/editions')
        editions.value = response.data.data
      } catch (e) {
        error.value = 'Error cargando ediciones'
      } finally {
        loading.value = false
        editionsPromise = null
      }
    })()
    return editionsPromise
  }

  async function fetchActiveEdition() {
    if (activeEdition.value) return
    if (activeEditionPromise) return activeEditionPromise
    loading.value = true
    activeEditionPromise = (async () => {
      try {
        const response = await api.get('/editions/active')
        activeEdition.value = response.data.data
      } catch (e) {
        error.value = 'Error cargando edicion activa'
      } finally {
        loading.value = false
        activeEditionPromise = null
      }
    })()
    return activeEditionPromise
  }

  async function fetchLatestEdition() {
    if (activeEdition.value) return
    if (latestEditionPromise) return latestEditionPromise
    loading.value = true
    latestEditionPromise = (async () => {
      try {
        const response = await api.get('/editions/latest')
        activeEdition.value = response.data.data
      } catch (e) {
        error.value = 'Error cargando ultima edicion'
      } finally {
        loading.value = false
        latestEditionPromise = null
      }
    })()
    return latestEditionPromise
  }

  return { editions, activeEdition, loading, error, fetchEditions, fetchActiveEdition, fetchLatestEdition }
})
