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
                  <input type="number" v-model="form.num_stock" id="num_stock" class="form-control" min="0" required>
                </div>
              </div>

              <!-- Row 2: Descripcion in its own row -->
              <div class="row mb-3">
                <div class="col-12">
                  <label for="descripcion" class="form-label">Descripción:</label>
                  <input type="text" v-model="form.descripcion" id="descripcion" class="form-control">
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
                  <div class="position-relative select-wrapper">
                    <select v-model="form.id_material" id="material" class="form-control">
                      <option value="" selected>Seleccione un material</option>
                      <option v-for="material in materiales" :key="material.id" :value="material.id">
                        {{ material.material }}
                      </option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
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
                  <div class="position-relative select-wrapper">
                    <select v-model="form.id_marca" id="marca" class="form-control" required>
                      <option value="" disabled selected>Seleccione una marca</option>
                      <option v-for="marca in marcas" :key="marca.id" :value="marca.id">
                        {{ marca.marca }}
                      </option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
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
      axios.get('/api/marcas')
        .then(response => {
          this.marcas = response.data.data;
        })
        .catch(error => {
          console.error('Error fetching marcas:', error);
        });
    },
    fetchMateriales() {
      axios.get('/api/materiales')
        .then(response => {
          this.materiales = response.data.data || response.data;
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
  },
  mounted() {
    this.fetchItems();
    this.fetchMarcas();
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
</style>
