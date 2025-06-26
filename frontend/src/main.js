import 'bootstrap/dist/css/bootstrap.min.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import Markdown from 'vue3-markdown-it';
import { useAuthStore } from './stores/auth';
import axios from 'axios';

const app = createApp(App)

app.use(createPinia())

// Configure axios defaults
axios.defaults.baseURL = 'http://localhost:8000/api';
axios.defaults.headers.common['Accept'] = 'application/json';

// Set up a global axios interceptor for Authorization
axios.interceptors.request.use(config => {
  const authStore = useAuthStore();
  if (authStore.token) {
    config.headers['Authorization'] = `Bearer ${authStore.token}`;
  }
  return config;
});

const authStore = useAuthStore();
authStore.initializeAuth();

app.use(router)
app.component('Markdown', Markdown);

app.mount('#app')
