<script setup lang="ts">
import type { RaceDocument } from '@/stores/document.store'

const props = defineProps<{
  documents: RaceDocument[]
}>()

const typeLabels: Record<string, string> = {
  route: 'Recorrido',
  profile: 'Perfil',
  results: 'Resultados',
  general: 'General',
  other: 'Otros',
}

const typeIcons: Record<string, string> = {
  route: '🗺️',
  profile: '📊',
  results: '🏆',
  general: '📄',
  other: '📎',
}

const grouped = computed(() => {
  const groups: Record<string, RaceDocument[]> = {}
  for (const doc of props.documents) {
    if (!groups[doc.type]) groups[doc.type] = []
    groups[doc.type].push(doc)
  }
  return groups
})
</script>

<script lang="ts">
import { computed } from 'vue'
</script>

<template>
  <div class="space-y-6">
    <div v-for="(docs, type) in grouped" :key="type">
      <h4 class="font-barlow-condensed font-bold text-lg uppercase tracking-wider text-naranja mb-3">
        {{ typeIcons[type] || '📄' }} {{ typeLabels[type] || type }}
      </h4>
      <div class="space-y-2">
        <a
          v-for="doc in docs"
          :key="doc.id"
          :href="doc.publicUrl"
          target="_blank"
          class="flex items-center gap-3 p-3 bg-gris-medio/50 rounded-lg hover:bg-gris-medio transition-colors"
        >
          <span class="text-2xl">📄</span>
          <span class="font-barlow text-white">{{ doc.name }}</span>
        </a>
      </div>
    </div>
  </div>
</template>
