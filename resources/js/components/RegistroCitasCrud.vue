<template>
  <div class="mb-4">
    <button @click="goBack" class="btn btn-link">← Regresar</button>
  </div>
  <div class="row">
    <div class="col-md-12 mb-4">
      <h2>Registro de Citas</h2>
    </div>
  </div>
  <div class="row">
    <div class="col-md-3">
      <div class="mb-3">
        <label for="medicoSelect" class="form-label">Seleccionar Médico:</label>
        <select v-model="selectedMedico" id="medicoSelect" class="form-select">
          <option value="" disabled>Seleccione un médico</option>
          <option v-if="loading">Cargando médicos...</option>
          <option v-else-if="error">Error al cargar médicos</option>
          <option v-else v-for="medico in medicos" :key="medico?.id" :value="medico?.id">
            {{ medico?.nombre || 'Médico sin nombre' }}
          </option>
        </select>
      </div>
      <div class="mb-3">
        <div class="calendar-section">
          <label class="form-label mb-2">Seleccionar Fecha:</label>
          <DatePicker v-model="selectedFecha" :min-date="new Date()" :attributes="attributes" locale="es" expanded
            class="calendar-container" />
        </div>
      </div>
    </div>
    <div class="col-md-9">
      <div class="table-responsive table-wrapper">
        <table class="table table-striped">
          <thead class="table-header">
            <tr>
              <th style="width: 5%">No.</th>
              <th style="width: 10%">Hora</th>
              <th style="width: 15%">Historia</th>
              <th style="width: 30%">Paciente</th>
              <th style="width: 15%">Teléfono</th>
              <th style="width: 25%">Observación</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(cita, index) in citas" :key="index" @click="openModal(cita.hora)"
              :class="{ 'cursor-pointer': !cita.paciente }">
              <td>{{ index + 1 }}</td>
              <td>{{ cita.hora }}</td>
              <td>{{ cita.historia }}</td>
              <td>{{ cita.paciente }}</td>
              <td>{{ cita.telefono }}</td>
              <td>{{ cita.observacion }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <CitaModal ref="citaModal" :selected-time="selectedTime" :selected-date="selectedFecha"
      :selected-medico="selectedMedico" @citaCreated="fetchCitas" />
  </div>
</template>

<script>
import { DatePicker } from 'v-calendar';
import CitaModal from './CitaModal.vue';

export default {
  components: {
    DatePicker,
    CitaModal
  },
  data() {
    return {
      medicos: [],
      selectedMedico: '',
      selectedFecha: new Date(), // Initialize with today's date
      loading: false,
      error: null,
      citas: [],
      attributes: [
        {
          highlight: true,
          dates: new Date()
        }
      ],
      timeSlots: [],
      selectedTime: '',
    };
  },
  created() {
    this.fetchMedicos();
    this.generateTimeSlots();
  },
  methods: {
    fetchMedicos() {
      this.loading = true;
      this.error = null;

      fetch('/api/medicos-list')
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          console.log('Medicos data:', data); // Debug line
          if (Array.isArray(data)) {
            this.medicos = data;
          } else if (data.data && Array.isArray(data.data)) {
            this.medicos = data.data;
          } else {
            throw new Error('Invalid data format');
          }
        })
        .catch(error => {
          console.error('Error fetching medicos:', error);
          this.error = error.message;
          this.medicos = [];
        })
        .finally(() => {
          this.loading = false;
        });
    },
    goBack() {
      this.$router.go(-1);
    },
    generateTimeSlots() {
      const slots = [];
      const startTime = new Date();
      startTime.setHours(8, 0, 0); // 8:00 AM
      const endTime = new Date();
      endTime.setHours(19, 0, 0); // 7:00 PM

      while (startTime < endTime) {
        slots.push({
          hora: startTime.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
          }),
          historia: '',
          paciente: '',
          telefono: '',
          observacion: ''
        });
        startTime.setMinutes(startTime.getMinutes() + 5);
      }
      this.citas = slots;
    },
    openModal(hora) {
      if (!this.selectedMedico) {
        alert('Por favor seleccione un médico');
        return;
      }
      this.selectedTime = hora;
      const modalElement = document.getElementById('citaModal');
      const modal = new window.bootstrap.Modal(modalElement);
      modal.show();
    },
    async fetchCitas() {
      // Implement this to refresh the citas list after creating a new one
      // You'll need a new API endpoint to fetch citas by date and médico
    }
  },
  watch: {
    selectedFecha(newDate) {
      this.attributes = [
        {
          highlight: true,
          dates: newDate
        }
      ];
      this.fetchCitas();
      console.log('Selected date changed:', newDate); // Debug line
    },
    selectedMedico() {
      this.fetchCitas();
    }
  }
};
</script>

<style scoped>
.calendar-section {
  display: flex;
  flex-direction: column;
}

.calendar-container {
  width: 100%;
  max-width: 400px;
  border: 1px solid #dee2e6;
  border-radius: 0.25rem;
  padding: 1rem;
  background: white;
}

.table-wrapper {
  max-height: 600px;
  /* Increased height */
  overflow-y: auto;
  position: relative;
}

.table-header {
  position: sticky;
  top: 0;
  background: white;
  z-index: 1;
}

.table {
  font-size: 0.9rem;
}

.cursor-pointer {
  cursor: pointer;
}

tr:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
</style>
