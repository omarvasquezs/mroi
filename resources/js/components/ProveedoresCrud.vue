<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Proveedores</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Proveedor</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Razón Social</th>
          <th>RUC</th>
          <th>Nombre del Representante</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.razon_social" @input="applyFilters" class="form-control" placeholder="Filtrar por Razón Social">
              <button v-if="filters.razon_social" @click="clearFilter('razon_social')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.ruc" @input="applyFilters" class="form-control" placeholder="Filtrar por RUC">
              <button v-if="filters.ruc" @click="clearFilter('ruc')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.nombre_representante" @input="applyFilters" class="form-control" placeholder="Filtrar por Nombre del Representante">
              <button v-if="filters.nombre_representante" @click="clearFilter('nombre_representante')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="proveedores.length === 0">
          <td colspan="4" class="text-center">No hay registros de proveedores en el sistema.</td>
        </tr>
        <tr v-else v-for="proveedor in proveedores" :key="proveedor.id">
          <td>{{ proveedor.razon_social }}</td>
          <td>{{ proveedor.ruc }}</td>
          <td>{{ proveedor.nombre_representante }}</td>
          <td>
            <button @click="viewProveedor(proveedor)" class="btn btn-info btn-sm me-2">
              <i class="fas fa-eye"></i>
            </button>
            <button @click="editProveedor(proveedor)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(proveedor.id)" class="btn btn-danger btn-sm">
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

    <!-- Modal for Viewing Proveedor -->
    <div class="modal fade" id="viewProveedorModal" tabindex="-1" aria-labelledby="viewProveedorModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="viewProveedorModalLabel">
              Información del Proveedor con RUC {{ selectedProveedor.ruc }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Razón Social:</strong></div>
              <div class="col-sm-9">{{ selectedProveedor.razon_social }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>RUC:</strong></div>
              <div class="col-sm-9">{{ selectedProveedor.ruc }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Dirección:</strong></div>
              <div class="col-sm-9">{{ selectedProveedor.direccion }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Teléfonos de Contacto:</strong></div>
              <div class="col-sm-9">{{ selectedProveedor.telefono }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Correo Electrónico:</strong></div>
              <div class="col-sm-9">{{ selectedProveedor.correo_electronico }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Página Web:</strong></div>
              <div class="col-sm-9">{{ selectedProveedor.pagina_web }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Nombre del Representante:</strong></div>
              <div class="col-sm-9">{{ selectedProveedor.nombre_representante }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="proveedorModal" tabindex="-1" aria-labelledby="proveedorModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="proveedorModalLabel">{{ isEditing ? 'Editar Proveedor' : 'Crear Proveedor' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div v-if="formErrors.length" class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul>
                  <li v-for="error in formErrors" :key="error">{{ error }}</li>
                </ul>
                <button type="button" class="btn-close" @click="closeFormErrorAlert" aria-label="Close"></button>
              </div>
              <div class="mb-3">
                <label for="razon_social" class="form-label">Razón Social*:</label>
                <input type="text" v-model="form.razon_social" id="razon_social" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="ruc" class="form-label">RUC*:</label>
                <input type="text" v-model="form.ruc" id="ruc" class="form-control" required @input="filterDigits" @blur="checkRucExists">
                <div v-if="rucExists" class="text-danger">RUC ya existe en la base de datos.</div>
              </div>
              <div class="mb-3">
                <label for="direccion" class="form-label">Dirección*:</label>
                <input type="text" v-model="form.direccion" id="direccion" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="telefono" class="form-label">Teléfonos de Contacto*:</label>
                <input type="text" v-model="form.telefono" id="telefono" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="correo_electronico" class="form-label">Correo Electrónico*:</label>
                <input type="email" v-model="form.correo_electronico" id="correo_electronico" class="form-control" required @blur="checkCorreoExists">
                <div v-if="correoExists" class="text-danger">Correo Electrónico ya existe en la base de datos.</div>
              </div>
              <div class="mb-3">
                <label for="pagina_web" class="form-label">Página Web:</label>
                <input type="url" v-model="form.pagina_web" id="pagina_web" class="form-control">
              </div>
              <div class="mb-3">
                <label for="nombre_representante" class="form-label">Nombre del Representante*:</label>
                <input type="text" v-model="form.nombre_representante" id="nombre_representante" class="form-control" required>
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

export default {
  data() {
    return {
      baseUrl: window.location.origin,
      proveedores: [],
      form: {
        razon_social: '',
        ruc: '',
        direccion: '',
        telefono: '',
        correo_electronico: '',
        pagina_web: '',
        nombre_representante: ''
      },
      selectedProveedor: {}, // Add this field
      rucExists: false,
      correoExists: false,
      formErrors: [],
      showForm: false,
      isEditing: false,
      editingId: null,
      alertMessage: '',
      pagination: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        next_page_url: null,
        prev_page_url: null
      },
      filters: {
        razon_social: '',
        ruc: '',
        nombre_representante: ''
      }
    };
  },
  methods: {
    fetchProveedores(url = '/api/proveedores') {
      const params = {
        razon_social: this.filters.razon_social,
        ruc: this.filters.ruc,
        nombre_representante: this.filters.nombre_representante,
        page: this.pagination.current_page
      };
      axios.get(url, { params })
        .then(response => {
          const { data, ...pagination } = response.data;
          this.proveedores = data;
          this.pagination = pagination;
        })
        .catch(error => {
          console.error(error);
        });
    },
    showCreateForm() {
      this.resetForm();
      this.showForm = true;
      const modal = new Modal(document.getElementById('proveedorModal'));
      modal.show();
    },
    editProveedor(proveedor) {
      this.form = { ...proveedor };
      this.showForm = true;
      this.isEditing = true;
      this.editingId = proveedor.id;
      const modal = new Modal(document.getElementById('proveedorModal'));
      modal.show();
    },
    viewProveedor(proveedor) {
      this.selectedProveedor = proveedor;
      const modal = new Modal(document.getElementById('viewProveedorModal'));
      modal.show();
    },
    async submitForm() {
      this.formErrors = [];
      if (this.rucExists || this.correoExists) {
        if (this.rucExists) this.formErrors.push('RUC ya existe en la base de datos.');
        if (this.correoExists) this.formErrors.push('Correo Electrónico ya existe en la base de datos.');
        return;
      }

      try {
        let response;
        if (this.isEditing) {
          response = await axios.put(`/api/proveedores/${this.editingId}`, this.form);
        } else {
          response = await axios.post('/api/proveedores', this.form);
        }

        this.alertMessage = this.isEditing ? 'Proveedor actualizado con éxito.' : 'Proveedor creado con éxito.';
        this.fetchProveedores();
        this.resetForm();
        this.closeModal();
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error(error);
      }
    },
    confirmDelete(id) {
      if (confirm('¿Está seguro de que desea eliminar este proveedor?')) {
        this.deleteProveedor(id);
      }
    },
    deleteProveedor(id) {
      axios.delete(`/api/proveedores/${id}`)
        .then(() => {
          this.fetchProveedores();
          this.alertMessage = 'Proveedor eliminado con éxito.';
          setTimeout(() => this.alertMessage = '', 5000);
        })
        .catch(error => console.error(error));
    },
    resetForm() {
      this.form = {
        razon_social: '',
        ruc: '',
        direccion: '',
        telefono: '',
        correo_electronico: '',
        pagina_web: '',
        nombre_representante: ''
      };
      this.rucExists = false;
      this.correoExists = false;
      this.isEditing = false;
      this.editingId = null;
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('proveedorModal'));
      if (modal) {
        modal.hide();
      }
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchProveedores(url);
      }
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchProveedores();
    },
    resetFilters() {
      this.filters = {
        razon_social: '',
        ruc: '',
        nombre_representante: ''
      };
      this.applyFilters();
    },
    clearFilter(filter) {
      this.filters[filter] = '';
      this.applyFilters();
    },
    closeAlert() {
      this.alertMessage = '';
    },
    goBack() {
      window.history.back();
    },
    filterDigits(event) {
      event.target.value = event.target.value.replace(/\D/g, '');
      this.form.ruc = event.target.value;
    },
    async checkRucExists() {
      if (!this.form.ruc) return;
      try {
        const response = await axios.get(`/api/proveedores/check-ruc`, { params: { ruc: this.form.ruc } });
        this.rucExists = response.data.exists;
      } catch (error) {
        console.error(error);
      }
    },
    async checkCorreoExists() {
      if (!this.form.correo_electronico) return;
      try {
        const response = await axios.get(`/api/proveedores/check-correo`, { params: { correo_electronico: this.form.correo_electronico } });
        this.correoExists = response.data.exists;
      } catch (error) {
        console.error(error);
      }
    },
    closeFormErrorAlert() {
      this.formErrors = [];
    }
  },
  mounted() {
    this.fetchProveedores();
  }
};
</script>

<style scoped>
.btn-clear {
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
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
