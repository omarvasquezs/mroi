<template>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img :src="`${baseUrl}/images/mroi_logo.png`" alt="MROI Logo" height="30">
            </a>
            <!-- Add navbar toggler button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav" aria-controls="navbarNav" 
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> {{ username }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><router-link class="dropdown-item" to="/change-password">Cambiar Clave</router-link></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form @submit.prevent="handleLogout">
                                    <button type="submit" class="dropdown-item">Salir del Sistema</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            baseUrl: window.location.origin,
            username: ''
        };
    },
    mounted() {
        const storedUser = localStorage.getItem('user');
        if (storedUser) {
            const userObj = JSON.parse(storedUser);
            this.username = userObj.username;
        }
    },
    methods: {
        async handleLogout() {
            try {
                await axios.post('/api/logout');
            } catch (error) {
                // Optionally handle errors
            }
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
    }
};
</script>
