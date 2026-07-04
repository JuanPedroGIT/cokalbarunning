<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRaceStore } from '@/stores/race.store'
import { usePageMeta } from '@/composables/usePageMeta'
import api from '@/services/api.service'

interface Runner {
  id: string
  firstName: string
  lastName: string
  fullName: string
  bibNumber: string | null
  club: string | null
  gender: string | null
  category: string | null
}

const raceStore = useRaceStore()
const allRunners = ref<Runner[]>([])
const loading = ref(false)
const error = ref('')
const searchName = ref('')
const searchBib = ref('')
const showBibSearch = ref(false)

const filteredRunners = computed(() => {
  const name = searchName.value.trim().toLowerCase()
  const bib = searchBib.value.trim()
  if (!name && !bib) return allRunners.value
  return allRunners.value.filter(r => {
    if (name && !r.fullName.toLowerCase().includes(name)) return false
    if (bib && !(r.bibNumber ?? '').includes(bib)) return false
    return true
  })
})

onMounted(async () => {
  loading.value = true
  try {
    await raceStore.fetchActiveEdition()
    showBibSearch.value = raceStore.activeEdition?.showBibSearch ?? false
    if (showBibSearch.value) {
      const response = await api.get('/runners')
      allRunners.value = response.data.data
    }
  } catch {
    error.value = 'Error al cargar los dorsales'
  } finally {
    loading.value = false
  }
})

usePageMeta({
  title: 'Dorsales',
  description: 'Consulta los dorsales de los participantes de la Carrera Solidaria Un Nuevo Impulso en Coca de Alba.',
  url: '/dorsales',
})
</script>

<template>
  <section class="relative z-10 pt-24 md:pt-32 pb-16 md:pb-20 px-4 md:px-6 max-w-6xl mx-auto">
    <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Edición actual</div>
    <h1 class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-8 md:mb-12">
      DORSALES
    </h1>

    <!-- Filtros -->
    <div class="bg-gris-oscuro border border-white/5 p-4 md:p-6 mb-8">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <label class="block text-xs text-gray-400 mb-1 uppercase tracking-wider">Buscar por nombre</label>
          <input
            v-model="searchName"
            type="text"
            placeholder="Nombre o apellido..."
            class="w-full bg-negro border border-white/20 px-4 py-2.5 text-sm text-white placeholder-white/30 focus:border-naranja focus:outline-none"
          />
        </div>
        <div class="sm:w-48">
          <label class="block text-xs text-gray-400 mb-1 uppercase tracking-wider">Buscar por dorsal</label>
          <input
            v-model="searchBib"
            type="text"
            placeholder="Nº dorsal..."
            class="w-full bg-negro border border-white/20 px-4 py-2.5 text-sm text-white placeholder-white/30 focus:border-naranja focus:outline-none"
          />
        </div>
      </div>
    </div>

    <!-- No disponible -->
    <div v-if="!loading && !showBibSearch" class="text-white/50 text-center py-20 bg-gris-oscuro border border-white/5">
      La búsqueda de dorsales no está disponible para esta edición.
    </div>

    <!-- Resultados -->
    <div v-else-if="loading" class="text-white/50 text-center py-20">Cargando...</div>

    <div v-else-if="error" class="text-naranja text-center py-20">{{ error }}</div>

    <div v-else-if="!filteredRunners.length" class="text-white/50 text-center py-20 bg-gris-oscuro border border-white/5">
      No se encontraron resultados.
    </div>

    <div v-else>
      <div class="text-right text-xs text-gray-500 mb-2">
        {{ filteredRunners.length }} de {{ allRunners.length }} {{ allRunners.length === 1 ? 'resultado' : 'resultados' }}
      </div>

      <!-- Tabla: md+ -->
      <div class="hidden md:block bg-gris-oscuro border border-white/5 overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-negro/50 text-gray-400">
            <tr>
              <th class="p-3 md:p-4 font-barlow-condensed font-semibold text-xs tracking-widest uppercase">Dorsal</th>
              <th class="p-3 md:p-4 font-barlow-condensed font-semibold text-xs tracking-widest uppercase">Nombre</th>
              <th class="p-3 md:p-4 font-barlow-condensed font-semibold text-xs tracking-widest uppercase">Apellidos</th>
              <th class="p-3 md:p-4 font-barlow-condensed font-semibold text-xs tracking-widest uppercase">Club</th>
              <th class="p-3 md:p-4 font-barlow-condensed font-semibold text-xs tracking-widest uppercase">Categoría</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr
              v-for="runner in filteredRunners"
              :key="runner.id"
              class="hover:bg-white/[0.02] transition-colors"
            >
              <td class="p-3 md:p-4">
                <span class="font-barlow-condensed font-bold text-naranja text-base md:text-lg">{{ runner.bibNumber ?? '-' }}</span>
              </td>
              <td class="p-3 md:p-4 text-white font-medium">{{ runner.firstName }}</td>
              <td class="p-3 md:p-4 text-white/70">{{ runner.lastName }}</td>
              <td class="p-3 md:p-4 text-white/50">{{ runner.club || '-' }}</td>
              <td class="p-3 md:p-4 text-white/50">{{ runner.category || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Cards: móvil -->
      <div class="md:hidden bg-gris-oscuro border border-white/5">
        <ul class="divide-y divide-white/5">
          <li
            v-for="runner in filteredRunners"
            :key="runner.id"
            class="px-4 py-3 flex flex-row justify-between items-center gap-2"
          >
            <div class="flex flex-col min-w-0">
              <span class="text-white text-sm truncate">{{ runner.fullName }}</span>
              <div class="flex flex-wrap items-center gap-x-3 text-xs text-gray-400 mt-0.5">
                <span v-if="runner.club">{{ runner.club }}</span>
                <span v-if="runner.gender">{{ runner.gender === 'F' ? 'Femenino' : runner.gender === 'M' ? 'Masculino' : runner.gender }}</span>
                <span v-if="runner.category">{{ runner.category }}</span>
              </div>
            </div>
            <span class="font-barlow-condensed font-bold text-naranja shrink-0 text-sm">Dorsal {{ runner.bibNumber ?? '-' }}</span>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>
