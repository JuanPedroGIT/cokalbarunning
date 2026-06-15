<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useRaceStore } from '@/stores/race.store'
import { useImageZoom } from '@/composables/useImageZoom'
import { usePageMeta } from '@/composables/usePageMeta'
import api from '@/services/api.service'

interface RunnerResult {
  id: string
  firstName: string
  lastName: string
  fullName: string
  bibNumber: string | null
}

const raceStore = useRaceStore()
const { zoomImage } = useImageZoom()

const activeEdition = computed(() => raceStore.editions[0] ?? null)

const searchName = ref('')
const activeResults = ref<RunnerResult[]>([])
const activeSearching = ref(false)

onMounted(() => {
  raceStore.fetchEditions()
})

usePageMeta({
  title: 'Ediciones',
  description: 'Historial de ediciones de la Carrera Solidaria Un Nuevo Impulso en Coca de Alba. Consulta resultados, galerías y busca tu dorsal.',
  url: '/ediciones',
})

async function searchActiveBibs() {
  const edition = activeEdition.value
  if (!edition) return

  activeSearching.value = true
  try {
    const response = await api.get('/runners', {
      params: { editionId: edition.id, name: searchName.value.trim() },
    })
    activeResults.value = response.data.data
  } catch {
    activeResults.value = []
  } finally {
    activeSearching.value = false
  }
}
</script>

