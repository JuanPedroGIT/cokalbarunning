<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/services/api.service'
import { usePageMeta } from '@/composables/usePageMeta'
import InstagramSvg from '@/assets/icons/instagram.svg?raw'
import WhatsappSvg from '@/assets/icons/whatsapp.svg?raw'
import XSvg from '@/assets/icons/x.svg?raw'

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

watch(() => post.value, (newPost) => {
  if (newPost) {
    usePageMeta({
      title: newPost.title,
      description: newPost.excerpt || 'Noticia del club Cokalba Running.',
      image: newPost.coverImage || undefined,
      type: 'article',
      url: `/blog/${newPost.slug}`,
    })
  }
})

function shareUrl(): string {
  return encodeURIComponent(window.location.href)
}

function shareText(): string {
  return encodeURIComponent(post.value?.title ?? document.title)
}

function shareToX() {
  window.open(
    `https://twitter.com/intent/tweet?url=${shareUrl()}&text=${shareText()}`,
    '_blank',
    'width=600,height=400',
  )
}

function shareToWhatsApp() {
  window.open(
    `https://wa.me/?text=${shareText()}%20${shareUrl()}`,
    '_blank',
  )
}

function shareToInstagram() {
  const url = window.location.href
  if (navigator.share) {
    navigator.share({ title: post.value?.title ?? document.title, url }).catch(() => {})
  } else if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => {
      alert('Enlace copiado al portapapeles. Ábrelo en Instagram para compartir.')
    })
  } else {
    window.open('https://www.instagram.com/', '_blank')
  }
}
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
      <div class="flex items-center gap-3 mb-6">
        <div class="text-sm text-gris-texto">
          {{ post.publishedAt ? new Date(post.publishedAt).toLocaleDateString('es-ES') : '' }}
        </div>
        <span class="text-white/10">|</span>
        <div class="flex items-center gap-2">
          <button
            @click="shareToX"
            title="Compartir en X"
            class="w-4 h-4 text-gris-texto hover:text-naranja opacity-70 hover:opacity-100 transition cursor-pointer"
            v-html="XSvg"
          />
          <button
            @click="shareToWhatsApp"
            title="Compartir por WhatsApp"
            class="w-4 h-4 text-gris-texto hover:text-naranja opacity-70 hover:opacity-100 transition cursor-pointer"
            v-html="WhatsappSvg"
          />
          <button
            @click="shareToInstagram"
            title="Compartir en Instagram"
            class="w-4 h-4 text-gris-texto hover:text-naranja opacity-70 hover:opacity-100 transition cursor-pointer"
            v-html="InstagramSvg"
          />
        </div>
      </div>
      <img v-if="post.coverImage" :src="post.coverImage" class="w-full max-h-96 object-cover mb-8 rounded border border-white/5" loading="lazy" />
      <!-- ADVERTENCIA: el backend debe sanitizar el HTML antes de guardarlo -->
      <div class="text-white/70 leading-relaxed" v-html="post.content"></div>
    </div>
  </section>
</template>
