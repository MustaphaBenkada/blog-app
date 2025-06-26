<template>
  <div class="container py-4">
    <div class="card shadow-lg">
      <div class="card-header bg-white border-bottom-0 d-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="text-primary"><path d="M12 20l9-5-9-5-9 5 9 5z"/></svg>
        <span class="h4 mb-0">{{ isEditing ? 'Edit Post' : 'Create New Post' }}</span>
      </div>
      <div class="card-body">
        <div v-if="isEditing" class="mb-3">
          <span v-if="post.status === 'draft'" class="badge bg-secondary">Draft</span>
          <span v-else-if="post.status === 'scheduled'" class="badge bg-info text-dark">Scheduled</span>
          <span v-else-if="post.status === 'published'" class="badge bg-success">Published</span>
          <span v-if="post.published_at">
            <span style="margin-left: 0.5em;"><!-- space --></span>
            <i class="bi bi-calendar-event me-1"></i>
            {{ new Date(post.published_at).toLocaleString() }}
          </span>
        </div>
    <form @submit.prevent="submitPost">
          <div class="row g-3 mb-3">
            <div class="col-12">
              <label for="title" class="form-label">Title</label>
              <input type="text" v-model="post.title" id="title" class="form-control" required>
              <div v-if="errors.title" class="invalid-feedback d-block">{{ errors.title }}</div>
            </div>
            <div class="col-12">
              <label for="excerpt" class="form-label">Excerpt</label>
              <textarea v-model="post.excerpt" id="excerpt" rows="3" class="form-control" required></textarea>
              <div v-if="errors.excerpt" class="invalid-feedback d-block">{{ errors.excerpt }}</div>
            </div>
            <div class="col-12">
              <label for="description" class="form-label">Description</label>
              <textarea ref="descriptionElement" v-model="post.description" id="description" rows="10" class="form-control" required></textarea>
              <div v-if="errors.description" class="invalid-feedback d-block">{{ errors.description }}</div>
            </div>
            <div class="col-12">
              <label for="tags" class="form-label">Keywords (tags)</label>
              <div class="d-flex flex-wrap gap-2 mb-2 align-items-center" aria-label="Tag list">
                <span v-for="(tag, idx) in post.tags" :key="tag + idx" class="badge rounded-pill bg-primary shadow-sm d-flex align-items-center gap-1 px-3 py-2">
                  <span>{{ tag }}</span>
                  <button type="button" class="btn-close btn-close-white btn-sm ms-1" aria-label="Remove tag" @click="removeTag(idx)"></button>
                </span>
                <input
                  id="tags"
                  ref="tagInput"
                  v-model="newTag"
                  @keydown.enter.prevent="addTag"
                  @keydown.backspace="removeLastTag"
                  type="text"
                  class="form-control border-0 shadow-none flex-grow-1"
                  :placeholder="post.tags.length === 0 ? 'Type a tag and press Enter' : ''"
                  autocomplete="off"
                  style="min-width: 120px; max-width: 200px;"
                  aria-label="Add tag"
                >
              </div>
              <div v-if="errors.tags" class="invalid-feedback d-block">{{ errors.tags }}</div>
        </div>
            <div class="col-12">
              <label for="image" class="form-label">Image</label>
              <input type="file" @change="handleImageUpload" id="image" class="form-control">
              <div v-if="generalError" class="alert alert-danger">{{ generalError }}</div>
              <div v-if="errors.image" class="alert alert-danger">{{ errors.image }}</div>
        </div>
            <div v-if="imagePreview" class="mb-3">
              <img :src="imagePreview" alt="Image preview" class="img-fluid rounded shadow-sm" style="max-height: 250px;" />
        </div>
        </div>
          <h2 class="h5 mb-3">SEO Metadata</h2>
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label for="meta_title" class="form-label">Meta Title</label>
              <input type="text" v-model="post.meta_title" id="meta_title" class="form-control">
              <div v-if="errors.meta_title" class="invalid-feedback d-block">{{ errors.meta_title }}</div>
        </div>
            <div class="col-12 col-md-6">
              <label for="meta_description" class="form-label">Meta Description</label>
              <input type="text" v-model="post.meta_description" id="meta_description" class="form-control">
              <div v-if="errors.meta_description" class="invalid-feedback d-block">{{ errors.meta_description }}</div>
        </div>
            <div class="col-12 col-md-6">
              <label for="published_at" class="form-label">Schedule Publish Date</label>
              <input type="datetime-local" v-model="post.published_at" id="published_at" class="form-control">
              <div class="form-text">
                Leave empty to publish immediately. 
        </div>
              <div v-if="errors.published_at" class="invalid-feedback d-block">{{ errors.published_at }}</div>
        </div>
      </div>
          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
              {{ isEditing ? 'Update Post' : (post.published_at ? 'Schedule Post' : 'Publish Now') }}
        </button>
      </div>
    </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const isEditing = ref(false);
