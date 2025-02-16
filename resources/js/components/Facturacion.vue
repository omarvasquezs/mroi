<template>
    <div class="container">
        <h1 class="my-4">Facturación</h1>
        <div v-if="successMessage" class="alert alert-success">
            {{ successMessage }}
        </div>
        <div class="mb-4">
            <button @click="goBack" class="btn btn-link">← Regresar</button>
        </div>
        <div class="card mb-4">
            <div class="card-header">Comprobantes Generados</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Número Comprobante</th>
                            <th>Tipo</th>
                            <th>Monto Total</th>
                            <th>Método de Pago</th>
                            <th>Pagado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="comprobante in comprobantes" :key="comprobante.id">
                            <td>{{ `${comprobante.serie}-${comprobante.correlativo.toString().padStart(8, '0')}` }}</td>
                            <td>{{ comprobante.tipo === 'b' ? 'Boleta' : 'Factura' }}</td>
                            <td>{{ formatCurrency(comprobante.monto_total) }}</td>
                            <td>{{ getMetodoPago(comprobante.id_metodo_pago) }}</td>
                            <td>{{ comprobante.pagado ? 'Sí' : 'No' }}</td>
                            <td>{{ formatDate(comprobante.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            comprobantes: [],
            metodosPago: [],
            successMessage: this.$route.query.successMessage || ''
        };
    },
    mounted() {
        this.fetchComprobantes();
        this.fetchMetodosPago();
    },
    methods: {
        goBack() {
            window.history.back();
        },
        async fetchComprobantes() {
            try {
                const response = await axios.get('/api/comprobantes');
                this.comprobantes = response.data;
            } catch (error) {
                console.error('Error fetching comprobantes:', error);
            }
        },
        async fetchMetodosPago() {
            try {
                const response = await axios.get('/api/active-metodos-pago');
                this.metodosPago = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                console.error('Error fetching metodos de pago:', error);
            }
        },
        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleString('es-PE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN'
            }).format(amount);
        },
        getMetodoPago(id) {
            if (!Array.isArray(this.metodosPago)) return 'N/A';
            const metodo = this.metodosPago.find(m => m.id === id);
            return metodo ? metodo.nombre : 'N/A';
        }
    }
};
</script>

<style scoped>
.table th, .table td {
    vertical-align: middle;
}
</style>
