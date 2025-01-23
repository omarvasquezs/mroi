<template>
  <div class="modal fade" id="citaModal" tabindex="-1" aria-labelledby="citaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Add modal-lg class for larger modal -->
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="citaModalLabel">Registrar Cita</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row mb-3">
            <div class="col-md-3"><strong>Fecha Seleccionada:</strong></div>
            <div class="col-md-9">{{ formattedDate }}</div>
            </div>
            <div class="row mb-3">
            <div class="col-md-3"><strong>Hora Seleccionada:</strong></div>
            <div class="col-md-9">{{ formattedTime }}</div>
            </div>
            <div class="row mb-3">
            <div class="col-md-3"><strong>Médico Seleccionado:</strong></div>
            <div class="col-md-9">{{ selectedMedicoName.toUpperCase() }}</div>
            </div>
          <div class="mb-3">
            <label class="form-label"><strong>Paciente:</strong></label>
            <select v-model="formData.num_historia" class="form-select">
              <option value="" disabled>Seleccione un paciente</option>
              <option v-for="paciente in pacientes" :key="paciente.num_historia" :value="paciente.num_historia">
                {{ paciente.nombre.toUpperCase() }}
              </option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label"><strong>Observaciones:</strong></label>
            <textarea v-model="formData.observaciones" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" @click="saveCita">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    selectedTime: {
      type: String,
      required: true
    },
    selectedDate: {
      type: Date,
      required: true
    },
    selectedMedico: {
      type: [String, Number],
      required: true
    },
    selectedMedicoName: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      pacientes: [],
      formData: {
        num_historia: '',
        observaciones: ''
      }
    }
  },
  computed: {
    formattedDate() {
      return this.selectedDate.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      }).replace(/ de /g, '/').toUpperCase();
    },
    formattedTime() {
      const [hour, minute] = this.selectedTime.split(':');
      const date = new Date();
      date.setHours(hour, minute);
      return date.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      }).toUpperCase();
    }
  },
  created() {
    this.fetchPacientes();
  },
  methods: {
    async fetchPacientes() {
      try {
        const response = await fetch('/api/pacientes-list');
        this.pacientes = await response.json();
      } catch (error) {
        console.error('Error fetching pacientes:', error);
      }
    },
    async saveCita() {
      try {
        const adjustedDate = new Date(this.selectedDate.getTime() - this.selectedDate.getTimezoneOffset() * 60000);
        const response = await fetch('/api/citas', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            num_historia: this.formData.num_historia,
            id_medico: this.selectedMedico,
            fecha: adjustedDate.toISOString().split('T')[0],
            hora: this.selectedTime,
            observaciones: this.formData.observaciones
          })
        });

        if (response.ok) {
          this.$emit('citaCreated');
          this.closeModal();
        }
      } catch (error) {
        console.error('Error saving cita:', error);
      }
    },
    closeModal() {
      this.formData = { num_historia: '', observaciones: '' };
      const modal = document.getElementById('citaModal');
      const bootstrapModal = bootstrap.Modal.getInstance(modal);
      bootstrapModal.hide();
    }
  }
}
</script>