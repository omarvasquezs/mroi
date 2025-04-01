<template>
  <div class="modal fade" id="intervencionModal" tabindex="-1" aria-labelledby="intervencionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="intervencionModalLabel">
            {{ intervencion ? 'Editar Intervención' : 'Registrar Nueva Intervención' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div v-if="loading">
            <p>Cargando...</p>
          </div>
          <div v-else-if="intervencion && !isRescheduling">
            <!-- Display intervention details view -->
            <div class="row mb-3">
              <div class="col-md-3"><strong>Historia:</strong></div>
              <div class="col-md-9">{{ intervencion.num_historia }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-md-3"><strong>Paciente:</strong></div>
              <div class="col-md-9">
                {{ intervencion.paciente ? `${intervencion.paciente.nombres} ${intervencion.paciente.ap_paterno} ${intervencion.paciente.ap_materno}` : '' }}
              </div>
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
              <div class="col-md-9">{{ intervencion.observaciones || 'Sin observaciones' }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-md-3"><strong>Tipo de Intervención:</strong></div>
              <div class="col-md-9">
                {{ intervencion.tipoIntervencion ? `${intervencion.tipoIntervencion.tipo_intervencion} - S/. ${intervencion.tipoIntervencion.precio}` : 'No especificado' }}
              </div>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
              <button v-if="intervencion" type="button" class="btn btn-danger" @click="confirmDelete">
                <i class="fas fa-trash-alt me-2"></i> Eliminar Intervención
              </button>
            </div>
          </div>
          <div v-else>
            <!-- Form for creating or updating intervention -->
            <form @submit.prevent="handleSubmit">
              <div class="mb-4">
                <label class="form-label"><strong>Paciente:</strong></label>
                <div class="input-group">
                  <select v-model="form.num_historia" class="form-select" required>
                    <option value="" disabled>Seleccione un paciente</option>
                    <option v-for="paciente in pacientes" :key="paciente.num_historia" :value="paciente.num_historia">
                      {{ paciente.nombre }}
                    </option>
                  </select>
                  <button @click="editPaciente(pacientes.find(p => p.num_historia === form.num_historia))" 
                    class="btn btn-success ms-2" v-if="form.num_historia" type="button">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button @click="showNewPacienteForm" class="btn btn-primary ms-2" 
                    title="Agregar nuevo paciente" type="button">
                    <i class="fas fa-user-plus"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label"><strong>Tipo de Intervención:</strong></label>
                <div class="input-group">
                  <select class="form-select" v-model="form.id_tipo_intervencion" required>
                    <option value="" disabled>Seleccione un tipo</option>
                    <option v-for="tipo in tiposIntervenciones" :key="tipo.id" :value="tipo.id">
                      {{ tipo.tipo_intervencion }} - S/. {{ tipo.precio }}
                    </option>
                  </select>
                  <button @click="editTipoIntervencion(tiposIntervenciones.find(t => t.id === form.id_tipo_intervencion))" 
                    class="btn btn-success ms-2" v-if="form.id_tipo_intervencion" type="button">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button @click="showCreateTipoIntervencionForm" class="btn btn-primary ms-2" 
                    title="Agregar nuevo tipo de intervención" type="button">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label"><strong>Observaciones:</strong></label>
                <textarea class="form-control" rows="3" v-model="form.observaciones"></textarea>
              </div>

              <div class="alert alert-danger" v-if="errorMessage">
                {{ errorMessage }}
              </div>

              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                  {{ intervencion ? 'Actualizar' : 'Registrar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal for Creating/Editing Tipo de Intervención -->
  <div class="modal fade" id="tipoIntervencionModal" tabindex="-1" aria-labelledby="tipoIntervencionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="tipoIntervencionModalLabel">
            {{ isEditingTipoIntervencion ? 'Editar Tipo de Intervención' : 'Crear Tipo de Intervención' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitTipoIntervencionForm">
            <div class="mb-3">
              <label for="tipo_intervencion" class="form-label">Tipo de Intervención*:</label>
              <input type="text" v-model="tipoIntervencionForm.tipo_intervencion" id="tipo_intervencion" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="precio" class="form-label">Precio*:</label>
              <div class="input-group">
                <span class="input-group-text">S/.</span>
                <input type="number" v-model="tipoIntervencionForm.precio" id="precio" class="form-control" step="0.01" required>
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary">{{ isEditingTipoIntervencion ? 'Actualizar' : 'Guardar' }}</button>
              <button type="button" @click="closeTipoIntervencionModal" class="btn btn-secondary">Cerrar</button>
            </div>
          </form>
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
            {{ isEditingPaciente ? `Editar Paciente con DNI ${pacienteForm.doc_identidad}` : 'Crear Paciente' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form @submit.prevent="submitPacienteForm" class="mb-3" autocomplete="off">
            <div v-if="pacienteFormErrors.includes('DNI ya existe en la base de datos.')"
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
                    <input type="text" v-model="pacienteForm.nombres" id="PacientesNombres" name="PacientesNombres"
                      class="form-control" placeholder="Ej: Juan" autocomplete="new-nombres" required>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesApPaterno" class="form-label">Apellido Paterno*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="pacienteForm.ap_paterno" id="PacientesApPaterno" name="PacientesApPaterno"
                      class="form-control" placeholder="Ej: Pérez" autocomplete="new-ap_paterno" required>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesApMaterno" class="form-label">Apellido Materno*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="pacienteForm.ap_materno" id="PacientesApMaterno" name="PacientesApMaterno"
                      class="form-control" placeholder="Ej: Gómez" autocomplete="new-ap_materno" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="PacientesDocIdentidad" class="form-label">DNI*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" v-model="pacienteForm.doc_identidad" @input="onlyNumbers"
                      id="PacientesDocIdentidad" name="PacientesDocIdentidad" class="form-control"
                      placeholder="Ej: 00123456" autocomplete="new-doc_identidad" required minlength="8"
                      maxlength="8">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesFNacimiento" class="form-label">Fecha de Nacimiento:</label>
                  <input type="date" v-model="pacienteForm.f_nacimiento" id="PacientesFNacimiento" name="PacientesFNacimiento"
                    class="form-control" autocomplete="new-f_nacimiento">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesEstadoCivil" class="form-label">Estado Civil:</label>
                  <select v-model="pacienteForm.estado_civil" id="PacientesEstadoCivil" name="PacientesEstadoCivil"
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
                    <input type="tel" v-model="pacienteForm.telefono_personal" id="PacientesTelefonoPersonal"
                      name="PacientesTelefonoPersonal" class="form-control" placeholder="Ej: 987654321"
                      autocomplete="new-telefono_personal" required maxlength="9">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesCorreoPersonal" class="form-label">Correo Personal:</label>
                  <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="email" v-model="pacienteForm.correo_personal" id="PacientesCorreoPersonal"
                      name="PacientesCorreoPersonal" class="form-control" placeholder="Ej: ejemplo@correo.com"
                      autocomplete="new-correo_personal">
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesDireccionPersonal" class="form-label">Dirección Personal:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                    <input type="text" v-model="pacienteForm.direccion_personal" id="PacientesDireccionPersonal"
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
                    <input type="text" v-model="pacienteForm.ocupacion" id="PacientesOcupacion" name="PacientesOcupacion"
                      class="form-control" placeholder="Ej: Ingeniero" autocomplete="new-ocupacion">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesNomCentroLaboral" class="form-label">Nombre del Centro Laboral:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                    <input type="text" v-model="pacienteForm.nom_centro_laboral" id="PacientesNomCentroLaboral"
                      name="PacientesNomCentroLaboral" class="form-control" placeholder="Ej: Empresa XYZ"
                      autocomplete="new-nom_centro_laboral">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesTelefonoTrabajo" class="form-label">Teléfono del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" v-model="pacienteForm.telefono_trabajo" id="PacientesTelefonoTrabajo"
                      name="PacientesTelefonoTrabajo" class="form-control" placeholder="Ej: 123456789"
                      autocomplete="new-telefono_trabajo" maxlength="9">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesCorreoTrabajo" class="form-label">Correo del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="email" v-model="pacienteForm.correo_trabajo" id="PacientesCorreoTrabajo"
                      name="PacientesCorreoTrabajo" class="form-control" placeholder="Ej: trabajo@empresa.com"
                      autocomplete="new-correo_trabajo">
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesDireccionTrabajo" class="form-label">Dirección del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                    <input type="text" v-model="pacienteForm.direccion_trabajo" id="PacientesDireccionTrabajo"
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
                    <input class="form-check-input" type="radio" v-model="pacienteForm.estado_historia" id="estadoActivo"
                      value="1" required>
                    <label class="form-check-label" for="estadoActivo">ACTIVO</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="pacienteForm.estado_historia" id="estadoInactivo"
                      value="0" required>
                    <label class="form-check-label" for="estadoInactivo">INACTIVO</label>
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesObservaciones" class="form-label">Observaciones:</label>
                  <textarea v-model="pacienteForm.observaciones" id="PacientesObservaciones" name="PacientesObservaciones"
                    class="form-control" placeholder="Escriba sus observaciones aquí" autocomplete="new-observaciones"
                    rows="5"></textarea>
                </div>
              </div>
            </fieldset>

            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary">{{ isEditingPaciente ? 'Actualizar' : 'Guardar' }}</button>
              <button type="button" @click="closePacienteModal" class="btn btn-secondary">Cerrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';
import axios from 'axios';

export default {
  props: {
    selectedDate: {
      type: Date,
      required: true
    },
    selectedTime: {
      type: String,
      default: ''
    },
    selectedMedico: {
      type: [String, Number],
      default: ''
    },
    selectedMedicoName: {
      type: String,
      default: ''
    },
    intervencion: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      modal: null,
      loading: false,
      isRescheduling: false,
      form: {
        num_historia: '',
        id_medico: '',
        fecha: '',
        hora: '',
        id_tipo_intervencion: '',
        observaciones: ''
      },
      pacientes: [],
      medicos: [],
      tiposIntervenciones: [],
      errorMessage: '',
      isSubmitting: false,
      // Add properties for local display of date and time
      displayDate: null,
      displayTime: '',
      // For tipo intervencion modal
      isEditingTipoIntervencion: false,
      tipoIntervencionForm: {
        tipo_intervencion: '',
        precio: ''
      },
      tipoIntervencionModal: null,
      // For paciente modal
      isEditingPaciente: false,
      pacienteForm: {
        num_historia: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        doc_identidad: '',
        estado_historia: '1',
        fecha_filiacion: '',
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
      pacienteFormErrors: [],
      editingPacienteId: null,
      pacienteModal: null
    };
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
    minDate() {
      const today = new Date();
      return today.toISOString().split('T')[0];
    }
  },
  created() {
    this.displayDate = this.selectedDate;
    this.displayTime = this.selectedTime;
  },
  mounted() {
    this.fetchPacientes();
    this.fetchMedicos();
    this.fetchTiposIntervenciones();
  },
  methods: {
    openModal() {
      this.loading = true;
      this.resetForm();
      
      if (this.selectedTime) {
        this.form.hora = this.selectedTime;
      }
      
      if (this.selectedDate) {
        const year = this.selectedDate.getFullYear();
        const month = String(this.selectedDate.getMonth() + 1).padStart(2, '0');
        const day = String(this.selectedDate.getDate()).padStart(2, '0');
        this.form.fecha = `${year}-${month}-${day}`;
      }
      
      if (this.selectedMedico) {
        this.form.id_medico = this.selectedMedico;
      }

      // If an intervention is provided, set it as the current intervention
      if (this.intervencion) {
        // Update display date and time
        if (this.intervencion.fecha) {
          const fechaObj = new Date(this.intervencion.fecha);
          this.displayDate = fechaObj;
          
          if (this.intervencion.hora) {
            this.displayTime = this.intervencion.hora.substring(0, 5); // Get HH:mm
          } else {
            this.displayTime = fechaObj.toTimeString().substring(0, 5); // Fallback to time from fecha
          }
        }
      } else {
        this.displayDate = this.selectedDate;
        this.displayTime = this.selectedTime;
      }

      if (!this.modal) {
        this.modal = new Modal(document.getElementById('intervencionModal'));
      }
      
      this.loading = false;
      this.modal.show();
    },
    
    populateFormWithIntervencion() {
      const fechaObj = new Date(this.intervencion.fecha);
      const year = fechaObj.getFullYear();
      const month = String(fechaObj.getMonth() + 1).padStart(2, '0');
      const day = String(fechaObj.getDate()).padStart(2, '0');
      const hours = String(fechaObj.getHours()).padStart(2, '0');
      const minutes = String(fechaObj.getMinutes()).padStart(2, '0');

      this.form = {
        num_historia: this.intervencion.num_historia,
        id_medico: this.intervencion.id_medico,
        fecha: `${year}-${month}-${day}`,
        hora: `${hours}:${minutes}`,
        id_tipo_intervencion: this.intervencion.id_tipo_intervencion,
        observaciones: this.intervencion.observaciones || ''
      };
    },
    
    resetForm() {
      this.form = {
        num_historia: '',
        id_medico: this.selectedMedico || '',
        fecha: this.selectedDate ? this.formatDate(this.selectedDate) : '',
        hora: this.selectedTime || '',
        id_tipo_intervencion: '',
        observaciones: ''
      };
      this.errorMessage = '';
      this.isSubmitting = false;
    },
    
    formatDate(date) {
      const d = new Date(date);
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
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
    
    formatPatientName(paciente) {
      if (!paciente) return '';
      const nombres = paciente.nombres || '';
      const paterno = paciente.ap_paterno || '';
      const materno = paciente.ap_materno || '';
      return `${nombres} ${paterno} ${materno}`.toUpperCase().trim();
    },
    
    async fetchMedicos() {
      try {
        const response = await fetch('/api/medicos-list');
        if (response.ok) {
          const data = await response.json();
          this.medicos = Array.isArray(data) ? data : (data.data || []);
        } else {
          console.error('Error fetching medicos');
        }
      } catch (error) {
        console.error('Error:', error);
      }
    },
    
    async fetchTiposIntervenciones() {
      try {
        const response = await fetch('/api/tipos-intervenciones-list');
        if (response.ok) {
          const data = await response.json();
          this.tiposIntervenciones = data;
        } else {
          console.error('Error fetching tipos intervenciones');
        }
      } catch (error) {
        console.error('Error:', error);
      }
    },
    
    async validateConflict() {
      if (!this.form.num_historia || !this.form.id_medico || !this.form.fecha || !this.form.hora) {
        return { valid: false, message: 'Por favor complete todos los campos requeridos.' };
      }

      try {
        const params = {
          num_historia: this.form.num_historia,
          id_medico: this.form.id_medico,
          fecha: this.form.fecha,
          hora: this.form.hora
        };

        if (this.intervencion) {
          params.current_intervencion_id = this.intervencion.id;
        }

        const response = await fetch('/api/intervenciones/validate-conflict', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(params)
        });

        const data = await response.json();

        if (!response.ok) {
          return { valid: false, message: data.message };
        }

        return { valid: true };
      } catch (error) {
        console.error('Error validating conflict:', error);
        return { valid: false, message: 'Error al validar la disponibilidad. Por favor intente de nuevo.' };
      }
    },
    
    async handleSubmit() {
      this.isSubmitting = true;
      this.errorMessage = '';

      try {
        const validationResult = await this.validateConflict();
        if (!validationResult.valid) {
          this.errorMessage = validationResult.message;
          this.isSubmitting = false;
          return;
        }

        const formData = {
          num_historia: this.form.num_historia,
          id_medico: this.form.id_medico,
          id_tipo_intervencion: this.form.id_tipo_intervencion,
          fecha: this.form.fecha,
          hora: this.form.hora,
          observaciones: this.form.observaciones
        };

        let response;
        if (this.intervencion) {
          response = await fetch(`/api/intervenciones/${this.intervencion.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
          });
        } else {
          response = await fetch('/api/intervenciones', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
          });
        }

        if (response.ok) {
          this.modal.hide();
          this.$emit('intervencionCreated');
          this.resetForm();
        } else {
          const errorData = await response.json();
          this.errorMessage = errorData.message || 'Error al guardar la intervención.';
        }
      } catch (error) {
        console.error('Error:', error);
        this.errorMessage = 'Error al procesar la solicitud.';
      } finally {
        this.isSubmitting = false;
      }
    },
    
    async confirmDelete() {
      if (confirm('¿Está seguro que desea eliminar esta intervención?')) {
        try {
          const response = await fetch(`/api/intervenciones/${this.intervencion.id}`, {
            method: 'DELETE'
          });

          if (response.ok) {
            this.modal.hide();
            this.$emit('intervencionCreated');
          } else {
            const errorData = await response.json();
            this.errorMessage = errorData.message || 'Error al eliminar la intervención.';
          }
        } catch (error) {
          console.error('Error:', error);
          this.errorMessage = 'Error al procesar la solicitud de eliminación.';
        }
      }
    },
    
    // Methods for Tipo de Intervención
    showCreateTipoIntervencionForm() {
      // Hide the intervención modal before showing tipo intervención modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Reset the form
      this.tipoIntervencionForm = {
        tipo_intervencion: '',
        precio: ''
      };
      
      // Set editing flag
      this.isEditingTipoIntervencion = false;
      
      // Show the tipo intervencion modal
      setTimeout(() => {
        this.tipoIntervencionModal = new Modal(document.getElementById('tipoIntervencionModal'));
        this.tipoIntervencionModal.show();
      }, 500);
    },
    
    editTipoIntervencion(tipoIntervencion) {
      if (!tipoIntervencion) return;
      
      // Hide the intervención modal before showing tipo intervención modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Copy tipo intervencion data to form
      this.tipoIntervencionForm = { 
        id: tipoIntervencion.id,
        tipo_intervencion: tipoIntervencion.tipo_intervencion,
        precio: tipoIntervencion.precio
      };
      
      // Set editing flag
      this.isEditingTipoIntervencion = true;
      
      // Show the tipo intervencion modal
      setTimeout(() => {
        this.tipoIntervencionModal = new Modal(document.getElementById('tipoIntervencionModal'));
        this.tipoIntervencionModal.show();
      }, 500);
    },
    
    async submitTipoIntervencionForm() {
      try {
        this.isSubmitting = true;
        let response;
        
        if (this.isEditingTipoIntervencion) {
          // Update existing tipo intervencion
          response = await axios.put(`/api/tipos-intervenciones/${this.tipoIntervencionForm.id}`, this.tipoIntervencionForm);
        } else {
          // Create new tipo intervencion
          response = await axios.post('/api/tipos-intervenciones', this.tipoIntervencionForm);
        }
        
        // Close modal and refresh tipos intervenciones
        this.closeTipoIntervencionModal();
        await this.fetchTiposIntervenciones();
        
        // If creating new, select the newly created tipo intervención
        if (!this.isEditingTipoIntervencion && response.data && response.data.id) {
          this.form.id_tipo_intervencion = response.data.id;
        }
        
        // Show success message
        const message = this.isEditingTipoIntervencion 
          ? 'Tipo de Intervención actualizado con éxito' 
          : 'Tipo de Intervención creado con éxito';
        alert(message);
        
      } catch (error) {
        console.error('Error saving tipo intervencion:', error);
        alert('Error al guardar el tipo de intervención');
      } finally {
        this.isSubmitting = false;
      }
    },
    
    closeTipoIntervencionModal() {
      if (this.tipoIntervencionModal) {
        this.tipoIntervencionModal.hide();
      }
      
      // Reopen the intervención modal
      setTimeout(() => {
        this.modal = new Modal(document.getElementById('intervencionModal'));
        this.modal.show();
      }, 500);
    },
    
    // Methods for handling patients
    editPaciente(paciente) {
      if (!paciente) return;
      
      console.log('Editing patient:', paciente);
      
      // Store the ID of the patient being edited
      this.editingPacienteId = paciente.id;
      
      // Copy all patient data to the form
      this.pacienteForm = { 
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
      
      // Set editing flag
      this.isEditingPaciente = true;
      
      // Close intervencion modal before showing paciente modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Show the paciente modal
      setTimeout(() => {
        this.pacienteModal = new Modal(document.getElementById('pacienteModal'));
        this.pacienteModal.show();
      }, 500);
    },
    
    showNewPacienteForm() {
      // Hide the intervencion modal before showing paciente modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Reset the form
      this.pacienteForm = {
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
      
      // Reset editing flag and ID
      this.isEditingPaciente = false;
      this.editingPacienteId = null;
      
      // Show the paciente modal
      setTimeout(() => {
        this.pacienteModal = new Modal(document.getElementById('pacienteModal'));
        this.pacienteModal.show();
      }, 500);
    },
    
    async submitPacienteForm() {
      this.pacienteFormErrors = [];
      
      try {
        // Check if DNI already exists (for new patients or changed DNIs)
        if (!this.isEditingPaciente || 
            (this.isEditingPaciente && this.pacienteForm.doc_identidad !== this.pacientes.find(p => p.id === this.editingPacienteId)?.doc_identidad)) {
          const dniExists = await this.checkDocIdentidadExists(this.pacienteForm.doc_identidad);
          if (dniExists) {
            this.pacienteFormErrors.push('DNI ya existe en la base de datos.');
            this.scrollToTop();
            setTimeout(() => {
              this.pacienteFormErrors = this.pacienteFormErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
            }, 3000);
            return;
          }
        }

        let response;
        if (this.isEditingPaciente) {
          // Update existing patient
          response = await axios.put(`/api/pacientes/${this.pacienteForm.id}`, this.pacienteForm);
        } else {
          // Create new patient
          response = await axios.post('/api/pacientes', this.pacienteForm);
        }
        
        const updatedPaciente = response.data;
        
        // Refresh the patients list
        await this.fetchPacientes();
        
        // Close the modal
        this.closePacienteModal();
        
        // Show success message
        alert(this.isEditingPaciente ? 'Paciente actualizado exitosamente.' : 'Paciente creado exitosamente.');
        
        // Reopen the intervencion modal and select the patient
        setTimeout(() => {
          this.form.num_historia = updatedPaciente.num_historia;
          this.modal = new Modal(document.getElementById('intervencionModal'));
          this.modal.show();
        }, 500);
        
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.pacienteFormErrors = Object.values(error.response.data.errors).flat();
          this.scrollToTop();
        } else {
          console.error('Error saving patient:', error);
          alert('Error al guardar el paciente');
        }
      }
    },
    
    async checkDocIdentidadExists(doc_identidad) {
      try {
        const response = await axios.get(`/api/pacientes/check-doc-identidad`, { params: { doc_identidad } });
        return response.data.exists;
      } catch (error) {
        console.error('Error checking DNI:', error);
        return false;
      }
    },
    
    closePacienteModal() {
      if (this.pacienteModal) {
        this.pacienteModal.hide();
      }
      
      // Reset form and errors
      this.pacienteFormErrors = [];
      
      // Reopen the intervencion modal
      setTimeout(() => {
        this.modal = new Modal(document.getElementById('intervencionModal'));
        this.modal.show();
      }, 500);
    },
    
    closeFormErrorAlert() {
      this.pacienteFormErrors = this.pacienteFormErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
    },
    
    scrollToTop() {
      const modal = document.getElementById('pacienteModal');
      if (modal) {
        modal.scrollTop = 0;
      }
    },
    
    onlyNumbers(event) {
      event.target.value = event.target.value.replace(/[^\d]/g, '');
    }
  }
};
</script>

<style scoped>
.search-dropdown {
  position: absolute;
  width: 100%;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  border: 1px solid #ced4da;
  border-radius: 0 0 4px 4px;
  z-index: 1000;
}

.search-item {
  padding: 8px 12px;
  cursor: pointer;
}

.search-item:hover {
  background-color: #f8f9fa;
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