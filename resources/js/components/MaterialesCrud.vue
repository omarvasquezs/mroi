<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Materiales</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Material</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Material</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.material" @input="applyFilters" class="form-control" placeholder="Filtrar por Material">
              <button v-if="filters.material" @click="clearFilter('material')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="materiales && materiales.length === 0">
          <td colspan="2" class="text-center">No hay registros de materiales en el sistema.</td>
        </tr>
        <tr v-else v-for="material in materiales" :key="material.id">
          <td>{{ material.material }}</td>
          <td>
            <button @click="editMaterial(material)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(material.id)" class="btn btn-danger btn-sm">
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

    <!-- Modal for Viewing Material -->
    <div class="modal fade" id="viewMaterialModal" tabindex="-1" aria-labelledby="viewMaterialModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="viewMaterialModalLabel">
              Información del Material
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Material:</strong></div>
              <div class="col-sm-9">{{ selectedMaterial.material }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Creado en:</strong></div>
              <div class="col-sm-9">{{ selectedMaterial.created_at }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Actualizado en:</strong></div>
              <div class="col-sm-9">{{ selectedMaterial.updated_at }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="materialModal" tabindex="-1" aria-labelledby="materialModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="materialModalLabel">{{ isEditing ? 'Editar Material' : 'Crear Material' }}</h5>
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
                <label for="material" class="form-label">Material*:</label>
                <input type="text" v-model="form.material" id="material" class="form-control" required>
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
      materiales: [],
      form: {
        material: ''
      },
      selectedMaterial: {},
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
        material: ''
      }
    };
  },
  methods: {
    fetchMateriales(url = '/api/materiales') {
      axios.get(url)
        .then(response => {
          console.log('API response:', response.data); // Add this line
          this.materiales = response.data;
          console.log('Fetched materiales:', this.materiales); // Add this line
        })
        .catch(error => {
          console.error(error);
        });
    },
    showCreateForm() {
      this.resetForm();
      this.showForm = true;
      const modal = new Modal(document.getElementById('materialModal'));
      modal.show();
    },
    editMaterial(material) {
      this.form = { ...material };
      this.showForm = true;
      this.isEditing = true;
      this.editingId = material.id;
      const modal = new Modal(document.getElementById('materialModal'));
      modal.show();
    },
    async submitForm() {
      this.formErrors = [];
      try {
        let response;
        if (this.isEditing) {
          response = await axios.put(`/api/materiales/${this.editingId}`, this.form);
          console.log('Update response:', response.data); // Add this line
        } else {
          response = await axios.post('/api/materiales', this.form);
          console.log('Create response:', response.data); // Add this line
        }
        this.alertMessage = this.isEditing ? 'Material actualizado con éxito.' : 'Material creado con éxito.';
        this.fetchMateriales();
        this.resetForm();
        this.closeModal();
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Submit form error:', error.response ? error.response.data : error); // Add this line
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
        } else {
          console.error(error);
        }
      }
    },
    confirmDelete(id) {
      if (confirm('¿Está seguro de que desea eliminar este material?')) {
        this.deleteMaterial(id);
      }
    },
    async deleteMaterial(id) {
      try {
        const response = await axios.delete(`/api/materiales/${id}`);
        console.log('Delete response:', response.data); // Add this line
        this.fetchMateriales();
        this.alertMessage = 'Material eliminado con éxito.';
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Delete material error:', error.response ? error.response.data : error); // Add this line
      }
    },
    resetForm() {
      this.form = {
        material: ''
      };
      this.isEditing = false;
      this.editingId = null;
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('materialModal'));
      if (modal) {
        modal.hide();
      }
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchMateriales(url);
      }
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchMateriales();
    },
    resetFilters() {
      this.filters = {
        material: ''
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
    closeFormErrorAlert() {
      this.formErrors = [];
    }
  },
  mounted() {
    this.fetchMateriales();
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