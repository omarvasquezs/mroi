<template>
  <div class="container-fluid mt-4">
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h2 class="mb-4">Catálogo de Productos</h2>

    <div class="row">
      <!-- Filters Section -->
      <div class="col-md-3">
        <div class="card sticky-top" style="top: 4rem;">
          <div class="card-body filter-card-body">
            <h5 class="card-title mb-3"></h5>
            <div class="mb-3">
              <label class="form-label">Búsqueda</label>
              <input type="text" v-model="filters.producto" class="form-control" placeholder="Buscar productos...">
            </div>
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Precio mínimo</label>
                <div class="input-group">
                  <span class="input-group-text">S/.</span>
                  <input type="number" v-model.number="filters.precio_min" class="form-control" placeholder="Mín"
                    min="0" step="0.01">
                </div>
              </div>
              <div class="col">
                <label class="form-label">Precio máximo</label>
                <div class="input-group">
                  <span class="input-group-text">S/.</span>
                  <input type="number" v-model.number="filters.precio_max" class="form-control" placeholder="Máx"
                    min="0" step="0.01">
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label d-flex justify-content-between align-items-center" role="button"
                @click="toggleTipoProducto">
                Tipo de Producto
                <i class="fas fa-chevron-down" :class="{ 'rotate-icon': showTipoProducto }"></i>
              </label>
              <div v-show="showTipoProducto">
                <div class="form-check custom-form-check" @click.prevent="toggleCheckbox('l')">
                  <input class="form-check-input" type="checkbox" value="l" id="lentes" v-model="filters.tipo_producto">
                  <label class="form-check-label no-select w-100" for="lentes">
                    Lentes
                  </label>
                </div>
                <div class="form-check custom-form-check" @click.prevent="toggleCheckbox('m')">
                  <input class="form-check-input" type="checkbox" value="m" id="montura"
                    v-model="filters.tipo_producto">
                  <label class="form-check-label no-select w-100" for="montura">
                    Montura
                  </label>
                </div>
                <div class="form-check custom-form-check" @click.prevent="toggleCheckbox('c')">
                  <input class="form-check-input" type="checkbox" value="c" id="contacto"
                    v-model="filters.tipo_producto">
                  <label class="form-check-label no-select w-100" for="contacto">
                    Lentes de Contacto
                  </label>
                </div>
                <div class="form-check custom-form-check" @click.prevent="toggleCheckbox('u')">
                  <input class="form-check-input" type="checkbox" value="u" id="lunas" v-model="filters.tipo_producto">
                  <label class="form-check-label no-select w-100" for="lunas">
                    Lunas
                  </label>
                </div>
              </div>
            </div>
            <button @click="resetFilters" class="btn btn-danger btn-sm w-100">
              <i class="fas fa-trash-alt"></i> Limpiar filtros
            </button>
          </div>
        </div>
      </div>

      <!-- Products Grid Section -->
      <div class="col-md-9">
        <!-- Loading State -->
        <div v-if="loading && !productos.length" class="text-center my-5">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>

        <!-- Products Grid -->
        <template v-else>
          <div v-if="!productos.length" class="text-center my-5">
            <p>No se encontraron productos.</p>
          </div>
          <div v-else class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <div v-for="producto in productos" :key="producto.id" class="col">
              <div :class="['card h-100 product-card', { 'animate-add': producto.id === recentlyAddedProductId }]">
                <div class="stock-badge" :class="producto.num_stock > 0 ? 'in-stock' : 'out-of-stock'">
                  {{ producto.num_stock > 0 ? `En stock: ${producto.num_stock}` : 'Agotado' }}
                </div>
                <img :src="`${baseUrl}/images/stock/${producto.imagen}`" class="card-img-top product-image"
                  :alt="producto.producto">
                <div class="card-body product-card-body">
                  <h5 class="card-title">{{ producto.producto }}</h5>
                  <p class="card-text">S/. {{ producto.precio }}</p>
                  <button @click="agregarProducto(producto)" class="btn btn-success w-100 mt-2" 
                          :disabled="producto.num_stock <= 0">
                    <i class="fas fa-plus-circle"></i> AGREGAR PRODUCTO
                  </button>
                </div>
                <div v-if="producto.id === recentlyAddedProductId" class="checkmark-overlay">
                  <i class="fas fa-check-circle"></i>
                  <span class="added-text">Agregado</span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Loading More Indicator -->
        <div v-if="loadingMore" class="text-center mt-4">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando más...</span>
          </div>
        </div>

        <!-- Intersection Observer Target -->
        <div ref="infiniteScrollTrigger" class="my-4"></div>
      </div>
    </div>
    <!-- Shopping Cart Icon Button -->
    <button @click="openCart" class="btn-cart-icon">
      <i class="fas fa-shopping-cart"></i>
      <span v-if="cartItemCount > 0" class="cart-item-count">{{ cartItemCount }}</span>
    </button>
    <button @click="scrollToTop" class="btn btn-primary back-to-top" v-show="showBackToTop">
      <i class="fas fa-arrow-up"></i> SUBIR
    </button>
  </div>

  <!-- Cart Modal with Transition -->
  <transition name="fade">
    <div v-if="showCart" class="modal d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Carrito</h5>
            <button type="button" class="btn-close" aria-label="Close" @click="closeCart"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="nombres" class="form-label">Nombres</label>
              <input type="text" v-model="nombres" class="form-control" id="nombres" placeholder="Ingrese nombres">
            </div>
            <div class="mb-3">
              <label for="telefono" class="form-label">Teléfono</label>
              <input type="text" v-model="telefono" class="form-control" id="telefono" placeholder="Ingrese teléfono">
            </div>
            <div class="mb-3">
              <label for="correo" class="form-label">Correo</label>
              <input type="email" v-model="correo" class="form-control" id="correo" placeholder="Ingrese correo">
            </div>
            <table v-if="cart.length" class="table table-bordered">
              <thead>
                <tr>
                  <th><input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected"></th>
                  <th>Producto</th>
                  <th>Precio</th>
                  <th>Cantidad</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in cart" :key="item.id">
                  <td><input type="checkbox" v-model="selectedItems" :value="item.id"></td>
                  <td>{{ item.producto }}</td>
                  <td>S/. {{ item.precio }}</td>
                  <td>
                    <input type="number" v-model.number="item.quantity" min="1" class="form-control"
                      style="width:100px;" @change="updateQuantity(index, $event)">
                  </td>
                  <td>
                    <div class="text-center">
                        <button @click="removerProducto(index)" class="btn btn-danger btn-sm" title="Eliminar">
                        <i class="fas fa-trash"></i>
                        </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-else class="mb-0">No hay productos</p>
          </div>
          <div class="modal-footer">
            <button v-if="selectedItems.length" type="button" class="btn btn-danger" @click="removerSeleccionados">Eliminar seleccionados</button>
            <button type="button" class="btn btn-primary" @click="procesarCart" v-if="selectedItems.length">Procesar</button>
            <button type="button" class="btn btn-secondary" @click="closeCart">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      baseUrl: window.location.origin,
      productos: [],
      loading: true,
      pagination: {
        total: 0,
        per_page: 12,
        current_page: 1,
        last_page: 1,
        next_page_url: null,
        prev_page_url: null
      },
      filters: {
        producto: '',
        precio_min: null,
        precio_max: null,
        tipo_producto: []
      },
      debounceTimeout: null,
      hasMorePages: true,
      observer: null,
      loadingMore: false,
      showBackToTop: false,
      showTipoProducto: true,
      cart: [],
      showCart: false,  // Added showCart property
      selectedItems: [], // Added selectedItems property
      recentlyAddedProductId: null, // Added recentlyAddedProductId property
      nombres: '', // Added nombres property
      telefono: '', // Added telefono property
      correo: '' // Added correo property
    }
  },
  watch: {
    'filters': {
      handler(newVal) {
        this.debouncedSearch();
      },
      deep: true
    }
  },
  computed: {
    cartItemCount() {
      return this.cart.reduce((total, item) => total + item.quantity, 0);
    },
    isAllSelected() {
      return this.cart.length && this.selectedItems.length === this.cart.length;
    }
  },
  methods: {
    debouncedSearch() {
      if (this.debounceTimeout) clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        this.productos = []; // Clear existing products
        this.pagination.current_page = 1; // Reset to first page
        this.hasMorePages = true; // Reset pages flag
        this.fetchProductos();
      }, 300);
    },
    handleSearch() {
      this.productos = []; // Clear existing products
      this.pagination.current_page = 1; // Reset to first page
      this.hasMorePages = true; // Reset pages flag
      this.fetchProductos();
    },
    fetchProductos(url = '/api/stock') {
      if (!this.hasMorePages || (this.loading && this.productos.length > 0)) return;

      if (this.productos.length === 0) {
        this.loading = true;
      } else {
        this.loadingMore = true;
      }

      const params = {
        ...this.filters,
        tipo_producto: this.filters.tipo_producto.join(','),
        page: this.pagination.current_page
      };

      // Remove null or empty string values
      Object.keys(params).forEach(key => {
        if (params[key] === null || params[key] === '' || (Array.isArray(params[key]) && params[key].length === 0)) {
          delete params[key];
        }
      });

      axios.get(url, { params })
        .then(response => {
          if (this.pagination.current_page === 1) {
            this.productos = response.data.data;
          } else {
            this.productos = [...this.productos, ...response.data.data];
          }

          this.pagination = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total,
            last_page: response.data.last_page,
            next_page_url: response.data.next_page_url,
            prev_page_url: response.data.prev_page_url
          };
          this.hasMorePages = !!response.data.next_page_url;
        })
        .catch(error => {
          console.error('Error:', error);
        })
        .finally(() => {
          this.loading = false;
          this.loadingMore = false;
        });
    },
    resetFilters() {
      this.filters = {
        producto: '',
        precio_min: null,
        precio_max: null,
        tipo_producto: []
      };
      this.productos = []; // Clear existing products
      this.pagination.current_page = 1; // Reset to first page
      this.hasMorePages = true; // Reset pages flag
      this.handleSearch();
    },
    setupInfiniteScroll() {
      this.observer = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting && this.hasMorePages && !this.loading) {
          this.pagination.current_page++;
          this.fetchProductos();
        }
      }, {
        root: null,
        rootMargin: '100px',
        threshold: 0.1
      });

      if (this.$refs.infiniteScrollTrigger) {
        this.observer.observe(this.$refs.infiniteScrollTrigger);
      }
    },
    goBack() {
      if (this.cart.length > 0) {
        if (!window.confirm('Hay items en el carrito de compras, estas seguro de irse?, se borraran si se va.')) {
          return;
        }
        // Clear cart if confirmed
        this.cart = [];
        this.selectedItems = [];
      }
      window.history.back();
    },
    scrollToTop() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    handleScroll() {
      this.showBackToTop = window.scrollY > 200;
    },
    toggleTipoProducto() {
      this.showTipoProducto = !this.showTipoProducto;
    },
    toggleCheckbox(value) {
      const index = this.filters.tipo_producto.indexOf(value);
      if (index === -1) {
        this.filters.tipo_producto.push(value);
      } else {
        this.filters.tipo_producto.splice(index, 1);
      }
    },
    agregarProducto(producto) {
      // Check if the product is already in the cart
      const existingProduct = this.cart.find(item => item.id === producto.id);

      if (existingProduct) {
        // If the product exists, update the quantity
        existingProduct.quantity += 1;
      } else {
        // If the product doesn't exist, add it to the cart with quantity 1
        this.cart.push({ ...producto, quantity: 1 });
      }

      // Set recently added product ID for animation
      this.recentlyAddedProductId = producto.id;
      setTimeout(() => {
        this.recentlyAddedProductId = null;
      }, 1500); // Remove the animation class after 1.5 seconds
    },
    removerProducto(index) {
      this.cart.splice(index, 1);
    },
    updateQuantity(index, event) {
      const newQuantity = parseInt(event.target.value, 10);
      if (newQuantity > 0) {
        this.cart[index].quantity = newQuantity;
      } else {
        // Remove the item if the quantity is set to 0 or less
        this.cart.splice(index, 1);
      }
    },
    closeCart() {
      this.showCart = false;
    },
    openCart() {
      this.showCart = true;
    },
    toggleSelectAll(event) {
      if (event.target.checked) {
        this.selectedItems = this.cart.map(item => item.id);
      } else {
        this.selectedItems = [];
      }
    },
    removerSeleccionados() {
      this.cart = this.cart.filter(item => !this.selectedItems.includes(item.id));
      this.selectedItems = [];
    },
    procesarCart() {
      if (!this.cart.length) return;
      // Calculate total amount
      const montoTotal = this.cart.reduce((total, item) => total + (item.quantity * item.precio), 0);
      const productoComprobantePayload = {
        nombres: this.nombres,
        telefono: this.telefono,
        correo: this.correo,
        monto_total: parseFloat(montoTotal.toFixed(2)),
        items: this.cart.map(item => ({
          stock_id: item.id,
          cantidad: item.quantity,
          precio: item.precio
        }))
      };
      axios.post('/api/productos-comprobante', productoComprobantePayload)
        .then(response => {
          alert('Producto comprobante y items creados correctamente');
          this.cart = [];
          this.selectedItems = [];
          this.closeCart();
        })
        .catch(error => {
          console.error('Error al crear producto comprobante/items:', error);
          alert('Error al crear producto comprobante/items');
        });
    }
  },
  mounted() {
    this.filters.tipo_producto = ['l', 'm', 'c', 'u']; // Set default to all options
    this.fetchProductos();
    this.setupInfiniteScroll();
    window.addEventListener('scroll', this.handleScroll);
  },
  beforeUnmount() {
    if (this.observer) {
      this.observer.disconnect();
    }
    window.removeEventListener('scroll', this.handleScroll);
  }
}
</script>

