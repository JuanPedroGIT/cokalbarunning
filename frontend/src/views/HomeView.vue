<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useCountdown } from '@/composables/useCountdown'
import { useImageZoom } from '@/composables/useImageZoom'
import { useSponsorStore } from '@/stores/sponsor.store'
import { useRaceStore } from '@/stores/race.store'
import api from '@/services/api.service'

interface ClubMember {
  id: string; name: string; description: string | null; bio: string | null; photoUrl: string | null
}
interface LatestPost {
  title: string; slug: string; excerpt: string; coverImage: string | null
}

const sponsorStore = useSponsorStore()
const raceStore = useRaceStore()

const raceDate = computed(() => {
  const d = raceStore.activeEdition?.date
  return d ? new Date(d + 'T09:00:00') : new Date('2026-07-05T09:00:00')
})
const { days, hours, minutes, seconds, isExpired } = useCountdown(raceDate)
const { zoomImage } = useImageZoom()
const loading = ref(true)
const clubMembers = ref<ClubMember[]>([])
const latestPost = ref<LatestPost | null>(null)

const mainSponsor = computed(() => sponsorStore.sponsors.find(s => s.tier === 'principal') || null)
const otherSponsors = computed(() => sponsorStore.sponsors.filter(s => s.tier !== 'principal'))

onMounted(async () => {
  await Promise.all([
    sponsorStore.fetchSponsors(),
    raceStore.fetchActiveEdition(),
    (async () => { try { const res = await api.get('/club-members'); clubMembers.value = res.data.data } catch {} })(),
    (async () => { try { const res = await api.get('/posts/latest'); latestPost.value = res.data.data } catch {} })(),
  ])
  loading.value = false
})
</script>

