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
              <input 
                type="text" 
                v-model="filters.producto" 
                class="form-control" 
                placeholder="Buscar productos..."
              >
            </div>
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Precio mínimo</label>
                <div class="input-group">
                  <span class="input-group-text">S/.</span>
                  <input 
                    type="number" 
                    v-model.number="filters.precio_min" 
                    class="form-control" 
                    placeholder="Mín"
                    min="0"
                    step="0.01"
                  >
                </div>
              </div>
              <div class="col">
                <label class="form-label">Precio máximo</label>
                <div class="input-group">
                  <span class="input-group-text">S/.</span>
                  <input 
                    type="number" 
                    v-model.number="filters.precio_max" 
                    class="form-control" 
                    placeholder="Máx"
                    min="0"
                    step="0.01"
                  >
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Tipo de Producto</label>
              <div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="l" id="lentes" v-model="filters.tipo_producto">
                  <label class="form-check-label no-select" for="lentes">
                    Lentes
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="m" id="montura" v-model="filters.tipo_producto">
                  <label class="form-check-label no-select" for="montura">
                    Montura
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="c" id="contacto" v-model="filters.tipo_producto">
                  <label class="form-check-label no-select" for="contacto">
                    Lentes de Contacto
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="u" id="lunas" v-model="filters.tipo_producto">
                  <label class="form-check-label no-select" for="lunas">
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
              <div class="card h-100 product-card">
                <img 
                  :src="`${baseUrl}/images/stock/${producto.imagen}`" 
                  class="card-img-top product-image" 
                  :alt="producto.producto"
                >
                <div class="card-body product-card-body">
                  <h5 class="card-title">{{ producto.producto }}</h5>
                  <p class="card-text">S/. {{ producto.precio }}</p>
                  <p class="card-text">En stock: {{ producto.num_stock }}</p>
                  <button class="btn btn-success w-100 mt-2">
                    <i class="fas fa-plus-circle"></i> AGREGAR PRODUCTO
                  </button>
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
    <button @click="scrollToTop" class="btn btn-primary back-to-top" v-show="showBackToTop">
      <i class="fas fa-arrow-up"></i> SUBIR
    </button>
  </div>
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
      showBackToTop: false
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
      window.history.back();
    },
    scrollToTop() {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    handleScroll() {
      this.showBackToTop = window.scrollY > 200;
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
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.product-image {
  height: 200px;
  object-fit: cover;
  padding: 1rem;
}

.card-body {
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
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
</style>
