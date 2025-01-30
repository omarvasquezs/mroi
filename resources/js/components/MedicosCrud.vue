<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Médicos</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Médico</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Nombres</th>
          <th>Apellido Paterno</th>
          <th>Apellido Materno</th>
          <th>DNI</th>
          <th>CMP</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.nombres" @input="applyFilters" class="form-control" placeholder="Filtrar por Nombres">
              <button v-if="filters.nombres" @click="clearFilter('nombres')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.ap_paterno" @input="applyFilters" class="form-control" placeholder="Filtrar por Apellido Paterno">
              <button v-if="filters.ap_paterno" @click="clearFilter('ap_paterno')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.ap_materno" @input="applyFilters" class="form-control" placeholder="Filtrar por Apellido Materno">
              <button v-if="filters.ap_materno" @click="clearFilter('ap_materno')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
                <input type="text" :value="filters.dni" @input="e => { filters.dni = e.target.value.replace(/\D/g, ''); applyFilters(); }" class="form-control" placeholder="Filtrar por DNI">
              <button v-if="filters.dni" @click="clearFilter('dni')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
                <input type="text" :value="filters.cmp" @input="e => { filters.cmp = e.target.value.replace(/\D/g, ''); applyFilters(); }" class="form-control" placeholder="Filtrar por CMP">
              <button v-if="filters.cmp" @click="clearFilter('cmp')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="medicos.length === 0">
          <td colspan="6" class="text-center">No hay registros de médicos en el sistema.</td>
        </tr>
        <tr v-else v-for="medico in medicos" :key="medico.id">
          <td>{{ medico.nombres }}</td>
          <td>{{ medico.ap_paterno }}</td>
          <td>{{ medico.ap_materno }}</td>
          <td>{{ medico.dni }}</td>
          <td>{{ medico.cmp }}</td>
          <td>
            <button @click="editMedico(medico)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(medico.id)" class="btn btn-danger btn-sm">
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

    <!-- Modal -->
    <div class="modal fade" id="medicoModal" tabindex="-1" aria-labelledby="medicoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="medicoModalLabel">{{ isEditing ? 'Editar Médico' : 'Crear Médico' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <form @submit.prevent="submitForm" class="mb-5" autocomplete="off">
              <div v-if="formErrors.includes('DNI ya existe en la base de datos.')" class="alert alert-danger alert-dismissible fade show" role="alert">
                DNI ya existe en la base de datos.
                <button type="button" class="btn-close" @click="closeFormErrorAlert('DNI ya existe en la base de datos.')" aria-label="Close"></button>
              </div>
              <div v-if="formErrors.includes('CMP ya existe en la base de datos.')" class="alert alert-danger alert-dismissible fade show" role="alert">
                CMP ya existe en la base de datos.
                <button type="button" class="btn-close" @click="closeFormErrorAlert('CMP ya existe en la base de datos.')" aria-label="Close"></button>
              </div>
              <div class="mb-3">
                <label for="nombres" class="form-label">Nombres*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                  <input type="text" v-model="form.nombres" id="nombres" class="form-control" placeholder="Ej: Juan" required>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="ap_paterno" class="form-label">Apellido Paterno*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="form.ap_paterno" id="ap_paterno" class="form-control" placeholder="Ej: Pérez" required>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="ap_materno" class="form-label">Apellido Materno*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="form.ap_materno" id="ap_materno" class="form-control" placeholder="Ej: Gómez" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="dni" class="form-label">DNI*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" v-model="form.dni" @input="onlyNumbers($event)" id="dni" class="form-control" placeholder="Ej: 00123456" required minlength="8" maxlength="8">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="cmp" class="form-label">CMP*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                    <input type="text" v-model="form.cmp" @input="onlyNumbers($event)" id="cmp" class="form-control" placeholder="Ej: 001234" required maxlength="6">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="telefono" class="form-label">Teléfono*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" v-model="form.telefono" id="telefono" class="form-control" placeholder="Ej: 987654321" required maxlength="9">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="email" class="form-label">Email*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" v-model="form.email" id="email" class="form-control" placeholder="Ej: juan@example.com" required>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label for="direccion" class="form-label">Dirección*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                  <input type="text" v-model="form.direccion" id="direccion" class="form-control" placeholder="Ej: Calle Falsa 123" required>
                </div>
              </div>
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
      medicos: [],
      form: {
        dni: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        cmp: '',
        telefono: '',
        email: '',
        direccion: ''
      },
      isEditing: false,
      alertMessage: '',
      formErrors: [],
      filters: {
        dni: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        cmp: ''
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
  created() {
    this.fetchMedicos();
  },
  methods: {
    fetchMedicos(url = '/api/medicos') {
      const params = {
      dni: this.filters.dni,
      nombres: this.filters.nombres,
      ap_paterno: this.filters.ap_paterno,
      ap_materno: this.filters.ap_materno,
      cmp: this.filters.cmp,
      page: this.pagination.current_page
      };
      axios.get(url, { params })
      .then(response => {
        const { data, ...pagination } = response.data;
        this.medicos = data;
        this.pagination = pagination;
      })
      .catch(error => {
        console.error(error);
      });
    },
    deleteMedico(id) {
      axios.delete(`/api/medicos/${id}`)
        .then(() => {
          this.fetchMedicos();
          this.showAlert('Médico eliminado exitosamente.');
        });
    },
    showCreateForm() {
      this.isEditing = false;
      this.form = {
        dni: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        cmp: '',
        telefono: '',
        email: '',
        direccion: ''
      };
      this.showModal();
    },
    editMedico(medico) {
      this.isEditing = true;
      this.form = { ...medico };
      this.showModal();
    },
    async submitForm() {
      this.formErrors = [];
      const formData = { ...this.form };
      let hasErrors = false;

      const dniExists = await this.checkDniExists(formData.dni);
      if (dniExists && (!this.isEditing || (this.isEditing && formData.dni !== this.medicos.find(m => m.id === this.form.id).dni))) {
        this.formErrors.push('DNI ya existe en la base de datos.');
        hasErrors = true;
      }

      const cmpExists = await this.checkCmpExists(formData.cmp);
      if (cmpExists && (!this.isEditing || (this.isEditing && formData.cmp !== this.medicos.find(m => m.id === this.form.id).cmp))) {
        this.formErrors.push('CMP ya existe en la base de datos.');
        hasErrors = true;
      }

      if (hasErrors) {
        this.scrollToTop();
        setTimeout(() => {
          this.formErrors = [];
        }, 3000);
        return;
      }

      if (this.isEditing) {
        await this.updateMedico();
      } else {
        await this.createMedico();
      }
    },
    async createMedico() {
      try {
        await axios.post('/api/medicos', this.form);
        this.fetchMedicos();
        this.closeModal();
        this.showAlert('Médico creado exitosamente.');
        this.clearFormErrors();
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
          this.scrollToTop();
        } else {
          console.error(error);
        }
      }
    },
    async updateMedico() {
      try {
        await axios.put(`/api/medicos/${this.form.id}`, this.form);
        this.fetchMedicos();
        this.closeModal();
        this.showAlert('Médico actualizado exitosamente.');
        this.clearFormErrors();
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
          this.scrollToTop();
        } else {
          console.error(error);
        }
      }
    },
    showModal() {
      const modalElement = document.getElementById('medicoModal');
      const modal = new Modal(modalElement);
      modal.show();
    },
    closeModal() {
      const modalElement = document.getElementById('medicoModal');
      const modal = Modal.getInstance(modalElement);
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
      this.fetchMedicos();
    },
    resetFilters() {
      this.filters = {
        dni: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        cmp: ''
      };
      this.applyFilters();
    },
    clearFilter(filter) {
      this.filters[filter] = '';
      this.applyFilters();
    },
    changePage(url) {
      axios.get(url)
        .then(response => {
          const { data, meta } = response.data;
          this.medicos = data;
          this.pagination = meta.pagination;
        })
        .catch(error => {
          console.error(error);
        });
    },
    goBack() {
      this.$router.go(-1);
    },
    confirmDelete(id) {
      if (confirm('¿Estás seguro de que deseas eliminar este médico?')) {
        this.deleteMedico(id);
      }
    },
    checkDniExists(dni) {
      return axios.get(`/api/medicos/check-dni`, { params: { dni } })
        .then(response => response.data.exists)
        .catch(error => {
          console.error(error);
          return false;
        });
    },
    checkCmpExists(cmp) {
      return axios.get(`/api/medicos/check-cmp`, { params: { cmp } })
        .then(response => response.data.exists)
        .catch(error => {
          console.error(error);
          return false;
        });
    },
    closeFormErrorAlert(error) {
      if (error) {
        this.formErrors = this.formErrors.filter(e => e !== error);
      } else {
        this.formErrors = [];
      }
    },
    clearFormErrors() {
      setTimeout(() => {
        this.formErrors = [];
      }, 3000);
    },
    scrollToTop() {
      const modal = document.getElementById('medicoModal');
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
