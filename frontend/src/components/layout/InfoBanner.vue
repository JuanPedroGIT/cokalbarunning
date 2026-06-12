<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api.service'

interface BannerPost {
  id: string
  title: string
  slug: string
  excerpt: string
}

const banner = ref<BannerPost | null>(null)
const dismissed = ref(false)

onMounted(async () => {
  try {
    const res = await api.get('/banner')
    banner.value = res.data.data
  } catch {
    banner.value = null
  }
})
</script>

<template>
  <div
    v-if="banner && !dismissed"
    class="relative z-[60] bg-gradient-to-r from-[#FF5C00] to-[#FFD600] text-[#0A0A0A]"
  >
    <div class="max-w-6xl mx-auto px-6 py-3 flex items-start justify-between gap-4">
      <RouterLink
        :to="`/blog/${banner.slug}`"
        class="flex-1 min-w-0 flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-3 hover:opacity-90 transition-opacity"
      >
        <span class="font-barlow-condensed font-black text-sm tracking-widest uppercase shrink-0">
          Información importante
        </span>
        <span class="hidden sm:block w-px h-4 bg-[#0A0A0A]/30 self-center shrink-0" />
        <span class="font-barlow-condensed font-bold text-sm sm:text-base leading-snug">
          {{ banner.title }}
        </span>
        <span class="text-xs sm:text-sm text-[#0A0A0A]/80 leading-snug line-clamp-2">
          {{ banner.excerpt }}
        </span>
      </RouterLink>
      <button
        @click="dismissed = true"
        class="shrink-0 p-1 hover:bg-[#0A0A0A]/10 rounded transition-colors mt-0.5"
        aria-label="Cerrar banner"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</template>
