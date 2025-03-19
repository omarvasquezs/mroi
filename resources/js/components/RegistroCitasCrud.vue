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
    <div class="col-lg-3 col-md-12">
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
          <DatePicker 
            v-model="selectedFecha" 
            :attributes="attributes" 
            locale="es" 
            expanded
            class="calendar-container" 
          />
        </div>
      </div>
    </div>
    <div class="col-lg-9 col-md-12">
      <div class="table-responsive table-wrapper">
        <table class="table table-bordered table-hover excel-table">
          <thead class="table-header">
            <tr>
              <th style="width: 5%">No.</th>
              <th style="width: 10%">Hora</th>
              <th style="width: 15%">Historia</th>
              <th style="width: 20%">Paciente</th>
              <th style="width: 20%">Tipo de Cita</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(cita, index) in citas" :key="index" @click="openModal(cita.hora)"
              class="cursor-pointer">
              <td>{{ index + 1 }}</td>
              <td>{{ cita.hora }}</td>
              <td>{{ cita.historia }}</td>
              <td>{{ cita.paciente }}</td>
              <td>{{ cita.tipo_cita }}</td>
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
      timeSlots: [], // Reintroduce timeSlots array
      attributes: [
        {
          highlight: true,
          dates: new Date()
        }
      ],
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
    this.citas = this.generateTimeSlots(); // Initialize the table with all time slots
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
    formatLocalDate(date) {
      const d = new Date(date);
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
    async openModal(hora) {
      if (!this.selectedMedico) {
        alert('Por favor seleccione un médico');
        return;
      }
      this.selectedTime = hora;

      // Use local date without UTC adjustment
      const date = this.formatLocalDate(this.selectedFecha);
      const time = this.selectedTime.padStart(5, '0');

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
    generateTimeSlots() {
      const slots = [];
      // Use the selected date for time slots instead of current date
      const startTime = new Date(this.selectedFecha);
      startTime.setHours(8, 0, 0);
      const endTime = new Date(this.selectedFecha);
      endTime.setHours(19, 0, 0);

      while (startTime < endTime) {
        slots.push({
          hora: startTime.toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
          }),
          historia: '',
          paciente: '',
          tipo_cita: ''
        });
        startTime.setMinutes(startTime.getMinutes() + 30); // Change to 30 minutes
      }
      return slots;
    },
    async fetchCitas() {
      if (!this.selectedMedico || !this.selectedFecha) {
        this.citas = this.generateTimeSlots();
        return;
      }

      // Use local date format
      const formattedDate = this.formatLocalDate(this.selectedFecha);
      const url = `/api/citas?medico=${this.selectedMedico}&fecha=${formattedDate}`;

      try {
        const response = await fetch(url);
        if (response.ok) {
          const data = await response.json();
          console.log('Raw citas data:', data);
          
          const slots = this.generateTimeSlots();
          if (Array.isArray(data)) {
            // Build citasMap with proper time formatting
            const citasMap = new Map();
            
            // First, log each cita's data structure
            data.forEach(cita => {
              console.log('Processing cita:', {
                original: cita,
                fecha: cita.fecha,
                hora: cita.hora,
                num_historia: cita.num_historia,
                paciente: cita.paciente,
                tipo_cita: cita.tipoCita
              });
              
              // Extract time from hora field (assuming format "HH:mm:ss")
              const timeStr = cita.hora ? cita.hora.substring(0, 5) : null;
              if (timeStr) {
                citasMap.set(timeStr, cita);
              }
            });

            console.log('CitasMap built:', Array.from(citasMap.entries()));

            // Map slots to citas
            this.citas = slots.map(slot => {
              console.log('Processing slot:', slot.hora);
              const matchingCita = citasMap.get(slot.hora);
              
              if (matchingCita) {
                console.log('Found match for slot', slot.hora, matchingCita);
                const pacienteData = matchingCita.paciente || {};
                const pacienteNombre = [
                  pacienteData.nombres,
                  pacienteData.ap_paterno,
                  pacienteData.ap_materno
                ].filter(Boolean).join(' ');
                
                return {
                  hora: slot.hora,
                  historia: matchingCita.num_historia || '',
                  paciente: pacienteNombre,
                  tipo_cita: matchingCita.tipoCita ? matchingCita.tipoCita.tipo_cita : ''
                };
              }
              return {
                ...slot,
                tipo_cita: ''
              };
            });
          } else {
            this.citas = slots.map(slot => ({ ...slot, tipo_cita: '' }));
          }
          console.log('Final processed citas:', this.citas);
        }
      } catch (error) {
        console.error('Error:', error);
        this.citas = this.generateTimeSlots().map(slot => ({ ...slot, tipo_cita: '' }));
      }
    }
  },
  watch: {
    selectedFecha: {
      handler(newDate, oldDate) {
        // Only update if the date actually changed (different day)
        if (!oldDate || 
            newDate.getDate() !== oldDate.getDate() || 
            newDate.getMonth() !== oldDate.getMonth() || 
            newDate.getFullYear() !== oldDate.getFullYear()) {
          
          this.attributes = [{
            highlight: true,
            dates: newDate
          }];
          
          // Only fetch if we have a selected medico
          if (this.selectedMedico) {
            this.fetchCitas();
          }
        }
      },
      deep: true
    },
    selectedMedico(newVal, oldVal) {
      if (newVal !== oldVal) {
        this.fetchCitas();
      }
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
