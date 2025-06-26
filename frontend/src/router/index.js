import { createRouter, createWebHistory } from 'vue-router'
import BlogListPage from '../views/BlogListPage.vue'
import BlogPostDetailsPage from '../views/BlogPostDetailsPage.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import PostEditor from '../views/PostEditor.vue'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: BlogListPage,
    },
    {
      path: '/posts/create',
      name: 'post-create',
      component: PostEditor,
      meta: { requiresAuth: true },
    },
    {
      path: '/posts/:id/edit',
      name: 'post-edit',
      component: PostEditor,
      meta: { requiresAuth: true },
    },
    {
      path: '/posts/:id',
      name: 'post-details',
      component: BlogPostDetailsPage,
      props: true,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
    },
    {
      path: '/my-posts',
      name: 'my-posts',
      component: () => import('@/views/MyPostsPage.vue'),
      meta: { requiresAuth: true }
    },
  ],
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  if (to.matched.some(record => record.meta.requiresAuth) && !authStore.isAuthenticated) {
    next({ name: 'login' })
  } else {
    next()
  }
})

export default router
