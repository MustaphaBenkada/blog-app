<template>
  <div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h1 class="h3 fw-bold mb-0"><i class="bi bi-journal-richtext me-2"></i>Blog Posts</h1>
      <form class="d-flex" @submit.prevent="onSearch">
        <input v-model="searchQuery" type="search" class="form-control me-2" placeholder="Search posts..." style="min-width: 220px;" inputmode="none" autocomplete="off">
        <button type="submit" class="btn btn-outline-primary me-2" title="Search"><i class="bi bi-search"></i></button>
        <button v-if="searchQuery" type="button" class="btn btn-outline-secondary" @click="clearSearch" title="Clear search"><i class="bi bi-x-lg"></i></button>
      </form>
    </div>
    <div class="row g-4">
      <template v-if="!loading">
        <div class="col-12 col-md-6 col-lg-4" v-for="post in posts" :key="post.id">
          <BlogPostCard :post="post" />
        </div>
        <div v-if="!posts.length && !loading" class="col-12 text-center text-muted py-5">
          <i class="bi bi-emoji-frown display-4 mb-2"></i>
          <div>No posts found.</div>
          <div v-if="searchQuery" class="small mt-2">
            Try a different search term or check back in a moment if the search index is being rebuilt.
          </div>
        </div>
      </template>
      <template v-else>
        <div class="col-12 col-md-6 col-lg-4" v-for="n in 6" :key="n">
          <div class="card h-100 shadow-sm border-0 p-4 placeholder-glow" style="min-height:420px;max-height:420px;">
            <div class="ratio ratio-16x9 mb-3 bg-light placeholder"></div>
            <h2 class="card-title placeholder col-8 mb-2"></h2>
            <p class="card-text placeholder col-10 mb-3"></p>
            <div class="d-flex justify-content-between align-items-center mt-auto small">
              <span class="placeholder col-4"></span>
              <span class="placeholder col-3"></span>
            </div>
          </div>
        </div>
      </template>
    </div>
    <div v-if="loadingMore" class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div v-if="noMorePosts && posts.length" class="text-center py-4 text-muted">
            No more posts to load.
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import BlogPostCard from '@/components/BlogPostCard.vue';

const posts = ref([]);
const page = ref(1);
const loading = ref(false);
const loadingMore = ref(false);
const noMorePosts = ref(false);
const searchQuery = ref('');

const fetchPosts = async (reset = false) => {
    if (loading.value || loadingMore.value) {
      // Only block if already fetching
      return;
    }
    if (reset) {
      posts.value = [];
      page.value = 1;
      noMorePosts.value = false;
    }
    loading.value = true;
    loadingMore.value = page.value > 1;
    try {
        const response = await axios.get(`/posts/search?query=${encodeURIComponent(searchQuery.value)}&page=${page.value}`);
        const data = response.data.data;
        if (data.length === 0) {
            noMorePosts.value = true;
        } else {
            if (page.value === 1) {
              posts.value = data;
            } else {
              posts.value = [...posts.value, ...data];
            }
            page.value++;
        }
    } catch (error) {
        // Show user-friendly error message
        if (error.response?.status === 500) {
            // This might be the first search after restart, show a helpful message
            posts.value = [];
        }
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const handleScroll = () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100 && !loading.value && !loadingMore.value && !noMorePosts.value) {
        fetchPosts();
    }
};

const onSearch = () => {
  fetchPosts(true);
};

const clearSearch = () => {
  searchQuery.value = '';
  fetchPosts(true);
};

onMounted(() => {
    fetchPosts();
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script> 

<style scoped>
.placeholder-glow .placeholder {
  animation: placeholder-glow 1.5s infinite linear;
}
@keyframes placeholder-glow {
  0% { opacity: 0.5; }
  50% { opacity: 0.9; }
  100% { opacity: 0.5; }
}

/* Hide the clear button (x icon) in search inputs */
input[type="search"]::-webkit-search-cancel-button {
  -webkit-appearance: none;
  appearance: none;
}

/* For Firefox */
input[type="search"]::-moz-search-clear {
  display: none;
}
</style> 