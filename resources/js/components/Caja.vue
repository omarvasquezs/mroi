<template>
    <div class="container">
        <!-- Back Button -->
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
                        <option value="intervenciones" class="pointer">Intervenciones</option>
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
                        <label for="paciente-cita-select">Seleccionar Paciente (con Citas Pendientes)</label>
                        <select
                            v-model="selectedPatientId"
                            class="form-control pointer"
                            id="paciente-cita-select"
                            @change="fetchPatientAppointments"
                        >
                            <option value="" disabled selected class="pointer">Seleccione un paciente</option>
                            <option v-for="paciente in pacientesCitas" :key="paciente.id" :value="paciente.id" class="pointer">
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
                                    Seleccionar
                                </th>
                                <th>Fecha</th>
                                <th>Tipo de Cita</th>
                                <th>Médico</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cita in pendingAppointments" :key="cita.id" @click="selectAppointment(cita.id)" class="clickable-row">
                                <td>
                                    <input 
                                        type="radio" 
                                        v-model="selectedAppointmentId" 
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
                                <td>{{ item.stock ? item.stock.descripcion : 'N/A' }}</td>
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

        <!-- Intervenciones Section -->
        <div v-if="comprobanteType === 'intervenciones'">
            <!-- Search Patient Section -->
            <div class="card mb-4">
                <div class="card-header">Búsqueda de Paciente</div>
                <div class="card-body">
                    <div class="form-group position-relative select-wrapper">
                        <label for="paciente-intervencion-select">Seleccionar Paciente (con Intervenciones Pendientes)</label>
                        <select
                            v-model="selectedPatientId"
                            class="form-control pointer"
                            id="paciente-intervencion-select"
                            @change="fetchPatientIntervenciones"
                        >
                            <option value="" disabled selected class="pointer">Seleccione un paciente</option>
                            <option v-for="paciente in pacientesIntervenciones" :key="paciente.id" :value="paciente.id" class="pointer">
                                {{ paciente.num_historia }} - {{ paciente.nombre }}
                            </option>
                        </select>
                        <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                </div>
            </div>

            <!-- Patient Pending Interventions Section -->
            <div v-if="selectedPatient" class="card mb-4">
                <div class="card-header">
                    Intervenciones Pendientes - {{ selectedPatient.nombre }}
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Seleccionar</th>
                                <th>Fecha y Rangos de Horas</th>
                                <th>Tipo de Intervención</th>
                                <th>Médico</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="intervencion in pendingIntervenciones" :key="intervencion.id" @click="selectIntervencion(intervencion.id)" class="clickable-row">
                                <td>
                                    <input
                                        type="radio"
                                        v-model="selectedIntervencionId"
                                        :value="intervencion.id"
                                    >
                                </td>
                                <td>{{ formatIntervencionDateTime(intervencion) }}</td>
                                <td>{{ intervencion.tipo_intervencion ? intervencion.tipo_intervencion.nombre : 'N/A' }}</td>
                                <td>{{ intervencion.medico ? intervencion.medico.nombre : 'N/A' }}</td>
                                <td>{{ formatCurrency(intervencion.monto) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Section -->
        <div v-if="(comprobanteType === 'citas' && selectedAppointmentId) || (comprobanteType === 'productos' && selectedProductoComprobante) || (comprobanteType === 'intervenciones' && selectedIntervencionId)" class="card mb-4">
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
                            <label>Método de Pago <span class="text-danger">*</span></label>
                            <select v-model="comprobante.id_metodo_pago" class="form-control pointer" required>
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
            comprobanteType: '', // Type of service selected (citas, productos, or intervenciones)
            pacientesCitas: [], // Patients with pending appointments
            pacientesIntervenciones: [], // Patients with pending interventions
            selectedPatientId: '', // ID of the selected patient
            selectedPatient: null, // Details of the selected patient
            pendingAppointments: [], // List of pending appointments for the selected patient
            selectedAppointmentId: '', // ID of the selected appointment
            pendingIntervenciones: [], // List of pending interventions for the selected patient
            selectedIntervencionId: '', // ID of the selected intervention
            metodosPago: [], // List of payment methods
            comprobante: {
                tipo: 'b', // Type of comprobante (boleta or factura)
                id_metodo_pago: '' // Selected payment method ID
            },
            productoComprobantes: [], // List of product purchase requests
            selectedProductoComprobanteId: '', // ID of the selected product purchase request
            selectedProductoComprobante: null, // Details of the selected product purchase request
            productoComprobanteItems: [] // List of items in the selected product purchase request
        }
    },
    watch: {
        // Watch for changes in the selected service type
        comprobanteType(newType, oldType) {
            console.log(`Comprobante type changed from ${oldType} to ${newType}`);
            // Reset selections when type changes
            this.selectedPatientId = '';
            this.selectedPatient = null;
            this.pendingAppointments = [];
            this.selectedAppointmentId = '';
            this.pendingIntervenciones = [];
            this.selectedIntervencionId = '';
            this.selectedProductoComprobanteId = '';
            this.selectedProductoComprobante = null;
            this.productoComprobanteItems = [];

            // Fetch the appropriate patient list
            if (newType === 'citas') {
                this.fetchPacientesWithCitas();
            } else if (newType === 'intervenciones') {
                this.fetchPacientesWithIntervenciones();
            } else if (newType === 'productos') {
                this.fetchProductoComprobantes(); // Fetch product requests if needed
            }
        }
    },
    computed: {
        // Calculate the total price of the selected product purchase request items
        totalPrecio() {
            return this.productoComprobanteItems.reduce((total, item) => total + item.precio_total, 0);
        }
    },
    mounted() {
        // Fetch initial data common to all types
        this.fetchMetodosPago();
        // Initial fetch based on default or initial comprobanteType if set
        if (this.comprobanteType === 'citas') {
            this.fetchPacientesWithCitas();
        } else if (this.comprobanteType === 'intervenciones') {
            this.fetchPacientesWithIntervenciones();
        } else if (this.comprobanteType === 'productos') {
             this.fetchProductoComprobantes();
        }
    },
    methods: {
        // Navigate back to the previous page
        goBack() {
            window.history.back();
        },

        // Fetch patients with pending appointments (estado='d')
        async fetchPacientesWithCitas() {
            console.log("Fetching patients with citas...");
            try {
                const response = await axios.get('/api/pacientes-with-citas');
                this.pacientesCitas = response.data;
                console.log("Fetched patients with citas:", this.pacientesCitas);
            } catch (error) {
                console.error('Error fetching pacientes with citas:', error);
                this.pacientesCitas = [];
            }
        },

        // Fetch patients with pending interventions (estado='d')
        async fetchPacientesWithIntervenciones() {
            console.log("Fetching patients with intervenciones...");
            try {
                const response = await axios.get('/api/pacientes-with-intervenciones');
                this.pacientesIntervenciones = response.data;
                console.log("Fetched patients with intervenciones:", this.pacientesIntervenciones);
            } catch (error) {
                console.error('Error fetching pacientes with intervenciones:', error);
                this.pacientesIntervenciones = [];
            }
        },

        // Fetch appointments for the selected patient (using num_historia)
        async fetchPatientAppointments() {
            // Find patient details from the citas list
            const selectedPatientDetails = this.pacientesCitas.find(p => p.id === this.selectedPatientId);
            if (!selectedPatientDetails) {
                console.error('Selected patient details not found in pacientesCitas list.');
                this.selectedPatient = null;
                this.pendingAppointments = [];
                return;
            }
            this.selectedPatient = selectedPatientDetails; // Store full patient details
            console.log(`Fetching appointments for patient ID: ${this.selectedPatientId}, Num Historia: ${this.selectedPatient.num_historia}`);

            try {
                // Use the search endpoint which expects num_historia
                const response = await axios.get(`/api/pacientes/search/${this.selectedPatient.num_historia}`);
                // Ensure the response structure matches expectations
                if (response.data && response.data.pendingAppointments) {
                    this.pendingAppointments = response.data.pendingAppointments;
                    console.log("Fetched pending appointments:", this.pendingAppointments);
                } else {
                    console.warn('Unexpected response structure for pending appointments:', response.data);
                    this.pendingAppointments = [];
                }
                this.selectedAppointmentId = ''; // Reset selection
            } catch (error) {
                console.error('Error fetching patient appointments:', error);
                this.pendingAppointments = [];
                this.selectedAppointmentId = '';
            }
        },

        // Fetch interventions for the selected patient (using patient ID)
        async fetchPatientIntervenciones() {
            // Find patient details from the intervenciones list
            const selectedPatientDetails = this.pacientesIntervenciones.find(p => p.id === this.selectedPatientId);
             if (!selectedPatientDetails) {
                console.error('Selected patient details not found in pacientesIntervenciones list.');
                this.selectedPatient = null;
                this.pendingIntervenciones = [];
                return;
            }
            this.selectedPatient = selectedPatientDetails; // Store full patient details
            console.log(`Fetching interventions for patient ID: ${this.selectedPatientId}`);

            try {
                const response = await axios.get(`/api/pacientes/${this.selectedPatientId}/intervenciones-pendientes`);
                console.log("API Response data for interventions:", JSON.stringify(response.data)); // Log raw response data
                this.pendingIntervenciones = response.data; // Assign data
                console.log("Updated pendingIntervenciones state:", JSON.stringify(this.pendingIntervenciones)); // Log state after update
                this.selectedIntervencionId = ''; // Reset selection
            } catch (error) {
                console.error('Error fetching patient interventions:', error);
                this.pendingIntervenciones = []; // Reset on error
                this.selectedIntervencionId = '';
            }
        },

        // Fetch the list of payment methods from the API
        async fetchMetodosPago() {
            try {
                const response = await axios.get('/api/active-metodos-pago');
                this.metodosPago = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                console.error('Error fetching metodos de pago:', error);
            }
        },
        // Fetch the list of product purchase requests from the API
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
        // Fetch the items of the selected product purchase request from the API
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
        // Format a date to a readable string (Date only)
        formatDateOnly(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('es-PE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                timeZone: 'UTC' // Assuming date from DB is UTC or doesn't have timezone issues
            });
        },

        // Format time string (HH:MM:SS) to HH:MM
        formatTime(time) {
            if (!time || typeof time !== 'string') return '';
            const parts = time.split(':');
            if (parts.length >= 2) {
                return `${parts[0]}:${parts[1]}`;
            }
            return time; // Return original if format is unexpected
        },

        // Format intervention date and time range
        formatIntervencionDateTime(intervencion) {
            const datePart = this.formatDateOnly(intervencion.fecha);
            const startTime = this.formatTime(intervencion.hora_inicio);
            const endTime = this.formatTime(intervencion.hora_fin);
            let dateTimeString = datePart;
            if (startTime) {
                dateTimeString += ` ${startTime}`;
                if (endTime) {
                    dateTimeString += ` - ${endTime}`;
                }
            }
            return dateTimeString;
        },

        // Format a date to a readable string (including time)
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

        // Format a number to a currency string
        formatCurrency(amount) {
            return new Intl.NumberFormat('es-PE', {
                style: 'currency',
                currency: 'PEN'
            }).format(amount);
        },
        // Select a specific appointment
        selectAppointment(id) {
            this.selectedAppointmentId = id;
        },
        // Select a specific intervention
        selectIntervencion(id) {
            this.selectedIntervencionId = id;
        },
        // Select a product purchase request and fetch its items
        selectProductoComprobante(id) {
            this.selectedProductoComprobanteId = id;
            this.fetchProductoComprobanteItems();
        },
        // Generate a comprobante based on the selected service type and details
        async generateComprobante() {
            if ((this.comprobanteType === 'citas' || this.comprobanteType === 'intervenciones') && !this.selectedPatient) {
                alert('Seleccione un paciente antes de generar el comprobante.');
                return;
            }
            
            if (!this.comprobante.id_metodo_pago) {
                alert('Por favor seleccione un método de pago.');
                return;
            }

            try {
                let payload = {
                    tipo: this.comprobante.tipo,
                    id_metodo_pago: this.comprobante.id_metodo_pago,
                };

                if (this.comprobanteType === 'citas') {
                    if (!this.selectedAppointmentId) {
                         alert('Seleccione una cita.');
                         return;
                    }
                    payload.citas = [this.selectedAppointmentId];
                    payload.paciente_id = this.selectedPatient.id;
                } else if (this.comprobanteType === 'productos') {
                     if (!this.selectedProductoComprobanteId) {
                         alert('Seleccione una solicitud de compra.');
                         return;
                    }
                    payload.productos_comprobante_id = this.selectedProductoComprobanteId;
                } else if (this.comprobanteType === 'intervenciones') {
                     if (!this.selectedIntervencionId) {
                         alert('Seleccione una intervención.');
                         return;
                    }
                    // Assuming the backend expects an 'intervenciones' array or similar
                    payload.intervenciones = [this.selectedIntervencionId];
                    payload.paciente_id = this.selectedPatient.id;
                }

                const response = await axios.post('/api/comprobantes', payload);

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
