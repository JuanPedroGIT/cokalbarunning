<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useRaceStore } from '@/stores/race.store'
import { useImageZoom } from '@/composables/useImageZoom'
import { usePageMeta } from '@/composables/usePageMeta'

const raceStore = useRaceStore()
const { zoomImage } = useImageZoom()

onMounted(() => {
  raceStore.fetchEditions()
})

usePageMeta({
  title: 'Ediciones Anteriores',
  description: 'Historial de ediciones de la Carrera Solidaria Un Nuevo Impulso en Coca de Alba. Consulta resultados y galerías de años anteriores.',
  url: '/ediciones',
})
</script>

<template>
  <section class="relative z-10 pt-32 pb-20 px-6 max-w-6xl mx-auto">
    <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Historial</div>
    <h1 class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-12">
      EDICIONES <span class="text-naranja">ANTERIORES</span>
    </h1>

    <div v-if="raceStore.loading" class="text-white/50 text-center py-20">Cargando...</div>

    <div v-else class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="edition in raceStore.editions"
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

          <div class="mt-auto pt-4 flex gap-2 items-center">
            <a
              v-if="edition.resultsUrl"
              :href="edition.resultsUrl"
              target="_blank"
              class="font-barlow-condensed font-bold text-xs tracking-widest uppercase text-naranja border border-naranja/40 px-4 py-2 hover:bg-naranja hover:text-negro transition-colors"
            >
              Ver Resultados
            </a>
            <span v-else class="text-gris-texto text-xs italic">Resultados no disponibles</span>
            <RouterLink
              :to="`/galeria?edicion=${edition.id}`"
              class="font-barlow-condensed font-bold text-xs tracking-widest uppercase text-white/50 border border-white/20 px-4 py-2 hover:border-naranja hover:text-naranja transition-colors"
            >
              Galería
            </RouterLink>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
