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
                <div class="input-group">
                  <select v-model="formData.num_historia" class="form-select">
                    <option value="" disabled>Seleccione un paciente</option>
                    <option v-for="paciente in pacientes" :key="paciente.num_historia" :value="paciente.num_historia">
                      {{ paciente.nombre.toUpperCase() }}
                    </option>
                  </select>
                  <button v-if="formData.num_historia" @click="editPaciente" class="btn btn-secondary ms-2">Editar</button>
                </div>
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
                  <label for="PacientesFNacimiento" class="form-label">Fecha de Nacimiento*:</label>
                  <input type="date" v-model="form.f_nacimiento" id="PacientesFNacimiento" name="PacientesFNacimiento"
                    class="form-control" autocomplete="new-f_nacimiento" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesEstadoCivil" class="form-label">Estado Civil*:</label>
                  <select v-model="form.estado_civil" id="PacientesEstadoCivil" name="PacientesEstadoCivil"
                    class="form-select" autocomplete="new-estado_civil" required>
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
                  <label for="PacientesTelefonoPersonal" class="form-label">Teléfono Personal:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" v-model="form.telefono_personal" id="PacientesTelefonoPersonal"
                      name="PacientesTelefonoPersonal" class="form-control" placeholder="Ej: 987654321"
                      autocomplete="new-telefono_personal" maxlength="9">
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
                  <label for="PacientesDireccionPersonal" class="form-label">Dirección Personal*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                    <input type="text" v-model="form.direccion_personal" id="PacientesDireccionPersonal"
                      name="PacientesDireccionPersonal" class="form-control" placeholder="Ej: Av. Siempre Viva 123"
                      autocomplete="new-direccion_personal" required>
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
        fecha_filiacion: '' // Add this field
      },
      isEditing: false,
      editingId: null,
      formErrors: []
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
    },
    editPaciente() {
      const paciente = this.pacientes.find(p => p.num_historia === this.formData.num_historia);
      if (paciente) {
        // Fetch complete patient data before editing
        axios.get(`/api/pacientes/${paciente.id}`)
          .then(response => {
            const completePatient = response.data;
            this.isEditing = true;
            this.editingId = completePatient.id;
            this.form = {
              id: completePatient.id,
              num_historia: completePatient.num_historia,
              nombres: completePatient.nombres,
              ap_paterno: completePatient.ap_paterno,
              ap_materno: completePatient.ap_materno,
              doc_identidad: completePatient.doc_identidad,
              estado_civil: completePatient.estado_civil,
              f_nacimiento: completePatient.f_nacimiento,
              direccion_personal: completePatient.direccion_personal,
              correo_personal: completePatient.correo_personal,
              telefono_personal: completePatient.telefono_personal,
              estado_historia: completePatient.estado_historia || '1',
              ocupacion: completePatient.ocupacion,
              nom_centro_laboral: completePatient.nom_centro_laboral,
              telefono_trabajo: completePatient.telefono_trabajo,
              correo_trabajo: completePatient.correo_trabajo,
              direccion_trabajo: completePatient.direccion_trabajo,
              observaciones: completePatient.observaciones
            };
            const modal = new bootstrap.Modal(document.getElementById('pacienteModal'));
            modal.show();
          })
          .catch(error => {
            console.error('Error fetching complete patient data:', error);
          });
      }
    },
    async submitForm() {
      this.formErrors = [];
      const formData = { ...this.form };

      const dniExists = await this.checkDocIdentidadExists(formData.doc_identidad);
      if (dniExists && (!this.isEditing || (this.isEditing && formData.doc_identidad !== this.pacientes.find(p => p.id === this.editingId).doc_identidad))) {
        this.formErrors.push('DNI ya existe en la base de datos.');
        this.scrollToTop();
        setTimeout(() => {
          this.formErrors = this.formErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
        }, 3000);
        return;
      }

      if (this.isEditing) {
        await this.updatePaciente();
      } else {
        await this.createPaciente();
      }
    },
    async createPaciente() {
      try {
        const response = await axios.post('/api/pacientes', this.form);
        const newPaciente = response.data;
        newPaciente.num_historia = this.formatNumHistoria(newPaciente.id);
        this.pacientes.push(newPaciente);
        this.closeModal();
        this.showAlert('Paciente creado exitosamente.');
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
          this.scrollToTop();
        } else {
          console.error(error);
        }
      }
    },
    async updatePaciente() {
      try {
        const response = await axios.put(`/api/pacientes/${this.form.id}`, this.form);
        const updatedPaciente = response.data;
        updatedPaciente.num_historia = this.formatNumHistoria(updatedPaciente.id);
        const index = this.pacientes.findIndex(paciente => paciente.id === this.form.id);
        if (index !== -1) {
          this.pacientes[index] = updatedPaciente; // Directly modify the reactive object
        }
        this.closeModal();
        this.showAlert('Paciente actualizado exitosamente.');
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
          this.scrollToTop();
        } else {
          console.error(error);
        }
      }
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