<style scoped>
.product-card {
  transition: transform 0.2s;
  border: none;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.product-image {
  height: 200px;
  object-fit: cover;
  padding: 1rem;
}

.product-card-body {
  text-align: center;
}

.filter-card-body {
  text-align: left;
}

.btn-link-custom {
  text-decoration: none;
  color: #6c757d;
}

.btn-link-custom:hover {
  color: #495057;
}

.card {
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  border: none;
}

.card-title {
  color: #495057;
  font-size: 1.1rem;
}

.no-select {
  user-select: none;
}

.back-to-top {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
  width: auto;
  height: 50px;
  border-radius: 25px;
  font-size: 1rem;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.rotate-icon {
  transform: rotate(180deg);
  transition: transform 0.2s ease;
}

.fas.fa-chevron-down {
  transition: transform 0.2s ease;
}

[role="button"] {
  cursor: pointer;
  user-select: none;
}

.custom-form-check {
  cursor: pointer;
  padding: 0.5rem;
  display: flex;
  align-items: center;
  margin-bottom: 0.25rem;
  position: relative;
}

.custom-form-check:hover {
  background-color: rgba(0, 0, 0, 0.05);
  border-radius: 0.25rem;
}

.custom-form-check .form-check-input {
  cursor: pointer;
  margin: 0;
  position: relative;
  flex-shrink: 0;
  width: 1rem;
  height: 1rem;
}

.custom-form-check .form-check-label {
  cursor: pointer;
  margin: 0;
  padding-left: 0.75rem;
  line-height: 1.2;
  display: flex;
  align-items: center;
  flex: 1;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter,
.fade-leave-to {
  opacity: 0;
}

.btn-cart-icon {
  position: fixed;
  bottom: 80px;
  right: 20px;
  z-index: 1001;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background-color: #007bff;
  color: #fff;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  cursor: pointer;
}

.btn-cart-icon i {
  font-size: 1.8rem;
}

.cart-modal {
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
  border: 2px solid #007bff;
  background: linear-gradient(180deg, #fff, #f7f7f7);
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.btn-close {
  cursor: pointer;
}

.cart-modal .btn-close {
  cursor: pointer;
}

.cart-modal .btn-close i {
  cursor: pointer;
}

.cart-item-count {
  position: absolute;
  top: -10px;
  right: -10px;
  background-color: red;
  color: white;
  border-radius: 50%;
  padding: 0.2rem 0.5rem;
  font-size: 0.8rem;
}

.animate-add {
  animation: addItem 0.5s ease-in-out;
}

@keyframes addItem {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}

.checkmark-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 128, 0, 0.7); /* Green background */
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 3rem;
  animation: fadeOut 1.5s forwards; /* Adjusted animation duration */
}

.added-text {
  font-size: 1.5rem;
  margin-top: 0.5rem;
}

@keyframes fadeOut {
  0% {
    opacity: 1;
  }
  100% {
    opacity: 0;
  }
}

.stock-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 5px 10px;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: bold;
  z-index: 1;
  color: white;
}

.in-stock {
  background-color: rgba(40, 167, 69, 0.8);
}

.out-of-stock {
  background-color: rgba(220, 53, 69, 0.8);
}

.product-card {
  position: relative;
}
</style>
