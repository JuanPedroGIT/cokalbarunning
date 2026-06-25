<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  label?: string
  selectedLabel?: string
  accept?: string
  compact?: boolean
  imageUrl?: string
  clearLabel?: string
  square?: boolean
}>()

const emit = defineEmits<{
  (e: 'select', file: File): void
  (e: 'clear'): void
}>()

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

function onDragOver(e: DragEvent) {
  e.preventDefault()
  isDragging.value = true
}

function onDragLeave(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  isDragging.value = false
  const droppedFile = e.dataTransfer?.files[0]
  if (droppedFile) {
    emit('select', droppedFile)
  }
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  const selectedFile = target.files?.[0]
  if (selectedFile) {
    emit('select', selectedFile)
  }
}

function triggerFileInput() {
  fileInput.value?.click()
}

function clearImage(e: MouseEvent) {
  e.stopPropagation()
  emit('clear')
}
</script>

<template>
  <div
    class="relative cursor-pointer border-2 border-dashed rounded-lg px-6 text-center transition-colors select-none overflow-hidden"
    :class="[
      compact ? 'py-2' : 'py-6',
      square ? 'aspect-square max-w-[240px] flex items-center justify-center p-0' : '',
      isDragging
        ? 'border-[#FF5C00] bg-[#FF5C00]/10'
        : 'border-white/20 bg-[#0A0A0A] hover:border-[#FF5C00]/50 hover:bg-[#1a1a1a]',
    ]"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
    @drop="onDrop"
    @click="triggerFileInput"
  >
    <input
      ref="fileInput"
      type="file"
      :accept="accept || 'image/*'"
      class="hidden"
      @change="onFileChange"
    />

    <template v-if="imageUrl">
      <img
        :src="imageUrl"
        alt="Preview"
        class="absolute inset-0 w-full h-full object-cover"
      />
      <div
        class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 opacity-0 hover:opacity-100 transition-opacity"
      >
        <svg
          class="mx-auto text-gray-300"
          :class="compact ? 'w-5 h-5 mb-1' : 'w-8 h-8 mb-2'"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          stroke-width="1.5"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3l4.5 4.5m-13.5 9V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0021 6.75v6.75"
          />
        </svg>
        <p class="text-gray-300" :class="compact ? 'text-xs' : 'text-sm'">
          {{ selectedLabel || label || 'Arrastra otra imagen o haz clic para cambiar' }}
        </p>
        <button
          type="button"
          class="mt-2 text-xs text-red-400 hover:text-red-300 underline"
          @click="clearImage"
        >
          {{ clearLabel || 'Eliminar' }}
        </button>
      </div>
    </template>

    <template v-else>
      <svg
        class="mx-auto text-gray-400"
        :class="compact ? 'w-5 h-5 mb-1' : 'w-8 h-8 mb-2'"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        stroke-width="1.5"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3l4.5 4.5m-13.5 9V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0021 6.75v6.75"
        />
      </svg>
      <p class="text-gray-400" :class="compact ? 'text-xs' : 'text-sm'">
        {{ label || 'Arrastra una imagen aquí o haz clic para seleccionar' }}
      </p>
    </template>
  </div>
</template>
