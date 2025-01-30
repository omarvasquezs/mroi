<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Pacientes</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Paciente</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Número de Historia</th>
          <th>Nombres</th>
          <th>Apellidos</th>
          <th>DNI</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.num_historia" @input="applyFilters" class="form-control"
                placeholder="Filtrar por Num Historia">
              <button v-if="filters.num_historia" @click="clearFilter('num_historia')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.nombres" @input="applyFilters" class="form-control"
                placeholder="Filtrar por Nombres">
              <button v-if="filters.nombres" @click="clearFilter('nombres')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.apellidos" @input="applyFilters" class="form-control"
                placeholder="Filtrar por Apellidos">
              <button v-if="filters.apellidos" @click="clearFilter('apellidos')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" :value="filters.doc_identidad" @input="e => { filters.doc_identidad = e.target.value.replace(/\D/g, ''); applyFilters(); }"
                class="form-control" placeholder="Filtrar por DNI">
              <button v-if="filters.doc_identidad" @click="clearFilter('doc_identidad')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="pacientes.length === 0">
          <td colspan="5" class="text-center">No hay registros de pacientes en el sistema.</td>
        </tr>
        <tr v-else v-for="paciente in pacientes" :key="paciente.id">
          <td>{{ paciente.num_historia }}</td>
          <td>{{ paciente.nombres }}</td>
          <td>{{ paciente.ap_paterno }} {{ paciente.ap_materno }}</td>
          <td>{{ paciente.doc_identidad }}</td>
          <td>
            <button @click="viewPaciente(paciente)" class="btn btn-info btn-sm me-2">
              <i class="fas fa-eye"></i>
            </button>
            <button @click="editPaciente(paciente)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(paciente.id)" class="btn btn-danger btn-sm">
              <i class="fas fa-trash-alt"></i>
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    <nav v-if="pagination.total > pagination.per_page">
      <ul class="pagination">
        <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
          <a class="page-link" href="#" @click.prevent="changePage(pagination.prev_page_url)">Anterior</a>
        </li>
        <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
          <a class="page-link" href="#" @click.prevent="changePage(pagination.next_page_url)">Siguiente</a>
        </li>
      </ul>
    </nav>

    <!-- Modal for Viewing Paciente -->
    <div class="modal fade" id="viewPacienteModal" tabindex="-1" aria-labelledby="viewPacienteModalLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="viewPacienteModalLabel">
              Información del Paciente con DNI {{ selectedPaciente.doc_identidad }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Número de Historia:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.num_historia }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Nombres:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.nombres }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Apellido Paterno:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.ap_paterno }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Apellido Materno:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.ap_materno }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>DNI:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.doc_identidad }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Estado Civil:</strong></div>
              <div class="col-sm-9">{{ getEstadoCivil(selectedPaciente.estado_civil) }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Fecha de Nacimiento:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.f_nacimiento }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Dirección Personal:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.direccion_personal }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Correo Personal:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.correo_personal }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Teléfono Personal:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.telefono_personal }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Estado de Historia:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.estado_historia == '1' ? 'ACTIVO' : 'INACTIVO' }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Ocupación:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.ocupacion }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Nombre del Centro Laboral:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.nom_centro_laboral }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Teléfono del Trabajo:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.telefono_trabajo }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Correo del Trabajo:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.correo_trabajo }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Dirección del Trabajo:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.direccion_trabajo }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Observaciones:</strong></div>
              <div class="col-sm-9">{{ selectedPaciente.observaciones }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
  </div>
</template>

<script>
import axios from 'axios';
import { Modal } from 'bootstrap';

const baseUrl = window.location.origin;

export default {
  data() {
    return {
      baseUrl,
      pacientes: [],
      form: {
        num_historia: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        doc_identidad: '',
        estado_historia: '1', // Default value to 1 (ACTIVO)
        fecha_filiacion: '' // Add this field
      },
      selectedPaciente: {}, // Add this field
      isEditing: false,
      editingId: null,
      alertMessage: '',
      formErrors: [],
      filters: {
        num_historia: '',
        nombres: '',
        apellidos: '',
        doc_identidad: ''
      },
      pagination: {
        total: 0,
        per_page: 10,
        current_page: 1,
        prev_page_url: null,
        next_page_url: null
      }
    };
  },
  methods: {
    goBack() {
      this.$router.go(-1);
    },
    showCreateForm() {
      this.isEditing = false;
      this.form = {
        num_historia: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        doc_identidad: '',
        estado_historia: '1', // Default value to 1 (ACTIVO)
        fecha_filiacion: new Date().toISOString().split('T')[0] // Set current date
      };
      const modal = new Modal(document.getElementById('pacienteModal'));
      modal.show();
    },
    editPaciente(paciente) {
      this.isEditing = true;
      this.editingId = paciente.id;
      this.form = { ...paciente };
      const modal = new Modal(document.getElementById('pacienteModal'));
      modal.show();
    },
    viewPaciente(paciente) {
      this.selectedPaciente = paciente;
      const modal = new Modal(document.getElementById('viewPacienteModal'));
      modal.show();
    },
    getEstadoCivil(estado) {
      switch (estado) {
        case 'S':
          return 'Solter(a)';
        case 'C':
          return 'Casad(a)';
        case 'V':
          return 'Viud(a)';
        case 'D':
          return 'Divorciad(a)';
        default:
          return '';
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
    confirmDelete(id) {
      if (confirm('¿Estás seguro de que deseas eliminar este paciente?')) {
        this.deletePaciente(id);
      }
    },
    deletePaciente(id) {
      axios.delete(`/api/pacientes/${id}`)
        .then(response => {
          this.pacientes = this.pacientes.filter(paciente => paciente.id !== id);
          this.showAlert('Paciente eliminado exitosamente.');
        })
        .catch(error => {
          console.error(error);
        });
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('pacienteModal'));
      modal.hide();
    },
    showAlert(message) {
      this.alertMessage = message;
      setTimeout(() => {
        this.alertMessage = '';
      }, 3000);
    },
    closeAlert() {
      this.alertMessage = '';
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchPacientes();
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchPacientes(url);
      }
    },
    fetchPacientes(url = '/api/pacientes') {
      const params = {
        num_historia: this.filters.num_historia,
        nombres: this.filters.nombres,
        apellidos: this.filters.apellidos,
        doc_identidad: this.filters.doc_identidad,
        page: this.pagination.current_page
      };
      axios.get(url, { params })
        .then(response => {
          const { data, ...pagination } = response.data;
          this.pacientes = data;
          this.pagination = pagination;
        })
        .catch(error => {
          console.error(error);
        });
    },
    resetFilters() {
      this.filters = {
        num_historia: '',
        nombres: '',
        apellidos: '',
        doc_identidad: ''
      };
      this.applyFilters();
    },
    clearFilter(filter) {
      this.filters[filter] = '';
      this.applyFilters();
    },
    checkDocIdentidadExists(doc_identidad) {
      return axios.get(`/api/pacientes/check-doc-identidad`, { params: { doc_identidad } })
        .then(response => response.data.exists)
        .catch(error => {
          console.error(error);
          return false;
        });
    },
    closeFormErrorAlert(error) {
      this.formErrors = this.formErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
    },
    formatNumHistoria(id) {
      return id.toString().padStart(7, '0');
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
  },
  mounted() {
    this.fetchPacientes();
  }
};
</script>

<style scoped>
.btn-clear {
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  font-size: 1rem;
  padding: 0;
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.clear-icon {
  width: 12px;
  height: 12px;
  object-fit: contain;
}
</style>
