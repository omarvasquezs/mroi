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
            <!-- Normal cita details view -->
            <div v-if="localCita && !isRescheduling">
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
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button class="btn btn-warning" @click="startRescheduling">
                  <i class="fas fa-calendar-alt me-2"></i> Reprogramar Cita
                </button>
              </div>
            </div>
            
            <!-- Rescheduling view -->
            <div v-else-if="isRescheduling">
              <div class="row mb-3">
                <div class="col-md-3"><strong>Paciente:</strong></div>
                <div class="col-md-9">
                  {{ localCita?.paciente.nombres }} {{ localCita?.paciente.ap_paterno }} {{ localCita?.paciente.ap_materno }}
                </div>
              </div>
              <div class="mb-4">
                <label class="form-label"><strong>Médico:</strong></label>
                <select v-model="rescheduleData.id_medico" @change="checkMedicoAvailability" class="form-select">
                  <option v-for="medico in medicos" :key="medico.id" :value="medico.id">
                    {{ medico.nombre.toUpperCase() }}
                  </option>
                </select>
              </div>
              <div class="mb-4">
                <label class="form-label"><strong>Fecha:</strong></label>
                <input type="date" v-model="rescheduleData.fecha" class="form-control" @change="checkMedicoAvailability">
              </div>
              <div class="mb-4">
                <label class="form-label"><strong>Hora:</strong></label>
                <div class="time-slots-container">
                  <button 
                    v-for="slot in availableTimeSlots" 
                    :key="slot.time" 
                    class="btn time-slot" 
                    :class="{ 
                      'btn-primary': rescheduleData.hora === slot.time,
                      'btn-outline-primary': rescheduleData.hora !== slot.time && slot.available, 
                      'btn-outline-secondary disabled': !slot.available 
                    }"
                    :disabled="!slot.available"
                    @click="rescheduleData.hora = slot.time"
                  >
                    {{ slot.time }}
                  </button>
                </div>
                <div v-if="availableTimeSlots.length === 0" class="alert alert-info mt-2">
                  Seleccione un médico y una fecha para ver las horas disponibles.
                </div>
              </div>
              <div class="mb-4">
                <label class="form-label"><strong>Observaciones:</strong></label>
                <textarea v-model="rescheduleData.observaciones" class="form-control" rows="3"></textarea>
              </div>
              <div v-if="rescheduleError" class="alert alert-danger">
                {{ rescheduleError }}
              </div>
              <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button class="btn btn-secondary me-md-2" @click="cancelRescheduling">Cancelar</button>
                <button class="btn btn-primary" @click="saveReschedule" :disabled="!isRescheduleFormValid">Guardar Cambios</button>
              </div>
            </div>

            <!-- Create new cita view -->
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
                <div class="input-group">
                  <select v-model="formData.num_historia" class="form-select" :class="{'is-invalid': formValidationErrors.includes('paciente')}">
                    <option value="" disabled>Seleccione un paciente</option>
                    <option v-for="paciente in pacientes" :key="paciente.num_historia" :value="paciente.num_historia">
                      {{ paciente.nombre.toUpperCase() }}
                    </option>
                  </select>
                    <button v-if="formData.num_historia" @click="editPaciente(pacientes.find(p => p.num_historia === formData.num_historia))" class="btn btn-success ms-2"><i class="fas fa-pencil-alt"></i></button>
                  <button @click="showNewPacienteForm" class="btn btn-primary ms-2" title="Agregar nuevo paciente">
                    <i class="fas fa-user-plus"></i>
                  </button>
                </div>
                <div class="invalid-feedback" v-if="formValidationErrors.includes('paciente')">
                  Por favor seleccione un paciente.
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Tipo de Cita:</strong></label>
                <select v-model="formData.id_tipo_cita" class="form-select" :class="{'is-invalid': formValidationErrors.includes('tipo_cita')}">
                  <option value="" disabled>Seleccione tipo de cita</option>
                  <option v-for="tipo in tiposCitas" :key="tipo.id" :value="tipo.id">
                    {{ tipo.tipo_cita }} - S/. {{ tipo.precio }}
                  </option>
                </select>
                <div class="invalid-feedback" v-if="formValidationErrors.includes('tipo_cita')">
                  Por favor seleccione un tipo de cita.
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label"><strong>Observaciones:</strong></label>
                <textarea v-model="formData.observaciones" class="form-control" rows="3"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" v-if="!showCitaInfo && !isRescheduling" @click="saveCita">GENERAR PRE-FACTURA</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CERRAR</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal for Creating/Editing Paciente -->
  <div class="modal fade" id="pacienteModal" tabindex="-1" aria-labelledby="pacienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="pacienteModalLabel">
            {{ isEditing ? `Editar Paciente con DNI ${form.doc_identidad}` : 'Crear Paciente' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form @submit.prevent="submitForm" class="mb-3" autocomplete="off">
            <div v-if="formErrors.includes('DNI ya existe en la base de datos.')"
              class="alert alert-danger alert-dismissible fade show" role="alert">
              DNI ya existe en la base de datos.
              <button type="button" class="btn-close" @click="closeFormErrorAlert" aria-label="Close"></button>
            </div>
            <fieldset class="mb-3">
              <legend>Identificación Personal</legend>
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label for="PacientesNombres" class="form-label">Nombres*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="form.nombres" id="PacientesNombres" name="PacientesNombres"
                      class="form-control" placeholder="Ej: Juan" autocomplete="new-nombres" required>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesApPaterno" class="form-label">Apellido Paterno*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="form.ap_paterno" id="PacientesApPaterno" name="PacientesApPaterno"
                      class="form-control" placeholder="Ej: Pérez" autocomplete="new-ap_paterno" required>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesApMaterno" class="form-label">Apellido Materno*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="form.ap_materno" id="PacientesApMaterno" name="PacientesApMaterno"
                      class="form-control" placeholder="Ej: Gómez" autocomplete="new-ap_materno" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="PacientesDocIdentidad" class="form-label">DNI*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" v-model="form.doc_identidad" @input="onlyNumbers($event)"
                      id="PacientesDocIdentidad" name="PacientesDocIdentidad" class="form-control"
                      placeholder="Ej: 00123456" autocomplete="new-doc_identidad" required minlength="8"
                      maxlength="8">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesFNacimiento" class="form-label">Fecha de Nacimiento:</label>
                  <input type="date" v-model="form.f_nacimiento" id="PacientesFNacimiento" name="PacientesFNacimiento"
                    class="form-control" autocomplete="new-f_nacimiento">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesEstadoCivil" class="form-label">Estado Civil:</label>
                  <select v-model="form.estado_civil" id="PacientesEstadoCivil" name="PacientesEstadoCivil"
                    class="form-select" autocomplete="new-estado_civil">
                    <option value="" disabled>Seleccione Estado Civil</option>
                    <option value="S">Soltero</option>
                    <option value="C">Casado</option>
                    <option value="V">Viudo</option>
                    <option value="D">Divorciado</option>
                  </select>
                </div>
              </div>
            </fieldset>

            <fieldset class="mb-3">
              <legend>Información de Contacto</legend>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="PacientesTelefonoPersonal" class="form-label">Teléfono Personal*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" v-model="form.telefono_personal" id="PacientesTelefonoPersonal"
                      name="PacientesTelefonoPersonal" class="form-control" placeholder="Ej: 987654321"
                      autocomplete="new-telefono_personal" required maxlength="9">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesCorreoPersonal" class="form-label">Correo Personal:</label>
                  <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="email" v-model="form.correo_personal" id="PacientesCorreoPersonal"
                      name="PacientesCorreoPersonal" class="form-control" placeholder="Ej: ejemplo@correo.com"
                      autocomplete="new-correo_personal">
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesDireccionPersonal" class="form-label">Dirección Personal:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                    <input type="text" v-model="form.direccion_personal" id="PacientesDireccionPersonal"
                      name="PacientesDireccionPersonal" class="form-control" placeholder="Ej: Av. Siempre Viva 123"
                      autocomplete="new-direccion_personal">
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="mb-3">
              <legend>Información Profesional (Opcional)</legend>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="PacientesOcupacion" class="form-label">Ocupación:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                    <input type="text" v-model="form.ocupacion" id="PacientesOcupacion" name="PacientesOcupacion"
                      class="form-control" placeholder="Ej: Ingeniero" autocomplete="new-ocupacion">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesNomCentroLaboral" class="form-label">Nombre del Centro Laboral:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                    <input type="text" v-model="form.nom_centro_laboral" id="PacientesNomCentroLaboral"
                      name="PacientesNomCentroLaboral" class="form-control" placeholder="Ej: Empresa XYZ"
                      autocomplete="new-nom_centro_laboral">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesTelefonoTrabajo" class="form-label">Teléfono del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" v-model="form.telefono_trabajo" id="PacientesTelefonoTrabajo"
                      name="PacientesTelefonoTrabajo" class="form-control" placeholder="Ej: 123456789"
                      autocomplete="new-telefono_trabajo" maxlength="9">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesCorreoTrabajo" class="form-label">Correo del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="email" v-model="form.correo_trabajo" id="PacientesCorreoTrabajo"
                      name="PacientesCorreoTrabajo" class="form-control" placeholder="Ej: trabajo@empresa.com"
                      autocomplete="new-correo_trabajo">
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesDireccionTrabajo" class="form-label">Dirección del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                    <input type="text" v-model="form.direccion_trabajo" id="PacientesDireccionTrabajo"
                      name="PacientesDireccionTrabajo" class="form-control" placeholder="Ej: Calle Falsa 456"
                      autocomplete="new-direccion_trabajo">
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="mb-3">
              <legend>Administración Médica</legend>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Estado de Historia*:</label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="form.estado_historia" id="estadoActivo"
                      value="1" required>
                    <label class="form-check-label" for="estadoActivo">ACTIVO</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="form.estado_historia" id="estadoInactivo"
                      value="0" required>
                    <label class="form-check-label" for="estadoInactivo">INACTIVO</label>
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesObservaciones" class="form-label">Observaciones:</label>
                  <textarea v-model="form.observaciones" id="PacientesObservaciones" name="PacientesObservaciones"
                    class="form-control" placeholder="Escriba sus observaciones aquí" autocomplete="new-observaciones"
                    rows="5"></textarea>
                </div>
              </div>
            </fieldset>

            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary">{{ isEditing ? 'Actualizar' : 'Guardar' }}</button>
              <button type="button" @click="closeModal" class="btn btn-secondary">Cerrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import PacientesCrud from './PacientesCrud.vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

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
  components: {
    PacientesCrud
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
      loading: false,
      form: {
        num_historia: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        doc_identidad: '',
        estado_historia: '1', // Default value to 1 (ACTIVO)
        fecha_filiacion: '', // Add this field
        f_nacimiento: '',
        estado_civil: '',
        telefono_personal: '',
        correo_personal: '',
        direccion_personal: '',
        ocupacion: '',
        nom_centro_laboral: '',
        telefono_trabajo: '',
        correo_trabajo: '',
        direccion_trabajo: '',
        observaciones: ''
      },
      editingId: null,
      formErrors: [],
      isEditing: false,
      pacienteModal: null,
      citaModal: null,
      formValidationErrors: [], // Add this field to store validation errors
      // Add new data properties for rescheduling
      isRescheduling: false,
      rescheduleData: {
        id_medico: '',
        fecha: '',
        hora: '',
        observaciones: ''
      },
      availableTimeSlots: [],
      medicos: [],
      rescheduleError: '',
      // Add properties for local display of date and time
      displayDate: null,
      displayTime: '',
    }
  },
  computed: {
    formattedDate() {
      // Use displayDate if available, otherwise fallback to selectedDate
      const dateToFormat = this.displayDate || this.selectedDate;
      return dateToFormat.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      }).replace(/ de /g, '/').toUpperCase();
    },
    formattedTime() {
      // Use displayTime if available, otherwise fallback to selectedTime
      const timeToFormat = this.displayTime || this.selectedTime;
      const [hour, minute] = timeToFormat.split(':');
      const date = new Date();
      date.setHours(hour, minute);
      return date.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      }).toUpperCase();
    },
    // Add new computed property for reschedule form validation
    isRescheduleFormValid() {
      return this.rescheduleData.id_medico && 
             this.rescheduleData.fecha && 
             this.rescheduleData.hora;
    }
  },
  created() {
    this.fetchPacientes();
    this.fetchTiposCitas();
    this.fetchMedicos();
    this.localCita = this.cita; // Initialize localCita with the prop's value
    this.displayDate = this.selectedDate;
    this.displayTime = this.selectedTime;
  },
  mounted() {
    this.citaModal = new Modal(document.getElementById('citaModal'));
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
        this.pacientes = Array.isArray(data) ? data.map(p => ({
          ...p,
          nombre: this.formatPatientName(p)
        })) : [];
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
      // Reset validation errors
      this.formValidationErrors = [];
      
      // Validate form fields
      if (!this.formData.id_tipo_cita) {
        this.formValidationErrors.push('tipo_cita');
      }
      
      if (!this.formData.num_historia) {
        this.formValidationErrors.push('paciente');
      }
      
      // Stop form submission if there are validation errors
      if (this.formValidationErrors.length > 0) {
        return;
      }

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
          this.formData = { num_historia: '', observaciones: '', id_tipo_cita: '' };
          this.formValidationErrors = []; // Clear validation errors on successful submission
          const modalx = document.getElementById('citaModal');
          const bootstrapModalx = bootstrap.Modal.getInstance(modalx);
          bootstrapModalx.hide();
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
      
      // Update display date and time from the cita data
      if (cita && cita.fecha) {
        const citaDate = new Date(cita.fecha);
        this.displayDate = citaDate;
        
        // Extract time from the fecha field (format: "YYYY-MM-DD HH:mm:ss")
        if (cita.hora) {
          this.displayTime = cita.hora.substring(0, 5); // Get HH:mm
        } else {
          this.displayTime = citaDate.toTimeString().substring(0, 5); // Fallback to time from fecha
        }
      }
      
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
    },
    editPaciente(paciente) {
      try {
        console.group('Patient Data Debug');
        console.log('Original patient data:', paciente);
        
        // Store the ID of the patient being edited
        this.editingId = paciente.id;
        
        // Copy all patient data to the form using spread operator
        this.form = { 
          ...paciente,
          // Ensure all required fields are present
          estado_historia: paciente.estado_historia || '1',
          f_nacimiento: paciente.f_nacimiento || '',
          estado_civil: paciente.estado_civil || '',
          telefono_personal: paciente.telefono_personal || '',
          correo_personal: paciente.correo_personal || '',
          direccion_personal: paciente.direccion_personal || '',
          ocupacion: paciente.ocupacion || '',
          nom_centro_laboral: paciente.nom_centro_laboral || '',
          telefono_trabajo: paciente.telefono_trabajo || '',
          correo_trabajo: paciente.correo_trabajo || '',
          direccion_trabajo: paciente.direccion_trabajo || '',
          observaciones: paciente.observaciones || ''
        };
        
        console.log('Processed form data:', this.form);
        console.groupEnd();
        
        // Close cita modal before showing paciente modal
        const citaModalElement = document.getElementById('citaModal');
        const citaModal = bootstrap.Modal.getInstance(citaModalElement);
        citaModal.hide();

        // Show the paciente modal
        setTimeout(() => {
          const modal = new bootstrap.Modal(document.getElementById('pacienteModal'));
          modal.show();
        }, 500);
      } catch (error) {
        console.error('Error editing patient:', error);
      }
    },
    async submitForm() {
      this.formErrors = [];
      const formData = { ...this.form };

      const dniExists = await this.checkDocIdentidadExists(formData.doc_identidad);
      // Check if the new DNI exists and is different from current patient's DNI
      if (dniExists && (formData.doc_identidad !== this.pacientes.find(p => p.id === this.editingId).doc_identidad)) {
        this.formErrors.push('DNI ya existe en la base de datos.');
        this.scrollToTop();
        setTimeout(() => {
          this.formErrors = this.formErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
        }, 3000);
        return;
      }

      await this.updatePaciente();
    },
    async updatePaciente() {
      try {
        const response = await axios.put(`/api/pacientes/${this.form.id}`, this.form);
        const updatedPaciente = response.data;
        
        // Refresh the patients list after update
        await this.fetchPacientes();
        
        // Close modal and show success message
        this.closeModal();
        this.showAlert('Paciente actualizado exitosamente.');
        
        // Reopen the cita modal
        setTimeout(() => {
          const citaModal = new bootstrap.Modal(document.getElementById('citaModal'));
          citaModal.show();
        }, 500);
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
          this.scrollToTop();
        } else {
          console.error(error);
        }
      }
    },
    
    showAlert(message) {
      // Add alert functionality
      alert(message);
    },
    
    closeModal() {
      const modal = bootstrap.Modal.getInstance(document.getElementById('pacienteModal'));
      modal.hide();
    },
    checkDocIdentidadExists(doc_identidad) {
      return axios.get(`/api/pacientes/check-doc-identidad`, { params: { doc_identidad } })
        .then(response => response.data.exists)
        .catch(error => {
          console.error(error);
          return false;
        });
    },
    scrollToTop() {
      const modal = document.getElementById('pacienteModal');
      if (modal) {
        modal.scrollTop = 0;
      } else {
        window.scrollTo(0, 0);
      }
    },
    onlyNumbers(event) {
      event.target.value = event.target.value.replace(/[^\d]/g, '');
    },
    showNewPacienteForm() {
      // Hide the cita modal before showing paciente modal
      const citaModalElement = document.getElementById('citaModal');
      const citaModal = Modal.getInstance(citaModalElement);
      citaModal.hide();

      // Reset the form in PacientesCrud component
      this.form = {
        num_historia: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        doc_identidad: '',
        estado_historia: '1',
        fecha_filiacion: new Date().toISOString().split('T')[0],
        f_nacimiento: '',
        estado_civil: '',
        telefono_personal: '',
        correo_personal: '',
        direccion_personal: '',
        ocupacion: '',
        nom_centro_laboral: '',
        telefono_trabajo: '',
        correo_trabajo: '',
        direccion_trabajo: '',
        observaciones: ''
      };
      
      // Show the paciente modal
      setTimeout(() => {
        this.isEditing = false;
        const modal = new Modal(document.getElementById('pacienteModal'));
        modal.show();
      }, 500);
    },
    
    async handlePacienteCreated(newPaciente) {
      // Close the paciente modal
      const pacienteModalElement = document.getElementById('pacienteModal');
      const pacienteModal = Modal.getInstance(pacienteModalElement);
      if (pacienteModal) {
        pacienteModal.hide();
      }
      
      // Refresh the patient list
      await this.fetchPacientes();
      
      // Reopen the cita modal and select the newly created patient
      setTimeout(() => {
        this.formData.num_historia = newPaciente.num_historia;
        const citaModalElement = document.getElementById('citaModal');
        const citaModal = new Modal(citaModalElement);
        citaModal.show();
      }, 500);
    },
    
    closeFormErrorAlert() {
      this.formErrors = this.formErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
    },
    
    onlyNumbers(event) {
      event.target.value = event.target.value.replace(/[^\d]/g, '');
    },
    
    async submitForm() {
      this.formErrors = [];
      const formData = { ...this.form };

      const dniExists = await this.checkDocIdentidadExists(formData.doc_identidad);
      // Check if the new DNI exists and is different from current patient's DNI
      if (dniExists && (formData.doc_identidad !== this.pacientes.find(p => p.id === this.editingId)?.doc_identidad)) {
        this.formErrors.push('DNI ya existe en la base de datos.');
        this.scrollToTop();
        setTimeout(() => {
          this.formErrors = this.formErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
        }, 3000);
        return;
      }

      try {
        let response;
        if (this.editingId) {
          response = await axios.put(`/api/pacientes/${this.form.id}`, this.form);
        } else {
          response = await axios.post('/api/pacientes', this.form);
        }
        const newPaciente = response.data;
        
        // Close modal and show success message
        this.closeModal();
        
        // Emit event or call method to update the list and select the new patient
        if (!this.editingId) {
          // If creating a new patient
          this.handlePacienteCreated(newPaciente);
        } else {
          // If editing an existing patient
          // Refresh the patients list after update
          await this.fetchPacientes();
          
          // Reopen the cita modal
          setTimeout(() => {
            const citaModal = new Modal(document.getElementById('citaModal'));
            citaModal.show();
          }, 500);
        }
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
          this.scrollToTop();
        } else {
          console.error(error);
        }
      }
    },
    
    async checkDocIdentidadExists(doc_identidad) {
      return axios.get(`/api/pacientes/check-doc-identidad`, { params: { doc_identidad } })
        .then(response => response.data.exists)
        .catch(error => {
          console.error(error);
          return false;
        });
    },
    
    scrollToTop() {
      const modal = document.getElementById('pacienteModal');
      if (modal) {
        modal.scrollTop = 0;
      } else {
        window.scrollTo(0, 0);
      }
    },
    // Add new methods for rescheduling
    startRescheduling() {
      if (!this.localCita) return;
      
      this.isRescheduling = true;
      this.modalTitle = 'Reprogramar Cita';
      
      // Set initial values for rescheduleData
      this.rescheduleData = {
        id_medico: this.selectedMedico,
        fecha: this.formatLocalDate(this.selectedDate),
        hora: this.selectedTime,
        observaciones: this.localCita.observaciones || ''
      };
      
      // Check availability with initial values
      this.checkMedicoAvailability();
    },
    
    cancelRescheduling() {
      this.isRescheduling = false;
      this.modalTitle = 'Información de la Cita';
      this.availableTimeSlots = [];
      this.rescheduleError = '';
    },
    
    async checkMedicoAvailability() {
      if (!this.rescheduleData.id_medico || !this.rescheduleData.fecha) {
        this.availableTimeSlots = [];
        return;
      }
      
      try {
        const params = new URLSearchParams({
          medico: this.rescheduleData.id_medico,
          fecha: this.rescheduleData.fecha
        });
        
        const response = await fetch(`/api/citas/availability?${params.toString()}`);
        if (!response.ok) throw new Error('Error checking availability');
        
        const data = await response.json();
        this.availableTimeSlots = data.availableSlots;
        
        // Mark the current appointment's time slot as available
        if (this.localCita && 
            this.rescheduleData.id_medico == this.selectedMedico &&
            this.rescheduleData.fecha == this.formatLocalDate(this.selectedDate)) {
          
          const currentTimeSlot = this.availableTimeSlots.find(
            slot => slot.time === this.selectedTime
          );
          
          if (currentTimeSlot) {
            currentTimeSlot.available = true;
          }
        }
      } catch (error) {
        console.error('Error checking availability:', error);
        this.availableTimeSlots = [];
      }
    },
    
    async saveReschedule() {
      if (!this.isRescheduleFormValid || !this.localCita) {
        return;
      }
      
      try {
        this.loading = true;
        this.rescheduleError = '';
        
        const response = await fetch(`/api/citas/${this.localCita.id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(this.rescheduleData)
        });
        
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.message || 'Error updating appointment');
        }
        
        const updatedCita = await response.json();
        
        // Update local cita data
        this.localCita = updatedCita;
        
        // Update display date and time with the new values
        if (this.rescheduleData.fecha) {
          this.displayDate = new Date(this.rescheduleData.fecha);
        }
        if (this.rescheduleData.hora) {
          this.displayTime = this.rescheduleData.hora;
        }
        
        // Reset UI
        this.isRescheduling = false;
        this.modalTitle = 'Información de la Cita';
        
        // Notify parent component
        this.$emit('citaCreated');
        
        // Show success message
        alert('Cita reprogramada exitosamente');
      } catch (error) {
        console.error('Error saving reschedule:', error);
        this.rescheduleError = error.message || 'Error al reprogramar la cita';
      } finally {
        this.loading = false;
      }
    },
    
    async fetchMedicos() {
      try {
        const response = await fetch('/api/medicos-list');
        const data = await response.json();
        if (Array.isArray(data)) {
          this.medicos = data;
        } else if (data.data && Array.isArray(data.data)) {
          this.medicos = data.data;
        } else {
          throw new Error('Invalid data format');
        }
      } catch (error) {
        console.error('Error fetching medicos:', error);
        this.medicos = [];
      }
    },
    
    formatLocalDate(date) {
      const d = new Date(date);
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
  },
  watch: {
    // Add watcher for cita prop
    cita(newVal) {
      this.localCita = newVal;
      this.isRescheduling = false;
      
      // Update display date and time when cita changes
      if (newVal && newVal.fecha) {
        const citaDate = new Date(newVal.fecha);
        this.displayDate = citaDate;
        
        if (newVal.hora) {
          this.displayTime = newVal.hora.substring(0, 5);
        } else {
          this.displayTime = citaDate.toTimeString().substring(0, 5);
        }
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

/* Add styles for time slot selection */
.time-slots-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.time-slot {
  width: 70px;
}
</style>