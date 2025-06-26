import { defineStore } from 'pinia';
import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost:8000/api',
});

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: JSON.parse(localStorage.getItem('user')) || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async requestOtp(email) {
            await api.post('/request-otp', { email });
        },
        async login(email, otp) {
            try {
                const response = await api.post('/login-otp', { email, otp_code: otp });
                const { access_token, user } = response.data;
                this.token = access_token;
                this.user = user;
                localStorage.setItem('token', access_token);
                localStorage.setItem('user', JSON.stringify(user));
                api.defaults.headers.common['Authorization'] = `Bearer ${access_token}`;
                return { success: true };
            } catch (error) {
                let errors = {};
                if (error.response && error.response.data && error.response.data.errors) {
                    errors = error.response.data.errors;
                } else if (error.response && error.response.data && error.response.data.message) {
                    errors = { general: [error.response.data.message] };
                } else {
                    errors = { general: [error.message || 'An unexpected error occurred.'] };
                }
                return { success: false, errors: errors };
            }
        },
        setAuth(token, user) {
            this.token = token;
            this.user = user;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        },
        logout() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            delete api.defaults.headers.common['Authorization'];
        },
        initializeAuth() {
            if (this.token) {
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            }
        },
        async validateToken() {
            if (!this.token) {
                return;
            }
            try {
                const res = await api.get('/profile'); // or '/me' if that's your endpoint
                this.user = res.data.user || res.data;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (e) {
                this.logout();
            }
        },
    },
}); 