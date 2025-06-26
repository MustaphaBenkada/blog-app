<template>
  <div class="container py-4">
    <div class="card shadow-lg">
      <div class="card-body">
    <div v-if="post && post.title">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h1 class="h2">{{ post.title }}</h1>
              <div class="mb-2">
                <span v-if="post.status === 'draft'" class="badge bg-secondary">Draft</span>
                <span v-else-if="post.status === 'scheduled'" class="badge bg-info text-dark">Scheduled</span>
                <span v-else-if="post.status === 'published'" class="badge bg-success">Published</span>
                <span v-if="post.published_at">
                  <span style="margin-left: 0.5em;"><!-- space --></span>
                  <i class="bi bi-calendar-event me-1"></i>
                  {{ new Date(post.published_at).toLocaleString() }}
                </span>
              </div>
            </div>
            <div v-if="authStore.isAuthenticated && authStore.user.id === post.user_id" class="d-flex gap-2">
              <router-link :to="{ name: 'post-edit', params: { id: post.id } }" class="btn btn-warning btn-sm">Edit</router-link>
              <button @click="handleDeleteClick" class="btn btn-danger btn-sm">Delete</button>
            </div>
          </div>
          <img :src="post.image_url && post.image_url.trim() ? post.image_url : 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=400&q=80'" :alt="post.title" class="img-fluid rounded mb-4 shadow-sm" />
          <div class="mb-4">
            <Markdown :source="post.description" />
          </div>
          <hr class="my-4" />
          <CommentSection :post="post" :show-add-comment="authStore.isAuthenticated" />
        </div>
        <div v-else class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading post...</span>
          </div>
        </div>
      </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.3);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="btn-close" @click="cancelDelete"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this post? This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cancelDelete">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDelete">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import CommentSection from '@/components/CommentSection.vue';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const post = ref({});
const authStore = useAuthStore();
const showDeleteModal = ref(false);

onMounted(async () => {
    const response = await axios.get(`/posts/${route.params.id}`);
    post.value = response.data.data;
});

const handleDeleteClick = () => {
    showDeleteModal.value = true;
};

const confirmDelete = async () => {
        try {
        await axios.delete(`/posts/${post.value.id}`);
            router.push('/');
        } catch (error) {
            console.error('Failed to delete post:', error);
    } finally {
        showDeleteModal.value = false;
    }
};

const cancelDelete = () => {
    showDeleteModal.value = false;
};
</script> 