<template>
  <section class="relative z-10 pt-32 pb-20 px-6 max-w-6xl mx-auto">
    <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Historial</div>
    <h1 class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-12">
      EDICIONES
    </h1>

    <div v-if="raceStore.loading" class="text-white/50 text-center py-20">Cargando...</div>

    <template v-else>
      <!-- Edición actual -->
      <div v-if="activeEdition" class="mb-16">
        <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">
          Edición actual
        </div>

        <div class="grid md:grid-cols-2 gap-8 items-stretch bg-gris-oscuro border border-white/5 p-6 md:p-8">
          <!-- Izquierda: info y opciones -->
          <div class="flex flex-col">
            <div class="font-barlow-condensed font-black text-6xl md:text-7xl text-naranja mb-2">
              {{ activeEdition.year }}
            </div>
            <div class="font-barlow-condensed font-bold text-2xl uppercase leading-tight mb-2">
              {{ activeEdition.name }}
            </div>
            <div class="text-gris-texto mb-6">
              {{ activeEdition.date }} &middot; {{ activeEdition.location }}
            </div>

            <div class="flex flex-wrap gap-3 mb-6">
              <a
                v-if="activeEdition.resultsUrl"
                :href="activeEdition.resultsUrl"
                target="_blank"
                class="font-barlow-condensed font-bold text-xs tracking-widest uppercase text-naranja border border-naranja/40 px-4 py-2 hover:bg-naranja hover:text-negro transition-colors text-center"
              >
                Resultados
              </a>
              <span
                v-else
                class="font-barlow-condensed font-bold text-xs tracking-widest uppercase text-white/30 border border-white/10 px-4 py-2 text-center"
              >
                Resultados no disponibles
              </span>

              <RouterLink
                :to="`/galeria?edicion=${activeEdition.id}`"
                class="font-barlow-condensed font-bold text-xs tracking-widest uppercase text-white/70 border border-white/20 px-4 py-2 hover:border-naranja hover:text-naranja transition-colors text-center"
              >
                Galería
              </RouterLink>
            </div>

            <!-- Búsqueda de dorsal edición actual -->
            <div v-if="activeEdition.showBibSearch" class="mt-auto">
              <div class="flex gap-2">
                <input
                  v-model="searchName"
                  type="text"
                  placeholder="Escribe un nombre..."
                  class="flex-1 bg-negro border border-white/20 px-4 py-2 text-white placeholder-white/30 focus:border-naranja focus:outline-none"
                  @keyup.enter="searchActiveBibs"
                />
                <button
                  type="button"
                  class="font-barlow-condensed font-bold text-xs tracking-widest uppercase bg-naranja text-negro px-4 py-2 hover:bg-naranja/90 transition-colors"
                  :disabled="activeSearching"
                  @click="searchActiveBibs"
                >
                  {{ activeSearching ? '...' : 'Buscar' }}
                </button>
              </div>

              <div v-if="activeResults.length > 0" class="mt-4 border border-white/10 bg-negro/50 max-h-64 overflow-y-auto">
                <ul class="divide-y divide-white/5">
                  <li
                    v-for="runner in activeResults"
                    :key="runner.id"
                    class="px-4 py-3 flex justify-between items-center"
                  >
                    <span class="text-white">{{ runner.fullName }}</span>
                    <span class="font-barlow-condensed font-bold text-naranja">Dorsal {{ runner.bibNumber ?? '-' }}</span>
                  </li>
                </ul>
              </div>
              <div v-else-if="searchName.trim() && !activeSearching" class="mt-3 text-gris-texto text-sm">
                No se encontraron resultados.
              </div>
            </div>
          </div>

          <!-- Derecha: cartel -->
          <div class="aspect-[2/3] bg-gris-medio flex items-center justify-center overflow-hidden">
            <img
              v-if="activeEdition.posterUrl"
              :src="activeEdition.posterUrl"
              :alt="activeEdition.name"
              class="w-full h-full object-cover cursor-zoom-in"
              @click="zoomImage($event.target as HTMLImageElement)"
            />
            <span v-else class="font-barlow-condensed text-sm tracking-widest uppercase text-white/15">
              Sin cartel
            </span>
          </div>
        </div>
      </div>

      <!-- Ediciones anteriores -->
      <div>
        <h2 class="font-barlow-condensed font-black text-4xl uppercase mb-8">
          EDICIONES <span class="text-naranja">ANTERIORES</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="edition in raceStore.editions.filter(e => e.id !== activeEdition?.id)"
            :key="edition.id"
            class="group bg-gris-oscuro border border-white/5 hover:border-naranja/30 transition-colors overflow-hidden flex flex-col"
          >
            <div class="aspect-[2/3] bg-gris-medio flex items-center justify-center overflow-hidden">
              <img
                v-if="edition.posterUrl"
                :src="edition.posterUrl"
                :alt="edition.name"
                class="w-full h-full object-cover cursor-zoom-in"
                loading="lazy"
                @click="zoomImage($event.target as HTMLImageElement)"
              />
              <span v-else class="font-barlow-condensed text-sm tracking-widest uppercase text-white/15">
                Sin cartel
              </span>
            </div>

            <div class="p-5 flex flex-col gap-2 flex-1">
              <div class="font-barlow-condensed font-black text-5xl text-naranja">{{ edition.year }}</div>
              <div class="font-barlow-condensed font-bold text-lg uppercase leading-tight">{{ edition.name }}</div>
              <div class="text-gris-texto text-sm">{{ edition.date }} &middot; {{ edition.location }}</div>

              <div class="mt-auto pt-4 flex flex-col gap-2">
                <div class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                  <a
                    v-if="edition.resultsUrl"
                    :href="edition.resultsUrl"
                    target="_blank"
                    class="font-barlow-condensed font-bold text-[0.6rem] sm:text-xs tracking-widest uppercase text-naranja border border-naranja/40 px-2 sm:px-4 py-1.5 sm:py-2 hover:bg-naranja hover:text-negro transition-colors text-center"
                  >
                    Resultados
                  </a>
                  <span
                    v-else
                    class="font-barlow-condensed font-bold text-[0.6rem] sm:text-xs text-white/30 border border-white/10 px-2 sm:px-4 py-1.5 sm:py-2 text-center"
                  >
                    Resultados no disponibles
                  </span>

                  <RouterLink
                    :to="`/galeria?edicion=${edition.id}`"
                    class="font-barlow-condensed font-bold text-[0.6rem] sm:text-xs tracking-widest uppercase text-white/50 border border-white/20 px-2 sm:px-4 py-1.5 sm:py-2 hover:border-naranja hover:text-naranja transition-colors text-center"
                  >
                    Galería
                  </RouterLink>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>
