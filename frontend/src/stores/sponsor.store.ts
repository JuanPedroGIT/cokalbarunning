import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api.service'

export interface Sponsor {
  id: string
  name: string
  logoUrl: string | null
  website: string | null
  tier: string
  isActive: boolean
  sortOrder: number
  message: string | null
}

export const useSponsorStore = defineStore('sponsor', () => {
  const sponsors = ref<Sponsor[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchSponsors() {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/sponsors')
      console.log('Sponsors API response:', response.data)
      sponsors.value = response.data.data || []
    } catch (e: any) {
      console.error('Error cargando patrocinadores:', e)
      error.value = e?.response?.data?.message || 'Error cargando patrocinadores'
    } finally {
      loading.value = false
      console.log('Sponsors loading done. Count:', sponsors.value.length)
    }
  }

  return { sponsors, loading, error, fetchSponsors }
})
