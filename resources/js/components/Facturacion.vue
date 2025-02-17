<template>
    <div class="container">
        <div class="mb-4">
            <button @click="goBack" class="btn btn-link">← Regresar</button>
        </div>
        <h2 class="my-4">Facturación</h2>
        <div v-if="successMessage" class="alert alert-success">
            {{ successMessage }}
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <label for="fecha-inicio">FECHA INICIO:</label>
                <input type="date" id="fecha-inicio" v-model="filters.fechaInicio" class="form-control" @input="clearCheckboxFilters">
            </div>
            <div class="col-md-3">
                <label for="fecha-fin">FECHA FIN:</label>
                <input type="date" id="fecha-fin" v-model="filters.fechaFin" class="form-control" @input="clearCheckboxFilters">
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="fecha-hoy-dia" v-model="filters.fechaHoyDia" @change="toggleFechaHoyDia">
                    <label class="form-check-label non-selectable pointer" for="fecha-hoy-dia">FECHA HOY DÍA</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="mes-actual" v-model="filters.mesActual" @change="toggleMesActual">
                    <label class="form-check-label non-selectable pointer" for="mes-actual">MES ACTUAL</label>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header">Comprobantes Generados</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Número de Comprobante</th>
                            <th>Tipo de Comprobante</th>
                            <th>Servicio</th>
                            <th>Monto</th>
                            <th>Método de Pago</th>
                            <th>Fecha de Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="comprobante in sortedComprobantes" :key="comprobante.id">
                            <td>{{ `${comprobante.serie}-${comprobante.correlativo.toString().padStart(8, '0')}` }}</td>
                            <td>{{ comprobante.tipo === 'b' ? 'Boleta' : 'Factura' }}</td>
                            <td>{{ comprobante.servicio }}</td>
                            <td>{{ formatCurrency(comprobante.monto_total) }}</td>
                            <td>{{ getMetodoPago(comprobante.id_metodo_pago) }}</td>
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
            successMessage: this.$route.query.successMessage || '',
            filters: {
                fechaInicio: '',
                fechaFin: '',
                fechaHoyDia: false,
                mesActual: false
            }
        };
    },
    computed: {
        filteredComprobantes() {
            let filtered = this.comprobantes;

            if (this.filters.fechaInicio) {
                filtered = filtered.filter(comprobante => new Date(comprobante.created_at) >= new Date(this.filters.fechaInicio));
            }

            if (this.filters.fechaFin) {
                filtered = filtered.filter(comprobante => new Date(comprobante.created_at) <= new Date(this.filters.fechaFin));
            }

            if (this.filters.fechaHoyDia) {
                const today = new Date().toISOString().split('T')[0];
                filtered = filtered.filter(comprobante => comprobante.created_at.startsWith(today));
            }

            if (this.filters.mesActual) {
                const currentMonth = new Date().toISOString().slice(0, 7);
                filtered = filtered.filter(comprobante => comprobante.created_at.startsWith(currentMonth));
            }

            return filtered;
        },
        sortedComprobantes() {
            return this.filteredComprobantes.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        }
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
        },
        clearDateFilters() {
            this.filters.fechaInicio = '';
            this.filters.fechaFin = '';
        },
        clearCheckboxFilters() {
            this.filters.fechaHoyDia = false;
            this.filters.mesActual = false;
        },
        toggleFechaHoyDia() {
            if (this.filters.fechaHoyDia) {
                this.filters.mesActual = false;
                const today = new Date().toISOString().split('T')[0];
                this.filters.fechaInicio = today;
                this.filters.fechaFin = today;
            } else {
                this.clearDateFilters();
            }
        },
        toggleMesActual() {
            if (this.filters.mesActual) {
                this.filters.fechaHoyDia = false;
                const currentDate = new Date();
                const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).toISOString().split('T')[0];
                const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).toISOString().split('T')[0];
                this.filters.fechaInicio = firstDay;
                this.filters.fechaFin = lastDay;
            } else {
                this.clearDateFilters();
            }
        }
    }
};
</script>

<style scoped>
.table th, .table td {
    vertical-align: middle;
}
.non-selectable {
    user-select: none;
}
.pointer {
    cursor: pointer;
}
</style>
