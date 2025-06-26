<template>
    <div class="mt-4">
        <h3 class="mb-3">Comments</h3>
        <div v-if="props.showAddComment" class="mb-3">
            <form @submit.prevent="addComment">
                <div class="mb-2">
                    <textarea ref="editorElement" v-model="newComment" class="form-control" placeholder="Add a comment" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Submit</button>
            </form>
        </div>
        <div v-else class="mb-3" v-if="!props.showAddComment">
            <p>Please <router-link to="/login">login</router-link> to comment.</p>
        </div>
        <div v-if="comments.length === 0" class="text-muted text-center py-4">
            <i class="bi bi-chat-left-text display-6"></i>
            <div>No comments yet. Be the first to comment!</div>
        </div>
        <ul class="list-unstyled">
            <li v-for="comment in comments" :key="comment.id" class="mb-4">
                <div class="d-flex align-items-start gap-3 p-3 bg-light rounded shadow-sm">
                    <div class="avatar flex-shrink-0 rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.2rem; font-weight: 600;">
                        {{ comment.user.name ? comment.user.name.split(' ').map(n => n[0]).join('').toUpperCase() : '?' }}
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">{{ comment.user.name || 'Unknown' }}</span>
                            <span class="text-muted small">&middot; {{ formatDate(comment.created_at) }}</span>
                        </div>
                        <div class="comment-content">
                            <Markdown :source="comment.content" />
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';
import Markdown from 'vue3-markdown-it';
import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';

// Use the default axios instance with global interceptors
const api = axios;
api.defaults.baseURL = 'http://localhost:8000/api';

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
    showAddComment: {
        type: Boolean,
        default: false,
    },
});

const authStore = useAuthStore();
const comments = ref([]);
const newComment = ref('');
const editor = ref(null);
const editorElement = ref(null);

const fetchComments = async () => {
    comments.value = props.post.comments || [];
};

onMounted(() => {
    fetchComments();
    if (editorElement.value) {
        editor.value = new EasyMDE({
            element: editorElement.value,
            spellChecker: false,
            status: false,
            placeholder: 'Add a comment...'
        });
    }
});
onBeforeUnmount(() => {
    if (editor.value) {
        editor.value.toTextArea();
        editor.value = null;
    }
});

const addComment = async () => {
    let content = newComment.value;
    if (editor.value) {
        content = editor.value.value();
    }
    if (!content.trim()) return;
    const response = await api.post(`/posts/${props.post.id}/comments`, {
        content,
    });
    comments.value.push(response.data);
    newComment.value = '';
    if (editor.value) {
        editor.value.value('');
    }
};

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleString();
}
</script>

<style scoped>
.avatar {
    box-shadow: 0 2px 8px rgba(60,60,120,0.08);
    text-transform: uppercase;
}
.comment-content :deep(p) {
    margin-bottom: 0;
}
</style> 