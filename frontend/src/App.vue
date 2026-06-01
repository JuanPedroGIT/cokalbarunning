<script setup lang="ts">
import { RouterView } from 'vue-router'
import NavBar from './components/layout/NavBar.vue'
import AppFooter from './components/layout/AppFooter.vue'
</script>

<template>
  <NavBar />
  <main>
    <RouterView />
  </main>
  <AppFooter />
</template>
