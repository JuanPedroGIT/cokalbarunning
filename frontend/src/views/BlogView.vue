<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/services/api.service'
import { ref } from 'vue'
import { usePageMeta } from '@/composables/usePageMeta'

interface Post {
  id: string
  title: string
  slug: string
  excerpt: string
  tag: string
  publishedAt: string
  coverImage: string | null
}

const posts = ref<Post[]>([])
const loading = ref(true)

usePageMeta({
  title: 'Blog',
  description: 'Últimas noticias, resultados y novedades del club Cokalba Running y la carrera solidaria Un Nuevo Impulso.',
  url: '/blog',
})

onMounted(async () => {
  try {
    const response = await api.get('/posts')
    posts.value = response.data.data
  } catch (e) {
    // Fallback demo data
    posts.value = [
      {
        id: '1',
        title: 'Abiertas las inscripciones de la IX edicion',
        slug: 'abiertas-inscripciones-ix-edicion',
        excerpt: 'Este año volvemos con más categorías y el mismo espíritu solidario de siempre, a favor de la Asociación Síndrome X-Frágil.',
        tag: 'Carrera',
        publishedAt: '2026-05-15',
        coverImage: null,
      },
      {
        id: '2',
        title: 'Nuestros atletas brillan en el campeonato provincial',
        slug: 'atletas-campeonato-provincial',
        excerpt: 'Tres medallas para Cokalba Running en el reciente campeonato provincial de atletismo en pista.',
        tag: 'Club',
        publishedAt: '2026-04-03',
        coverImage: null,
      },
      {
        id: '3',
        title: 'Resumen de la VIII edicion: record de participantes',
        slug: 'resumen-viii-edicion',
        excerpt: 'La pasada edición batió todos los récords. Gracias a todos los que lo hicisteis posible.',
        tag: 'Noticias',
        publishedAt: '2025-07-12',
        coverImage: null,
      },
    ]
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="relative z-10 pt-32 pb-20 px-6 max-w-6xl mx-auto">
    <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">Noticias</div>
    <h1 class="font-barlow-condensed font-black text-[clamp(2.5rem,5vw,4.5rem)] leading-[0.95] uppercase mb-12">
      LO ULTIMO <span class="text-naranja">DEL CLUB</span>
    </h1>

    <div v-if="loading" class="text-white/50 text-center py-20">Cargando...</div>

    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <RouterLink
        v-for="post in posts"
        :key="post.id"
        :to="`/blog/${post.slug}`"
        class="bg-negro overflow-hidden group">
        <div class="h-44 bg-gris-medio relative overflow-hidden">
          <img v-if="post.coverImage" :src="post.coverImage" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
          <div v-else class="absolute inset-0 bg-gradient-to-br from-naranja/20 to-red-600/10" />
          <div v-if="!post.coverImage" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-4xl opacity-15">📣</div>
        </div>
        <div class="p-6">
          <div class="font-barlow-condensed text-xs font-semibold tracking-[0.2em] uppercase text-naranja">{{ post.tag }}</div>
          <div class="font-barlow-condensed font-bold text-lg uppercase leading-tight mt-2 group-hover:text-naranja transition-colors">{{ post.title }}</div>
          <div class="text-sm text-white/50 leading-relaxed mt-2">{{ post.excerpt }}</div>
          <div class="text-sm text-gris-texto mt-4">{{ post.publishedAt }}</div>
        </div>
      </RouterLink>
    </div>
  </section>
</template>
