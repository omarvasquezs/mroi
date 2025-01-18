<template>
  <div>
    <div class="mb-4">
      <button @click="goBack" class="btn btn-link btn-link-custom">← Regresar</button>
    </div>
    <h1>Gestión de Usuarios</h1>
    <button @click="showCreateForm" class="btn btn-primary mb-3">Crear Usuario</button>
    <button @click="resetFilters" class="btn btn-secondary mb-3 ms-2">Resetear Filtros</button>
    <div v-if="alertMessage" class="alert alert-success alert-dismissible fade show" role="alert">
      {{ alertMessage }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Nombre Completo</th>
          <th>Usuario</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
        <tr>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.name" @input="applyFilters" class="form-control" placeholder="Filtrar por Nombre Completo">
              <button v-if="filters.name" @click="clearFilter('name')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <input type="text" v-model="filters.username" @input="applyFilters" class="form-control" placeholder="Filtrar por Usuario">
              <button v-if="filters.username" @click="clearFilter('username')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <select v-model="filters.role" @change="applyFilters" class="form-control">
                <option value="">Todos</option>
                <option value="a">Admisión</option>
                <option value="o">Óptica</option>
                <option value="m">Médico</option>
                <option value="s">Sistemas</option>
              </select>
              <button v-if="filters.role" @click="clearFilter('role')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th>
            <div class="position-relative">
              <select v-model="filters.estado" @change="applyFilters" class="form-control">
                <option value="">Todos</option>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
              <button v-if="filters.estado !== ''" @click="clearFilter('estado')" class="btn-clear">
                <img :src="`${baseUrl}/images/close.png`" alt="Clear" class="clear-icon">
              </button>
            </div>
          </th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="usuarios.length === 0">
          <td colspan="5" class="text-center">No hay registros de usuarios en el sistema.</td>
        </tr>
        <tr v-else v-for="usuario in usuarios" :key="usuario.id">
          <td>{{ usuario.name }}</td>
          <td>{{ usuario.username }}</td>
          <td>{{ getRoleName(usuario.role) }}</td>
          <td>{{ usuario.estado ? 'Activo' : 'Inactivo' }}</td>
          <td>
            <button @click="editUsuario(usuario)" class="btn btn-warning btn-sm me-2">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <button @click="confirmDelete(usuario.id)" class="btn btn-danger btn-sm">
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
    <div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="usuarioModalLabel">{{ isEditing ? 'Editar Usuario' : 'Crear Usuario' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitForm" autocomplete="off">
              <div v-if="formErrors.includes('Usuario ya existe en la base de datos.')" class="alert alert-danger alert-dismissible fade show" role="alert">
                Usuario ya existe en la base de datos.
                <button type="button" class="btn-close" @click="closeFormErrorAlert" aria-label="Close"></button>
              </div>
              <div class="mb-3">
                <label for="name" class="form-label">Nombre Completo*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                  <input type="text" v-model="form.name" id="name" class="form-control" placeholder="Ej: Juan Pérez" required>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="username" class="form-label">Usuario*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="form.username" id="username" class="form-control" placeholder="Ej: juanperez" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <label for="password" class="form-label">Contraseña{{ isEditing ? '' : '*' }}:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" v-model="form.password" id="password" class="form-control" :placeholder="isEditing ? 'Dejar en blanco para no cambiar' : 'Contraseña'" :required="!isEditing">
                  </div>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Rol*:</label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="form.role" id="roleA" value="a" required>
                    <label class="form-check-label" for="roleA">Admisión</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="form.role" id="roleO" value="o" required>
                    <label class="form-check-label" for="roleO">Óptica</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="form.role" id="roleM" value="m" required>
                    <label class="form-check-label" for="roleM">Médico</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="form.role" id="roleS" value="s" required>
                    <label class="form-check-label" for="roleS">Sistemas</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Estado*:</label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="form.estado" id="estadoActivo" value="1" required>
                    <label class="form-check-label" for="estadoActivo">Activo</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="form.estado" id="estadoInactivo" value="0" required>
                    <label class="form-check-label" for="estadoInactivo">Inactivo</label>
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
      usuarios: [],
      form: {
        username: '',
        name: '',
        password: '',
        role: '',
        estado: 1 // Default value to 1 (Activo)
      },
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
        username: '',
        name: '',
        role: '',
        estado: ''
      }
    };
  },
  methods: {
    fetchUsuarios(url = '/api/usuarios') {
      const params = {
        username: this.filters.username,
        name: this.filters.name,
        role: this.filters.role,
        estado: this.filters.estado !== '' ? this.filters.estado : null,
        page: this.pagination.current_page
      };
      axios.get(url, { params })
        .then(response => {
          const { data, ...pagination } = response.data;
          this.usuarios = data;
          this.pagination = pagination;
        })
        .catch(error => {
          console.error(error);
        });
    },
    changePage(url) {
      if (url) {
        const page = new URL(url).searchParams.get('page');
        this.pagination.current_page = page;
        this.fetchUsuarios(url);
      }
    },
    applyFilters() {
      this.pagination.current_page = 1;
      this.fetchUsuarios();
    },
    resetFilters() {
      this.filters = {
        username: '',
        name: '',
        role: '',
        estado: ''
      };
      this.applyFilters();
    },
    clearFilter(filter) {
      this.filters[filter] = '';
      this.applyFilters();
    },
    showCreateForm() {
      this.resetForm();
      this.showForm = true;
      this.isEditing = false;
      new Modal(document.getElementById('usuarioModal')).show();
    },
    editUsuario(usuario) {
      this.form = { ...usuario, password: '' };
      this.showForm = true;
      this.isEditing = true;
      this.editingId = usuario.id;
      new Modal(document.getElementById('usuarioModal')).show();
    },
    confirmDelete(id) {
      if (confirm('¿Está seguro de que desea eliminar este usuario?')) {
        this.deleteUsuario(id);
      }
    },
    deleteUsuario(id) {
      axios.delete(`/api/usuarios/${id}`)
        .then(response => {
          this.fetchUsuarios();
        })
        .catch(error => {
          console.error(error);
        });
    },
    checkUsernameExists(username) {
      return axios.get(`/api/usuarios/check-username`, { params: { username } })
        .then(response => response.data.exists)
        .catch(error => {
          console.error(error);
          return false;
        });
    },
    async submitForm() {
      this.formErrors = [];
      const formData = { ...this.form };
      if (this.isEditing && !formData.password) {
        delete formData.password;
      }

      const usernameExists = await this.checkUsernameExists(formData.username);
      if (usernameExists && (!this.isEditing || (this.isEditing && formData.username !== this.usuarios.find(u => u.id === this.editingId).username))) {
        this.formErrors.push('Usuario ya existe en la base de datos.');
        setTimeout(() => {
          this.formErrors = this.formErrors.filter(error => error !== 'Usuario ya existe en la base de datos.');
        }, 5000);
        return;
      }

      const request = this.isEditing
        ? axios.put(`/api/usuarios/${this.editingId}`, formData)
        : axios.post('/api/usuarios', formData);

      request
        .then(response => {
          this.alertMessage = this.isEditing ? 'Usuario actualizado con éxito.' : 'Usuario creado con éxito.';
          this.fetchUsuarios();
          this.resetForm();
          this.showForm = false;
          this.closeModal();
          setTimeout(() => {
            this.alertMessage = '';
          }, 5000);
        })
        .catch(error => {
          if (error.response && error.response.status === 422) {
            this.formErrors = Object.values(error.response.data.errors).flat();
          } else {
            console.error(error);
          }
        });
    },
    closeFormErrorAlert() {
      this.formErrors = this.formErrors.filter(error => error !== 'Usuario ya existe en la base de datos.');
    },
    resetForm() {
      this.form = {
        username: '',
        name: '',
        password: '',
        role: '',
        estado: 1 // Default value to 1 (Activo)
      };
      this.isEditing = false;
      this.editingId = null;
    },
    closeModal() {
      const modal = Modal.getInstance(document.getElementById('usuarioModal'));
      if (modal) {
        modal.hide();
      }
    },
    closeAlert() {
      this.alertMessage = '';
    },
    goBack() {
      window.history.back();
    },
    getRoleName(role) {
      switch (role) {
        case 'a':
          return 'Admisión';
        case 'o':
          return 'Óptica';
        case 'm':
          return 'Médico';
        case 's':
          return 'Sistemas';
        default:
          return '';
      }
    }
  },
  mounted() {
    this.fetchUsuarios();
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
