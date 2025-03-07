<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Marcas</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Marca</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Marca</th>
          <th>Proveedores</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.marca" @input="applyFilters" class="form-control" placeholder="Filtrar por Marca">
              <button v-if="filters.marca" @click="clearFilter('marca')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th></th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="3" class="text-center">Cargando...</td>
        </tr>
        <tr v-else-if="marcas && marcas.length === 0">
          <td colspan="3" class="text-center">No hay registros de marcas en el sistema.</td>
        </tr>
        <tr v-else v-for="marca in marcas" :key="marca.id">
          <td>{{ marca.marca }}</td>
          <td>
            <div class="proveedores-tags">
              <span v-for="proveedor in marca.proveedores" :key="proveedor.id" class="badge bg-info me-1">
                {{ proveedor.razon_social }}
              </span>
              <span v-if="!marca.proveedores || marca.proveedores.length === 0" class="text-muted">
                Sin proveedores asignados
              </span>
            </div>
          </td>
          <td>
            <button @click="viewMarca(marca)" class="btn btn-info btn-sm me-2">
              <i class="fas fa-eye"></i>
            </button>
            <button @click="editMarca(marca)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(marca.id)" class="btn btn-danger btn-sm">
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

    <!-- Modal for Viewing Marca -->
    <div class="modal fade" id="viewMarcaModal" tabindex="-1" aria-labelledby="viewMarcaModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="viewMarcaModalLabel">
              Información de la Marca
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Marca:</strong></div>
              <div class="col-sm-9">{{ selectedMarca.marca }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Proveedores:</strong></div>
              <div class="col-sm-9">
                <div v-if="selectedMarca.proveedores && selectedMarca.proveedores.length > 0">
                  <span v-for="proveedor in selectedMarca.proveedores" :key="proveedor.id" 
                        class="badge bg-info me-1 mb-1">
                    {{ proveedor.razon_social }}
                  </span>
                </div>
                <div v-else class="text-muted">Sin proveedores asignados</div>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Creado en:</strong></div>
              <div class="col-sm-9">{{ selectedMarca.created_at }}</div>
            </div>
            <div class="row mb-3">
              <div class="col-sm-3"><strong>Actualizado en:</strong></div>
              <div class="col-sm-9">{{ selectedMarca.updated_at }}</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="marcaModal" tabindex="-1" aria-labelledby="marcaModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="marcaModalLabel">{{ isEditing ? 'Editar Marca' : 'Crear Marca' }}</h5>
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
                <label for="marca" class="form-label">Marca*:</label>
                <input type="text" v-model="form.marca" id="marca" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="proveedores" class="form-label">Proveedores:</label>
                <div class="proveedor-selection">
                  <!-- Selected Proveedores -->
                  <div class="selected-proveedores mb-2">
                    <div v-for="(proveedor, index) in selectedProveedores" :key="proveedor.id" 
                         class="badge bg-info px-2 py-1 me-1 mb-1 selected-proveedor">
                      {{ proveedor.razon_social }}
                      <button type="button" @click="removeProveedor(index)" class="btn-close btn-close-white ms-1" 
                              aria-label="Eliminar" style="font-size: 0.5rem;">
                      </button>
                    </div>
                  </div>
                  
                  <!-- Search Input -->
                  <div class="position-relative">
                    <input 
                      type="text" 
                      v-model="proveedorSearch"
                      @input="searchProveedores"
                      @focus="showProveedorResults = true"
                      @keydown.down.prevent="navigateResults('down')"
                      @keydown.up.prevent="navigateResults('up')"
                      @keydown.enter.prevent="selectProveedor(selectedResultIndex)"
                      @keydown.escape="showProveedorResults = false"
                      class="form-control" 
                      placeholder="Buscar proveedores..."
                      autocomplete="off"
                    >
                    
                    <!-- Dropdown Results -->
                    <div v-if="showProveedorResults && filteredProveedores.length > 0" class="proveedor-results">
                      <div 
                        v-for="(proveedor, index) in filteredProveedores" 
                        :key="proveedor.id" 
                        class="proveedor-result-item"
                        :class="{ 'active': index === selectedResultIndex }"
                        @click="addProveedor(proveedor)"
                        @mouseover="selectedResultIndex = index"
                      >
                        {{ proveedor.razon_social }}
                      </div>
                    </div>
                    
                    <!-- No Results Message -->
                    <div v-if="showProveedorResults && proveedorSearch && filteredProveedores.length === 0" class="proveedor-no-results">
                      No se encontraron proveedores
                    </div>
                  </div>
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
      marcas: [],
      allProveedores: [],
      form: {
        marca: '',
        proveedores: [] // Will store IDs of selected proveedores
      },
      selectedMarca: {},
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
        marca: ''
      },
      loading: true,
      proveedorSearch: '',
      filteredProveedores: [],
      selectedProveedores: [],
      showProveedorResults: false,
      selectedResultIndex: -1,
      debounceTimer: null
    };
  },
  methods: {
    fetchMarcas(url = '/api/marcas') {
      this.loading = true;
      const params = {
        page: this.pagination.current_page,
        per_page: this.pagination.per_page,
        ...this.filters
      };
      
      axios.get(url, { params })
        .then(response => {
          this.marcas = response.data.data;
          this.pagination = {
            ...this.pagination,
            total: response.data.total,
            last_page: response.data.last_page,
            next_page_url: response.data.next_page_url,
            prev_page_url: response.data.prev_page_url
          };
          console.log('Fetched marcas:', this.marcas);
        })
        .catch(error => {
          console.error('Error fetching marcas:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchProveedores() {
      axios.get('/api/proveedores?all=true')
        .then(response => {
          this.allProveedores = response.data.data || response.data;
          console.log('Fetched proveedores:', this.allProveedores);
        })
        .catch(error => {
          console.error('Error fetching proveedores:', error);
        });
    },
    searchProveedores() {
      if (this.debounceTimer) clearTimeout(this.debounceTimer);
      
      this.debounceTimer = setTimeout(() => {
        const searchTerm = this.proveedorSearch.toLowerCase().trim();
        
        // Filter out already selected proveedores
        const selectedIds = this.selectedProveedores.map(p => p.id);
        
        this.filteredProveedores = this.allProveedores
          .filter(p => !selectedIds.includes(p.id) && 
                      (p.razon_social.toLowerCase().includes(searchTerm) || 
                       p.ruc.includes(searchTerm)))
          .slice(0, 10); // Limit to 10 results
          
        this.selectedResultIndex = this.filteredProveedores.length > 0 ? 0 : -1;
        this.showProveedorResults = true;
      }, 300);
    },
    addProveedor(proveedor) {
      if (!this.selectedProveedores.some(p => p.id === proveedor.id)) {
        this.selectedProveedores.push(proveedor);
        this.form.proveedores = this.selectedProveedores.map(p => p.id);
      }
      this.proveedorSearch = '';
      this.showProveedorResults = false;
      this.filteredProveedores = [];
    },
    removeProveedor(index) {
      this.selectedProveedores.splice(index, 1);
      this.form.proveedores = this.selectedProveedores.map(p => p.id);
    },
    navigateResults(direction) {
      if (this.filteredProveedores.length === 0) return;
      
      if (direction === 'down') {
        this.selectedResultIndex = (this.selectedResultIndex + 1) % this.filteredProveedores.length;
      } else if (direction === 'up') {
        this.selectedResultIndex = this.selectedResultIndex <= 0 ? 
          this.filteredProveedores.length - 1 : this.selectedResultIndex - 1;
      }
    },
    selectProveedor(index) {
      if (index >= 0 && index < this.filteredProveedores.length) {
        this.addProveedor(this.filteredProveedores[index]);
      }
    },
    showCreateForm() {
      this.resetForm();
      this.showForm = true;
      this.isEditing = false;
      const modal = new Modal(document.getElementById('marcaModal'));
      modal.show();
    },
    viewMarca(marca) {
      this.selectedMarca = marca;
      const modal = new Modal(document.getElementById('viewMarcaModal'));
      modal.show();
    },
    editMarca(marca) {
      this.form = { 
        marca: marca.marca,
        proveedores: marca.proveedores ? marca.proveedores.map(p => p.id) : []
      };
      
      this.selectedProveedores = marca.proveedores || [];
      
      this.showForm = true;
      this.isEditing = true;
      this.editingId = marca.id;
      const modal = new Modal(document.getElementById('marcaModal'));
      modal.show();
    },
    async submitForm() {
      this.formErrors = [];
      try {
        let response;
        
        if (this.isEditing) {
          response = await axios.put(`/api/marcas/${this.editingId}`, this.form);
        } else {
          response = await axios.post('/api/marcas', this.form);
        }
        
        this.alertMessage = this.isEditing ? 'Marca actualizada con éxito.' : 'Marca creada con éxito.';
        this.fetchMarcas();
        this.resetForm();
        this.closeModal();
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Submit form error:', error.response ? error.response.data : error);
        if (error.response && error.response.status === 422) {
          this.formErrors = Object.values(error.response.data.errors).flat();
        } else {
          this.formErrors = ['Ha ocurrido un error al procesar la solicitud.'];
        }
      }
    },
    confirmDelete(id) {
      if (confirm('¿Está seguro de que desea eliminar esta marca?')) {
        this.deleteMarca(id);
      }
    },
    async deleteMarca(id) {
      try {
        const response = await axios.delete(`/api/marcas/${id}`);
        this.fetchMarcas();
        this.alertMessage = 'Marca eliminada con éxito.';
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Delete marca error:', error.response ? error.response.data : error);
        this.alertMessage = 'Error al eliminar la marca.';
      }
    },
    resetForm() {
      this.form = {
        marca: '',
        proveedores: []
      };
      this.selectedProveedores = [];
      this.proveedorSearch = '';
      this.isEditing = false;
      this.editingId = null;
      this.formErrors = [];
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('marcaModal'));
      if (modal) {
        modal.hide();
      }
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchMarcas(url);
      }
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchMarcas();
    },
    resetFilters() {
      this.filters = {
        marca: ''
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
    this.fetchMarcas();
    this.fetchProveedores();
    
    // Close proveedor dropdown when clicking outside
    document.addEventListener('click', (e) => {
      const isClickInsideResults = e.target.closest('.proveedor-results');
      const isClickInsideInput = e.target.closest('.position-relative input');
      
      if (!isClickInsideResults && !isClickInsideInput) {
        this.showProveedorResults = false;
      }
    });
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
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

.proveedores-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.proveedor-selection {
  position: relative;
}

.selected-proveedores {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.selected-proveedor {
  display: inline-flex;
  align-items: center;
  font-size: 0.875rem;
}

.proveedor-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0 0 4px 4px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.proveedor-result-item {
  padding: 8px 12px;
  cursor: pointer;
}

.proveedor-result-item:hover,
.proveedor-result-item.active {
  background-color: #f8f9fa;
}

.proveedor-no-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 0 0 4px 4px;
  padding: 8px 12px;
  color: #6c757d;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}

/* Ensure text doesn't overflow in selected proveedores badges */
.selected-proveedor .btn-close {
  width: 0.5em;
  height: 0.5em;
}
</style>
