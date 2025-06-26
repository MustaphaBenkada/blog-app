<template>
  <div class="card post-card-custom d-flex flex-column h-100">
    <router-link :to="{ name: 'post-details', params: { id: post.id } }" class="text-decoration-none text-dark d-flex flex-column h-100">
      <div class="ratio ratio-16x9 bg-light rounded-top overflow-hidden">
        <img :src="post.image_url && post.image_url.trim() ? post.image_url : 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=400&q=80'" :alt="post.title || 'No title'" class="object-fit-cover w-100 h-100" loading="lazy">
      </div>
      <div class="card-body d-flex flex-column flex-grow-1">
        <div v-if="post.tags && post.tags.length" class="mb-2 d-flex flex-wrap gap-1">
          <span v-for="(tag, idx) in post.tags" :key="tag + idx" class="badge bg-secondary text-capitalize">#{{ tag }}</span>
        </div>
        <h2 class="card-title h5 mb-2 text-truncate" :title="post.title">{{ post.title || 'Untitled Post' }}</h2>
        <p class="card-text text-muted mb-3 flex-grow-1 text-truncate-3 excerpt-min-height" :title="post.excerpt">{{ post.excerpt || 'No excerpt available.' }}</p>
        <div class="d-flex justify-content-between align-items-center mt-auto small text-secondary">
          <span>
            <i class="bi bi-calendar-event me-1"></i>
            <span v-if="post.status === 'draft'" class="badge bg-secondary">Draft</span>
            <span v-else-if="post.status === 'scheduled'" class="badge bg-info text-dark">Scheduled</span>
            <span v-else-if="post.status === 'published'" class="badge bg-success">Published</span>
            <span v-if="post.published_at">
              <span style="margin-left: 0.5em;"></span>
              {{ new Date(post.published_at).toLocaleDateString() }}
              <span v-if="post.status === 'scheduled'"> ({{ new Date(post.published_at).toLocaleString() }})</span>
            </span>
          </span>
          <span><i class="bi bi-chat-left-text me-1"></i>{{ post.comments_count ?? 0 }} Comments</span>
        </div>
      </div>
    </router-link>
  </div>
</template>

<script setup>
defineProps({
    post: {
        type: Object,
        required: true,
    },
});
</script>

<style scoped>
.card.post-card-custom {
  min-width: 0;
  min-height: 420px;
  max-height: 420px;
  display: flex;
  flex-direction: column;
  transition: box-shadow 0.2s;
  border-radius: 1rem;
  height: 100%;
}
.card.post-card-custom:hover {
  box-shadow: 0 8px 32px rgba(60,60,120,0.15), 0 1.5px 6px rgba(0,0,0,0.08);
  transform: translateY(-2px) scale(1.01);
}
.ratio {
  background: #f8f9fa;
}
.object-fit-cover {
  object-fit: cover;
}
.card-title {
  font-weight: 700;
  letter-spacing: 0.01em;
}
.text-truncate-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}
.excerpt-min-height {
  min-height: 3.6em;
}
</style> 