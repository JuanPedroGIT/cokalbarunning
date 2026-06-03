<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import api from '@/services/api.service'
interface ClubProfile {
  id: string
  name: string
  description: string | null
  bio: string | null
  photoUrl: string | null
  isActive: boolean
  sortOrder: number
  userId: string | null
}

const auth = useAuthStore()
const profile = ref<ClubProfile | null>(null)
const loading = ref(true)
const error = ref('')
const message = ref('')

const profileForm = ref({ name: '', bio: '' })
const passwordForm = ref({ currentPassword: '', newPassword: '', confirmPassword: '' })
const savingBio = ref(false)
const savingPassword = ref(false)
const photoInput = ref<HTMLInputElement | null>(null)
const isPhotoDragging = ref(false)

async function fetchProfile() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get('/me/club-profile')
    profile.value = res.data.data
    profileForm.value.name = profile.value?.name || ''
    profileForm.value.bio = profile.value?.bio || ''
  } catch (e: any) {
    if (e.response?.status === 404) {
      profile.value = null
    } else {
      error.value = 'Error cargando perfil'
    }
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  savingBio.value = true
  message.value = ''
  try {
    await api.put('/me/club-profile', {
      name: profileForm.value.name,
      bio: profileForm.value.bio,
    })
    if (profile.value) {
      profile.value.name = profileForm.value.name
      profile.value.bio = profileForm.value.bio
    }
    message.value = 'Perfil actualizado'
  } catch {
    error.value = 'Error guardando perfil'
  } finally {
    savingBio.value = false
  }
}

async function uploadPhoto(file: File) {
  if (!profile.value) return
  const fd = new FormData()
  fd.append('file', file)
  try {
    await api.post(`/admin/club-members/${profile.value.id}/photo`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    message.value = 'Foto actualizada'
    await fetchProfile()
  } catch {
    error.value = 'Error subiendo foto'
  }
}

function onPhotoSelect(e: Event) {
  const target = e.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) uploadPhoto(file)
}

function onPhotoDrop(e: DragEvent) {
  e.preventDefault()
  isPhotoDragging.value = false
  const file = e.dataTransfer?.files[0]
  if (file && file.type.startsWith('image/')) uploadPhoto(file)
}

function onPhotoDragOver(e: DragEvent) {
  e.preventDefault()
  isPhotoDragging.value = true
}

function onPhotoDragLeave(e: DragEvent) {
  e.preventDefault()
  isPhotoDragging.value = false
}

async function changePassword() {
  error.value = ''
  message.value = ''
  if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
    error.value = 'Las contrasenas no coinciden'
    return
  }
  if (passwordForm.value.newPassword.length < 6) {
    error.value = 'La nueva contrasena debe tener al menos 6 caracteres'
    return
  }
  savingPassword.value = true
  try {
    await api.put('/me/password', {
      currentPassword: passwordForm.value.currentPassword,
      newPassword: passwordForm.value.newPassword,
    })
    passwordForm.value = { currentPassword: '', newPassword: '', confirmPassword: '' }
    message.value = 'Contrasena actualizada'
  } catch (e: any) {
    error.value = e.response?.data?.error || 'Error cambiando contrasena'
  } finally {
    savingPassword.value = false
  }
}

onMounted(fetchProfile)
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white pt-28 pb-12 px-6">
    <div class="max-w-3xl mx-auto">
      <h1 class="font-barlow-condensed font-black text-4xl uppercase tracking-wider mb-2">
        Mi <span class="text-naranja">Perfil</span>
      </h1>
      <p class="text-white/40 text-sm mb-8">{{ auth.user?.email }}</p>

      <div v-if="message" class="mb-6 bg-green-500/10 border border-green-500/20 text-green-400 text-sm p-3 rounded">
        {{ message }}
      </div>
      <div v-if="error" class="mb-6 bg-red-500/10 border border-red-500/20 text-red-400 text-sm p-3 rounded">
        {{ error }}
      </div>

      <div v-if="loading" class="text-white/30 text-center py-10">Cargando...</div>

      <template v-else>
        <!-- Perfil de miembro -->
        <div class="bg-[#141414] rounded-lg border border-white/5 p-6 mb-6">
          <h2 class="font-barlow-condensed font-bold text-lg uppercase tracking-wider text-naranja mb-4">
            Perfil de Miembro
          </h2>

          <div v-if="!profile" class="text-white/40 text-sm py-4">
            No tienes un perfil de miembro del club asignado. Contacta con un administrador.
          </div>

          <div v-else class="space-y-5">
            <div class="flex items-center gap-4">
              <div
                class="relative w-20 h-20 shrink-0 cursor-pointer rounded-full overflow-hidden group"
                @click="photoInput?.click()"
                @drop="onPhotoDrop"
                @dragover="onPhotoDragOver"
                @dragleave="onPhotoDragLeave"
              >
                <img
                  v-if="profile.photoUrl"
                  :src="profile.photoUrl"
                  :alt="profile.name"
                  class="w-full h-full object-cover"
                  :class="isPhotoDragging ? 'opacity-40' : ''"
                />
                <div
                  v-else
                  class="w-full h-full bg-white/5 flex items-center justify-center text-white/20 text-2xl"
                  :class="isPhotoDragging ? 'opacity-40' : ''"
                >
                  👤
                </div>
                <div
                  class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity"
                  :class="isPhotoDragging ? 'opacity-100' : ''"
                >
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                  </svg>
                </div>
                <input
                  ref="photoInput"
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="onPhotoSelect"
                />
              </div>
              <div>
                <div class="font-barlow-condensed font-bold text-xl uppercase">{{ profile.name }}</div>
                <div class="text-gris-texto text-sm">{{ profile.description || 'Sin rol asignado' }}</div>
              </div>
            </div>

            <div class="space-y-3">
              <div>
                <label class="block text-xs text-gray-400 mb-1">Nombre</label>
                <input
                  v-model="profileForm.name"
                  type="text"
                  placeholder="Tu nombre publico"
                  class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
                />
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1">Biografia</label>
                <textarea
                  v-model="profileForm.bio"
                  rows="5"
                  placeholder="Escribe tu biografia..."
                  class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition resize-none"
                ></textarea>
              </div>
              <div class="flex justify-end">
                <button
                  @click="saveProfile"
                  :disabled="savingBio"
                  class="bg-[#FF5C00] text-white px-4 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 text-sm cursor-pointer"
                >
                  {{ savingBio ? 'Guardando...' : 'Guardar perfil' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Cambio de contrasena -->
        <div class="bg-[#141414] rounded-lg border border-white/5 p-6">
          <h2 class="font-barlow-condensed font-bold text-lg uppercase tracking-wider text-naranja mb-4">
            Cambiar Contrasena
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-xs text-gray-400 mb-1">Contrasena actual</label>
              <input
                v-model="passwordForm.currentPassword"
                type="password"
                class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
              />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Nueva contrasena</label>
              <input
                v-model="passwordForm.newPassword"
                type="password"
                class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
              />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">Confirmar nueva contrasena</label>
              <input
                v-model="passwordForm.confirmPassword"
                type="password"
                class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition"
              />
            </div>
          </div>
          <div class="flex justify-end mt-4">
            <button
              @click="changePassword"
              :disabled="savingPassword"
              class="bg-[#FF5C00] text-white px-4 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition disabled:opacity-50 text-sm cursor-pointer"
            >
              {{ savingPassword ? 'Guardando...' : 'Cambiar contrasena' }}
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
