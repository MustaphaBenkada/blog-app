<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { reactive, ref, onMounted, onBeforeUnmount } from 'vue'

const authStore = useAuthStore()
const router = useRouter()

const $store = reactive({
  darkMode: localStorage.getItem('darkMode') === 'true',
})

const showProfileModal = ref(false)
const newName = ref(authStore.user?.name || '')
const isUpdating = ref(false)
const errorMsg = ref('')
const profileMenuOpen = ref(false)

const toggleDarkMode = () => {
  $store.darkMode = !$store.darkMode
  localStorage.setItem('darkMode', $store.darkMode)
  document.documentElement.classList.toggle('dark', $store.darkMode)
}

if ($store.darkMode) {
  document.documentElement.classList.add('dark')
}

const logout = () => {
  authStore.logout()
  router.push('/login')
}

function openProfileModal() {
  newName.value = authStore.user?.name || ''
  errorMsg.value = ''
  showProfileModal.value = true
}

async function updateProfile() {
  if (!newName.value.trim()) {
    errorMsg.value = 'Name cannot be empty.'
    return
  }
  isUpdating.value = true
  errorMsg.value = ''
  try {
    const res = await fetch('http://localhost:8000/api/profile', {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ name: newName.value })
    })
    const data = await res.json()
    if (!res.ok) throw new Error(data.message || 'Failed to update profile')
    authStore.user.name = data.user.name
    showProfileModal.value = false
  } catch (e) {
    errorMsg.value = e.message
  } finally {
    isUpdating.value = false
  }
}

function toggleProfileMenu() {
  profileMenuOpen.value = !profileMenuOpen.value
}

function closeProfileMenu() {
  profileMenuOpen.value = false
}

function handleEditProfile() {
  openProfileModal();
  closeProfileMenu();
}

function handleLogout() {
  logout();
  closeProfileMenu();
}

// Hide dropdown on outside click
let clickHandler = null
onMounted(() => {
  clickHandler = (e) => {
    const menu = document.getElementById('profile-menu-dropdown')
    const btn = document.getElementById('profile-menu-btn')
    if (menu && !menu.contains(e.target) && btn && !btn.contains(e.target)) {
      profileMenuOpen.value = false
    }
  }
  document.addEventListener('mousedown', clickHandler)
  // Validate token on app load
  authStore.validateToken();
})
onBeforeUnmount(() => {
  if (clickHandler) document.removeEventListener('mousedown', clickHandler)
})
</script>

<template>
  <div>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
      <div class="container">
        <router-link to="/" class="navbar-brand d-flex align-items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="me-2" width="32" height="32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 20l9-5-9-5-9 5 9 5z"/></svg>
          <span class="fw-bold">BlogApp</span>
        </router-link>
        <div class="d-flex align-items-center ms-auto gap-2">
          <router-link to="/" class="nav-link">Home</router-link>
          <router-link v-if="authStore.isAuthenticated" to="/my-posts" class="nav-link">My Posts</router-link>
          <router-link v-if="authStore.isAuthenticated" to="/posts/create" class="btn btn-primary btn-sm">Create Post</router-link>
          <router-link v-if="!authStore.isAuthenticated" to="/login" class="nav-link">Login</router-link>
          <div v-if="authStore.isAuthenticated" class="position-relative">
            <button id="profile-menu-btn" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" @click="toggleProfileMenu" type="button">
              <i class="bi bi-person-circle"></i>{{ authStore.user?.name || 'Profile' }}
              <i class="bi bi-caret-down-fill small"></i>
          </button>
            <ul v-if="profileMenuOpen" id="profile-menu-dropdown" class="dropdown-menu show position-absolute end-0 mt-2" style="min-width: 180px;">
              <li><button class="dropdown-item" @click="handleEditProfile">Edit Profile</button></li>
              <li><hr class="dropdown-divider"></li>
              <li><button class="dropdown-item text-danger" @click="handleLogout">Logout</button></li>
            </ul>
          </div>
        </div>
        </div>
      </nav>
    <main class="container mb-5">
      <router-view />
    </main>

    <!-- Profile Modal -->
    <div v-if="showProfileModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.3);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Profile</h5>
            <button type="button" class="btn-close" @click="showProfileModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="profileName" class="form-label">Name</label>
              <input id="profileName" v-model="newName" type="text" class="form-control" :disabled="isUpdating">
            </div>
            <div v-if="errorMsg" class="alert alert-danger py-2">{{ errorMsg }}</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showProfileModal = false" :disabled="isUpdating">Cancel</button>
            <button type="button" class="btn btn-primary" @click="updateProfile" :disabled="isUpdating">
              <span v-if="isUpdating" class="spinner-border spinner-border-sm me-2"></span>
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
body {
  background: #f8f9fa;
}
</style>
