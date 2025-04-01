<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Tipos de Intervenciones</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Tipo de Intervención</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Tipo de Intervención</th>
          <th>Precio</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.tipo_intervencion" @input="applyFilters" class="form-control" placeholder="Filtrar por Tipo de Intervención">
              <button v-if="filters.tipo_intervencion" @click="clearFilter('tipo_intervencion')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.precio" @input="applyFilters" class="form-control" placeholder="Filtrar por Precio">
              <button v-if="filters.precio" @click="clearFilter('precio')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="tiposIntervenciones.length === 0">
          <td colspan="3" class="text-center">No hay registros de tipos de intervenciones en el sistema.</td>
        </tr>
        <tr v-else v-for="tipoIntervencion in tiposIntervenciones" :key="tipoIntervencion.id">
          <td>{{ tipoIntervencion.tipo_intervencion }}</td>
          <td>S/. {{ tipoIntervencion.precio }}</td>
          <td>
            <button @click="editTipoIntervencion(tipoIntervencion)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(tipoIntervencion.id)" class="btn btn-danger btn-sm">
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
    <div class="modal fade" id="tipoIntervencionModal" tabindex="-1" aria-labelledby="tipoIntervencionModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="tipoIntervencionModalLabel">{{ isEditing ? 'Editar Tipo de Intervención' : 'Crear Tipo de Intervención' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="mb-3">
                <label for="tipo_intervencion" class="form-label">Tipo de Intervención*:</label>
                <input type="text" v-model="form.tipo_intervencion" id="tipo_intervencion" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="precio" class="form-label">Precio*:</label>
                <div class="input-group">
                  <span class="input-group-text">S/.</span>
                  <input type="number" v-model="form.precio" id="precio" class="form-control" step="0.01" required>
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

export default {
  data() {
    return {
      baseUrl: window.location.origin,
      tiposIntervenciones: [],
      form: {
        tipo_intervencion: '',
        precio: ''
      },
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
        tipo_intervencion: '',
        precio: ''
      }
    };
  },
  methods: {
    fetchTiposIntervenciones(url = '/api/tipos-intervenciones') {
      const params = {
        tipo_intervencion: this.filters.tipo_intervencion,
        precio: this.filters.precio,
        page: this.pagination.current_page
      };
      axios.get(url, { params })
        .then(response => {
          const { data, ...pagination } = response.data;
          this.tiposIntervenciones = data;
          this.pagination = pagination;
        })
        .catch(error => {
          console.error(error);
        });
    },
    showCreateForm() {
      this.resetForm();
      this.showForm = true;
      const modal = new Modal(document.getElementById('tipoIntervencionModal'));
      modal.show();
    },
    editTipoIntervencion(tipoIntervencion) {
      this.form = { ...tipoIntervencion };
      this.showForm = true;
      this.isEditing = true;
      this.editingId = tipoIntervencion.id;
      const modal = new Modal(document.getElementById('tipoIntervencionModal'));
      modal.show();
    },
    async submitForm() {
      try {
        let response;
        if (this.isEditing) {
          response = await axios.put(`/api/tipos-intervenciones/${this.editingId}`, this.form);
        } else {
          response = await axios.post('/api/tipos-intervenciones', this.form);
        }

        this.alertMessage = this.isEditing ? 'Tipo de Intervención actualizado con éxito.' : 'Tipo de Intervención creado con éxito.';
        this.fetchTiposIntervenciones();
        this.resetForm();
        this.closeModal();
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error(error);
      }
    },
    confirmDelete(id) {
      if (confirm('¿Está seguro de que desea eliminar este tipo de intervención?')) {
        this.deleteTipoIntervencion(id);
      }
    },
    deleteTipoIntervencion(id) {
      axios.delete(`/api/tipos-intervenciones/${id}`)
        .then(() => {
          this.fetchTiposIntervenciones();
          this.alertMessage = 'Tipo de Intervención eliminado con éxito.';
          setTimeout(() => this.alertMessage = '', 5000);
        })
        .catch(error => console.error(error));
    },
    resetForm() {
      this.form = {
        tipo_intervencion: '',
        precio: ''
      };
      this.isEditing = false;
      this.editingId = null;
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('tipoIntervencionModal'));
      if (modal) {
        modal.hide();
      }
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchTiposIntervenciones(url);
      }
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchTiposIntervenciones();
    },
    resetFilters() {
      this.filters = {
        tipo_intervencion: '',
        precio: ''
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
    this.fetchTiposIntervenciones();
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