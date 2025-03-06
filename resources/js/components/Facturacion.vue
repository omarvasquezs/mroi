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
            <div class="col-md-3">
                <label for="per-page" class="pointer">POR PÁGINA:</label>
                <div class="input-group">
                    <select id="per-page" v-model="pagination.perPage" class="form-control form-select pointer" @change="handlePerPageChange">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
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
                            <th>Paciente</th>
                            <th>Servicio</th>
                            <th>Monto</th>
                            <th>Método de Pago</th>
                            <th>Fecha de Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="comprobante in sortedComprobantes" :key="comprobante.id">
                            <td>{{ `${comprobante.serie}-${comprobante.correlativo.toString().padStart(8, '0')}` }}</td>
                            <td>{{ comprobante.tipo === 'b' ? 'Boleta' : 'Factura' }}</td>
                            <td>{{ comprobante.paciente_nombre || 'N/A' }}</td>
                            <td>{{ comprobante.servicio }}</td>
                            <td>{{ formatCurrency(comprobante.monto_total) }}</td>
                            <td>{{ getMetodoPago(comprobante.id_metodo_pago) }}</td>
                            <td>{{ formatDate(comprobante.created_at) }}</td>
                            <td>
                                <i class="fas fa-file-pdf pointer" @click="generateComprobante(comprobante.id)"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Updated Pagination Controls - removed the "Por página" selector -->
                <nav v-if="pagination.total > pagination.perPage" class="mt-4 d-flex justify-content-end">
                    <ul class="pagination">
                        <li class="page-item" :class="{ disabled: pagination.currentPage <= 1 }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.currentPage - 1)">Anterior</a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.currentPage >= pagination.lastPage }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.currentPage + 1)">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- PDF Modal -->
        <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfModalLabel">Comprobante {{ modalComprobanteNumero }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="loading" class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <iframe v-else :src="pdfUrl" width="100%" height="100%"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
            comprobantes: [],
            metodosPago: [],
            successMessage: this.$route.query.successMessage || '',
            filters: {
                fechaInicio: '',
                fechaFin: '',
                fechaHoyDia: false,
                mesActual: false
            },
            pdfUrl: '', // URL of the PDF to be displayed in the modal
            modalComprobanteNumero: '', // Número de Comprobante for the modal title
            loading: false, // Loading state for PDF generation
            pagination: {
                currentPage: 1,
                perPage: 10,
                total: 0,
                lastPage: 1
            }
        };
    },
    computed: {
        // Replace the filteredComprobantes method with this one since filtering will be done on the server
        filteredComprobantes() {
            return this.comprobantes;
        },
        
        sortedComprobantes() {
            return this.filteredComprobantes;
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
                const response = await axios.get('/api/comprobantes', {
                    params: {
                        page: this.pagination.currentPage,
                        per_page: this.pagination.perPage,
                        fecha_inicio: this.filters.fechaInicio || '',
                        fecha_fin: this.filters.fechaFin || '',
                        fecha_hoy_dia: this.filters.fechaHoyDia ? 1 : 0,
                        mes_actual: this.filters.mesActual ? 1 : 0
                    }
                });
                
                this.comprobantes = response.data.data;
                this.pagination.currentPage = response.data.current_page;
                this.pagination.lastPage = response.data.last_page;
                this.pagination.total = response.data.total;
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
        changePage(page) {
            // Don't navigate past bounds
            if (page < 1 || page > this.pagination.lastPage) return;
            
            this.pagination.currentPage = page;
            this.fetchComprobantes();
        },
        handlePerPageChange() {
            this.pagination.currentPage = 1; // Reset to first page when changing items per page
            this.fetchComprobantes();
        },
        clearDateFilters() {
            this.filters.fechaInicio = '';
            this.filters.fechaFin = '';
            this.pagination.currentPage = 1; // Reset to first page when changing filters
            this.fetchComprobantes();
        },
        clearCheckboxFilters() {
            this.filters.fechaHoyDia = false;
            this.filters.mesActual = false;
            this.pagination.currentPage = 1; // Reset to first page when changing filters
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
            this.pagination.currentPage = 1; // Reset to first page when changing filters
            this.fetchComprobantes();
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
            this.pagination.currentPage = 1; // Reset to first page when changing filters
            this.fetchComprobantes();
        },
        async generateComprobante(comprobanteId) {
            this.loading = true; // Set loading state to true
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await axios.post(`/api/comprobantes/${comprobanteId}/generate`, {}, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (response.data.error) {
                    console.error('Server error details:', response.data.details);
                    alert('Error: ' + response.data.error);
                    return;
                }

                const comprobante = response.data.comprobante;
                if (!comprobante || !comprobante.id) {
                    alert('Error: Comprobante no válido');
                    return;
                }

                // Set PDF URL
                this.pdfUrl = `/api/comprobantes/${comprobante.id}/pdf`;
                this.modalComprobanteNumero = `${comprobante.serie}-${comprobante.correlativo.toString().padStart(8, '0')}`;

                // Show modal
                const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
                pdfModal.show();
            } catch (error) {
                console.error('Error generating comprobante:', error);
                const errorMessage = error.response?.data?.error || error.message;
                const errorDetails = error.response?.data?.details;
                if (errorDetails) {
                    console.error('Error details:', errorDetails);
                }
                alert('Error al generar el comprobante: ' + errorMessage);
            } finally {
                this.loading = false; // Set loading state to false
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
.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>
