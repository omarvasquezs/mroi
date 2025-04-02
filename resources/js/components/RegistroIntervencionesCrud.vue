<template>
  <div class="mb-4">
    <button @click="goBack" class="btn btn-link">← Regresar</button>
  </div>
  <div class="row">
    <div class="col-md-12 mb-4">
      <h2>Registro de Intervenciones</h2>
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
              <th style="width: 20%">Tipo de Intervención</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="(intervencion, index) in intervenciones" 
              :key="index"
              class="time-slot-row"
              :class="{ 'selected-row': isRowSelected(index), 'has-intervention': hasIntervention(intervencion) }"
              @mousedown="startDragSelection(index)"
              @mouseover="updateDragSelection(index)"
              @mouseup="endDragSelection()"
            >
              <td>{{ index + 1 }}</td>
              <td>{{ intervencion.hora }}</td>
              <td>{{ intervencion.historia }}</td>
              <td>{{ intervencion.paciente }}</td>
              <td>{{ intervencion.tipo_intervencion }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="isDragging" class="selection-controls-bar">
        <button @click="cancelSelection" class="btn btn-outline-secondary me-2">
          <i class="fas fa-times"></i> Cancelar
        </button>
        <button @click="confirmSelection" class="btn btn-primary">
          <i class="fas fa-check"></i> Registrar Intervención
        </button>
      </div>
    </div>
    <IntervencionModal 
      ref="intervencionModal" 
      :selected-time="selectedTimeStart" 
      :selected-time-end="selectedTimeEnd"
      :selected-date="selectedFecha"
      :selected-medico="selectedMedico" 
      :selected-medico-name="selectedMedicoName" 
      :intervencion="selectedIntervencion" 
      @intervencionCreated="fetchIntervenciones" 
    />
  </div>
</template>

<script>
import { DatePicker } from 'v-calendar';
import IntervencionModal from './IntervencionModal.vue';

export default {
  components: {
    DatePicker,
    IntervencionModal
  },
  data() {
    return {
      medicos: [],
      loading: false,
      error: false,
      selectedMedico: '',
      selectedMedicoName: '',
      selectedFecha: new Date(),
      intervenciones: [],
      attributes: [],
      selectedIntervencion: null,
      
      // Drag selection properties
      isDragging: false,
      dragStartIndex: null,
      dragEndIndex: null,
      selectedTimeStart: '',
      selectedTimeEnd: ''
    };
  },
  watch: {
    selectedMedico() {
      this.fetchIntervenciones();
      this.updateSelectedMedicoName();
    },
    selectedFecha() {
      this.fetchIntervenciones();
    }
  },
  mounted() {
    this.fetchMedicos();
    this.generateIntervenciones();
    
    // Add mouse up listener for cases when the mouse is released outside the table
    document.addEventListener('mouseup', this.endDragSelection);
  },
  beforeUnmount() {
    // Clean up event listener
    document.removeEventListener('mouseup', this.endDragSelection);
  },
  methods: {
    async fetchMedicos() {
      this.loading = true;
      try {
        console.log('Fetching médicos...');
        const response = await fetch('/api/medicos-list');
        if (!response.ok) {
          throw new Error(`Error al cargar los médicos: ${response.status} ${response.statusText}`);
        }
        const data = await response.json();
        console.log('Médicos data:', data);
        this.medicos = data;
        if (data.length === 0) {
          console.warn('No se encontraron médicos en la base de datos');
        }
      } catch (error) {
        console.error('Error fetchMedicos:', error);
        this.error = true;
      } finally {
        this.loading = false;
      }
    },
    updateSelectedMedicoName() {
      const medico = this.medicos.find(m => m.id === this.selectedMedico);
      this.selectedMedicoName = medico ? medico.nombre : '';
    },
    async fetchIntervenciones() {
      if (!this.selectedMedico || !this.selectedFecha) return;
      
      try {
        const fecha = this.formatDate(this.selectedFecha);
        const response = await fetch(`/api/intervenciones?id_medico=${this.selectedMedico}&fecha=${fecha}`);
        
        if (!response.ok) {
          throw new Error('Error al cargar las intervenciones');
        }
        
        const data = await response.json();
        this.mergeIntervencionesWithTimeSlots(data);
        
      } catch (error) {
        console.error('Error fetchIntervenciones:', error);
      }
    },
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
    generateIntervenciones() {
      // Generar horas de 8:00 a 20:00 con intervalos de 30 minutos
      const startHour = 8;
      const endHour = 20;
      const intervenciones = [];
      
      for (let hour = startHour; hour <= endHour; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
          const hourStr = hour.toString().padStart(2, '0');
          const minuteStr = minute.toString().padStart(2, '0');
          const timeStr = `${hourStr}:${minuteStr}`;
          
          intervenciones.push({
            hora: timeStr,
            historia: '',
            paciente: '',
            tipo_intervencion: ''
          });
        }
      }
      
      this.intervenciones = intervenciones;
    },
    mergeIntervencionesWithTimeSlots(intervenciones) {
      // Reset slots to empty
      this.generateIntervenciones();
      
      // Map each intervention to its corresponding time slot
      intervenciones.forEach(intervencion => {
        const time = this.extractTime(intervencion.fecha_hora_inicio);
        const slot = this.intervenciones.find(i => i.hora === time);
        
        if (slot) {
          slot.id = intervencion.id;
          slot.historia = intervencion.num_historia || '';
          slot.paciente = intervencion.paciente ? 
            `${intervencion.paciente.nombres} ${intervencion.paciente.ap_paterno} ${intervencion.paciente.ap_materno}` : 
            '';
          slot.tipo_intervencion = intervencion.tipoIntervencion ? 
            intervencion.tipoIntervencion.tipo_intervencion : '';
          slot.intervencion = intervencion;
        }
      });
    },
    extractTime(dateTimeStr) {
      const date = new Date(dateTimeStr);
      const hours = date.getHours().toString().padStart(2, '0');
      const minutes = date.getMinutes().toString().padStart(2, '0');
      return `${hours}:${minutes}`;
    },
    
    // Drag selection methods
    startDragSelection(index) {
      this.isDragging = true;
      this.dragStartIndex = index;
      this.dragEndIndex = index;
      this.selectedTimeStart = this.intervenciones[index].hora;
      this.selectedTimeEnd = this.intervenciones[index].hora;
    },
    
    updateDragSelection(index) {
      if (!this.isDragging) return;
      
      // Update the end of the selection
      this.dragEndIndex = index;
      this.selectedTimeEnd = this.intervenciones[index].hora;
    },
    
    endDragSelection() {
      if (!this.isDragging) return;
      
      // Sort the indices to handle selection in both directions
      const [startIndex, endIndex] = [this.dragStartIndex, this.dragEndIndex].sort((a, b) => a - b);
      
      // Update the selected time range
      this.selectedTimeStart = this.intervenciones[startIndex].hora;
      this.selectedTimeEnd = this.intervenciones[endIndex].hora;
      
      // Keep isDragging true to show the controls but prevent further updates
      // The selection can only be cancelled or confirmed by the user
    },
    
    isRowSelected(index) {
      if (!this.isDragging) return false;
      
      const [startIndex, endIndex] = [this.dragStartIndex, this.dragEndIndex].sort((a, b) => a - b);
      return index >= startIndex && index <= endIndex;
    },
    
    hasIntervention(intervencion) {
      return intervencion && intervencion.historia && intervencion.historia !== '';
    },
    
    cancelSelection() {
      this.isDragging = false;
      this.dragStartIndex = null;
      this.dragEndIndex = null;
      this.selectedTimeStart = '';
      this.selectedTimeEnd = '';
    },
    
    confirmSelection() {
      if (!this.selectedMedico) {
        alert('Por favor seleccione un médico antes de registrar una intervención.');
        return;
      }
      
      if (!this.selectedFecha) {
        alert('Por favor seleccione una fecha antes de registrar una intervención.');
        return;
      }
      
      // Explicitly trigger the modal based on user action
      this.$refs.intervencionModal.openModal();
      
      // Reset the selection state
      this.isDragging = false;
    }
  }
};
</script>

<style scoped>
.calendar-section {
  margin-bottom: 1rem;
}

.calendar-container {
  max-width: 100%;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table-wrapper {
  position: relative;
  margin-bottom: 1rem;
  max-height: 500px;
  overflow-y: auto;
}

.excel-table {
  font-size: 0.9rem;
}

.excel-table th {
  background-color: #f0f0f0;
  position: sticky;
  top: 0;
  z-index: 10;
}

.time-slot-row {
  cursor: pointer;
  transition: background-color 0.2s;
}

.time-slot-row:hover {
  background-color: #f8f9fa;
}

.time-slot-row.selected-row {
  background-color: #cfe2ff !important;
}

.time-slot-row.has-intervention {
  background-color: #e2f0d9;
}

.time-slot-row.has-intervention.selected-row {
  background-color: #9fc5e8 !important;
}

.selection-controls-bar {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #ffffff;
  padding: 12px 20px;
  border-radius: 30px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px solid #dee2e6;
}
</style>