<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.service'
import ImageDropZone from '@/components/ui/ImageDropZone.vue'
import InstagramSvg from '@/assets/icons/instagram.svg?raw'

interface Post {
  id: string
  title: string
  slug: string
  excerpt: string
  content: string
  tag: string
  publishedAt: string | null
  bannerEndAt: string | null
  isPublished: boolean
  coverImage: string | null
  priority: number | null
  type: number
}

const POST_TYPE_LABELS: Record<number, string> = {
  1: 'Noticia',
  2: 'Banner',
  3: 'Carrera',
  4: 'Club',
  5: 'Otro',
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
const form = ref<Partial<Post>>({ title: '', excerpt: '', content: '', tag: 'noticia', publishedAt: null, bannerEndAt: null, priority: null, type: 1 })
const router = useRouter()
const editingId = ref<string | null>(null)
const coverFile = ref<File | null>(null)
const coverPreviewUrl = ref<string | null>(null)
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
    if (coverPreviewUrl.value) {
      URL.revokeObjectURL(coverPreviewUrl.value)
      coverPreviewUrl.value = null
    }
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

function onCoverSelect(selectedFile: File) {
  if (coverPreviewUrl.value) {
    URL.revokeObjectURL(coverPreviewUrl.value)
  }
  coverFile.value = selectedFile
  coverPreviewUrl.value = URL.createObjectURL(selectedFile)
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
  if (coverPreviewUrl.value) {
    URL.revokeObjectURL(coverPreviewUrl.value)
    coverPreviewUrl.value = null
  }
  form.value = { title: '', excerpt: '', content: '', tag: 'noticia', publishedAt: null, bannerEndAt: null, priority: null, type: 1 }
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
            <label class="block text-xs text-gray-400 mb-1">Tipo</label>
            <select v-model.number="form.type" class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition">
              <option :value="1">Noticia</option>
              <option :value="2">Banner informativo</option>
              <option :value="3">Carrera</option>
              <option :value="4">Club</option>
              <option :value="5">Otro</option>
            </select>
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
          <div v-if="form.type === 2">
            <label class="block text-xs text-gray-400 mb-1">Fecha fin del banner <span class="text-red-400">*</span></label>
            <input
              :value="form.bannerEndAt ? form.bannerEndAt.slice(0, 10) : ''"
              type="date"
              class="w-full bg-[#0A0A0A] border border-white/10 rounded px-3 py-2 text-white focus:border-[#FF5C00] focus:outline-none transition [color-scheme:dark]"
              @input="ev => form.bannerEndAt = (ev.target as HTMLInputElement).value || null"
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
        </div>
        <!-- Imagen de portada — dropzone cuadrado -->
        <div>
          <label class="block text-xs text-gray-400 mb-1">Imagen de portada</label>
          <div class="w-full sm:w-[240px]">
            <ImageDropZone
              :label="'Arrastra o haz clic'"
              :selected-label="coverFile ? coverFile.name : undefined"
              :image-url="coverPreviewUrl || form.coverImage || undefined"
              :square="true"
              @select="onCoverSelect"
            />
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
              <th class="p-2 md:p-3 font-medium">Tipo</th>
              <th class="p-2 md:p-3 font-medium">Etiqueta</th>
              <th class="p-2 md:p-3 font-medium">Prioridad</th>
              <th class="p-2 md:p-3 font-medium">Publicado</th>
              <th class="p-2 md:p-3 text-right font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in posts" :key="p.id" class="border-t border-white/5 hover:bg-[#1a1a1a] transition">
              <td class="p-2 md:p-3">{{ p.title }}</td>
              <td class="p-2 md:p-3"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-500/10 text-blue-400">{{ POST_TYPE_LABELS[p.type] ?? 'Noticia' }}</span></td>
              <td class="p-2 md:p-3"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#FF5C00]/10 text-[#FF5C00]">{{ p.tag }}</span></td>
              <td class="p-2 md:p-3">
                <span v-if="p.priority" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-500/10 text-yellow-400">{{ p.priority }}</span>
                <span v-else class="text-gray-500">—</span>
              </td>
              <td class="p-2 md:p-3">
                <span v-if="p.isPublished" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-500/10 text-green-400">Si</span>
                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400">No</span>
              </td>
              <td class="p-2 md:p-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="edit(p)" class="text-[#FF5C00] hover:text-[#FFD600] text-sm font-medium transition">Editar</button>
                  <span class="text-white/10">|</span>
                  <button
                    @click="publishToInstagram(p.id)"
                    :disabled="getInstagramStatus(p.id) !== 'none' || publishingIds.has(p.id) || !p.coverImage || p.type === 2"
                    :title="p.type === 2 ? 'Las noticias tipo banner no se publican en Instagram' : !p.coverImage ? 'Falta imagen de portada' : getInstagramStatus(p.id) === 'published' ? 'Ya publicado en Instagram' : getInstagramStatus(p.id) === 'pending' ? 'Publicación en curso' : 'Publicar en Instagram'"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 text-white hover:scale-105 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100"
                  >
                    <span
                      v-if="!publishingIds.has(p.id)"
                      class="w-4 h-4"
                      v-html="InstagramSvg"
                    />
                    <span
                      v-else
                      class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
                    />
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
