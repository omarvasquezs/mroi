<template>
    <div class="container">
        <!-- Search Patient Section -->
        <div class="card mb-4">
            <div class="card-header">Búsqueda de Paciente</div>
            <div class="card-body">
                <div class="form-group">
                    <label for="paciente-select">Seleccionar Paciente</label>
                    <select 
                        v-model="selectedPatientId" 
                        class="form-control" 
                        id="paciente-select"
                        @change="fetchPatientAppointments"
                    >
                        <option value="" disabled selected>Seleccione un paciente</option>
                        <option v-for="paciente in pacientes" :key="paciente.id" :value="paciente.id">
                            {{ paciente.num_historia }} - {{ paciente.nombre }}
                        </option>
                    </select>
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
                        <tr v-for="cita in pendingAppointments" :key="cita.id">
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

        <!-- Payment Section -->
        <div v-if="selectedAppointments.length" class="card mb-4">
            <div class="card-header">Generar Comprobante</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tipo de Comprobante</label>
                            <select v-model="comprobante.tipo" class="form-control">
                                <option value="b">Boleta</option>
                                <option value="f">Factura</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Método de Pago</label>
                            <select v-model="comprobante.id_metodo_pago" class="form-control">
                                <option value="" disabled selected>Seleccione método de pago</option>
                                <option v-for="metodo in metodosPago" 
                                        :key="metodo.id" 
                                        :value="metodo.id">
                                    {{ metodo.nombre }}
                                </option>
                            </select>
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
            searchTerm: '',
            pacientes: [], // Initialize as empty array
            selectedPatientId: '',
            selectedPatient: null,
            pendingAppointments: [],
            selectedAppointments: [],
            selectAll: false,
            metodosPago: [], // Add this line
            comprobante: {
                tipo: 'b',
                id_metodo_pago: ''  // Changed to empty string for proper initialization
            }
        }
    },
    mounted() {
        this.fetchPacientes();
        this.fetchMetodosPago(); // Add this line
    },
    methods: {
        async fetchPacientes() {
            try {
                const response = await axios.get('/api/pacientes-list');
                this.pacientes = response.data;
                console.log('Pacientes loaded:', this.pacientes); // Debug line
            } catch (error) {
                console.error('Error fetching pacientes:', error);
                this.pacientes = [];
            }
        },
        async fetchPatientAppointments() {
            try {
                // Use the patient's num_historia instead of id
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
                // Using a static array since we have the data
                this.metodosPago = [
                    { id: 1, nombre: 'Efectivo' },
                    { id: 2, nombre: 'Tarjeta' },
                    { id: 3, nombre: 'Yape' },
                    { id: 4, nombre: 'Plin' }
                ];
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
        toggleAll() {
            if (this.selectAll) {
                this.selectedAppointments = this.pendingAppointments.map(cita => cita.id);
            } else {
                this.selectedAppointments = [];
            }
        },
        async generateComprobante() {
            if (!this.selectedPatient) {
                alert('Seleccione un paciente antes de generar el comprobante.');
                return;
            }

            try {
                const response = await axios.post('/api/comprobantes', {
                    tipo: this.comprobante.tipo,
                    id_metodo_pago: this.comprobante.id_metodo_pago,  // Changed from metodo_pago
                    citas: this.selectedAppointments,
                    paciente_id: this.selectedPatient.id
                });
                
                if (response.data.error) {
                    alert('Error: ' + response.data.error);
                    return;
                }
                
                this.printComprobante(response.data.comprobante);
                this.selectedAppointments = [];
                await this.fetchPatientAppointments();
            } catch (error) {
                console.error('Error generating comprobante:', error);
                alert('Error al generar el comprobante: ' + (error.response?.data?.error || error.message));
            }
        },
        printComprobante(comprobante) {
            // Open new window with print template
            const printWindow = window.open('', '_blank');
            printWindow.document.write(this.getPrintTemplate(comprobante));
            printWindow.document.close();
            printWindow.print();
        },
        getPrintTemplate(comprobante) {
            const metodoPago = this.metodosPago.find(m => m.id === this.comprobante.id_metodo_pago)?.nombre || '';
            const tipoComprobante = this.comprobante.tipo === 'b' ? 'BOLETA' : 'FACTURA';
            const fecha = new Date().toLocaleString('es-PE');
            
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
                        <p>Paciente: ${this.selectedPatient.nombre}</p>
                        <p>Historia: ${this.selectedPatient.num_historia}</p>
                        <p>Método de pago: ${metodoPago}</p>
                    </div>

                    <div class="items">
                        <div style="border-bottom: 1px dashed #000; margin: 10px 0;">
                            <p>DETALLE DE SERVICIOS</p>
                        </div>
                        ${this.pendingAppointments
                            .filter(cita => this.selectedAppointments.includes(cita.id))
                            .map(cita => `
                                <div class="item">
                                    <p>${cita.tipo_cita}</p>
                                    <p>Médico: ${cita.medico}</p>
                                    <p>Fecha: ${this.formatDate(cita.fecha)}</p>
                                    <p>Monto: ${this.formatCurrency(cita.monto)}</p>
                                </div>
                            `).join('<hr style="border-top: 1px dashed #ccc">')}
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
        }
    }
}
</script>
