<template>
    <div class="container">
        <h1 class="my-4">Facturación</h1>
        <div class="mb-4">
            <button @click="goBack" class="btn btn-link">← Regresar</button>
        </div>
        <div class="card mb-4">
            <div class="card-header">Comprobantes Generados</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Serie</th>
                            <th>Correlativo</th>
                            <th>Monto Total</th>
                            <th>Método de Pago</th>
                            <th>Pagado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="comprobante in comprobantes" :key="comprobante.id">
                            <td>{{ comprobante.tipo === 'b' ? 'Boleta' : 'Factura' }}</td>
                            <td>{{ comprobante.serie }}</td>
                            <td>{{ comprobante.correlativo }}</td>
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
            metodosPago: []
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
                const response = await axios.get('/api/metodos-pago');
                this.metodosPago = response.data;
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