<template>
  <div>
    <!-- Loading -->
    <section v-if="loading" class="min-h-screen relative z-10 flex items-center justify-center">
      <div class="text-white/30 text-center">
        <div class="font-barlow-condensed font-black text-6xl text-naranja/20 mb-4">COKALBA RUNNING</div>
        <div class="text-sm tracking-widest uppercase">Cargando...</div>
      </div>
    </section>

    <template v-else>
    <!-- HERO -->
    <section id="inicio" class="relative z-10 overflow-hidden" :class="raceStore.activeEdition ? 'min-h-screen' : ''">
      <div class="relative z-10 grid grid-cols-1 lg:grid-cols-[1fr_420px] gap-16 items-center px-6 pt-32 pb-8 lg:pb-20 max-w-6xl mx-auto">

      <!-- Carrera activa -->
      <div v-if="raceStore.activeEdition" class="relative z-10 text-center lg:text-left">
        <div class="font-barlow-condensed font-semibold text-sm tracking-[0.25em] uppercase text-naranja mb-5">
          {{ raceStore.activeEdition.name }} - {{ new Date(raceStore.activeEdition.date + 'T00:00:00').toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' }) }}
        </div>
        <h1 class="font-barlow-condensed font-black text-[clamp(3.5rem,8vw,8rem)] leading-[0.92] uppercase">
          Un Nuevo<br><span class="text-naranja">Impulso</span>
        </h1>
        <p class="text-lg font-light text-white/60 max-w-lg mt-6 leading-relaxed mx-auto lg:mx-0">
          {{ raceStore.activeEdition.description || 'La carrera solidaria de Coca de Alba vuelve un año más. Corre por una buena causa y ayuda a impulsar el futuro de muchas personas.' }}
        </p>
        <div class="flex gap-4 mt-10 justify-center lg:justify-start">
          <a v-if="!isExpired" href="https://www.deporticket.com/web-evento/13254-ix-carrera-solidaria-un-nuevo-impulso" target="_blank" class="font-barlow-condensed font-bold text-base tracking-widest uppercase bg-naranja text-negro px-8 py-3 hover:bg-amarillo transition-colors inline-block">
            Inscribete
          </a>
          <span v-else class="font-barlow-condensed font-bold text-base tracking-widest uppercase bg-gray-600 text-gray-300 px-8 py-3 inline-block cursor-not-allowed">
            Inscripciones cerradas
          </span>
          <RouterLink to="/carrera" class="font-barlow-condensed font-bold text-base tracking-widest uppercase bg-transparent text-white border border-white/30 px-8 py-3 hover:border-white transition-colors inline-block">
            La Carrera
          </RouterLink>
        </div>

        <div class="font-barlow-condensed text-xs tracking-[0.25em] uppercase text-white/40 mb-2 mt-10">
          {{ isExpired ? 'Carrera finalizada' : 'Cuenta atrás · Salida 09:00h' }}
        </div>
        <div class="flex flex-col lg:flex-row gap-0 lg:gap-0">
          <div class="flex gap-0 justify-center lg:justify-start">
            <div class="text-center px-2 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 border border-white/10 border-r-0 bg-white/[0.03]">
              <div class="font-barlow-condensed font-black text-xl sm:text-2xl lg:text-4xl text-naranja leading-none">{{ days }}</div>
              <div class="text-[0.6rem] sm:text-xs tracking-widest uppercase text-gris-texto mt-1">Dias</div>
            </div>
            <div class="text-center px-2 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 border border-white/10 border-r-0 bg-white/[0.03]">
              <div class="font-barlow-condensed font-black text-xl sm:text-2xl lg:text-4xl text-naranja leading-none">{{ hours }}</div>
              <div class="text-[0.6rem] sm:text-xs tracking-widest uppercase text-gris-texto mt-1">Horas</div>
            </div>
            <div class="text-center px-2 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 border border-white/10 border-r-0 bg-white/[0.03]">
              <div class="font-barlow-condensed font-black text-xl sm:text-2xl lg:text-4xl text-naranja leading-none">{{ minutes }}</div>
              <div class="text-[0.6rem] sm:text-xs tracking-widest uppercase text-gris-texto mt-1">Min</div>
            </div>
            <div class="text-center px-2 sm:px-4 lg:px-6 py-2 sm:py-3 lg:py-4 border border-white/10 bg-white/[0.03]">
              <div class="font-barlow-condensed font-black text-xl sm:text-2xl lg:text-4xl text-naranja leading-none">{{ seconds }}</div>
              <div class="text-[0.6rem] sm:text-xs tracking-widest uppercase text-gris-texto mt-1">Seg</div>
            </div>
          </div>
          <div class="font-barlow-condensed text-xs sm:text-sm tracking-widest uppercase text-white/40 text-center lg:text-left lg:self-center lg:ml-5 mt-2 lg:mt-0">
            {{ raceStore.activeEdition.date }}<br>09:00h
          </div>
        </div>
      </div>

      <!-- Última noticia (igual que en Blog) -->
      <RouterLink v-if="latestPost" :to="`/blog/${latestPost.slug}`" class="relative z-10 w-full group block no-underline bg-negro overflow-hidden" :class="raceStore.activeEdition ? 'lg:max-w-none' : 'lg:col-span-2'">
        <div class="h-64 lg:h-80 bg-gris-medio relative overflow-hidden">
          <img
            v-if="latestPost.coverImage"
            :src="latestPost.coverImage"
            :alt="latestPost.title"
            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            loading="lazy"
          />
          <div v-else class="absolute inset-0 bg-gradient-to-br from-naranja/20 to-red-600/10" />
          <div v-if="!latestPost.coverImage" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-4xl opacity-15">📣</div>
        </div>
        <div class="p-6">
          <div class="font-barlow-condensed text-xs font-semibold tracking-[0.2em] uppercase text-naranja">Última noticia</div>
          <div class="font-barlow-condensed font-bold text-lg uppercase leading-tight mt-2 group-hover:text-naranja transition-colors">{{ latestPost.title }}</div>
          <div class="text-sm text-white/50 leading-relaxed mt-2">{{ latestPost.excerpt }}</div>
          <div class="text-sm text-gris-texto mt-4">{{ latestPost.publishedAt ? new Date(latestPost.publishedAt).toLocaleDateString('es-ES') : '' }}</div>
        </div>
      </RouterLink>
      <!-- Fallback cartel si no hay noticias ni carrera -->
      <div v-else class="relative z-10 w-full lg:max-w-none" :class="raceStore.activeEdition ? '' : 'lg:col-span-2 max-w-md mx-auto'">
        <div class="aspect-[3/4] flex items-center justify-center overflow-hidden">
          <span class="font-barlow-condensed text-sm tracking-widest uppercase text-white/25">Cartel IX Edicion</span>
        </div>
        <div class="font-barlow-condensed text-xs tracking-[0.2em] uppercase text-white/30 text-center mt-3">
          IX Edicion - Un Nuevo Impulso
        </div>
      </div>
      </div>
    </section>

    <!-- CLUB -->
    <section id="club" class="relative z-10 bg-gris-oscuro py-24 px-6">
      <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
        <div>
          <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">El Club</div>
          <h2 class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-6">
            COKALBA <span class="text-naranja">RUNNING</span>
          </h2>
          <p class="text-lg leading-relaxed text-white/65 mb-5">
            Somos un club de atletismo apasionado por el running y comprometido con nuestra comunidad. Cada ano organizamos la Carrera Solidaria "Un Nuevo Impulso" para recaudar fondos destinados a causas sociales.
          </p>
          <p class="text-lg leading-relaxed text-white/65">
            Nuestro objetivo es fomentar el deporte y la solidaridad, creando eventos donde cada paso cuenta. Unete a nosotros y se parte de esta gran familia.
          </p>
        </div>
        <div class="grid grid-cols-2 gap-6">
          <div class="bg-gris-medio p-7 border-l-[3px] border-naranja">
            <div class="font-barlow-condensed font-black text-5xl text-white leading-none">150+</div>
            <div class="text-sm text-gris-texto mt-2 uppercase tracking-wider">Corredores</div>
          </div>
          <div class="bg-gris-medio p-7 border-l-[3px] border-naranja">
            <div class="font-barlow-condensed font-black text-5xl text-white leading-none">9</div>
            <div class="text-sm text-gris-texto mt-2 uppercase tracking-wider">Ediciones</div>
          </div>
          <div class="bg-gris-medio p-7 border-l-[3px] border-naranja">
            <div class="font-barlow-condensed font-black text-5xl text-white leading-none">500+</div>
            <div class="text-sm text-gris-texto mt-2 uppercase tracking-wider">Participantes</div>
          </div>
          <div class="bg-gris-medio p-7 border-l-[3px] border-naranja">
            <div class="font-barlow-condensed font-black text-5xl text-white leading-none">1</div>
            <div class="text-sm text-gris-texto mt-2 uppercase tracking-wider">Causa Solidaria</div>
          </div>
        </div>
      </div>
    </section>


    <!-- MIEMBROS DEL CLUB -->
    <section v-if="clubMembers.length" class="relative z-10 bg-gris-oscuro py-24 px-6">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
          <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">El Equipo</div>
          <h2 class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-4">
            MIEMBROS <span class="text-naranja">DEL CLUB</span>
          </h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
          <div v-for="m in clubMembers" :key="m.id" class="text-center group">
            <div class="aspect-square rounded-full overflow-hidden bg-gris-medio mb-4 mx-auto max-w-[180px]">
              <img
                v-if="m.photoUrl"
                :src="m.photoUrl"
                :alt="m.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-white/15 text-4xl">👤</div>
            </div>
            <div class="font-barlow-condensed font-bold text-lg uppercase">{{ m.name }}</div>
            <div v-if="m.description" class="text-gris-texto text-sm mt-1">{{ m.description }}</div>
            <p v-if="m.bio" class="text-white/50 text-xs sm:text-sm mt-2 leading-relaxed">{{ m.bio }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- PATROCINADORES -->
    <section id="patrocinadores" class="relative z-10 bg-[#0A0A0A] py-24 px-6">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
          <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Colaboradores</div>
          <h2 class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-4">
            NUESTROS <span class="text-naranja">PATROCINADORES</span>
          </h2>
          <p class="text-white/40 max-w-lg mx-auto leading-relaxed">
            Gracias a su apoyo, la carrera solidaria "Un Nuevo Impulso" es posible ano tras ano. Sin ellos, este evento no existiria.
          </p>
        </div>

        <div v-if="sponsorStore.loading" class="text-white/50 text-center py-10">Cargando patrocinadores...</div>
        <div v-else-if="sponsorStore.error" class="text-red-400 text-center py-10">{{ sponsorStore.error }}</div>
        <div v-else-if="sponsorStore.sponsors.length === 0" class="text-white/50 text-center py-10">No hay patrocinadores disponibles.</div>
        <template v-else>
          <!-- Main sponsor -->
          <component
            :is="mainSponsor.website ? 'a' : 'div'"
            v-if="mainSponsor"
            :href="mainSponsor.website"
            target="_blank"
            rel="noopener noreferrer"
            class="block text-inherit no-underline mb-14"
          >
            <div class="flex flex-col items-center p-10 bg-white/[0.03] border border-white/[0.08] relative overflow-hidden">
              <div class="absolute inset-0 bg-gradient-to-br from-[rgba(112,195,55,0.04)] to-[rgba(0,162,224,0.04)] pointer-events-none" />
              <div class="relative z-10 font-barlow-condensed font-bold text-xs tracking-[0.28em] uppercase text-naranja border border-naranja/35 px-4 py-1.5 mb-7">
                ⭐ Sponsor Principal
              </div>
              <div class="relative z-10 bg-white p-7 shadow-[0_15px_50px_rgba(0,0,0,0.5)] mb-7 max-w-[320px] w-full">
                <img
                  v-if="mainSponsor.logoUrl"
                  :src="mainSponsor.logoUrl"
                  :alt="mainSponsor.name"
                  class="w-full block"
                />
              </div>
              <p v-if="mainSponsor.message" class="relative z-10 text-center max-w-[520px] text-white/50 leading-relaxed text-sm" v-html="mainSponsor.message.replace(/\*\*(.+?)\*\*/g, '<strong class=\'text-white/80 font-semibold\'>$1</strong>')" />
            </div>
          </component>

          <!-- Other sponsors -->
          <div v-if="otherSponsors.length > 0" class="font-barlow-condensed font-bold text-xs tracking-[0.25em] uppercase text-white/25 text-center mb-5">
            Colaboradores y patrocinadores
          </div>
          <div v-if="otherSponsors.length > 0" class="flex flex-wrap justify-center gap-px bg-white/[0.07] border border-white/[0.07]">
            <component
              v-for="s in otherSponsors"
              :key="s.id"
              :is="s.website ? 'a' : 'div'"
              :href="s.website"
              :target="s.website ? '_blank' : undefined"
              :rel="s.website ? 'noopener noreferrer' : undefined"
              class="bg-white flex items-center justify-center w-[220px] min-h-[130px] p-2.5 hover:bg-gray-100 transition-colors"
            >
              <img
                v-if="s.logoUrl"
                :src="s.logoUrl"
                :alt="s.name"
                class="w-full max-h-[120px] object-contain opacity-80 hover:opacity-100 transition-opacity"
                style="filter: grayscale(40%);"
                @mouseenter="($event.target as HTMLImageElement).style.filter = 'grayscale(0%)'"
                @mouseleave="($event.target as HTMLImageElement).style.filter = 'grayscale(40%)'"
              />
              <span v-else class="font-barlow-condensed font-semibold text-sm uppercase tracking-wider text-black/15 text-center">{{ s.name }}</span>
            </component>
          </div>
        </template>

        <div class="text-center mt-10 p-7 border border-white/[0.06]">
          <p class="text-white/30 text-sm mb-4">Tu empresa quiere apoyar la carrera? Contacta con nosotros.</p>
          <a href="mailto:info@cokalbarunning.es" class="font-barlow-condensed font-bold text-sm tracking-widest uppercase bg-transparent text-white border border-white/30 px-8 py-3 hover:border-naranja hover:text-naranja transition-colors inline-block">
            Conviertete en patrocinador
          </a>
        </div>
      </div>
    </section>
  </template>
  </div>
</template>
