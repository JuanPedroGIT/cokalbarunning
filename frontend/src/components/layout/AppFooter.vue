<script setup lang="ts">
import { RouterLink } from 'vue-router'
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
      <div class="text-gris-texto text-sm">&copy; 2026 Cokalba Running - Coca de Alba</div>
    </div>
  </footer>
</template>
