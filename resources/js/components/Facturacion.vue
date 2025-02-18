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
                            <th>Acciones</th>
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
                            <td>
                                <i class="fas fa-file-pdf pointer" @click="generateComprobante(comprobante.id)"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PDF Modal -->
        <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfModalLabel">Comprobante PDF</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <iframe :src="pdfUrl" width="100%" height="500px"></iframe>
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
            pdfUrl: '' // URL of the PDF to be displayed in the modal
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
        },
        async generateComprobante(comprobanteId) {
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

                // Create PDF blob and URL
                const pdfContent = atob(response.data.pdf);
                const pdfBlob = new Blob([new Uint8Array([...pdfContent].map(char => char.charCodeAt(0)))], { type: 'application/pdf' });
                const pdfUrl = URL.createObjectURL(pdfBlob);

                // Set PDF URL
                this.pdfUrl = pdfUrl;

                // Print and show modal
                this.printComprobante(comprobante);
                this.openPdfModal(comprobante.id);
            } catch (error) {
                console.error('Error generating comprobante:', error);
                const errorMessage = error.response?.data?.error || error.message;
                const errorDetails = error.response?.data?.details;
                if (errorDetails) {
                    console.error('Error details:', errorDetails);
                }
                alert('Error al generar el comprobante: ' + errorMessage);
            }
        },
        printComprobante(comprobante) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(this.getPrintTemplate(comprobante));
            printWindow.document.close();
            printWindow.print();
        },
        getPrintTemplate(comprobante) {
            if (!comprobante || !comprobante.servicio) {
                return '<p>Error: Comprobante no válido</p>';
            }

            const metodoPago = comprobante.id_metodo_pago 
                ? this.metodosPago.find(m => m.id === comprobante.id_metodo_pago)?.nombre || '' 
                : 'N/A';
            const tipoComprobante = comprobante.tipo === 'b' ? 'BOLETA' : 'FACTURA';
            const fecha = new Date().toLocaleString('es-PE');
            
            let itemsHtml = '';
            if (comprobante.servicio === 'Cita') {
                itemsHtml = comprobante.citas
                    .map(cita => `
                        <div class="item">
                            <p>${cita.tipo_cita}</p>
                            <p>Médico: ${cita.medico}</p>
                            <p>Fecha: ${this.formatDate(cita.fecha)}</p>
                            <p>Monto: ${this.formatCurrency(cita.pivot.monto)}</p>
                        </div>
                    `).join('<hr style="border-top: 1px dashed #ccc">');
            } else if (comprobante.servicio === 'Producto') {
                itemsHtml = comprobante.productoComprobante && comprobante.productoComprobante.items
                    ? comprobante.productoComprobante.items
                        .map(item => `
                            <div class="item">
                                <p>Producto: ${item.stock ? item.stock.producto : 'N/A'}</p>
                                <p>Cantidad: ${item.cantidad}</p>
                                <p>Precio: ${this.formatCurrency(item.precio_unitario)}</p>
                            </div>
                        `).join('<hr style="border-top: 1px dashed #ccc">')
                    : '<p>No hay items disponibles</p>';
            }

            return `
                <html>
                <head>
                    <style>
                        @page {
                            size: 80mm 297mm;
                            margin: 0;
                        }
                        body {
                            font-family: 'Courier New', monospace;
                            width: 80mm;
                            padding: 5mm;
                            font-size: 12px;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 10px;
                            border-bottom: 1px dashed #000;
                            padding-bottom: 5px;
                        }
                        .details {
                            margin-bottom: 10px;
                        }
                        .item {
                            margin-bottom: 5px;
                        }
                        .total {
                            border-top: 1px dashed #000;
                            margin-top: 10px;
                            padding-top: 5px;
                            font-weight: bold;
                        }
                        .footer {
                            margin-top: 20px;
                            text-align: center;
                            font-size: 10px;
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2 style="margin:0;">CLÍNICA GYF</h2>
                        <p style="margin:5px 0;">${tipoComprobante} DE VENTA ELECTRÓNICA</p>
                        <p style="margin:5px 0;">${comprobante.serie}-${comprobante.correlativo.toString().padStart(8, '0')}</p>
                    </div>
                    
                    <div class="details">
                        <p>Fecha: ${fecha}</p>
                        <p>Paciente: ${comprobante.paciente?.nombre || 'N/A'}</p>
                        <p>Historia: ${comprobante.paciente?.num_historia || 'N/A'}</p>
                        <p>Método de pago: ${metodoPago}</p>
                    </div>

                    <div class="items">
                        <div style="border-bottom: 1px dashed #000; margin: 10px 0;">
                            <p>DETALLE DE SERVICIOS</p>
                        </div>
                        ${itemsHtml}
                    </div>

                    <div class="total">
                        <p>TOTAL: ${this.formatCurrency(comprobante.monto_total)}</p>
                    </div>

                    <div class="footer">
                        <p>¡Gracias por su preferencia!</p>
                        <p>Representación impresa de la ${tipoComprobante} de venta electrónica</p>
                    </div>
                </body>
                </html>
            `;
        },
        openPdfModal(comprobanteId) {
            this.pdfUrl = `/api/comprobantes/${comprobanteId}/pdf?format=thermal`;
            const pdfModal = new bootstrap.Modal(document.getElementById('pdfModal'));
            pdfModal.show();
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
