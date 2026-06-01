<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="relative z-10 min-h-screen flex items-center justify-center px-6 pt-20">
    <div class="text-center">
      <div class="font-barlow-condensed font-black text-[clamp(6rem,15vw,12rem)] leading-none text-naranja/20">404</div>
      <h1 class="font-barlow-condensed font-black text-[clamp(2rem,4vw,3rem)] uppercase mb-4">
        Página no <span class="text-naranja">encontrada</span>
      </h1>
      <p class="text-white/40 max-w-md mx-auto mb-8 leading-relaxed">
        La página que buscas no existe o ha sido movida.
      </p>
      <RouterLink
        to="/"
        class="font-barlow-condensed font-bold text-sm tracking-widest uppercase bg-naranja text-negro px-8 py-3 hover:bg-amarillo transition-colors inline-block"
      >
        Volver al inicio
      </RouterLink>
    </div>
  </section>
</template>
