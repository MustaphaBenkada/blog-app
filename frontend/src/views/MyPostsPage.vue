<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h1 class="h3 fw-bold mb-0"><i class="bi bi-person-lines-fill me-2"></i>My Posts</h1>
      <div>
        <button v-for="s in statuses" :key="s.value" @click="setStatus(s.value)" :class="['btn', 'me-2', status === s.value ? 'btn-primary' : 'btn-outline-primary']">
          {{ s.label }}
        </button>
      </div>
    </div>
    <div class="row g-4">
      <div class="col-12 col-md-6 col-lg-4" v-for="post in filteredPosts" :key="post.id">
        <BlogPostCard :post="post" />
      </div>
      <div v-if="!filteredPosts.length && !loading" class="col-12 text-center text-muted py-5">
        <i class="bi bi-emoji-frown display-4 mb-2"></i>
        <div>No posts found.</div>
      </div>
    </div>
    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import BlogPostCard from '@/components/BlogPostCard.vue';

const posts = ref([]);
const loading = ref(false);
const status = ref('all');
const statuses = [
  { label: 'All', value: 'all' },
  { label: 'Drafts', value: 'draft' },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Published', value: 'published' },
];

const setStatus = (val) => {
  status.value = val;
};

const filteredPosts = computed(() => {
  if (status.value === 'all') return posts.value;
  return posts.value.filter(p => p.status === status.value);
});

onMounted(async () => {
  loading.value = true;
  try {
    const response = await axios.get('/my-posts');
    posts.value = response.data.data;
  } catch (e) {
    posts.value = [];
  } finally {
    loading.value = false;
  }
});
</script> 