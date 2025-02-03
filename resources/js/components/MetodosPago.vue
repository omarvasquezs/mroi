<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Métodos de Pago</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Método de Pago</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Estado</th>
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
          <th></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="metodosPago.length === 0">
          <td colspan="3" class="text-center">No hay registros de métodos de pago en el sistema.</td>
        </tr>
        <tr v-else v-for="metodo in metodosPago" :key="metodo.id">
          <td>{{ metodo.nombre }}</td>
          <td class="text-center">
            <span :class="['badge', metodo.activo ? 'bg-success' : 'bg-danger']">
              {{ metodo.activo ? 'Activo' : 'Inactivo' }}
            </span>
          </td>
          <td>
            <button @click="editMetodoPago(metodo)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(metodo.id)" class="btn btn-danger btn-sm">
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
    <div class="modal fade" id="metodoPagoModal" tabindex="-1" data-bs-backdrop="static">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="metodoPagoModalLabel">
              {{ isEditing ? 'Editar' : 'Crear' }} Método de Pago
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitForm">
              <div class="mb-3">
                <label class="form-label">Nombre*:</label>
                <input type="text" v-model="form.nombre" class="form-control" required>
              </div>
              <div class="mb-3 d-flex align-items-center">
                <div class="custom-checkbox">
                  <input id="statusToggle" 
                         type="checkbox" 
                         v-model="form.activo">
                  <label for="statusToggle">
                    <div class="status-switch"
                         data-unchecked="Inactivo"
                         data-checked="Activo">
                    </div>
                  </label>
                </div>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" @click="closeModal">
                  <i class="fas fa-times fa-sm"></i> Cerrar
                </button>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save fa-sm"></i> {{ isEditing ? 'Actualizar' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';

export default {
  data() {
    return {
      baseUrl: window.location.origin,
      metodosPago: [],
      form: {
        nombre: '',
        activo: true
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
        nombre: ''
      }
    };
  },
  methods: {
    fetchMetodosPago(url = '/api/metodos-pago') {
      const params = {
        nombre: this.filters.nombre,
        page: this.pagination.current_page
      };
      axios.get(url, { params })
        .then(response => {
          const { data, ...pagination } = response.data;
          this.metodosPago = data;
          this.pagination = pagination;
        })
        .catch(error => {
          console.error('Error fetching metodos de pago:', error);
        });
    },
    showCreateForm() {
      this.resetForm();
      this.showForm = true;
      const modal = new Modal(document.getElementById('metodoPagoModal'));
      modal.show();
    },
    editMetodoPago(metodoPago) {
      this.form = { 
        nombre: metodoPago.nombre,
        activo: metodoPago.activo
      };
      this.isEditing = true;
      this.editingId = metodoPago.id;
      const modal = new Modal(document.getElementById('metodoPagoModal'));
      modal.show();
    },
    async submitForm() {
      try {
        if (this.isEditing) {
          await axios.put(`/api/metodos-pago/${this.editingId}`, this.form);
          this.alertMessage = 'Método de pago actualizado con éxito.';
        } else {
          await axios.post('/api/metodos-pago', this.form);
          this.alertMessage = 'Método de pago creado con éxito.';
        }
        
        const modal = Modal.getInstance(document.getElementById('metodoPagoModal'));
        if (modal) {
          modal.hide();
        }
        
        await this.fetchMetodosPago();
        this.resetForm();
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar el método de pago: ' + (error.response?.data?.message || error.message));
      }
    },
    confirmDelete(id) {
      if (confirm('¿Está seguro de que desea eliminar este método de pago?')) {
        this.deleteMetodoPago(id);
      }
    },
    async deleteMetodoPago(id) {
      try {
        if (!confirm('¿Está seguro de que desea eliminar este método de pago?')) {
          return;
        }
        
        await axios.delete(`/api/metodos-pago/${id}`);
        this.alertMessage = 'Método de pago eliminado con éxito.';
        await this.fetchMetodosPago();
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Error:', error);
        alert('Error al eliminar el método de pago: ' + (error.response?.data?.message || error.message));
      }
    },
    resetForm() {
      this.form = {
        nombre: '',
        activo: true
      };
      this.isEditing = false;
      this.editingId = null;
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('metodoPagoModal'));
      if (modal) {
        modal.hide();
      }
      this.resetForm();
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchMetodosPago(url);
      }
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchMetodosPago();
    },
    resetFilters() {
      this.filters = {
        nombre: ''
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
    this.fetchMetodosPago();
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

/* New toggle switch styles */
.custom-checkbox {
  width: 200px;
  height: 40px;
}

.custom-checkbox input {
  display: none;
}

.custom-checkbox input + label {
  height: 100%;
  width: 100%;
}

.custom-checkbox input + label > .status-switch {
  cursor: pointer;
  width: 100%;
  height: 100%;
  position: relative;
  background-color: #dc3545;
  color: white;
  transition: all 0.5s ease;
  padding: 3px;
  border-radius: 3px;
}

.custom-checkbox input + label > .status-switch:before,
.custom-checkbox input + label > .status-switch:after {
  border-radius: 2px;
  height: calc(100% - 6px);
  width: calc(50% - 3px);
  display: flex;
  align-items: center;
  position: absolute;
  justify-content: center;
  transition: all 0.3s ease;
}

.custom-checkbox input + label > .status-switch:before {
  background-color: white;
  color: #6c757d;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
  left: 3px;
  z-index: 10;
  content: attr(data-unchecked);
  font-size: 14px;
}

.custom-checkbox input + label > .status-switch:after {
  right: 0;
  content: attr(data-checked);
  font-size: 14px;
}

.custom-checkbox input:checked + label > .status-switch {
  background-color: #198754;
}

.custom-checkbox input:checked + label > .status-switch:after {
  left: 0;
  content: attr(data-unchecked);
}

.custom-checkbox input:checked + label > .status-switch:before {
  color: #198754;
  left: 50%;
  content: attr(data-checked);
}
</style>
