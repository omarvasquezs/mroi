
<template>
  <div class="modal fade" id="citaModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Registrar Cita</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Paciente:</label>
            <select v-model="formData.num_historia" class="form-select">
              <option value="" disabled>Seleccione un paciente</option>
              <option v-for="paciente in pacientes" 
                      :key="paciente.num_historia" 
                      :value="paciente.num_historia">
                {{ paciente.nombre }}
              </option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Observaciones:</label>
            <textarea v-model="formData.observaciones" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" @click="saveCita">Guardar</button>
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
        const response = await fetch('/api/citas', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            num_historia: this.formData.num_historia,
            id_medico: this.selectedMedico,
            fecha: this.selectedDate.toISOString().split('T')[0],
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