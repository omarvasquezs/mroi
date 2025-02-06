<template>
    <div class="container mt-5 pt-5">
        <div class="mb-4">
            <button @click="goBack" class="btn btn-link">← Regresar</button>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Cambiar Clave</div>
                    <div class="card-body">
                        <form @submit.prevent="changePassword">
                            <div class="mb-3">
                                <label class="form-label">Nueva Clave</label>
                                <input type="password" class="form-control" v-model="form.new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar Nueva Clave</label>
                                <input type="password" class="form-control" v-model="form.confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Cambiar Clave</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            form: {
                new_password: '',
                confirm_password: ''
            },
            id: null
        }
    },
    created() {
        // Extract id from localStorage user
        this.id = JSON.parse(localStorage.getItem('user')).id;
    },
    methods: {
        async changePassword() {
            if (this.form.new_password !== this.form.confirm_password) {
                alert('Las claves nuevas no coinciden');
                return;
            }

            try {
                const response = await axios.post('/api/change-password', 
                    { 
                        id: this.id,
                        new_password: this.form.new_password,
                        password_confirmation: this.form.confirm_password
                    }
                );

                alert('Clave cambiada exitosamente');
                this.form = {
                    new_password: '',
                    confirm_password: ''
                };
            } catch (error) {
                alert(error.response?.data?.message || 'Error al cambiar la clave');
            }
        },
        goBack() {
            this.$router.go(-1);
        }
    }
}
</script>
