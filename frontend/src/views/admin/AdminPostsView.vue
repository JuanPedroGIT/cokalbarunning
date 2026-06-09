<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'

interface Post {
  id: string
  title: string
  slug: string
  excerpt: string
  content: string
  tag: string
  publishedAt: string | null
  isPublished: boolean
  coverImage: string | null
  priority: number | null
}

interface SocialPublishLog {
  id: string
  postId: string
  network: string
  status: string
  publishedAt: string | null
  externalUrl: string | null
  publishedBy: string | null
}

const posts = ref<Post[]>([])
const form = ref<Partial<Post>>({ title: '', excerpt: '', content: '', tag: 'noticia', publishedAt: null, priority: null })
const router = useRouter()
const editingId = ref<string | null>(null)
const coverFile = ref<File | null>(null)
const socialPublishes = ref<Record<string, SocialPublishLog>>({})
const publishingIds = ref<Set<string>>(new Set())

async function fetchPosts() {
  const res = await api.get('/admin/posts')
  posts.value = res.data.data
}

async function fetchSocialPublishes() {
  const res = await api.get('/admin/social-publishes')
  const logs: SocialPublishLog[] = res.data.data
  socialPublishes.value = logs.reduce((acc, log) => {
    acc[log.postId] = log
    return acc
  }, {} as Record<string, SocialPublishLog>)
}

function getInstagramStatus(postId: string): 'none' | 'pending' | 'published' {
  const log = socialPublishes.value[postId]
  if (!log) return 'none'
  if (log.status === 'published') return 'published'
  if (log.status === 'pending') return 'pending'
  return 'none'
}

async function publishToInstagram(id: string) {
  if (!confirm('¿Publicar esta noticia en Instagram?')) return
  publishingIds.value.add(id)
  try {
    await api.post(`/admin/posts/${id}/publish-instagram`)
    await fetchSocialPublishes()
  } catch (err: any) {
    alert(err.response?.data?.error || 'Error al publicar en Instagram')
  } finally {
    publishingIds.value.delete(id)
  }
}

async function save() {
  const payload = { ...form.value }
  delete payload.coverImage
  let id = editingId.value
  if (id) {
    await api.put(`/admin/posts/${id}`, payload)
  } else {
    const res = await api.post('/admin/posts', payload)
    id = res.data.data?.id
  }
  if (coverFile.value && id) {
    await uploadCover(id, coverFile.value)
    coverFile.value = null
  }
  resetForm()
  await fetchPosts()
}

