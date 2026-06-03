import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/carrera',
      name: 'race',
      component: () => import('../views/RaceView.vue'),
    },
    {
      path: '/ediciones',
      name: 'editions',
      component: () => import('../views/EditionsView.vue'),
    },
    {
      path: '/galeria',
      name: 'gallery',
      component: () => import('../views/GalleryView.vue'),
    },
    {
      path: '/blog',
      name: 'blog',
      component: () => import('../views/BlogView.vue'),
    },
    {
      path: '/blog/:slug',
      name: 'blog-post',
      component: () => import('../views/BlogPostView.vue'),
    },
    {
      path: '/admin/login',
      name: 'admin-login',
      component: () => import('../views/admin/AdminLoginView.vue'),
    },
    {
      path: '/admin',
      name: 'admin-dashboard',
      component: () => import('../views/admin/AdminDashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/admin/editions',
      name: 'admin-editions',
      component: () => import('../views/admin/AdminEditionsView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/results',
      name: 'admin-results',
      component: () => import('../views/admin/AdminResultsImportView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/photos',
      name: 'admin-photos',
      component: () => import('../views/admin/AdminPhotosView.vue'),
      meta: { requiresAuth: true, requiresEditor: true },
    },
    {
      path: '/admin/posts',
      name: 'admin-posts',
      component: () => import('../views/admin/AdminPostsView.vue'),
      meta: { requiresAuth: true, requiresEditor: true },
    },
    {
      path: '/admin/sponsors',
      name: 'admin-sponsors',
      component: () => import('../views/admin/AdminSponsorsView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/club-members',
      name: 'admin-club-members',
      component: () => import('../views/admin/AdminClubMembersView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/admin/users',
      name: 'admin-users',
      component: () => import('../views/admin/AdminUsersView.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
      path: '/perfil',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('../views/NotFoundView.vue'),
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'admin-login', query: { redirect: to.fullPath } }
  }
  if (to.meta.requiresEditor && !auth.hasRole('ROLE_EDITOR') && !auth.hasRole('ROLE_ADMIN')) {
    return { name: 'admin-dashboard' }
  }
  if (to.meta.requiresAdmin && !auth.hasRole('ROLE_ADMIN')) {
    return { name: 'admin-dashboard' }
  }
  if (to.name === 'admin-login' && auth.isAuthenticated) {
    return { name: 'admin-dashboard' }
  }
})

export default router
