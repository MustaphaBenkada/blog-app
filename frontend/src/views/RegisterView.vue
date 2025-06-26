<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card shadow border-0">
          <div class="card-body p-4">
            <div class="text-center mb-4">
              <h2 class="h3 fw-bold text-primary">
                <i class="bi bi-person-plus me-2"></i>Register
              </h2>
              <p class="text-muted">Create your account</p>
            </div>

            <form @submit.prevent="handleRegister">
              <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="form-control"
                  id="name"
                  :class="{ 'is-invalid': errors.name }"
                  placeholder="Enter your name"
                  required
                />
                <div v-if="errors.name" class="invalid-feedback">
                  {{ errors.name }}
                </div>
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="form-control"
                  id="email"
                  :class="{ 'is-invalid': errors.email }"
                  placeholder="Enter your email"
                  required
                />
                <div v-if="errors.email" class="invalid-feedback">
                  {{ errors.email }}
                </div>
              </div>

              <div class="d-grid mb-3">
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="loading"
                >
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                  {{ loading ? 'Registering...' : 'Register' }}
                </button>
              </div>

              <div v-if="error" class="alert alert-danger" role="alert">
                {{ error }}
              </div>

              <div class="text-center">
                <p class="mb-0">
                  Already have an account?
                  <router-link to="/login" class="text-decoration-none">
                    Login here
                  </router-link>
                </p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
  name: '',
  email: ''
});

const errors = reactive({
  name: '',
  email: ''
});

const loading = ref(false);
const error = ref('');

const clearErrors = () => {
  Object.keys(errors).forEach(key => {
    errors[key] = '';
  });
  error.value = '';
};

const handleRegister = async () => {
  clearErrors();
  loading.value = true;

  try {
    const response = await axios.post('/auth/register', {
      name: form.name,
      email: form.email
    });

    const { access_token, user } = response.data;
    
    // Store the token and user data
    authStore.setAuth(access_token, user);
    
    // Redirect to home page
    router.push('/');
  } catch (err) {
    if (err.response?.data?.errors) {
      // Handle validation errors from backend
      const backendErrors = err.response.data.errors;
      Object.keys(backendErrors).forEach(key => {
        if (errors.hasOwnProperty(key)) {
          errors[key] = backendErrors[key][0];
        }
      });
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'Registration failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.card {
  border-radius: 1rem;
}

.input-group-text {
  background-color: #f8f9fa;
  border-right: none;
}

.form-control {
  border-left: none;
}

.form-control:focus {
  border-color: #dee2e6;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.input-group:focus-within .input-group-text {
  border-color: #86b7fe;
}
</style> 