<script setup lang="ts">
import { onMounted, ref, onUnmounted, computed } from 'vue'
import { useRaceStore } from '@/stores/race.store'
import { useImageZoom } from '@/composables/useImageZoom'

const raceStore = useRaceStore()
const { zoomImage } = useImageZoom()

const raceDate = computed(() => {
  const d = raceStore.activeEdition?.date
  return d ? new Date(d + 'T09:00:00') : new Date('2026-07-05T09:00:00')
})

const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0, expired: false })
let timer: ReturnType<typeof setInterval> | null = null

function tick() {
  const diff = raceDate.value.getTime() - Date.now()
  if (diff <= 0) {
    countdown.value = { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true }
    if (timer) { clearInterval(timer); timer = null }
    return
  }
  countdown.value = {
    days: Math.floor(diff / 86400000),
    hours: Math.floor((diff % 86400000) / 3600000),
    minutes: Math.floor((diff % 3600000) / 60000),
    seconds: Math.floor((diff % 60000) / 1000),
    expired: false,
  }
}

const pad = (n: number) => String(n).padStart(2, '0')

onMounted(() => {
  raceStore.fetchActiveEdition().then(() => { tick(); timer = setInterval(tick, 1000) })
})
onUnmounted(() => { if (timer) clearInterval(timer) })
</script>