async function uploadCover(postId: string, file: File) {
  const fd = new FormData(); fd.append('file', file)
  await api.post(`/admin/posts/${postId}/cover`, fd, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

function edit(p: Post) {
  editingId.value = p.id
  form.value = { ...p }
}

async function remove(id: string) {
  if (!confirm('Eliminar entrada?')) return
  await api.delete(`/admin/posts/${id}`)
  await fetchPosts()
}

function resetForm() {
  editingId.value = null
  coverFile.value = null
  form.value = { title: '', excerpt: '', content: '', tag: 'noticia', publishedAt: null, priority: null }
}

onMounted(async () => {
  await fetchPosts()
  await fetchSocialPublishes()
})
</script>

<template>
  <div class="relative z-10 min-h-screen bg-[#0A0A0A] text-white">
    <header class="bg-[#141414] p-4 flex items-center gap-4 border-b border-white/5">
      <button @click="router.back()" class="text-sm bg-[#222] text-white px-3 py-1.5 rounded hover:bg-[#333] transition cursor-pointer border border-white/10">← Volver</button>
      <h1 class="text-xl font-bold uppercase tracking-wider">Blog</h1>
    </header>

    <main class="max-w-5xl mx-auto p-6 space-y-6">
      <div class="bg-[#141414] rounded-lg border border-white/5 p-6 space-y-4">
        <h2 class="text-lg font-semibold border-b border-white/5 pb-3">{{ editingId ? 'Editar' : 'Nueva' }} Entrada</h2>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Titulo</label>
          <input v-model="form.title" placeholder="Titulo del post" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Resumen</label>
          <input v-model="form.excerpt" placeholder="Breve resumen..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Contenido</label>
          <textarea v-model="form.content" placeholder="Contenido completo..." rows="6" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition font-mono text-sm"></textarea>
          <p class="text-[0.65rem] text-gray-500 mt-1">Se admite HTML: &lt;p&gt;, &lt;h2&gt;, &lt;strong&gt;, &lt;a&gt;, &lt;img&gt;, etc.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Etiqueta</label>
            <input v-model="form.tag" placeholder="noticias, entrenamiento..." class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition" />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Fecha publicacion</label>
            <input
              :value="form.publishedAt ? form.publishedAt.slice(0, 10) : ''"
              type="date"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition [color-scheme:dark]"
              @input="ev => form.publishedAt = (ev.target as HTMLInputElement).value || null"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Prioridad</label>
            <select v-model.number="form.priority" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
              <option :value="null">Ninguna</option>
              <option :value="1">1 - Principal (Home)</option>
              <option :value="2">2</option>
              <option :value="3">3</option>
              <option :value="4">4</option>
              <option :value="5">5</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Imagen portada</label>
            <input type="file" accept="image/*" :id="`cover-upload`" class="hidden" @change="ev => coverFile = (ev.target as HTMLInputElement).files?.[0] || null" />
            <label :for="`cover-upload`" class="cursor-pointer bg-[#0A0A0A] border-2 border-dashed border-white/20 rounded-lg px-4 py-3 hover:border-[#FF5C00]/50 hover:bg-[#1a1a1a] transition flex items-center gap-3">
              <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3l4.5 4.5m-13.5 9V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0021 6.75v6.75" />
              </svg>
              <span v-if="!coverFile && !form.coverImage" class="text-sm text-gray-400">Arrastra una imagen o haz clic aquí</span>
              <span v-else-if="coverFile" class="text-sm text-white font-medium">{{ coverFile.name }}</span>
              <img v-else-if="form.coverImage" :src="form.coverImage" class="h-12 w-auto object-contain rounded" />
            </label>
          </div>
        </div>
        <div class="flex gap-2 pt-2">
          <button @click="save" class="bg-[#FF5C00] text-white px-4 py-2 rounded font-medium hover:bg-[#FFD600] hover:text-[#0A0A0A] transition">Guardar</button>
          <button v-if="editingId" @click="resetForm" class="bg-[#222] px-4 py-2 rounded hover:bg-[#333] transition">Cancelar</button>
        </div>
      </div>

      <div class="bg-[#141414] rounded-lg border border-white/5 overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[480px]">
          <thead class="bg-[#1a1a1a] text-gray-400">
            <tr>
              <th class="p-2 md:p-3 font-medium">Título</th>
              <th class="p-2 md:p-3 font-medium">Etiqueta</th>
              <th class="p-2 md:p-3 font-medium">Prioridad</th>
              <th class="p-2 md:p-3 font-medium">Publicado</th>
              <th class="p-2 md:p-3 font-medium">Redes</th>
              <th class="p-2 md:p-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in posts" :key="p.id" class="border-t border-white/5 hover:bg-[#1a1a1a] transition">
              <td class="p-2 md:p-3">{{ p.title }}</td>
              <td class="p-2 md:p-3"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#FF5C00]/10 text-[#FF5C00]">{{ p.tag }}</span></td>
              <td class="p-2 md:p-3">
                <span v-if="p.priority" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-500/10 text-yellow-400">{{ p.priority }}</span>
                <span v-else class="text-gray-500">—</span>
              </td>
              <td class="p-2 md:p-3">
                <span v-if="p.isPublished" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/10 text-green-400">Si</span>
                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400">No</span>
              </td>
              <td class="p-2 md:p-3">
                <span v-if="getInstagramStatus(p.id) === 'published'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-500/10 text-pink-400">Instagram ✅</span>
                <span v-else-if="getInstagramStatus(p.id) === 'pending'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-500/10 text-yellow-400">Instagram ⏳</span>
                <span v-else class="text-gray-500">—</span>
              </td>
              <td class="p-2 md:p-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="edit(p)" class="text-[#FF5C00] hover:text-[#FFD600] text-sm font-medium transition">Editar</button>
                  <span class="text-white/10">|</span>
                  <button
                    @click="publishToInstagram(p.id)"
                    :disabled="getInstagramStatus(p.id) === 'published' || publishingIds.has(p.id)"
                    class="text-pink-400 hover:text-pink-300 text-sm font-medium transition disabled:opacity-40 disabled:cursor-not-allowed"
                  >
                    {{ publishingIds.has(p.id) ? 'Publicando...' : 'Instagram' }}
                  </button>
                  <span class="text-white/10">|</span>
                  <button @click="remove(p.id)" class="text-red-400 hover:text-red-300 text-sm font-medium transition">Eliminar</button>
                </div>
              </td>
            </tr>
            <tr v-if="!posts.length">
              <td colspan="6" class="p-6 text-center text-gray-500">No hay entradas</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</template>
