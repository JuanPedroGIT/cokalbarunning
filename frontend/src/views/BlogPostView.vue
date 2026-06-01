<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api.service'

interface Post {
  id: string
  title: string
  slug: string
  excerpt: string
  content: string
  tag: string
  publishedAt: string | null
  coverImage: string | null
  createdAt: string
}

const route = useRoute()
const post = ref<Post | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

async function fetchPost(slug: string) {
  loading.value = true
  error.value = null
  try {
    const response = await api.get(`/posts/${slug}`)
    post.value = response.data.data
  } catch (e) {
    error.value = 'No se pudo cargar la noticia.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPost(route.params.slug as string)
})

watch(() => route.params.slug, (newSlug) => {
  if (newSlug) fetchPost(newSlug as string)
})
</script>

<template>
  <section class="relative z-10 pt-32 pb-20 px-6 max-w-4xl mx-auto">
    <div v-if="loading" class="text-white/50 text-center py-20">Cargando...</div>

    <div v-else-if="error" class="text-white/50 text-center py-20">{{ error }}</div>

    <div v-else-if="post">
      <div class="font-barlow-condensed font-semibold text-sm tracking-[0.3em] uppercase text-naranja mb-3">{{ post.tag }}</div>
      <h1 class="font-barlow-condensed font-black text-4xl uppercase mb-6">
        {{ post.title }}
      </h1>
      <div class="text-sm text-gris-texto mb-6">
        {{ post.publishedAt ? new Date(post.publishedAt).toLocaleDateString('es-ES') : '' }}
      </div>
      <img v-if="post.coverImage" :src="post.coverImage" class="w-full max-h-96 object-cover mb-8 rounded border border-white/5" loading="lazy" />
      <div class="text-white/70 leading-relaxed whitespace-pre-line">
        {{ post.content }}
      </div>
    </div>
  </section>
</template>
