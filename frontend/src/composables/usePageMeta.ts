import { useHead } from '@vueuse/head'
import { computed } from 'vue'

interface PageMetaOptions {
  title: string
  description?: string
  image?: string
  type?: 'website' | 'article'
  url?: string
  noindex?: boolean
}

const SITE_NAME = 'Cokalba Running'
const DEFAULT_DESCRIPTION =
  'Carrera solidaria de Coca de Alba. Un Nuevo Impulso. Corre por una buena causa y ayuda a impulsar el futuro de muchas personas.'
const DEFAULT_IMAGE =
  'https://media.cokalba-running.com/un-nuevo-impulso/carrera/2026/docs/cartel-2026.jpg'
const SITE_URL = 'https://cokalba-running.com'

export function usePageMeta(options: PageMetaOptions) {
  const fullTitle = computed(() => `${options.title} · ${SITE_NAME}`)
  const description = computed(() => options.description || DEFAULT_DESCRIPTION)
  const image = computed(() => options.image || DEFAULT_IMAGE)
  const url = computed(() => (options.url ? `${SITE_URL}${options.url}` : SITE_URL))
  const type = options.type || 'website'

  useHead({
    title: fullTitle,
    meta: [
      { name: 'description', content: description },

      // Open Graph
      { property: 'og:title', content: fullTitle },
      { property: 'og:description', content: description },
      { property: 'og:type', content: type },
      { property: 'og:url', content: url },
      { property: 'og:image', content: image },
      { property: 'og:site_name', content: SITE_NAME },
      { property: 'og:locale', content: 'es_ES' },

      // Twitter Card
      { name: 'twitter:card', content: 'summary_large_image' },
      { name: 'twitter:title', content: fullTitle },
      { name: 'twitter:description', content: description },
      { name: 'twitter:image', content: image },

      // Robots
      ...(options.noindex ? [{ name: 'robots', content: 'noindex, nofollow' }] : []),
    ],
    link: [
      { rel: 'canonical', href: url },
    ],
  })
}
