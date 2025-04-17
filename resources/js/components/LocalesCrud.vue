<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Locales</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Local</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Dirección</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.nombre" @input="applyFilters" class="form-control" placeholder="Filtrar por Nombre">
              <button v-if="filters.nombre" @click="clearFilter('nombre')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.direccion" @input="applyFilters" class="form-control" placeholder="Filtrar por Dirección">
              <button v-if="filters.direccion" @click="clearFilter('direccion')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="locales.length === 0">
          <td colspan="3" class="text-center">No hay registros de locales en el sistema.</td>
        </tr>
        <tr v-else v-for="local in locales" :key="local.id">
          <td>{{ local.nombre }}</td>
          <td>{{ local.direccion }}</td>
          <td>
            <button @click="editLocal(local)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(local.id)" class="btn btn-danger btn-sm">
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
    <div class="modal fade" id="localModal" tabindex="-1" aria-labelledby="localModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="localModalLabel">{{ isEditing ? 'Editar Local' : 'Crear Local' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div v-if="formErrors.length" class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                  <li v-for="(error, idx) in formErrors" :key="idx">{{ error }}</li>
                </ul>
                <button type="button" class="btn-close" @click="formErrors = []" aria-label="Close"></button>
              </div>
              <div class="mb-3">
                <label for="nombre" class="form-label">Nombre*:</label>
                <input type="text" v-model="form.nombre" id="nombre" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="direccion" class="form-label">Dirección:</label>
                <input type="text" v-model="form.direccion" id="direccion" class="form-control">
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
      locales: [],
      form: {
        nombre: '',
        direccion: ''
      },
      showForm: false,
      isEditing: false,
      editingId: null,
      alertMessage: '',
      formErrors: [],
      pagination: {
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1,
        next_page_url: null,
        prev_page_url: null
      },
      filters: {
        nombre: '',
        direccion: ''
      }
    };
  },
  methods: {
    fetchLocales(url = '/api/locales') {
      const params = {
        nombre: this.filters.nombre,
        direccion: this.filters.direccion,
        page: this.pagination.current_page
      };
      axios.get(url, { params })
        .then(response => {
          const { data, ...pagination } = response.data;
          this.locales = data;
          this.pagination = pagination;
        })
        .catch(error => {
          console.error(error);
        });
    },
    showCreateForm() {
      this.resetForm();
      this.showForm = true;
      const modal = new Modal(document.getElementById('localModal'));
      modal.show();
    },
    editLocal(local) {
      this.form = { ...local };
      this.showForm = true;
      this.isEditing = true;
      this.editingId = local.id;
      const modal = new Modal(document.getElementById('localModal'));
      modal.show();
    },
    async submitForm() {
      this.formErrors = [];
      try {
        let response;
        if (this.isEditing) {
          response = await axios.put(`/api/locales/${this.editingId}`, this.form);
        } else {
          response = await axios.post('/api/locales', this.form);
        }
        this.alertMessage = this.isEditing ? 'Local actualizado con éxito.' : 'Local creado con éxito.';
        this.fetchLocales();
        this.resetForm();
        this.closeModal();
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
        } else {
          this.formErrors = ['Ocurrió un error inesperado.'];
        }
      }
    },
    confirmDelete(id) {
      if (confirm('¿Está seguro de que desea eliminar este local?')) {
        this.deleteLocal(id);
      }
    },
    deleteLocal(id) {
      axios.delete(`/api/locales/${id}`)
        .then(() => {
          this.fetchLocales();
          this.alertMessage = 'Local eliminado con éxito.';
          setTimeout(() => this.alertMessage = '', 5000);
        })
        .catch(error => console.error(error));
    },
    resetForm() {
      this.form = {
        nombre: '',
        direccion: ''
      };
      this.isEditing = false;
      this.editingId = null;
      this.formErrors = [];
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('localModal'));
      if (modal) {
        modal.hide();
      }
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchLocales(url);
      }
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchLocales();
    },
    resetFilters() {
      this.filters = {
        nombre: '',
        direccion: ''
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
    }
  },
  mounted() {
    this.fetchLocales();
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
