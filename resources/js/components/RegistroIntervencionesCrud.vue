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
              :class="{ 
                'selected-row': isRowSelected(index),
                'has-intervention': hasIntervention(intervencion)
              }"
              @mousedown="startDragSelection($event, index)"
              @mouseover="updateDragSelection(index)"
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
      isMouseDown: false,
      startRowIndex: null,
      currentRowIndex: null,
      selectedTimeStart: '',
      selectedTimeEnd: '',
      
      // Drag and drop properties
      draggedRowIndex: null,
      dragOverRowIndex: null
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
    
    // Add mouse up listener to document
    document.addEventListener('mouseup', this.endDragSelection);
  },
  methods: {
    goBack() {
      window.history.back();
    },
    
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
        // Fix: Using 'medico' as the parameter name instead of 'id_medico'
        const response = await fetch(`/api/intervenciones?medico=${this.selectedMedico}&fecha=${fecha}`);
        
        if (!response.ok) {
          throw new Error(`Error al cargar las intervenciones: ${response.status} ${response.statusText}`);
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
      
      console.log('Received intervenciones:', intervenciones);
      
      // Map each intervention to its corresponding time slot
      intervenciones.forEach(intervencion => {
        // Use hora_inicio directly instead of extracting from fecha_hora_inicio
        const time = intervencion.hora_inicio;
        console.log(`Processing intervención with time: ${time}`, intervencion);
        
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
        } else {
          console.warn(`No matching slot found for time: ${time}`);
        }
      });
    },
    
    // Drag selection methods
    clearSelection() {
      // Reset selection states
      this.selectedTimeStart = '';
      this.selectedTimeEnd = '';
    },
    
    startDragSelection(event, index) {
      // Prevent text selection
      event.preventDefault();
      
      this.isMouseDown = true;
      this.startRowIndex = index;
      this.currentRowIndex = index;
      this.isDragging = true;
      
      // Clear any existing selection first
      this.clearSelection();
      
      // Set initial time range
      this.selectedTimeStart = this.intervenciones[index].hora;
      this.selectedTimeEnd = this.intervenciones[index].hora;
      
      // Add event listeners to the document for mousemove and mouseup events
      document.addEventListener('mousemove', this.handleMouseMove);
    },
    
    handleMouseMove(event) {
      if (!this.isMouseDown) return;
      
      const tableWrapper = document.querySelector('.table-wrapper');
      if (!tableWrapper) return;
      
      const tableRect = tableWrapper.getBoundingClientRect();
      
      // Auto-scroll functionality
      if (event.clientY < tableRect.top + 50) {
        // Near top edge - scroll up
        tableWrapper.scrollTop -= 10;
      } else if (event.clientY > tableRect.bottom - 50) {
        // Near bottom edge - scroll down
        tableWrapper.scrollTop += 10;
      }
    },
    
    updateDragSelection(index, event) {
      if (!this.isMouseDown) return;
      
      this.currentRowIndex = index;
      this.selectedTimeEnd = this.intervenciones[index].hora;
      
      // Force Vue to re-render the selection
      this.$forceUpdate();
    },
    
    endDragSelection() {
      if (this.isMouseDown) {
        this.isMouseDown = false;
        // Remove the mousemove event listener
        document.removeEventListener('mousemove', this.handleMouseMove);
        
        // Keep isDragging true to maintain the selection visible
        if (this.startRowIndex !== this.currentRowIndex) {
          this.isDragging = true;
        }
      }
    },
    
    isRowSelected(index) {
      if (!this.isDragging && !this.isMouseDown) return false;
      
      const start = Math.min(this.startRowIndex, this.currentRowIndex);
      const end = Math.max(this.startRowIndex, this.currentRowIndex);
      
      return index >= start && index <= end;
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
    },
    
    // Row dragging methods
    handleDragStart(event, index) {
      this.draggedRowIndex = index;
      this.dragOverRowIndex = null;
      
      // Set data for the drag operation
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', index);
      
      // Add a class to the dragged row for styling
      setTimeout(() => {
        event.target.classList.add('dragging');
      }, 0);
    },
    
    handleDragOver(event, index) {
      // Allow the drop
      event.preventDefault();
      return false;
    },
    
    handleDragEnter(event, index) {
      // Highlight the row being dragged over
      this.dragOverRowIndex = index;
      event.target.closest('tr').classList.add('drag-over');
    },
    
    handleDragLeave(event) {
      // Remove highlight from the row
      const row = event.target.closest('tr');
      if (row) row.classList.remove('drag-over');
    },
    
    handleDrop(event, index) {
      // Remove highlight from the row
      const row = event.target.closest('tr');
      if (row) row.classList.remove('drag-over');
      
      // Don't do anything if dropped on the same row
      if (this.draggedRowIndex === index) {
        return;
      }
      
      // Get the data from the drag operation
      const fromIndex = this.draggedRowIndex;
      const toIndex = index;
      
      // Reorder the rows
      const item = this.intervenciones[fromIndex];
      this.intervenciones.splice(fromIndex, 1);
      this.intervenciones.splice(toIndex, 0, item);
      
      // Reset drag state
      this.draggedRowIndex = null;
      this.dragOverRowIndex = null;
    },
    
    handleDragEnd() {
      // Reset drag state
      this.draggedRowIndex = null;
      this.dragOverRowIndex = null;
      
      // Remove all drag classes from rows
      document.querySelectorAll('.time-slot-row').forEach(row => {
        row.classList.remove('dragging', 'drag-over');
      });
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
  user-select: none; /* Prevent text selection */
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
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}

.time-slot-row:hover {
  background-color: #f8f9fa;
}

.time-slot-row.selected-row {
  background-color: #99ccff !important;
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

/* Global style added to document when dragging */
:global(.dragging-in-progress) {
  cursor: grabbing !important;
}

:global(.dragging-in-progress *) {
  cursor: grabbing !important;
}

:global(.dragging-in-progress .time-slot-row) {
  transition: background-color 0.1s ease;
}

/* Drag and drop styling */
.time-slot-row.dragging {
  opacity: 0.7;
  background-color: #cfe2ff !important;
  cursor: grabbing;
}

.time-slot-row.drag-over {
  border-top: 3px solid #0d6efd;
}

.time-slot-row.drag-over td {
  background-color: #e8f0fe;
}
</style>