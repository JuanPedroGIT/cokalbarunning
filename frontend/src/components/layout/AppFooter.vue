<script setup lang="ts">
import { RouterLink } from 'vue-router'
import InstagramSvg from '@/assets/icons/instagram.svg?raw'
import WhatsappSvg from '@/assets/icons/whatsapp.svg?raw'
import XSvg from '@/assets/icons/x.svg?raw'

function shareUrl(): string {
  return encodeURIComponent(window.location.href)
}

function shareText(): string {
  return encodeURIComponent(document.title)
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
    navigator.share({ title: document.title, url }).catch(() => {})
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
  <footer class="relative z-10 bg-gris-oscuro py-12 px-6 text-center">
    <div class="max-w-4xl mx-auto">
      <div class="font-barlow-condensed font-black text-2xl tracking-widest text-white uppercase mb-6">
        COKALBA <span class="text-naranja">RUNNING</span>
      </div>
      <ul class="flex flex-wrap justify-center gap-6 list-none mb-6">
        <li><RouterLink to="/carrera" class="text-gris-texto hover:text-naranja transition-colors text-sm uppercase tracking-wider">El Club</RouterLink></li>
        <li><RouterLink to="/carrera" class="text-gris-texto hover:text-naranja transition-colors text-sm uppercase tracking-wider">La Carrera</RouterLink></li>
        <li><RouterLink to="/ediciones" class="text-gris-texto hover:text-naranja transition-colors text-sm uppercase tracking-wider">Ediciones</RouterLink></li>
        <li><RouterLink to="/galeria" class="text-gris-texto hover:text-naranja transition-colors text-sm uppercase tracking-wider">Galeria</RouterLink></li>
        <li><RouterLink to="/blog" class="text-gris-texto hover:text-naranja transition-colors text-sm uppercase tracking-wider">Noticias</RouterLink></li>
      </ul>

      <div class="flex justify-center items-center gap-4 mb-6">
        <button
          @click="shareToInstagram"
          title="Compartir en Instagram"
          class="w-5 h-5 text-gris-texto hover:text-naranja opacity-70 hover:opacity-100 transition cursor-pointer"
          v-html="InstagramSvg"
        />
        <button
          @click="shareToWhatsApp"
          title="Compartir por WhatsApp"
          class="w-5 h-5 text-gris-texto hover:text-naranja opacity-70 hover:opacity-100 transition cursor-pointer"
          v-html="WhatsappSvg"
        />
        <button
          @click="shareToX"
          title="Compartir en X"
          class="w-5 h-5 text-gris-texto hover:text-naranja opacity-70 hover:opacity-100 transition cursor-pointer"
          v-html="XSvg"
        />
      </div>

      <div class="text-gris-texto text-sm">&copy; 2026 Cokalba Running - Coca de Alba</div>
    </div>
  </footer>
</template>
