<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2>Gestión de Stock</h2>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Producto</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>

    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>

    <table class="table table-striped">
      <thead>
        <tr>
          <th>Tipo de Producto</th>
          <th>Descripción</th>
          <th>Marca</th>
          <th>Material</th>
          <th>Precio</th>
          <th>En Stock</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative select-wrapper">
              <select v-model="filters.tipo_producto" @change="applyFilters" class="form-control">
                <option value="">Todos</option>
                <option value="l">Lentes de Sol</option>
                <option value="m">Montura</option>
                <option value="c">Lentes de Contacto</option>
                <option value="u">Lunas</option>
              </select>
              <i class="fas fa-chevron-down select-arrow"></i>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.descripcion" @input="applyFilters" class="form-control" placeholder="Filtrar por Descripción">
              <button v-if="filters.descripcion" @click="clearFilter('descripcion')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.marca" @input="applyFilters" class="form-control" placeholder="Filtrar por Marca">
              <button v-if="filters.marca" @click="clearFilter('marca')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.material" @input="applyFilters" class="form-control" placeholder="Filtrar por Material">
              <button v-if="filters.material" @click="clearFilter('material')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="number" v-model="filters.precio" @input="applyFilters" class="form-control" placeholder="Filtrar por Precio">
              <button v-if="filters.precio" @click="clearFilter('precio')" class="btn-clear">
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
          <td colspan="7" class="text-center">Cargando...</td>
        </tr>
        <tr v-else-if="!items || items.length === 0">
          <td colspan="7" class="text-center">No hay productos en el stock.</td>
        </tr>
        <tr v-else v-for="item in items" :key="item.id">
          <td>{{ getTipoProductoLabel(item.tipo_producto) }}</td>
          <td>{{ item.descripcion || 'Sin descripción' }}</td>
          <td>{{ item.marca ? item.marca.marca : 'N/A' }}</td>
          <td>{{ item.material ? item.material.material : 'N/A' }}</td>
          <td>S/. {{ item.precio }}</td>
          <td class="text-center">
            <span class="badge rounded-pill" :class="item.num_stock > 0 ? 'bg-success' : 'bg-danger'">
              {{ item.num_stock }}
            </span>
          </td>
          <td>
            <button @click="viewDetails(item)" class="btn btn-info btn-sm me-2" title="Ver detalles">
              <i class="fas fa-eye"></i>
            </button>
            <button @click="editItem(item)" class="btn btn-warning btn-sm me-2" title="Editar">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(item.id)" class="btn btn-danger btn-sm" title="Eliminar">
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
    <div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="stockModalLabel">{{ isEditing ? 'Editar Producto' : 'Crear Producto' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitForm" enctype="multipart/form-data">
              <!-- Row 1: Tipo de producto only -->
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="tipo_producto" class="form-label">Tipo de Producto*:</label>
                  <div class="position-relative select-wrapper">
                    <select v-model="form.tipo_producto" id="tipo_producto" class="form-control" required>
                      <option value="" disabled selected>Seleccione un tipo de producto</option>
                      <option value="l">Lentes de Sol</option>
                      <option value="m">Montura</option>
                      <option value="c">Lentes de Contacto</option>
                      <option value="u">Lunas</option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
                  </div>
                </div>
                <div class="col-md-6">
                  <label for="num_stock" class="form-label">Cantidad en Stock*:</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-boxes"></i>
                    </span>
                    <input type="number" v-model="form.num_stock" id="num_stock" class="form-control" min="0" required>
                  </div>
                </div>
              </div>

              <!-- Row 2: Descripcion in its own row -->
              <div class="row mb-3">
                <div class="col-12">
                  <label for="descripcion" class="form-label">Descripción:</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-tag"></i>
                    </span>
                    <input type="text" v-model="form.descripcion" id="descripcion" class="form-control">
                  </div>
                </div>
              </div>

              <!-- Row 3: Código and Precio -->
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="codigo" class="form-label">Código:</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="fas fa-barcode"></i>
                    </span>
                    <input type="text" v-model="form.codigo" id="codigo" class="form-control">
                  </div>
                </div>
                <div class="col-md-6">
                  <label for="precio" class="form-label">Precio*:</label>
                  <div class="input-group">
                    <span class="input-group-text">S/.</span>
                    <input type="number" v-model="form.precio" id="precio" class="form-control" step="0.01" required>
                  </div>
                </div>
              </div>

              <!-- Row 4: Género and Material -->
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="genero" class="form-label">Género:</label>
                  <div class="position-relative select-wrapper">
                    <select v-model="form.genero" id="genero" class="form-control">
                      <option value="" selected>Seleccione un género</option>
                      <option value="H">Hombre</option>
                      <option value="M">Mujer</option>
                      <option value="N">Niño</option>
                      <option value="U">Unisex</option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
                  </div>
                </div>
                <div class="col-md-6">
                  <label for="material" class="form-label">Material:</label>
                  <div class="d-flex">
                    <div class="position-relative select-wrapper flex-grow-1">
                      <select v-model="form.id_material" id="material" class="form-control">
                        <option value="" disabled selected>Seleccione un material</option>
                        <option v-for="material in materiales" :key="material.id" :value="material.id">
                          {{ material.material }}
                        </option>
                      </select>
                      <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                    <button type="button" @click="showMaterialForm" class="btn btn-sm btn-outline-primary ms-2" title="Crear nuevo material">
                      <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" @click="editSelectedMaterial" class="btn btn-sm ms-2" 
                      :class="form.id_material ? 'btn-outline-warning' : 'btn-outline-secondary'"
                      :disabled="!form.id_material" title="Editar material seleccionado">
                      <i class="fas fa-pencil-alt"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Row 5: Fecha de compra and Marca (instead of Proveedor) -->
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="fecha_compra" class="form-label">Fecha de Compra:</label>
                  <input type="date" v-model="form.fecha_compra" id="fecha_compra" class="form-control">
                </div>
                <div class="col-md-6">
                  <label for="marca" class="form-label">Marca*:</label>
                  <div class="d-flex">
                    <div class="position-relative select-wrapper flex-grow-1">
                      <select v-model="form.id_marca" id="marca" class="form-control" required>
                        <option value="" disabled selected>Seleccione una marca</option>
                        <option v-for="marca in marcas" :key="marca.id" :value="marca.id">
                          {{ marca.marca }}
                        </option>
                      </select>
                      <i class="fas fa-chevron-down select-arrow"></i>
                    </div>
                    <button type="button" @click="showMarcaForm" class="btn btn-sm btn-outline-primary ms-2" title="Crear nueva marca">
                      <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" @click="editSelectedMarca" class="btn btn-sm ms-2" 
                      :class="form.id_marca ? 'btn-outline-warning' : 'btn-outline-secondary'"
                      :disabled="!form.id_marca" title="Editar marca seleccionada">
                      <i class="fas fa-pencil-alt"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Row 6: Imagen upload -->
              <div class="mb-3">
                <label for="imagen" class="form-label">Imagen{{ isEditing ? ' (dejar en blanco para mantener la actual)' : '' }}:</label>
                <input 
                  type="file" 
                  @change="handleImageUpload" 
                  id="imagen" 
                  ref="fileInput"
                  class="form-control" 
                >
              </div>

              <!-- Row 7: Image preview -->
              <div v-if="imagePreview" class="mb-3">
                <img :src="imagePreview" alt="Preview" style="max-height: 200px;" class="preview-image">
              </div>

              <!-- Form buttons -->
              <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">{{ isEditing ? 'Actualizar' : 'Guardar' }}</button>
                <button type="button" @click="closeModal" class="btn btn-secondary">Cerrar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Marca Modal -->
    <div class="modal fade" id="marcaModal" tabindex="-1" aria-labelledby="marcaModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="marcaModalLabel">{{ isEditingMarca ? 'Editar Marca' : 'Crear Marca' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitMarcaForm">
              <div v-if="marcaFormErrors.length" class="alert alert-danger">
                <ul class="mb-0">
                  <li v-for="(error, index) in marcaFormErrors" :key="index">{{ error }}</li>
                </ul>
              </div>
              
              <div class="mb-3">
                <label for="marcaNombre" class="form-label">Nombre de la Marca*:</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-trademark"></i>
                  </span>
                  <input type="text" v-model="marcaForm.marca" id="marcaNombre" class="form-control" required>
                </div>
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
                      @focus="showAllProveedores"
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
                <button type="submit" class="btn btn-primary">{{ isEditingMarca ? 'Actualizar' : 'Guardar' }}</button>
                <button type="button" @click="closeMarcaModal" class="btn btn-secondary">Cerrar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Material Modal -->
    <div class="modal fade" id="materialModal" tabindex="-1" aria-labelledby="materialModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="materialModalLabel">{{ isEditingMaterial ? 'Editar Material' : 'Crear Material' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitMaterialForm">
              <div v-if="materialFormErrors.length" class="alert alert-danger">
                <ul class="mb-0">
                  <li v-for="(error, index) in materialFormErrors" :key="index">{{ error }}</li>
                </ul>
              </div>
              
              <div class="mb-3">
                <label for="materialNombre" class="form-label">Nombre del Material*:</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-cube"></i>
                  </span>
                  <input type="text" v-model="materialForm.material" id="materialNombre" class="form-control" required>
                </div>
              </div>
              
              <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">{{ isEditingMaterial ? 'Actualizar' : 'Guardar' }}</button>
                <button type="button" @click="closeMaterialModal" class="btn btn-secondary">Cerrar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detailsModalLabel">Detalles del Producto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="detailItem">
              <div class="row mb-4">
                <div class="col-md-6 text-center mb-3">
                  <img v-if="detailItem.imagen" :src="`${baseUrl}/images/stock/${detailItem.imagen}`" alt="Producto" class="img-fluid details-image" style="max-height: 250px;">
                  <div v-else class="no-image-placeholder">Sin imagen</div>
                </div>
                <div class="col-md-6">
                  <h4>{{ detailItem.descripcion || 'Sin descripción' }}</h4>
                  <p class="mb-1"><strong>Código:</strong> {{ detailItem.codigo || 'N/A' }}</p>
                  <p class="mb-1"><strong>Precio:</strong> S/. {{ detailItem.precio }}</p>
                  <p class="mb-1"><strong>Stock:</strong> {{ detailItem.num_stock }}</p>
                  <p class="mb-1"><strong>Tipo de Producto:</strong> {{ getTipoProductoLabel(detailItem.tipo_producto) }}</p>
                  <p class="mb-1"><strong>Género:</strong> {{ getGeneroLabel(detailItem.genero) }}</p>
                </div>
              </div>
              <hr>
              <div class="row">
                <div class="col-md-6">
                  <p class="mb-1"><strong>Material:</strong> {{ detailItem.material ? detailItem.material.material : 'N/A' }}</p>
                  <p class="mb-1"><strong>Fecha de Compra:</strong> {{ detailItem.fecha_compra || 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                  <p class="mb-1"><strong>Marca:</strong> {{ detailItem.marca ? detailItem.marca.marca : 'N/A' }}</p>
                  <p class="mb-1"><strong>ID del Producto:</strong> {{ detailItem.id }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
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
      items: [],
      marcas: [],
      materiales: [],
      loading: true,
      form: {
        tipo_producto: '',
        descripcion: '',
        precio: '',
        id_marca: '',
        imagen: null,
        codigo: '',
        genero: '',
        id_material: '',
        fecha_compra: '',
        num_stock: 0
      },
      imagePreview: null,
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
        descripcion: '',
        precio: '',
        tipo_producto: '',
        marca: '',
        material: ''
      },
      detailItem: null,
      marcaForm: {
        marca: '',
        proveedores: []
      },
      isEditingMarca: false,
      editingMarcaId: null,
      marcaFormErrors: [],
      allProveedores: [],
      proveedorSearch: '',
      filteredProveedores: [],
      selectedProveedores: [],
      showProveedorResults: false,
      selectedResultIndex: -1,
      debounceTimer: null,
      materialForm: {
        material: ''
      },
      isEditingMaterial: false,
      editingMaterialId: null,
      materialFormErrors: [],
    };
  },
  methods: {
    fetchItems(url = '/api/stock') {
      this.loading = true;
      const params = {
        descripcion: this.filters.descripcion || null,
        precio: this.filters.precio || null,
        tipo_producto: this.filters.tipo_producto || null,
        marca: this.filters.marca || null,
        material: this.filters.material || null,
        page: this.pagination.current_page
      };
      
      axios.get(url, { params })
        .then(response => {
          console.log('Response:', response.data);
          this.items = response.data.data;
          this.pagination = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total,
            last_page: response.data.last_page,
            next_page_url: response.data.next_page_url,
            prev_page_url: response.data.prev_page_url
          };
        })
        .catch(error => {
          console.error('Error fetching items:', error);
          this.alertMessage = 'Error al cargar los productos.';
        })
        .finally(() => {
          this.loading = false;
        });
    },
    fetchMarcas() {
      axios.get('/api/marcas', { params: { all: true, per_page: 1000 } }) // Request a high number per page to effectively get all
        .then(response => {
          this.marcas = response.data.data;
          console.log('Fetched all marcas:', this.marcas.length);
        })
        .catch(error => {
          console.error('Error fetching marcas:', error);
        });
    },
    fetchMateriales() {
      axios.get('/api/materiales', { params: { all: true, per_page: 1000 } }) // Request a high number per page to effectively get all
        .then(response => {
          this.materiales = response.data.data || response.data;
          console.log('Fetched all materiales:', this.materiales.length);
        })
        .catch(error => {
          console.error('Error fetching materiales:', error);
        });
    },
    handleImageUpload(event) {
      const file = event.target.files[0];
      this.form.imagen = file;
      this.imagePreview = URL.createObjectURL(file);
    },
    showCreateForm() {
      this.resetForm();
      this.showForm = true;
      this.isEditing = false;
      new Modal(document.getElementById('stockModal')).show();
    },
    editItem(item) {
      this.form = {
        descripcion: item.descripcion || '',
        precio: item.precio,
        tipo_producto: item.tipo_producto,
        id_marca: item.id_marca,
        imagen: null,
        codigo: item.codigo || '',
        genero: item.genero || '',
        id_material: item.id_material || '',
        fecha_compra: item.fecha_compra || '',
        num_stock: item.num_stock || 0
      };
      this.imagePreview = item.imagen ? `${this.baseUrl}/images/stock/${item.imagen}` : null;
      this.showForm = true;
      this.isEditing = true;
      this.editingId = item.id;
      new Modal(document.getElementById('stockModal')).show();
    },
    async submitForm() {
      const formData = new FormData();
      formData.append('descripcion', this.form.descripcion || '');
      formData.append('precio', this.form.precio);
      formData.append('tipo_producto', this.form.tipo_producto);
      formData.append('id_marca', this.form.id_marca);
      formData.append('num_stock', this.form.num_stock);
      
      // Append new fields
      if (this.form.codigo) formData.append('codigo', this.form.codigo);
      if (this.form.genero) formData.append('genero', this.form.genero);
      if (this.form.id_material) formData.append('id_material', this.form.id_material);
      if (this.form.fecha_compra) formData.append('fecha_compra', this.form.fecha_compra);
      
      if (this.form.imagen instanceof File) {
        formData.append('imagen', this.form.imagen);
      }

      try {
        let response;
        if (this.isEditing) {
          formData.append('_method', 'PUT');
          response = await axios.post(`/api/stock/${this.editingId}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
        } else {
          response = await axios.post('/api/stock', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          });
        }

        this.alertMessage = this.isEditing ? 'Producto actualizado con éxito.' : 'Producto creado con éxito.';
        this.fetchItems();
        this.resetForm();
        this.closeModal();
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Error details:', error.response?.data);
        this.alertMessage = error.response?.data?.error || 'Error al ' + (this.isEditing ? 'actualizar' : 'crear') + ' el producto.';
      }
    },
    confirmDelete(id) {
      if (confirm('¿Está seguro de que desea eliminar este producto?')) {
        this.deleteItem(id);
      }
    },
    deleteItem(id) {
      axios.delete(`/api/stock/${id}`)
        .then(() => {
          this.fetchItems();
          this.alertMessage = 'Producto eliminado con éxito.';
          setTimeout(() => this.alertMessage = '', 5000);
        })
        .catch(error => console.error(error));
    },
    resetForm() {
      this.form = {
        tipo_producto: '',
        descripcion: '',
        precio: '',
        id_marca: '',
        imagen: null,
        codigo: '',
        genero: '',
        id_material: '',
        fecha_compra: '',
        num_stock: 0
      };
      this.imagePreview = null;
      this.isEditing = false;
      this.editingId = null;
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = '';
      }
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('stockModal'));
      if (modal) {
        modal.hide();
        this.resetForm();
      }
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchItems(url);
      }
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchItems();
    },
    resetFilters() {
      this.filters = {
        descripcion: '',
        precio: '',
        tipo_producto: '',
        marca: '',
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
    getTipoProductoLabel(tipo) {
      switch (tipo) {
        case 'l':
          return 'Lentes de Sol';
        case 'm':
          return 'Montura';
        case 'c':
          return 'Lentes de Contacto';
        case 'u':
          return 'Lunas';
        default:
          return tipo;
      }
    },
    viewDetails(item) {
      // If we need to fetch additional details that might not be in the item
      axios.get(`/api/stock/${item.id}`)
        .then(response => {
          this.detailItem = response.data;
          new Modal(document.getElementById('detailsModal')).show();
        })
        .catch(error => {
          console.error('Error fetching item details:', error);
          this.alertMessage = 'Error al cargar los detalles del producto.';
        });
    },
    getGeneroLabel(genero) {
      switch (genero) {
        case 'H':
          return 'Hombre';
        case 'M':
          return 'Mujer';
        case 'N':
          return 'Niño';
        case 'U':
          return 'Unisex';
        default:
          return 'N/A';
      }
    },
    // Marca related methods
    showMarcaForm() {
      this.resetMarcaForm();
      this.isEditingMarca = false;
      
      // Ensure the stock modal is kept open by storing its instance
      this.stockModalInstance = Modal.getInstance(document.getElementById('stockModal'));
      
      // Create and show the marca modal
      this.marcaModalInstance = new Modal(document.getElementById('marcaModal'));
      this.marcaModalInstance.show();
    },
    
    resetMarcaForm() {
      this.marcaForm = {
        marca: '',
        proveedores: []
      };
      this.selectedProveedores = [];
      this.isEditingMarca = false;
      this.editingMarcaId = null;
      this.marcaFormErrors = [];
    },
    
    editSelectedMarca() {
      if (!this.form.id_marca) return;
      
      // Find the selected marca
      const selectedMarca = this.marcas.find(m => m.id === this.form.id_marca);
      if (!selectedMarca) return;
      
      // Set up form for editing
      this.marcaForm = { 
        marca: selectedMarca.marca,
        proveedores: selectedMarca.proveedores ? selectedMarca.proveedores.map(p => p.id) : []
      };
      
      this.selectedProveedores = selectedMarca.proveedores || [];
      this.isEditingMarca = true;
      this.editingMarcaId = selectedMarca.id;
      
      // Store stock modal instance
      this.stockModalInstance = Modal.getInstance(document.getElementById('stockModal'));
      
      // Show marca modal
      this.marcaModalInstance = new Modal(document.getElementById('marcaModal'));
      this.marcaModalInstance.show();
    },
    
    closeMarcaModal() {
      if (this.marcaModalInstance) {
        this.marcaModalInstance.hide();
      }
    },
    
    async submitMarcaForm() {
      this.marcaFormErrors = [];
      try {
        let response;
        
        if (this.isEditingMarca) {
          response = await axios.put(`/api/marcas/${this.editingMarcaId}`, this.marcaForm);
        } else {
          response = await axios.post('/api/marcas', this.marcaForm);
        }
        
        // Close the marca modal
        this.closeMarcaModal();
        
        // Show success message
        this.alertMessage = this.isEditingMarca ? 'Marca actualizada con éxito.' : 'Marca creada con éxito.';
        
        // Fetch updated list of marcas
        await this.fetchMarcas();
        
        // Set the newly created/edited marca as the selected marca
        const newMarca = response.data.id || (response.data.marca && response.data.marca.id) || this.editingMarcaId;
        if (newMarca) {
          this.form.id_marca = newMarca;
        }
        
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Error submitting marca form:', error.response?.data);
        if (error.response && error.response.status === 422) {
          this.marcaFormErrors = Object.values(error.response.data.errors).flat();
        } else {
          this.marcaFormErrors = ['Ha ocurrido un error al procesar la solicitud.'];
        }
      }
    },
    
    // Proveedores methods for marca form
    fetchProveedores() {
      axios.get('/api/proveedores?all=true')
        .then(response => {
          this.allProveedores = response.data.data || response.data;
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
        
        // Show all available providers when search term is empty
        if (searchTerm === '') {
          this.filteredProveedores = this.allProveedores
            .filter(p => !selectedIds.includes(p.id))
            .slice(0, 10); // Limit to 10 results
        } else {
          this.filteredProveedores = this.allProveedores
            .filter(p => !selectedIds.includes(p.id) && 
                        (p.razon_social.toLowerCase().includes(searchTerm) || 
                         (p.ruc && p.ruc.includes(searchTerm))))
            .slice(0, 10); // Limit to 10 results
        }
        
        this.selectedResultIndex = this.filteredProveedores.length > 0 ? 0 : -1;
        this.showProveedorResults = true;
      }, 300);
    },
    
    showAllProveedores() {
      const selectedIds = this.selectedProveedores.map(p => p.id);
      
      this.filteredProveedores = this.allProveedores
        .filter(p => !selectedIds.includes(p.id))
        .slice(0, 10); // Limit to 10 results
      
      this.selectedResultIndex = this.filteredProveedores.length > 0 ? 0 : -1;
      this.showProveedorResults = true;
    },
    
    addProveedor(proveedor) {
      if (!this.selectedProveedores.some(p => p.id === proveedor.id)) {
        this.selectedProveedores.push(proveedor);
        this.marcaForm.proveedores = this.selectedProveedores.map(p => p.id);
      }
      this.proveedorSearch = '';
      this.showProveedorResults = false;
      this.filteredProveedores = [];
    },
    
    removeProveedor(index) {
      this.selectedProveedores.splice(index, 1);
      this.marcaForm.proveedores = this.selectedProveedores.map(p => p.id);
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
    // Material related methods
    showMaterialForm() {
      this.resetMaterialForm();
      this.isEditingMaterial = false;
      
      // Ensure the stock modal is kept open by storing its instance
      this.stockModalInstance = Modal.getInstance(document.getElementById('stockModal'));
      
      // Create and show the material modal
      this.materialModalInstance = new Modal(document.getElementById('materialModal'));
      this.materialModalInstance.show();
    },
    
    resetMaterialForm() {
      this.materialForm = {
        material: ''
      };
      this.isEditingMaterial = false;
      this.editingMaterialId = null;
      this.materialFormErrors = [];
    },
    
    editSelectedMaterial() {
      if (!this.form.id_material) return;
      
      // Find the selected material
      const selectedMaterial = this.materiales.find(m => m.id === this.form.id_material);
      if (!selectedMaterial) return;
      
      // Set up form for editing
      this.materialForm = { 
        material: selectedMaterial.material
      };
      
      this.isEditingMaterial = true;
      this.editingMaterialId = selectedMaterial.id;
      
      // Store stock modal instance
      this.stockModalInstance = Modal.getInstance(document.getElementById('stockModal'));
      
      // Show material modal
      this.materialModalInstance = new Modal(document.getElementById('materialModal'));
      this.materialModalInstance.show();
    },
    
    closeMaterialModal() {
      if (this.materialModalInstance) {
        this.materialModalInstance.hide();
      }
    },
    
    async submitMaterialForm() {
      this.materialFormErrors = [];
      try {
        let response;
        
        if (this.isEditingMaterial) {
          response = await axios.put(`/api/materiales/${this.editingMaterialId}`, this.materialForm);
        } else {
          response = await axios.post('/api/materiales', this.materialForm);
        }
        
        // Close the material modal
        this.closeMaterialModal();
        
        // Show success message
        this.alertMessage = this.isEditingMaterial ? 'Material actualizado con éxito.' : 'Material creado con éxito.';
        
        // Fetch updated list of materiales
        await this.fetchMateriales();
        
        // Set the newly created/edited material as the selected material
        const newMaterial = response.data.id || (response.data.material && response.data.material.id) || this.editingMaterialId;
        if (newMaterial) {
          this.form.id_material = newMaterial;
        }
        
        setTimeout(() => this.alertMessage = '', 5000);
      } catch (error) {
        console.error('Error submitting material form:', error.response?.data);
        if (error.response && error.response.status === 422) {
          this.materialFormErrors = Object.values(error.response.data.errors).flat();
        } else {
          this.materialFormErrors = ['Ha ocurrido un error al procesar la solicitud.'];
        }
      }
    },
  },
  mounted() {
    this.fetchItems();
    this.fetchMarcas();
    this.fetchMateriales();
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
    document.removeEventListener('click', () => {});
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

.preview-image {
  max-width: 100%;
  object-fit: contain;
}

/* Update select-wrapper styles to apply to all select inputs */
.select-wrapper {
  position: relative;
  width: 100%;
}

.select-wrapper select {
  width: 100%;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  padding-right: 30px;
  background-color: #fff;  /* Ensure background is white */
}

.select-arrow {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #6c757d;
}

.details-image {
  object-fit: contain;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 5px;
}

.no-image-placeholder {
  height: 250px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed #ccc;
  color: #999;
  font-style: italic;
}

/* Proveedor selection styles */
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