const postId = ref(null);

const post = ref({
    title: '',
    excerpt: '',
    description: '',
    tags: [],
    meta_title: '',
    meta_description: '',
    published_at: '',
    image: null,
});

const imagePreview = ref('');
const errors = ref({});
const generalError = ref('');
const newTag = ref('');

// Timezone helpers
const userTimezone = ref(Intl.DateTimeFormat().resolvedOptions().timeZone);

const formatScheduledTime = (datetimeString) => {
    if (!datetimeString) return '';
    const localDate = new Date(datetimeString);
    const utcDate = new Date(localDate.toISOString());
    return `${localDate.toLocaleString()} (${userTimezone.value}) → ${utcDate.toISOString()} (UTC)`;
};

const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;
    imagePreview.value = URL.createObjectURL(file);
    errors.value.image = undefined;
    generalError.value = '';
    try {
        const imageForm = new FormData();
        imageForm.append('image', file);
        const response = await axios.post('/posts/upload-image', imageForm, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        post.value.image = response.data.path;
    } catch (error) {
        post.value.image = '';
        imagePreview.value = '';
        if (error.response && error.response.data && error.response.data.errors) {
            errors.value.image = error.response.data.errors.image?.[0] || 'Image upload failed.';
        } else {
            generalError.value = 'Image upload failed.';
        }
    }
};

const descriptionEditor = ref(null);
const descriptionElement = ref(null);

onMounted(async () => {
    if (route.params.id) {
        isEditing.value = true;
        postId.value = route.params.id;
        const response = await axios.get(`/posts/${postId.value}`);
        const data = response.data.data;
        post.value = {
            ...data,
            tags: Array.isArray(data.tags)
              ? data.tags.map(tag => typeof tag === 'string' ? tag : tag.name)
              : (typeof data.tags === 'string'
                  ? data.tags.split(',').map(t => t.trim()).filter(Boolean)
                  : []),
            image: null
        };
    }
    if (descriptionElement.value) {
        descriptionEditor.value = new EasyMDE({
            element: descriptionElement.value,
            spellChecker: false,
            status: false,
            placeholder: 'Write your post description in markdown...'
        });
        descriptionEditor.value.value(post.value.description || '');
        descriptionEditor.value.codemirror.on('change', () => {
            post.value.description = descriptionEditor.value.value();
        });
    }
});

onBeforeUnmount(() => {
    if (descriptionEditor.value) {
        descriptionEditor.value.toTextArea();
        descriptionEditor.value = null;
    }
});

const submitPost = async () => {
    errors.value = {};
    generalError.value = '';
    
    // Convert local datetime to UTC for the server
    let publishedAtUTC = null;
    if (post.value.published_at) {
        // Create a Date object from the local datetime string
        const localDate = new Date(post.value.published_at);
        // Convert to UTC ISO string
        publishedAtUTC = localDate.toISOString();
    }
    
    const payload = {
        title: post.value.title,
        excerpt: post.value.excerpt,
        description: post.value.description,
        tags: post.value.tags,
        meta_title: post.value.meta_title,
        meta_description: post.value.meta_description,
        published_at: publishedAtUTC,
        image: post.value.image || '',
    };

    try {
        if (isEditing.value) {
            await axios.put(`/posts/${postId.value}`, payload);
        } else {
            await axios.post('/posts', payload);
        }
        router.push('/');
    } catch (error) {
        if (error.response && error.response.data && error.response.data.errors) {
            const rawErrors = error.response.data.errors;
            const flatErrors = {};
            for (const key in rawErrors) {
                if (key.startsWith('tags.')) {
                    flatErrors.tags = rawErrors[key][0];
                } else {
                    flatErrors[key] = rawErrors[key][0];
                }
            }
            errors.value = flatErrors;
        } else if (error.response && error.response.data && error.response.data.message) {
            generalError.value = error.response.data.message;
        } else {
            generalError.value = 'Failed to submit post.';
        }
    }
};

function addTag() {
  const tag = newTag.value.trim();
  if (tag && !post.value.tags.includes(tag)) {
    post.value.tags.push(tag);
    }
  newTag.value = '';
}

function removeTag(idx) {
  post.value.tags.splice(idx, 1);
}
</script> 