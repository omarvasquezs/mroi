<template>
    <div class="container">
        <div class="mb-4">
            <button @click="goBack" class="btn btn-link">← Regresar</button>
        </div>
        <!-- Selection Section -->
        <div class="card mb-4">
            <div class="card-header">Servicio</div>
            <div class="card-body">
                <div class="form-group position-relative select-wrapper">
                    <label for="tipo-servicio-select">Tipo de Servicio</label>
                    <select v-model="comprobanteType" class="form-control pointer" id="tipo-servicio-select">
                        <option value="" disabled selected class="pointer">Seleccione tipo de servicio</option>
                        <option value="citas" class="pointer">Citas</option>
                        <option value="productos" class="pointer">Productos</option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
                </div>
            </div>
        </div>

        <!-- Citas Section -->
        <div v-if="comprobanteType === 'citas'">
            <!-- Search Patient Section -->
            <div class="card mb-4">
                <div class="card-header">Búsqueda de Paciente</div>
                <div class="card-body">
                    <div class="form-group position-relative select-wrapper">
                        <label for="paciente-select">Seleccionar Paciente</label>
                        <select 
                            v-model="selectedPatientId" 
                            class="form-control pointer" 
                            id="paciente-select"
                            @change="fetchPatientAppointments"
                        >
                            <option value="" disabled selected class="pointer">Seleccione un paciente</option>
                            <option v-for="paciente in pacientes" :key="paciente.id" :value="paciente.id" class="pointer">
                                {{ paciente.num_historia }} - {{ paciente.nombre }}
                            </option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
            </div>

            <!-- Patient Pending Appointments Section -->
            <div v-if="selectedPatient" class="card mb-4">
                <div class="card-header">
                    Citas Pendientes - {{ selectedPatient.nombre }}
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <input 
                                        type="checkbox" 
                                        v-model="selectAll"
                                        @change="toggleAll"
                                    >
                                </th>
                                <th>Fecha</th>
                                <th>Tipo de Cita</th>
                                <th>Médico</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cita in pendingAppointments" :key="cita.id" @click="toggleAppointmentSelection(cita.id)" class="clickable-row">
                                <td>
                                    <input 
                                        type="checkbox" 
                                        v-model="selectedAppointments" 
                                        :value="cita.id"
                                    >
                                </td>
                                <td>{{ formatDate(cita.fecha) }}</td>
                                <td>{{ cita.tipo_cita }}</td>
                                <td>{{ cita.medico }}</td>
                                <td>{{ formatCurrency(cita.monto) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Productos Section -->
        <div v-if="comprobanteType === 'productos'">
            <!-- List Producto Comprobante Section -->
            <div class="card mb-4">
                <div class="card-header">Lista de solicitudes de compra</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="text-center">Seleccionar</th>
                                <th>Nombres</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Monto Total</th>
                                <th>Registrado en</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="productoComprobante in productoComprobantes" :key="productoComprobante.id" @click="selectProductoComprobante(productoComprobante.id)" class="clickable-row">
                                <td class="text-center">
                                    <input 
                                        type="radio" 
                                        v-model="selectedProductoComprobanteId" 
                                        :value="productoComprobante.id"
                                    >
                                </td>
                                <td>{{ productoComprobante.nombres }}</td>
                                <td>{{ productoComprobante.telefono }}</td>
                                <td>{{ productoComprobante.correo }}</td>
                                <td>{{ formatCurrency(productoComprobante.monto_total) }}</td>
                                <td>{{ formatDate(productoComprobante.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Producto Comprobante Items Section -->
            <div v-if="selectedProductoComprobante" class="card mb-4">
                <div class="card-header">
                    Detallado de productos de la solicitud seleccionada
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Precio Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in productoComprobanteItems" :key="item.id">
                                <td>{{ item.stock ? item.stock.producto : 'N/A' }}</td>
                                <td class="text-center">{{ item.cantidad }}</td>
                                <td>{{ formatCurrency(item.precio_unitario) }}</td>
                                <td>{{ formatCurrency(item.precio_total) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong>{{ formatCurrency(totalPrecio) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Section -->
        <div v-if="(comprobanteType === 'citas' && selectedAppointments.length) || (comprobanteType === 'productos' && selectedProductoComprobante)" class="card mb-4">
            <div class="card-header">Generar Comprobante</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group position-relative select-wrapper">
                            <label>Tipo de Comprobante</label>
                            <select v-model="comprobante.tipo" class="form-control pointer">
                                <option value="b" class="pointer">Boleta</option>
                                <option value="f" class="pointer">Factura</option>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group position-relative select-wrapper">
                            <label>Método de Pago</label>
                            <select v-model="comprobante.id_metodo_pago" class="form-control pointer">
                                <option value="" disabled selected class="pointer">Seleccione método de pago</option>
                                <option v-for="metodo in metodosPago" 
                                        :key="metodo.id" 
                                        :value="metodo.id"
                                        class="pointer">
                                    {{ metodo.nombre }}
                                </option>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                </div>

                <div class="text-right mt-3">
                    <button 
                        class="btn btn-success" 
                        @click="generateComprobante"
                    >
                        Generar Comprobante
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            comprobanteType: '',
            searchTerm: '',
            pacientes: [],
            selectedPatientId: '',
            selectedPatient: null,
            pendingAppointments: [],
            selectedAppointments: [],
            selectAll: false,
            metodosPago: [],
            comprobante: {
                tipo: 'b',
                id_metodo_pago: ''
            },
            productoComprobantes: [],
            selectedProductoComprobanteId: '',
            selectedProductoComprobante: null,
            productoComprobanteItems: []
        }
    },
    computed: {
        totalPrecio() {
            return this.productoComprobanteItems.reduce((total, item) => total + item.precio_total, 0);
        }
    },
    mounted() {
        this.fetchPacientes();
        this.fetchMetodosPago();
        this.fetchProductoComprobantes();
    },
    methods: {
        goBack() {
            window.history.back();
        },
        async fetchPacientes() {
            try {
                const response = await axios.get('/api/pacientes-list');
                this.pacientes = response.data;
            } catch (error) {
                console.error('Error fetching pacientes:', error);
                this.pacientes = [];
            }
        },
        async fetchPatientAppointments() {
            try {
                const selectedPatient = this.pacientes.find(p => p.id === this.selectedPatientId);
                if (!selectedPatient) {
                    console.error('No patient found with selected ID');
                    return;
                }
                
                const response = await axios.get(`/api/pacientes/search/${selectedPatient.num_historia}`);
                this.selectedPatient = response.data.patient;
                this.pendingAppointments = response.data.pendingAppointments;
            } catch (error) {
                console.error('Error fetching patient appointments:', error);
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
        async fetchProductoComprobantes() {
            try {
                const response = await axios.get('/api/productos-comprobante');
                const data = Array.isArray(response.data.data) ? response.data.data : 
                            Array.isArray(response.data) ? response.data : [];
                this.productoComprobantes = data;
            } catch (error) {
                console.error('Error fetching producto comprobantes:', error);
                this.productoComprobantes = [];
            }
        },
        async fetchProductoComprobanteItems() {
            try {
                const response = await axios.get(`/api/productos-comprobante/${this.selectedProductoComprobanteId}`);
                this.selectedProductoComprobante = response.data;
                this.productoComprobanteItems = response.data.items.map(item => ({
                    ...item,
                    precio_unitario: item.precio,
                    precio_total: item.precio * item.cantidad
                }));
            } catch (error) {
                console.error('Error fetching producto comprobante items:', error);
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
        toggleAll() {
            if (this.selectAll) {
                this.selectedAppointments = this.pendingAppointments.map(cita => cita.id);
            } else {
                this.selectedAppointments = [];
            }
        },
        toggleAppointmentSelection(id) {
            const index = this.selectedAppointments.indexOf(id);
            if (index === -1) {
                this.selectedAppointments.push(id);
            } else {
                this.selectedAppointments.splice(index, 1);
            }
        },
        selectProductoComprobante(id) {
            this.selectedProductoComprobanteId = id;
            this.fetchProductoComprobanteItems();
        },
        async generateComprobante() {
            if (this.comprobanteType === 'citas' && !this.selectedPatient) {
                alert('Seleccione un paciente antes de generar el comprobante.');
                return;
            }

            try {
                let response;
                if (this.comprobanteType === 'citas') {
                    response = await axios.post('/api/comprobantes', {
                        tipo: this.comprobante.tipo,
                        id_metodo_pago: this.comprobante.id_metodo_pago,
                        citas: this.selectedAppointments,
                        paciente_id: this.selectedPatient.id
                    });
                } else if (this.comprobanteType === 'productos') {
                    response = await axios.post('/api/comprobantes', {
                        tipo: this.comprobante.tipo,
                        id_metodo_pago: this.comprobante.id_metodo_pago,
                        productos_comprobante_id: this.selectedProductoComprobante.id
                    });
                }

                if (response.data.error) {
                    alert('Error: ' + response.data.error);
                    return;
                }

                const comprobante = response.data.comprobante;
                this.$router.push({
                    path: '/facturacion',
                    query: { successMessage: `SE GENERO COMPROBANTE ${comprobante.serie}-${comprobante.correlativo.toString().padStart(8, '0')}` }
                });
            } catch (error) {
                console.error('Error generating comprobante:', error);
                alert('Error al generar el comprobante: ' + (error.response?.data?.error || error.message));
            }
        }
    }
}
</script>

<style scoped>
.select-wrapper {
  position: relative;
  width: 100%;
}

.select-wrapper select {
  width: 100%;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  padding-right: 30px;
  background-color: #fff;  /* Ensure background is white */
  cursor: pointer;
}

.select-wrapper option {
  cursor: pointer !important;
  appearance: none !important;
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
}

.select-arrow {
  position: absolute;
  right: 15px; /* Adjusted to align properly */
  top: 69%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #6c757d;
}

.table th, .table td {
  vertical-align: middle; /* Center align the content vertically */
}

.clickable-row {
  cursor: pointer;
}

.pointer {
  cursor: pointer;
}
</style>
