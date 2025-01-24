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
        <table class="table table-bordered table-hover excel-table">
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
      :selected-medico="selectedMedico" :selected-medico-name="selectedMedicoName" :cita="selectedCita" @citaCreated="fetchCitas" />
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
      selectedCita: null, // Add selectedCita to store the selected cita
    };
  },
  computed: {
    selectedMedicoName() {
      const medico = this.medicos.find(m => m.id === this.selectedMedico);
      return medico ? medico.nombre : '';
    }
  },
  created() {
    this.fetchMedicos();
    this.citas = this.generateTimeSlots(); // Initialize citas with time slots
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
          }), // Format as HH:MM
          historia: '',
          paciente: '',
          telefono: '',
          observacion: ''
        });
        startTime.setMinutes(startTime.getMinutes() + 5);
      }
      return slots;
    },
    async openModal(hora) {
      if (!this.selectedMedico) {
        alert('Por favor seleccione un médico');
        return;
      }
      this.selectedTime = hora;
      console.log('Selected time:', this.selectedTime); // Debug line

      // Adjust date to UTC-5
      const adjustedDate = new Date(this.selectedFecha.getTime() - (5 * 60 * 60 * 1000));
      const date = adjustedDate.toISOString().split('T')[0];
      const time = this.selectedTime.padStart(5, '0'); // Ensure HH:mm format

      // Encode parameters
      const params = new URLSearchParams({
        medico: this.selectedMedico,
        fecha: date,
        hora: `${time}:00`
      });

      const url = `/api/citas/check?${params.toString()}`;

      try {
        const response = await fetch(url);
        const contentType = response.headers.get('content-type');

        if (response.ok && contentType && contentType.includes('application/json')) {
          const cita = await response.json();
          console.log('Cita found:', cita); // Debug line
          if (cita && Object.keys(cita).length > 0) {
            this.selectedCita = cita; // Set the selected cita
          } else {
            this.selectedCita = null; // No matching cita found
          }
        } else {
          const responseText = await response.text();
          console.error('Expected JSON response but got:', contentType, responseText);
          this.selectedCita = null; // Error in fetching cita
        }
      } catch (error) {
        console.error('Error checking cita:', error);
        this.selectedCita = null; // Error in fetching cita
      }

      this.$refs.citaModal.openModal(); // Open the modal
    },
    async fetchCitas() {
      if (!this.selectedMedico || !this.selectedFecha) {
        this.citas = this.generateTimeSlots();
        return;
      }

      const formattedDate = this.selectedFecha.toISOString().split('T')[0]; // Format date as YYYY-MM-DD
      const url = `/api/citas?medico=${this.selectedMedico}&fecha=${formattedDate}`;
      try {
        const response = await fetch(url);
        const contentType = response.headers.get('content-type');

        if (response.ok && contentType && contentType.includes('application/json')) {
          const data = await response.json();
          if (Array.isArray(data)) {
            const citasMap = new Map(data.map(cita => {
              const paciente = cita.paciente || ''; // Ensure paciente property is set
              return [new Date(cita.fecha).toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
              }), { ...cita, paciente }];
            })); // Extract HH:MM from fecha
            this.citas = this.generateTimeSlots().map(slot => citasMap.get(slot.hora) || slot);
          } else {
            this.citas = this.generateTimeSlots();
          }
        } else {
          const responseText = await response.text();
          console.error('Expected JSON response but got:', contentType, responseText);
          console.error('Response URL:', url);
          console.error('Response Status:', response.status);
          this.citas = this.generateTimeSlots();
        }
      } catch (error) {
        console.error('Error fetching citas:', error);
        console.error('Response URL:', url);
        this.citas = this.generateTimeSlots();
      }
      console.log('Citas:', this.citas); // Debug line
    },
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

.excel-table {
  border-collapse: collapse;
}

.excel-table th,
.excel-table td {
  border: 1px solid #000;
  padding: 8px;
  text-align: left;
}

.excel-table th {
  background-color: #f2f2f2;
}

.excel-table tr:nth-child(even) {
  background-color: #f9f9f9;
}
</style>
