<template>
  <div class="container-fluid mt-4">
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h1 class="text-center mb-4">Catálogo de Productos</h1>

    <div class="row">
      <!-- Filters Section -->
      <div class="col-md-3">
        <div class="card sticky-top" style="top: 1rem;">
          <div class="card-body">
            <h5 class="card-title mb-3">Filtros</h5>
            <div class="mb-3">
              <label class="form-label">Búsqueda</label>
              <input 
                type="text" 
                v-model="filters.producto" 
                class="form-control" 
                placeholder="Buscar productos..."
              >
            </div>
            <div class="mb-3">
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
            <div class="mb-3">
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
            <button @click="resetFilters" class="btn btn-secondary btn-sm w-100">
              Limpiar filtros
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
                <div class="card-body">
                  <h5 class="card-title">{{ producto.producto }}</h5>
                  <p class="card-text">S/. {{ producto.precio }}</p>
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
        precio_max: null
      },
      debounceTimeout: null,
      hasMorePages: true,
      observer: null,
      loadingMore: false
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
        page: this.pagination.current_page
      };

      // Remove null or empty string values
      Object.keys(params).forEach(key => {
        if (params[key] === null || params[key] === '') {
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
        precio_max: null
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
    }
  },
  mounted() {
    this.fetchProductos();
    this.setupInfiniteScroll();
  },
  beforeUnmount() {
    if (this.observer) {
      this.observer.disconnect();
    }
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
  text-align: center;
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
</style>
