<template>
  <div class="container mt-4">
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link">← Regresar</button>
    </div>
    <h1 class="text-center mb-4">Catálogo de Productos</h1>

    <!-- Filters Section -->
    <div class="row mb-4">
      <div class="col-md-6 mx-auto">
        <div class="card">
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
            <div class="row">
              <div class="col-6">
                <label class="form-label">Precio mínimo</label>
                <div class="input-group mb-3">
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
              <div class="col-6">
                <label class="form-label">Precio máximo</label>
                <div class="input-group mb-3">
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
            <button @click="resetFilters" class="btn btn-secondary btn-sm">
              Limpiar filtros
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>

    <!-- No Results -->
    <div v-else-if="!productos.length" class="text-center">
      <p>No se encontraron productos.</p>
    </div>

    <!-- Products Grid -->
    <div v-else class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
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

    <!-- Pagination -->
    <nav v-if="pagination.total > pagination.per_page" class="mt-4">
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
          <a class="page-link" href="#" @click.prevent="changePage(pagination.prev_page_url)">Anterior</a>
        </li>
        <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
          <a class="page-link" href="#" @click.prevent="changePage(pagination.next_page_url)">Siguiente</a>
        </li>
      </ul>
    </nav>
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
      debounceTimeout: null
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
        this.handleSearch();
      }, 300);
    },
    handleSearch() {
      this.pagination.current_page = 1;
      this.fetchProductos();
    },
    fetchProductos(url = '/api/stock') {
      this.loading = true;
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
          this.productos = response.data.data;
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
          console.error('Error:', error);
        })
        .finally(() => {
          this.loading = false;
        });
    },
    resetFilters() {
      this.filters = {
        producto: '',
        precio_min: null,
        precio_max: null
      };
      this.handleSearch();
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchProductos(url);
      }
    },
    goBack() {
      window.history.back();
    }
  },
  mounted() {
    this.fetchProductos();
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
