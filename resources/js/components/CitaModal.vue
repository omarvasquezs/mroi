<template>
  <div class="modal fade" id="citaModal" tabindex="-1" aria-labelledby="citaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Add modal-lg class for larger modal -->
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="citaModalLabel">{{ modalTitle }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading">
            <p>Cargando...</p>
          </div>
          <div v-else>
            <div v-if="localCita">
              <div class="row mb-3">
                <div class="col-md-3"><strong>Historia:</strong></div>
                <div class="col-md-9">{{ localCita.num_historia }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Paciente:</strong></div>
                <div class="col-md-9">{{ localCita.paciente.nombres }} {{ localCita.paciente.ap_paterno }} {{
                  localCita.paciente.ap_materno }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Médico:</strong></div>
                <div class="col-md-9">{{ selectedMedicoName }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Fecha:</strong></div>
                <div class="col-md-9">{{ formattedDate }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Hora:</strong></div>
                <div class="col-md-9">{{ formattedTime }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Observaciones:</strong></div>
                <div class="col-md-9">{{ localCita.observaciones }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Tipo de Cita:</strong></div>
                <div class="col-md-9">
                  {{ localCita?.tipo_cita ? `${localCita.tipo_cita.tipo_cita} - S/. ${localCita.tipo_cita.precio}` : 'No especificado' }}
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Estado de Pago:</strong></div>
                <div class="col-md-9">
                  <span :class="['badge', localCita.estado === 'p' ? 'bg-success' : 'bg-warning']">
                    {{ localCita.estado === 'p' ? 'PAGADO' : 'PENDIENTE' }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Fecha Seleccionada:</strong></div>
                <div class="col-md-9">{{ formattedDate }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Hora Seleccionada:</strong></div>
                <div class="col-md-9">{{ formattedTime }}</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3"><strong>Médico Seleccionado:</strong></div>
                <div class="col-md-9">{{ selectedMedicoName.toUpperCase() }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Paciente:</strong></label>
                <select v-model="formData.num_historia" class="form-select">
                  <option value="" disabled>Seleccione un paciente</option>
                  <option v-for="paciente in pacientes" :key="paciente.num_historia" :value="paciente.num_historia">
                    {{ paciente.nombre.toUpperCase() }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Tipo de Cita:</strong></label>
                <select v-model="formData.id_tipo_cita" class="form-select">
                  <option value="" disabled>Seleccione tipo de cita</option>
                  <option v-for="tipo in tiposCitas" :key="tipo.id" :value="tipo.id">
                    {{ tipo.tipo_cita }} - S/. {{ tipo.precio }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Observaciones:</strong></label>
                <textarea v-model="formData.observaciones" class="form-control" rows="3"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" v-if="!showCitaInfo" @click="saveCita">GENERAR PRE-FACTURA</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CERRAR</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    selectedTime: {
      type: String,
      required: true
    },
    selectedDate: {
      type: Date,
      required: true
    },
    selectedMedico: {
      type: [String, Number],
      required: true
    },
    selectedMedicoName: {
      type: String,
      required: true
    },
    cita: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      localCita: null, // Store the cita data locally
      pacientes: [],
      tiposCitas: [],
      formData: {
        num_historia: '',
        observaciones: '',
        id_tipo_cita: ''
      },
      showCitaInfo: false,
      modalTitle: 'Crear Cita',
      loading: false
    }
  },
  computed: {
    formattedDate() {
      return this.selectedDate.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      }).replace(/ de /g, '/').toUpperCase();
    },
    formattedTime() {
      const [hour, minute] = this.selectedTime.split(':');
      const date = new Date();
      date.setHours(hour, minute);
      return date.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      }).toUpperCase();
    }
  },
  created() {
    this.fetchPacientes();
    this.fetchTiposCitas();
    this.localCita = this.cita; // Initialize localCita with the prop's value
  },
  methods: {
    formatPatientName(paciente) {
      if (!paciente) return '';
      const nombres = paciente.nombres || '';
      const paterno = paciente.ap_paterno || '';
      const materno = paciente.ap_materno || '';
      return `${nombres} ${paterno} ${materno}`.toUpperCase().trim();
    },
    async fetchPacientes() {
      try {
        const response = await fetch('/api/pacientes-list');
        const data = await response.json();
        this.pacientes = Array.isArray(data) ? data : [];
      } catch (error) {
        console.error('Error fetching pacientes:', error);
        this.pacientes = [];
      }
    },
    async fetchTiposCitas() {
      try {
        const response = await fetch('/api/tipos-citas-list');
        const data2 = await response.json();
        this.tiposCitas = Array.isArray(data2) ? data2 : [];
      } catch (error) {
        console.error('Error fetching tipos citas:', error);
      }
    },
    async saveCita() {
      try {
        const adjustedDate = new Date(this.selectedDate.getTime() - this.selectedDate.getTimezoneOffset() * 60000);
        const response = await fetch('/api/citas', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            num_historia: this.formData.num_historia,
            id_medico: this.selectedMedico,
            fecha: adjustedDate.toISOString().split('T')[0],
            hora: this.selectedTime,
            observaciones: this.formData.observaciones,
            id_tipo_cita: this.formData.id_tipo_cita
          })
        });

        if (response.ok) {
          this.$emit('citaCreated');
          this.closeModal();
        }
      } catch (error) {
        console.error('Error saving cita:', error);
      }
    },
    closeModal() {
      this.formData = { num_historia: '', observaciones: '', id_tipo_cita: '' };
      const modal = document.getElementById('citaModal');
      const bootstrapModal = bootstrap.Modal.getInstance(modal);
      bootstrapModal.hide();
    },
    async openModal() {
      this.loading = true;
      // Adjust date to UTC-5
      const adjustedDate = new Date(this.selectedDate.getTime() - (5 * 60 * 60 * 1000));
      const date = adjustedDate.toISOString().split('T')[0];
      const time = this.selectedTime.padStart(5, '0'); // Ensure HH:mm format

      // Encode parameters
      const params = new URLSearchParams({
        medico: this.selectedMedico,
        fecha: date,
        hora: `${time}:00`
      });

      const url = `/api/citas/check?${params.toString()}`;

      try {
        const response = await fetch(url);
        const contentType = response.headers.get('content-type');

        if (response.ok && contentType && contentType.includes('application/json')) {
          const cita = await response.json();
          console.log('Cita found:', cita);
          // Add estado to the debug information
          console.log('Estado de pago:', cita.estado);
          // Only show cita info if cita.id is valid
          if (cita && cita.id && cita.id !== 0) {
            this.showCitaInfoMethod(cita);
          } else {
            this.showForm();
          }
        } else {
          const responseText = await response.text();
          console.error('Expected JSON response but got:', contentType, responseText);
          this.showForm();
        }
      } catch (error) {
        console.error('Error checking cita:', error);
        this.showForm();
      } finally {
        this.loading = false;
      }
    },
    showCitaInfoMethod(cita) {
      console.log('Showing cita info:', cita); // Debug line
      this.localCita = cita; // Assign to the local data property
      this.showCitaInfo = true;
      this.modalTitle = 'Información de la Cita';
      const modalElement = document.getElementById('citaModal');
      const modal = new window.bootstrap.Modal(modalElement);
      modal.show();
    },
    showForm() {
      this.localCita = null; // Reset the localCita data
      this.formData = { num_historia: '', observaciones: '', id_tipo_cita: '' }; // Reset form
      this.showCitaInfo = false;
      this.modalTitle = 'Crear Cita';
      const modalElement = document.getElementById('citaModal');
      const modal = new window.bootstrap.Modal(modalElement);
      modal.show();
    },
    async checkCita() {
      try {
        const response = await fetch(url);
        const cita = await response.json();
        if (cita && cita.id) {
          // Ensure tipo_cita exists
          if (!cita.tipo_cita) {
            cita.tipo_cita = { tipo_cita: 'No especificado', precio: 0 };
          }
          this.localCita = cita;
        }
      } catch (error) {
        console.error('Error checking cita:', error);
      }
    }
  }
}
</script>

<style scoped>
.badge {
  font-size: 0.9em;
  padding: 0.5em 1em;
}
</style>