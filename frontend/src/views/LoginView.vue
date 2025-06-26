<template>
  <div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
      <div class="card-body">
        <h2 class="card-title text-center mb-4">Sign in to your account</h2>
        <form @submit.prevent="handleRequestOtp">
          <div class="mb-3">
            <label for="email-address" class="form-label">Email address</label>
            <input id="email-address" name="email" type="email" v-model="email" required class="form-control" placeholder="Email address">
          </div>
          <div class="d-grid mb-3">
            <button type="submit" :disabled="otpSent" class="btn btn-primary">
            {{ otpSent ? 'OTP Sent' : 'Request OTP' }}
          </button>
        </div>
      </form>
        <form v-if="otpSent" @submit.prevent="handleLogin">
          <div class="mb-3">
            <label for="otp" class="form-label">OTP</label>
            <input id="otp" name="otp" type="text" v-model="otp" required class="form-control" :class="{ 'is-invalid': errors.otp }" placeholder="One-Time Password">
            <div v-if="errors.otp" class="invalid-feedback">
              {{ errors.otp }}
            </div>
          </div>
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-success">
            Login
          </button>
        </div>
      </form>
        <div class="text-center">
          <p class="mb-0">
            Don't have an account?
            <router-link to="/register" class="text-decoration-none">
              Register here
            </router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();
const email = ref('');
const otp = ref('');
const otpSent = ref(false);
const errors = ref({});

const handleRequestOtp = async () => {
    errors.value = {};
    try {
        await authStore.requestOtp(email.value);
        otpSent.value = true;
    } catch (err) {
        // Assuming request OTP error is email related
        errors.value.email = err.message || 'Failed to send OTP.';
    }
};

const handleLogin = async () => {
    errors.value = {};
    const result = await authStore.login(email.value, otp.value);
    if (result.success) {
        router.push('/');
    } else {
        if (result.errors.otp_code) {
            errors.value.otp = result.errors.otp_code[0];
        } else if (result.errors.general) {
            errors.value.otp = result.errors.general[0];
        } else {
            errors.value.otp = 'An unknown validation error occurred.';
        }
    }
};
</script> 