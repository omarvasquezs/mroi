<template>
    <div class="login-container" id="login">
        <form @submit.prevent="handleSubmit" class="login-form">
            <img :src="`${baseUrl}/images/gyf_logo_transparent.png`" alt="GYF Logo" class="logo" style="max-width: 100px;"/>
            <h2>SISTEMA OPTICA v1.0.0</h2>
            <div class="form-group">
                <label>Usuario:</label>
                <input v-model="username" type="text" required />
            </div>
            <div class="form-group">
                <label>Contraseña:</label>
                <input v-model="password" type="password" required />
            </div>
            <button type="submit" class="login-button" :disabled="isLoading">
                {{ isLoading ? 'CARGANDO...' : 'ACCEDER' }}
            </button>
            <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
        </form>
    </div>
</template>

<script>
import axios from 'axios';

const baseUrl = window.location.origin;

export default {
    name: 'LoginForm',
    data() {
        return {
            baseUrl,
            username: '',
            password: '',
            errorMessage: '',
            isLoading: false
        }
    },
    methods: {
        async handleSubmit() {
            this.isLoading = true;
            this.errorMessage = '';
            
            try {
                const response = await axios.post('/api/login', {
                    username: this.username,
                    password: this.password
                });

                // Store user data in localStorage for persistence
                localStorage.setItem('user', JSON.stringify(response.data.user));
                
                // Redirect based on role
                this.redirectBasedOnRole(response.data.user.role);
            } catch (error) {
                this.errorMessage = error.response?.data?.error || 'Error al iniciar sesión';
            } finally {
                this.isLoading = false;
            }
        },
        redirectBasedOnRole() {
            window.location.href = '/';
        }
    }
}
</script>

<style scoped>
.login-container {
    display: flex;
    justify-content: center;
    align-items: flex-start; /* Align items to the top */
    height: 100vh; /* Full viewport height */
    padding-top: 10vh; /* Add padding to move it down slightly */
}

.login-form {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    width: 100%; /* Full width */
    max-width: 500px; /* Max width */
}

.login-form h2 {
    margin-bottom: 1.5rem; /* Increased margin */
    text-align: center;
}

.form-group {
    margin-bottom: 1.5rem; /* Increased margin */
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
}

.form-group input {
    width: 100%;
    padding: 0.75rem; /* Increased padding */
    border: 1px solid #ccc;
    border-radius: 4px;
}

.login-button {
    width: 100%;
    padding: 1rem; /* Increased padding */
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
}

.login-button:hover {
    background-color: #0056b3;
}

.error-message {
    color: red;
    margin-top: 1rem;
    text-align: center;
}

.logo {
    width: 200px;
    height: auto;
    display: block;
    margin: 0 auto 1.5rem;
}
</style>