<template>
  <div v-if="raceStore.loading" class="text-white/50 text-center py-32">Cargando...</div>

  <template v-else>
    <div class="relative z-10">
    <!-- HERO -->
    <section class="pt-32 pb-16 px-6 max-w-6xl mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-12 items-center">
        <div>
          <div class="inline-flex items-center gap-2 font-barlow-condensed font-bold text-xs tracking-[0.25em] uppercase text-naranja border border-naranja/30 px-3 py-1.5 mb-6">
            <span class="w-2 h-2 rounded-full bg-naranja" :class="{ 'animate-pulse': !countdown.expired }" />
            {{ raceStore.activeEdition?.name || 'IX Edición · Carrera Solidaria' }}
          </div>
          <h1 class="font-barlow-condensed font-black text-[clamp(3.5rem,8vw,7.5rem)] leading-[0.9] uppercase mb-6">
            UN NUEVO<br><span class="text-naranja">IMPULSO</span>
          </h1>
          <p class="text-base font-light text-white/50 leading-relaxed max-w-[460px] mb-3">
            {{ raceStore.activeEdition?.description || 'El 5 de julio de 2026 volvemos a las calles y caminos de Coca de Alba. Inscríbete y forma parte de la carrera solidaria más especial de la comarca.' }}
          </p>
          <p v-if="raceStore.activeEdition?.solidarityCause" class="font-barlow-condensed text-xs font-semibold tracking-[0.15em] uppercase text-white/30 mb-8">
            <a v-if="raceStore.activeEdition?.solidarityUrl" :href="raceStore.activeEdition.solidarityUrl" target="_blank" class="hover:text-naranja transition-colors">
              {{ raceStore.activeEdition.solidarityCause }}
            </a>
            <span v-else>{{ raceStore.activeEdition.solidarityCause }}</span>
          </p>

          <!-- Countdown -->
          <div class="font-barlow-condensed font-semibold text-[0.7rem] tracking-[0.25em] uppercase text-white/30 mb-2">
            {{ countdown.expired ? '¡La carrera ha comenzado!' : 'Cuenta atrás · Salida 09:00h' }}
          </div>
          <div class="flex gap-0 mb-3">
            <div class="text-center px-5 py-3 border border-white/[0.08] border-r-0 bg-white/[0.02] min-w-[76px]">
              <div class="font-barlow-condensed font-black text-4xl text-naranja leading-none tabular-nums">{{ pad(countdown.days) }}</div>
              <div class="text-[0.6rem] tracking-[0.18em] uppercase text-white/30 mt-1">Días</div>
            </div>
            <div class="text-center px-5 py-3 border border-white/[0.08] border-r-0 bg-white/[0.02] min-w-[76px]">
              <div class="font-barlow-condensed font-black text-4xl text-naranja leading-none tabular-nums">{{ pad(countdown.hours) }}</div>
              <div class="text-[0.6rem] tracking-[0.18em] uppercase text-white/30 mt-1">Horas</div>
            </div>
            <div class="text-center px-5 py-3 border border-white/[0.08] border-r-0 bg-white/[0.02] min-w-[76px]">
              <div class="font-barlow-condensed font-black text-4xl text-naranja leading-none tabular-nums">{{ pad(countdown.minutes) }}</div>
              <div class="text-[0.6rem] tracking-[0.18em] uppercase text-white/30 mt-1">Min</div>
            </div>
            <div class="text-center px-5 py-3 border border-white/[0.08] bg-white/[0.02] min-w-[76px]">
              <div class="font-barlow-condensed font-black text-4xl text-naranja leading-none tabular-nums">{{ pad(countdown.seconds) }}</div>
              <div class="text-[0.6rem] tracking-[0.18em] uppercase text-white/30 mt-1">Seg</div>
            </div>
          </div>
          <div class="font-barlow-condensed text-xs tracking-[0.12em] uppercase text-white/25 mb-8">
            📍 Coca de Alba · 8.124 m · 9.735 Varas Castellanas
          </div>

          <!-- CTA -->
          <div class="flex flex-wrap gap-3 mb-8">
            <a v-if="!countdown.expired" :href="raceStore.activeEdition?.registrationUrl || 'https://www.deporticket.com/web-evento/13254-ix-carrera-solidaria-un-nuevo-impulso'" target="_blank" class="font-barlow-condensed font-bold text-base tracking-[0.1em] uppercase bg-naranja text-negro px-8 py-3 hover:bg-amarillo transition-colors inline-block">
              ¡Inscríbete ahora!
            </a>
            <span v-else class="font-barlow-condensed font-bold text-base tracking-[0.1em] uppercase bg-gray-600 text-gray-300 px-8 py-3 inline-block cursor-not-allowed">
              Inscripciones cerradas
            </span>
            <a href="#recorrido" class="font-barlow-condensed font-bold text-sm tracking-[0.1em] uppercase bg-transparent text-white border border-white/25 px-6 py-3 hover:border-naranja hover:text-naranja transition-colors inline-block">
              Ver recorrido
            </a>
            <a href="https://media.cokalba-running.com/un-nuevo-impulso/docs/REGLAMENTO_IX_CARRERA_SOLIDARIA_UN_NUEVO_IMPULSO.pdf" target="_blank" class="font-barlow-condensed font-bold text-sm tracking-[0.1em] uppercase bg-transparent text-white border border-white/25 px-6 py-3 hover:border-naranja hover:text-naranja transition-colors inline-block">
              Reglamento PDF
            </a>
          </div>
          <div v-if="raceStore.activeEdition?.inscriptionInfo" class="text-xs text-white/25 italic">
            {{ raceStore.activeEdition.inscriptionInfo }}
          </div>
        </div>

        <!-- Cartel -->
        <div v-if="raceStore.activeEdition?.posterUrl" class="relative max-w-[320px] mx-auto lg:max-w-none">
          <div class="absolute inset-[-10px] border border-naranja/[0.18] pointer-events-none" />
          <div class="absolute inset-[-20px] border border-naranja/[0.07] pointer-events-none" />
          <img :src="raceStore.activeEdition.posterUrl" alt="Cartel oficial" class="w-full relative z-10 cursor-zoom-in shadow-[0_30px_80px_rgba(0,0,0,0.8),0_0_60px_rgba(255,92,0,0.1)] hover:scale-[1.02] hover:-translate-y-1 transition-transform duration-400" loading="lazy" @click="zoomImage($event.target as HTMLImageElement)" />
          <div class="font-barlow-condensed font-semibold text-[0.68rem] tracking-[0.2em] uppercase text-white/20 text-center mt-4 relative z-10">
            {{ raceStore.activeEdition?.name || 'IX Edición · 5 de Julio 2026' }}
          </div>
        </div>
      </div>
    </section>

    <div class="divider"></div>

    <!-- INFO RÁPIDA -->
    <section class="bg-[#141414] py-20 px-6">
      <div class="max-w-6xl mx-auto">
        <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Datos de la prueba</div>
        <h2 class="font-barlow-condensed font-black text-[clamp(2rem,4.5vw,3.8rem)] leading-[0.95] uppercase mb-10">
          {{ raceStore.activeEdition?.name || 'IX CARRERA SOLIDARIA' }}
        </h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-px bg-white/[0.07] border border-white/[0.07]">
          <div class="bg-[#0A0A0A] p-6 hover:bg-naranja/5 transition-colors">
            <div class="text-2xl mb-2">📅</div>
            <div class="font-barlow-condensed font-semibold text-xs tracking-[0.2em] uppercase text-gray-500 mb-1">Fecha y hora</div>
            <div class="font-barlow-condensed font-bold text-lg text-white">{{ raceStore.activeEdition?.date || '5 Julio 2026' }}</div>
            <div class="text-sm text-white/40 mt-1">Salida: 09:00h<br>Cierre meta: 10:20h</div>
          </div>
          <div class="bg-[#0A0A0A] p-6 hover:bg-naranja/5 transition-colors">
            <div class="text-2xl mb-2">📍</div>
            <div class="font-barlow-condensed font-semibold text-xs tracking-[0.2em] uppercase text-gray-500 mb-1">Lugar</div>
            <div class="font-barlow-condensed font-bold text-lg text-white">{{ raceStore.activeEdition?.location || 'Coca de Alba' }}</div>
            <div class="text-sm text-white/40 mt-1">Salida y meta:<br>Calle Larga nº 16</div>
          </div>
          <div class="bg-[#0A0A0A] p-6 hover:bg-naranja/5 transition-colors">
            <div class="text-2xl mb-2">📏</div>
            <div class="font-barlow-condensed font-semibold text-xs tracking-[0.2em] uppercase text-gray-500 mb-1">Distancia</div>
            <div class="font-barlow-condensed font-bold text-lg text-white">8.124 metros</div>
            <div class="text-sm text-white/40 mt-1">9.735 Varas Castellanas<br>Homologado D.S. Atletismo</div>
          </div>
          <div class="bg-[#0A0A0A] p-6 hover:bg-naranja/5 transition-colors">
            <div class="text-2xl mb-2">💶</div>
            <div class="font-barlow-condensed font-semibold text-xs tracking-[0.2em] uppercase text-gray-500 mb-1">Inscripción</div>
            <div class="font-barlow-condensed font-bold text-lg text-white">9 € + gestión</div>
            <div class="text-sm text-white/40 mt-1">Infantiles gratuito<br>Hasta 2 julio o 150 plazas</div>
          </div>
          <div class="bg-[#0A0A0A] p-6 hover:bg-naranja/5 transition-colors">
            <div class="text-2xl mb-2">👕</div>
            <div class="font-barlow-condensed font-semibold text-xs tracking-[0.2em] uppercase text-gray-500 mb-1">Dorsal</div>
            <div class="font-barlow-condensed font-bold text-lg text-white">Día de la carrera</div>
            <div class="text-sm text-white/40 mt-1">Desde las 07:45h<br>Con DNI obligatorio</div>
          </div>
          <div class="bg-[#0A0A0A] p-6 hover:bg-naranja/5 transition-colors">
            <div class="text-2xl mb-2">🏥</div>
            <div class="font-barlow-condensed font-semibold text-xs tracking-[0.2em] uppercase text-gray-500 mb-1">Servicio médico</div>
            <div class="font-barlow-condensed font-bold text-lg text-white">Ambulancia</div>
            <div class="text-sm text-white/40 mt-1">En meta y durante el recorrido</div>
          </div>
          <div class="bg-[#0A0A0A] p-6 hover:bg-naranja/5 transition-colors">
            <div class="text-2xl mb-2">💧</div>
            <div class="font-barlow-condensed font-semibold text-xs tracking-[0.2em] uppercase text-gray-500 mb-1">Avituallamiento</div>
            <div class="font-barlow-condensed font-bold text-lg text-white">Km 5 + Meta</div>
            <div class="text-sm text-white/40 mt-1">Según norma World Athletics<br>Ágape final en Multiusos</div>
          </div>
          <div v-if="raceStore.activeEdition?.solidarityCause" class="bg-[#0A0A0A] p-6 hover:bg-naranja/5 transition-colors">
            <div class="text-2xl mb-2">❤️</div>
            <div class="font-barlow-condensed font-semibold text-xs tracking-[0.2em] uppercase text-gray-500 mb-1">Causa solidaria</div>
            <a v-if="raceStore.activeEdition?.solidarityUrl" :href="raceStore.activeEdition.solidarityUrl" target="_blank" class="font-barlow-condensed font-bold text-lg text-white hover:text-naranja transition-colors">{{ raceStore.activeEdition.solidarityCause }}</a>
            <span v-else class="font-barlow-condensed font-bold text-lg text-white">{{ raceStore.activeEdition.solidarityCause }}</span>
            <div class="text-sm text-white/40 mt-1">Toda la recaudación para investigación de la asociación</div>
          </div>
        </div>
      </div>
    </section>

    <div class="divider"></div>

    <!-- CATEGORÍAS -->
    <section class="py-20 px-6">
      <div class="max-w-6xl mx-auto">
        <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Participantes</div>
        <h2 class="font-barlow-condensed font-black text-[clamp(2rem,4.5vw,3.8rem)] leading-[0.95] uppercase mb-4">
          CATEGORÍAS <span class="text-naranja">Y DISTANCIAS</span>
        </h2>
        <p class="text-white/45 max-w-[580px] leading-relaxed mb-2">
          Desde los más pequeños hasta los veteranos. Las categorías se computan por año natural. Edad mínima para la carrera absoluta: 16 años.
        </p>

        <!-- Promocionales -->
        <div class="font-barlow-condensed font-bold text-xs tracking-[0.2em] uppercase text-naranja mt-8 mb-3">— Carreras de promoción (gratuitas) —</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-px bg-white/[0.07] border border-white/[0.07]">
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">140m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Chupetines</div>
            <div class="text-sm text-gray-500">Nacidos 2021–2025</div>
            <div class="text-xs font-semibold tracking-wider uppercase text-amber-400 mt-1">Gratuito</div>
            <div class="text-xs text-white/30 mt-0.5">No competitiva</div>
          </div>
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">315m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Pre-Benjamines</div>
            <div class="text-sm text-gray-500">Nacidos 2019–2020</div>
            <div class="text-xs font-semibold tracking-wider uppercase text-amber-400 mt-1">Gratuito</div>
            <div class="text-xs text-white/30 mt-0.5">No competitiva</div>
          </div>
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">590m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Benjamines</div>
            <div class="text-sm text-gray-500">Nacidos 2017–2018</div>
            <div class="text-xs font-semibold tracking-wider uppercase text-amber-400 mt-1">Gratuito</div>
          </div>
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">590m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Alevines</div>
            <div class="text-sm text-gray-500">Nacidos 2015–2016</div>
            <div class="text-xs font-semibold tracking-wider uppercase text-amber-400 mt-1">Gratuito</div>
          </div>
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">1.950m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Infantiles</div>
            <div class="text-sm text-gray-500">Nacidos 2013–2014</div>
            <div class="text-xs font-semibold tracking-wider uppercase text-amber-400 mt-1">Gratuito</div>
          </div>
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">1.950m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Cadetes</div>
            <div class="text-sm text-gray-500">Nacidos 2011–2012</div>
            <div class="text-xs font-semibold tracking-wider uppercase text-amber-400 mt-1">Gratuito</div>
          </div>
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">8.124m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Juveniles</div>
            <div class="text-sm text-gray-500">Nacidos 2009–2010</div>
            <div class="text-xs font-semibold tracking-wider uppercase text-amber-400 mt-1">Gratuito</div>
          </div>
        </div>

        <!-- Absolutas -->
        <div class="font-barlow-condensed font-bold text-xs tracking-[0.2em] uppercase text-naranja mt-8 mb-3">— Categorías absolutas (9 € + gestión) —</div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-px bg-white/[0.07] border border-white/[0.07]">
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">8.124m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Senior</div>
            <div class="text-sm text-gray-500">Nacidos 1991–2010 (16 a 35 años)</div>
          </div>
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">8.124m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Veterano A</div>
            <div class="text-sm text-gray-500">Nacidos 1982–1992 (34 a 44 años)</div>
          </div>
          <div class="bg-[#0A0A0A] p-5 hover:bg-naranja/5 transition-colors">
            <div class="font-barlow-condensed font-black text-3xl text-naranja">8.124m</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mt-1">Veterano B</div>
            <div class="text-sm text-gray-500">Nacidos antes de 1982 (45 años en adelante)</div>
          </div>
        </div>
        <p class="text-white/30 text-xs mt-4">* Menores de 18 años en categoría absoluta necesitan consentimiento firmado de padres/tutores.</p>
      </div>
    </section>

    <div class="divider"></div>

    <!-- RECORRIDO -->
    <section id="recorrido" class="bg-[#141414] py-20 px-6">
      <div class="max-w-6xl mx-auto">
        <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Trazado</div>
        <h2 class="font-barlow-condensed font-black text-[clamp(2rem,4.5vw,3.8rem)] leading-[0.95] uppercase mb-10">
          RECORRIDO <span class="text-naranja">DE LA CARRERA</span>
        </h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div>
            <div class="mb-6">
              <img src="https://media.cokalba-running.com/un-nuevo-impulso/docs/recorrido-carrera.jpeg" alt="Recorrido carrera" class="w-full border border-white/5 shadow-[0_15px_50px_rgba(0,0,0,0.6)] cursor-zoom-in" @click="zoomImage($event.target as HTMLImageElement)" />
              <div class="font-barlow-condensed font-semibold text-xs tracking-[0.18em] uppercase text-white/30 text-center mt-2">Vista 3D del recorrido · Coca de Alba</div>
            </div>
            <div>
              <img src="https://media.cokalba-running.com/un-nuevo-impulso/docs/perfil-carrera.jpeg" alt="Perfil carrera" class="w-full border border-white/5 shadow-[0_8px_30px_rgba(0,0,0,0.5)] cursor-zoom-in" @click="zoomImage($event.target as HTMLImageElement)" />
              <div class="font-barlow-condensed font-semibold text-xs tracking-[0.18em] uppercase text-white/30 text-center mt-2">Perfil altimétrico · Desnivel suave entre 840m y 905m</div>
            </div>
          </div>
          <div class="flex flex-col gap-3">
            <div class="flex items-start gap-4 p-4 bg-white/[0.03] border-l-2 border-naranja/50">
              <div class="text-xl mt-0.5">🏁</div>
              <div>
                <div class="font-barlow-condensed font-semibold text-xs tracking-[0.18em] uppercase text-gray-500 mb-0.5">Salida y Meta</div>
                <div class="font-barlow-condensed font-bold text-lg text-white">Calle Larga nº 16</div>
                <div class="text-sm text-white/40 mt-0.5">Coca de Alba, Salamanca</div>
              </div>
            </div>
            <div class="flex items-start gap-4 p-4 bg-white/[0.03] border-l-2 border-naranja/50">
              <div class="text-xl mt-0.5">📏</div>
              <div>
                <div class="font-barlow-condensed font-semibold text-xs tracking-[0.18em] uppercase text-gray-500 mb-0.5">Distancia total</div>
                <div class="font-barlow-condensed font-bold text-lg text-white">8.124 metros</div>
                <div class="text-sm text-white/40 mt-0.5">9.735 Varas Castellanas. Medido y validado por el Comité de Jueces de la Delegación Salmantina de Atletismo</div>
              </div>
            </div>
            <div class="flex items-start gap-4 p-4 bg-white/[0.03] border-l-2 border-naranja/50">
              <div class="text-xl mt-0.5">⛰️</div>
              <div>
                <div class="font-barlow-condensed font-semibold text-xs tracking-[0.18em] uppercase text-gray-500 mb-0.5">Altimetría</div>
                <div class="font-barlow-condensed font-bold text-lg text-white">840 – 905 m</div>
                <div class="text-sm text-white/40 mt-0.5">Desnivel acumulado moderado. Parte por casco urbano y parte por caminos de tierra.</div>
              </div>
            </div>
            <div class="flex items-start gap-4 p-4 bg-white/[0.03] border-l-2 border-naranja/50">
              <div class="text-xl mt-0.5">💧</div>
              <div>
                <div class="font-barlow-condensed font-semibold text-xs tracking-[0.18em] uppercase text-gray-500 mb-0.5">Avituallamiento</div>
                <div class="font-barlow-condensed font-bold text-lg text-white">Km 5 aprox.</div>
                <div class="text-sm text-white/40 mt-0.5">Según norma World Athletics. También en zona de meta.</div>
              </div>
            </div>
            <div class="flex items-start gap-4 p-4 bg-white/[0.03] border-l-2 border-naranja/50">
              <div class="text-xl mt-0.5">⏱️</div>
              <div>
                <div class="font-barlow-condensed font-semibold text-xs tracking-[0.18em] uppercase text-gray-500 mb-0.5">Tiempo máximo</div>
                <div class="font-barlow-condensed font-bold text-lg text-white">1h 20 min</div>
                <div class="text-sm text-white/40 mt-0.5">Cierre de control de llegada a las 10:20h. Cronometrado por Delegación Salmantina de Atletismo.</div>
              </div>
            </div>
            <div class="flex items-start gap-4 p-4 bg-white/[0.03] border-l-2 border-naranja/50">
              <div class="text-xl mt-0.5">📍</div>
              <div>
                <div class="font-barlow-condensed font-semibold text-xs tracking-[0.18em] uppercase text-gray-500 mb-0.5">Puntos kilométricos</div>
                <div class="font-barlow-condensed font-bold text-lg text-white">Marcados en ruta</div>
                <div class="text-sm text-white/40 mt-0.5">Señalización cada km: km 1 · km 2 · km 3 · km 4 · km 5 · km 6 · km 7</div>
              </div>
            </div>
            <div class="mt-2">
              <a href="https://media.cokalba-running.com/un-nuevo-impulso/docs/recorrido_ni%C3%B1os.pdf" target="_blank" class="font-barlow-condensed font-bold text-xs tracking-[0.12em] uppercase text-naranja border border-naranja/40 px-4 py-2 hover:bg-naranja hover:text-negro transition-colors inline-block">
                📄 Ver recorridos infantiles PDF
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="divider"></div>

    <!-- REGLAMENTO -->
    <section class="py-20 px-6">
      <div class="max-w-6xl mx-auto">
        <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Normativa</div>
        <h2 class="font-barlow-condensed font-black text-[clamp(2rem,4.5vw,3.8rem)] leading-[0.95] uppercase mb-3">
          REGLAMENTO <span class="text-naranja">RESUMIDO</span>
        </h2>
        <p class="text-white/45 max-w-[560px] leading-relaxed mb-8">Los puntos más importantes para participar.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-white/[0.03] border border-white/[0.07] p-5">
            <div class="font-barlow-condensed font-bold text-xs tracking-[0.2em] uppercase text-naranja mb-2">Art. 3 · Dorsales</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Recogida el día de la carrera</div>
            <div class="text-sm text-white/50 leading-relaxed">Desde las <strong class="text-white/75 font-semibold">07:45h hasta 10 min antes</strong> de la salida. Imprescindible presentar <strong class="text-white/75 font-semibold">DNI o documento identificativo</strong>. No se realizan inscripciones el día de la carrera. Con el dorsal se entrega <strong class="text-white/75 font-semibold">camiseta conmemorativa</strong> (hasta fin de existencias, aprox. 180 uds).</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-5">
            <div class="font-barlow-condensed font-bold text-xs tracking-[0.2em] uppercase text-naranja mb-2">Art. 4 · Normas</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Dorsal visible en la parte delantera</div>
            <div class="text-sm text-white/50 leading-relaxed">El dorsal debe llevarse <strong class="text-white/75 font-semibold">en la parte delantera</strong> de la camiseta y siempre visible. Quedan descalificados quienes no lleven dorsal a la llegada, no cubran el recorrido completo o no atiendan las indicaciones de los jueces.</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-5">
            <div class="font-barlow-condensed font-bold text-xs tracking-[0.2em] uppercase text-naranja mb-2">Art. 5 · Inscripciones</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Plazo hasta el 2 de julio</div>
            <div class="text-sm text-white/50 leading-relaxed">Las inscripciones cierran el <strong class="text-white/75 font-semibold">2 de julio de 2026 a las 23:59h</strong> o al completarse <strong class="text-white/75 font-semibold">150 inscritos</strong>. Coste desde categoría Senior: <strong class="text-white/75 font-semibold">9 € + gastos de gestión</strong>. Existe <strong class="text-white/75 font-semibold">Dorsal Cero</strong> para quienes quieran colaborar con la Asociación X-Frágil.</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-5">
            <div class="font-barlow-condensed font-bold text-xs tracking-[0.2em] uppercase text-naranja mb-2">Art. 8 · Categorías</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Por año natural de nacimiento</div>
            <div class="text-sm text-white/50 leading-relaxed">Las categorías se asignan por <strong class="text-white/75 font-semibold">año de nacimiento</strong>, no por edad cumplida el día de la carrera. Edad mínima para la carrera absoluta: <strong class="text-white/75 font-semibold">16 años</strong> (con consentimiento paterno si es menor de 18). Carreras de Chupetines y Pre-Benjamines son <strong class="text-white/75 font-semibold">no competitivas</strong>.</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-5">
            <div class="font-barlow-condensed font-bold text-xs tracking-[0.2em] uppercase text-naranja mb-2">Art. 12 · Seguro</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Seguro de accidentes incluido</div>
            <div class="text-sm text-white/50 leading-relaxed">Todos los participantes están cubiertos por un <strong class="text-white/75 font-semibold">seguro de accidentes deportivo y de responsabilidad civil</strong> durante el desarrollo de la carrera. No cubre desplazamientos al lugar ni padecimientos previos.</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-5">
            <div class="font-barlow-condensed font-bold text-xs tracking-[0.2em] uppercase text-naranja mb-2">Art. 17 · Acompañamiento</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Sin mascotas ni acompañantes</div>
            <div class="text-sm text-white/50 leading-relaxed">No está permitido correr acompañado de <strong class="text-white/75 font-semibold">mascotas ni personas ajenas</strong> a la competición, en especial menores de edad. El incumplimiento conlleva <strong class="text-white/75 font-semibold">descalificación automática</strong> y exclusión de premios.</div>
          </div>
        </div>
        <div class="mt-8 flex flex-wrap items-center gap-4">
          <p class="text-white/40 text-sm">¿Quieres consultar el reglamento completo?</p>
          <a href="https://media.cokalba-running.com/un-nuevo-impulso/docs/REGLAMENTO_IX_CARRERA_SOLIDARIA_UN_NUEVO_IMPULSO.pdf" target="_blank" class="font-barlow-condensed font-bold text-sm tracking-widest uppercase bg-naranja text-negro px-6 py-3 hover:bg-amarillo transition-colors inline-block">
            📄 Descargar reglamento completo
          </a>
        </div>
      </div>
    </section>

    <div class="divider"></div>

    <!-- PREMIOS -->
    <section class="bg-[#141414] py-20 px-6">
      <div class="max-w-6xl mx-auto">
        <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Trofeos y reconocimientos</div>
        <h2 class="font-barlow-condensed font-black text-[clamp(2rem,4.5vw,3.8rem)] leading-[0.95] uppercase mb-10">
          PREMIOS <span class="text-naranja">Y TROFEOS</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="bg-white/[0.03] border border-white/[0.07] p-6 text-center">
            <div class="text-2xl mb-2">🥇</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Absoluta M/F</div>
            <div class="text-sm text-white/45 leading-relaxed">Premio a los 3 primeros clasificados masculino y femenino en categoría absoluta</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-6 text-center">
            <div class="text-2xl mb-2">🏅</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Local M/F</div>
            <div class="text-sm text-white/45 leading-relaxed">Premio a los 3 primeros clasificados locales masculino y femenino. Acumulable con otras categorías</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-6 text-center">
            <div class="text-2xl mb-2">🎖️</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Senior M/F</div>
            <div class="text-sm text-white/45 leading-relaxed">Premio a los 3 primeros clasificados Senior masculino y femenino</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-6 text-center">
            <div class="text-2xl mb-2">🏆</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Veterano A M/F</div>
            <div class="text-sm text-white/45 leading-relaxed">Premio a los 3 primeros clasificados Veterano A masculino y femenino</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-6 text-center">
            <div class="text-2xl mb-2">🏆</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Veterano B M/F</div>
            <div class="text-sm text-white/45 leading-relaxed">Premio a los 3 primeros clasificados Veterano B masculino y femenino</div>
          </div>
          <div class="bg-white/[0.03] border border-white/[0.07] p-6 text-center">
            <div class="text-2xl mb-2">🎽</div>
            <div class="font-barlow-condensed font-bold text-base uppercase mb-2">Bolsa del corredor</div>
            <div class="text-sm text-white/45 leading-relaxed">Para todos los participantes que recojan el dorsal. Ágape final en el Multiusos para todos los que lleguen a meta</div>
          </div>
        </div>

        <!-- Camiseta -->
        <div v-if="raceStore.activeEdition?.shirtUrl" class="mt-12 flex flex-col items-center gap-4 text-center">
          <div class="font-barlow-condensed font-bold text-base tracking-[0.2em] uppercase text-naranja">Camiseta conmemorativa 2026</div>
          <img :src="raceStore.activeEdition.shirtUrl" alt="Camiseta conmemorativa" class="w-full max-w-2xl border border-white/[0.08] shadow-[0_20px_60px_rgba(0,0,0,0.6)] cursor-zoom-in" loading="lazy" @click="zoomImage($event.target as HTMLImageElement)" />
        </div>

        <p class="text-xs text-white/30 mt-6">* Los premios no son acumulativos, salvo para la categoría Local. Medalla para los 3 primeros de cada categoría infantil. Bolsa del corredor infantil para todos los participantes en las carreras de promoción.</p>
      </div>
    </section>

    <div class="divider"></div>

    <!-- CTA FINAL -->
    <section class="py-20 px-6 text-center">
      <div class="max-w-6xl mx-auto">
        <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">¿Todo listo?</div>
        <h2 class="font-barlow-condensed font-black text-[clamp(2rem,4.5vw,3.8rem)] leading-[0.95] uppercase mb-4">
          {{ countdown.expired ? 'GRACIAS' : '¡ASEGURA' }} <span class="text-naranja">{{ countdown.expired ? 'A TODOS!' : 'TU DORSAL!' }}</span>
        </h2>
        <p class="text-white/45 max-w-[480px] mx-auto mb-8 leading-relaxed">
          {{ countdown.expired ? 'Gracias a todos los participantes, voluntarios y patrocinadores que hicieron posible esta edición.' : 'Plazas limitadas a 150 corredores. Inscripciones abiertas hasta el 2 de julio de 2026 o hasta completar el aforo.' }}
        </p>
        <a v-if="!countdown.expired" href="https://www.deporticket.com/web-evento/13254-ix-carrera-solidaria-un-nuevo-impulso" target="_blank" class="font-barlow-condensed font-bold text-lg tracking-widest uppercase bg-naranja text-negro px-10 py-4 hover:bg-amarillo transition-colors inline-block">
          Inscríbete en Deporticket →
        </a>
        <div v-if="!countdown.expired" class="mt-4 text-sm text-white/25">9 € + gestión · Categorías infantiles gratuitas · Camiseta conmemorativa incluida</div>
      </div>
    </section>
    </div>
  </template>
</template>

<style scoped>
.divider {
  height: 1px;
  background: linear-gradient(to right, transparent, rgba(255, 92, 0, 0.35), transparent);
  max-width: 1200px;
  margin: 0 auto;
}
</style>
