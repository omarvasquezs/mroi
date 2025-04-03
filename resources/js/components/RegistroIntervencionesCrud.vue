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
      <div class="table-responsive table-wrapper" ref="tableWrapper">
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
                'has-intervention': hasIntervention(intervencion),
                'intervention-start': isInterventionStart(intervencion),
                'intervention-middle': isInterventionMiddle(intervencion),
                'intervention-end': isInterventionEnd(intervencion)
              }"
              @mousedown="handleRowMouseDown($event, index, intervencion)"
              @mouseover="handleMouseOver($event, index, intervencion)"
              @click="handleRowClick(intervencion)"
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
      dragOverRowIndex: null,
      scrollInterval: null,
      autoScrollSpeed: 5,
      autoScrollThreshold: 80,
      
      // New properties for tracking interventions
      occupiedTimeSlots: [], // To track time slots that have interventions
      interventionBlocks: [], // To track complete intervention time blocks
    };
  },
  watch: {
    selectedMedico() {
      this.fetchIntervenciones();
      this.updateSelectedMedicoName();
      // Also clear any selection when changing médico
      this.cancelSelection();
    },
    selectedFecha() {
      // Clear any active selection when the date changes
      this.cancelSelection();
      // Then fetch interventions for the new date
      this.fetchIntervenciones();
    }
  },
  mounted() {
    this.fetchMedicos();
    this.generateIntervenciones();
    
    // Add event listeners to document
    document.addEventListener('mouseup', this.endDragSelection);
    document.addEventListener('mousemove', this.handleMouseMove);

    // Force an update after component is mounted to ensure styles are applied
    this.$nextTick(() => {
      this.$forceUpdate();
    });
  },
  beforeUnmount() {
    // Clean up event listeners when component is unmounted
    document.removeEventListener('mouseup', this.endDragSelection);
    document.removeEventListener('mousemove', this.handleMouseMove);
    this.clearAutoScrollInterval();
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
        
        console.log(`Fetching intervenciones for medico=${this.selectedMedico} and fecha=${fecha}`);
        
        // Add a timestamp to prevent browser caching
        const timestamp = new Date().getTime();
        const response = await fetch(`/api/intervenciones?medico=${this.selectedMedico}&fecha=${fecha}&_=${timestamp}`);
        
        if (!response.ok) {
          throw new Error(`Error al cargar las intervenciones: ${response.status} ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Intervenciones received:', data);
        this.processInterventions(data);
        
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
    processInterventions(interventions) {
      // Reset time slots
      this.generateIntervenciones();
      this.occupiedTimeSlots = [];
      this.interventionBlocks = [];
      
      if (!interventions || interventions.length === 0) {
        console.log('No intervenciones to display');
        return;
      }
      
      // Debug: Log all interventions with their tipo_intervencion data
      console.log('All interventions received:', interventions.map(i => ({
        id: i.id,
        tipoIntervencion: i.tipoIntervencion,
        id_tipo_intervencion: i.id_tipo_intervencion
      })));
      
      // Process each intervention
      interventions.forEach(intervencion => {
        try {
          // Format start and end times
          let startTime = intervencion.hora_inicio?.substring(0, 5);
          let endTime = intervencion.hora_fin?.substring(0, 5);
          
          if (!startTime || !endTime) {
            console.warn('Invalid time format for intervention', intervencion);
            return;
          }
          
          console.log(`Processing intervention from ${startTime} to ${endTime}`, intervencion);
          
          // For display purposes, keep identical times as is
          // We will only mark one slot as occupied
          const identicalTimes = startTime === endTime;
          
          // Store the full intervention block
          this.interventionBlocks.push({
            id: intervencion.id,
            startTime,
            endTime: identicalTimes ? startTime : endTime, // Use start time for both if identical
            intervention: intervencion
          });
          
          // Find all time slots within the range
          let currentTime = startTime;
          
          // If times are identical, just process one slot
          if (identicalTimes) {
            const slot = this.intervenciones.find(i => i.hora === currentTime);
            
            if (slot) {
              // Add to occupied slots
              this.occupiedTimeSlots.push(currentTime);
              
              // Populate the slot with intervention data
              slot.id = intervencion.id;
              slot.historia = intervencion.num_historia || '';
              slot.paciente = intervencion.paciente ? 
                `${intervencion.paciente.nombres} ${intervencion.paciente.ap_paterno} ${intervencion.paciente.ap_materno}` : 
                '';
                
              // Enhanced handling for tipo_intervencion - add fallback paths to find the data
              if (intervencion.tipoIntervencion && intervencion.tipoIntervencion.tipo_intervencion) {
                slot.tipo_intervencion = intervencion.tipoIntervencion.tipo_intervencion;
              } else if (intervencion.tipo_intervencion && typeof intervencion.tipo_intervencion === 'object') {
                slot.tipo_intervencion = intervencion.tipo_intervencion.tipo_intervencion || 'No especificado';
              } else if (intervencion.tipo_intervencion && typeof intervencion.tipo_intervencion === 'string') {
                slot.tipo_intervencion = intervencion.tipo_intervencion;
              } else {
                console.warn('No tipo_intervencion found for intervention:', intervencion);
                slot.tipo_intervencion = 'No especificado';
              }
              
              slot.intervencion = intervencion;
            }
          } else {
            // Process normal range (start time different from end time)
            while (currentTime <= endTime) {
              // Find the time slot for this time
              const slot = this.intervenciones.find(i => i.hora === currentTime);
              
              if (slot) {
                // Add to occupied slots
                this.occupiedTimeSlots.push(currentTime);
                
                // Populate the slot with intervention data
                slot.id = intervencion.id;
                slot.historia = intervencion.num_historia || '';
                slot.paciente = intervencion.paciente ? 
                  `${intervencion.paciente.nombres} ${intervencion.paciente.ap_paterno} ${intervencion.paciente.ap_materno}` : 
                  '';
                
                // Enhanced handling for tipo_intervencion - add fallback paths to find the data
                if (intervencion.tipoIntervencion && intervencion.tipoIntervencion.tipo_intervencion) {
                  slot.tipo_intervencion = intervencion.tipoIntervencion.tipo_intervencion;
                } else if (intervencion.tipo_intervencion && typeof intervencion.tipo_intervencion === 'object') {
                  slot.tipo_intervencion = intervencion.tipo_intervencion.tipo_intervencion || 'No especificado';
                } else if (intervencion.tipo_intervencion && typeof intervencion.tipo_intervencion === 'string') {
                  slot.tipo_intervencion = intervencion.tipo_intervencion;
                } else {
                  console.warn('No tipo_intervencion found for intervention:', intervencion);
                  slot.tipo_intervencion = 'No especificado';
                }
                
                slot.intervencion = intervencion;
              } else {
                console.warn(`No time slot found for time ${currentTime}`);
              }
              
              // If we've reached the end time, break the loop
              if (currentTime === endTime) {
                break;
              }
              
              // Move to next time slot
              currentTime = this.getNextTimeSlot(currentTime);
            }
          }
        } catch (error) {
          console.error('Error processing intervention:', error, intervencion);
        }
      });
    },
    
    // Handle row click to display intervention details
    handleRowClick(intervencion) {
      // Only handle click if it wasn't part of a drag operation
      if (this.isMouseDown) return;
      
      // If this row has an intervention, show its details
      if (intervencion.intervencion) {
        this.selectedIntervencion = intervencion.intervencion;
        this.$refs.intervencionModal.openModal();
      }
    },
    
    // Handle mousedown to start drag selection
    handleRowMouseDown(event, index, intervencion) {
      // Prevent starting a drag on an occupied slot
      if (intervencion.intervencion || this.isOccupiedTimeSlot(intervencion.hora)) {
        // If there's an intervention, handle as a click instead
        if (!this.isDragging) {
          this.handleRowClick(intervencion);
        }
        return;
      }
      
      // Start drag selection if the slot is free
      this.startDragSelection(event, index);
    },
    
    // Check if a time slot is the start of an intervention
    isInterventionStart(intervencion) {
      if (!intervencion.intervencion) return false;
      
      // Check if this is the first slot of an intervention
      return intervencion.hora === intervencion.intervencion.hora_inicio.substring(0, 5);
    },
    
    // Check if a time slot is in the middle of an intervention
    isInterventionMiddle(intervencion) {
      if (!intervencion.intervencion) return false;
      
      const interventionId = intervencion.intervencion.id;
      const slotTime = intervencion.hora;
      
      // Get the start and end times of this intervention
      const startTime = intervencion.intervencion.hora_inicio.substring(0, 5);
      const endTime = intervencion.intervencion.hora_fin.substring(0, 5);
      
      // A row is in the middle if it's after the start time and before the end time
      return slotTime > startTime && slotTime < endTime;
    },
    
    // Check if a time slot is the end of an intervention
    isInterventionEnd(intervencion) {
      if (!intervencion.intervencion) return false;
      
      // Special handling for intervention #2 and similar cases
      // Add more debugging to help identify the issue
      const interventionId = intervencion.intervencion.id;
      const slotTime = intervencion.hora;
      const endTime = intervencion.intervencion.hora_fin.substring(0, 5);
      
      console.log(`Checking if slot ${slotTime} is end of intervention ${interventionId} with end time ${endTime}`);
      
      // Fix: Make a direct string comparison after normalizing formats
      return slotTime === endTime;
    },
    
    // Get the next time slot (30 min later)
    getNextTimeSlot(timeStr) {
      const [hours, minutes] = timeStr.split(':').map(Number);
      let newHours = hours;
      let newMinutes = minutes + 30;
      
      if (newMinutes >= 60) {
        newHours += 1;
        newMinutes -= 60;
      }
      
      return `${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}`;
    },
    
    // Check if a time slot is occupied by any intervention
    isOccupiedTimeSlot(timeStr) {
      return this.occupiedTimeSlots.includes(timeStr);
    },
    
    // Modified clearSelection to properly reset all selection state
    clearSelection() {
      this.selectedTimeStart = '';
      this.selectedTimeEnd = '';
      this.clearAutoScrollInterval();
    },
    
    // Clear auto-scroll interval
    clearAutoScrollInterval() {
      if (this.scrollInterval) {
        clearInterval(this.scrollInterval);
        this.scrollInterval = null;
      }
    },
    
    // Improved drag selection start
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
      
      document.body.classList.add('dragging-in-progress');
    },
    
    // Handle mouse over during drag
    handleMouseOver(event, index, intervencion) {
      if (!this.isMouseDown) return;
      
      // Don't allow selection over occupied slots
      if (intervencion.intervencion || this.isOccupiedTimeSlot(intervencion.hora)) {
        return;
      }
      
      // Check if we're entering an occupied time slot range
      let canSelectToThisIndex = true;
      
      // If dragging downward
      if (index > this.startRowIndex) {
        // Check all slots between start and current index
        for (let i = this.startRowIndex + 1; i <= index; i++) {
          if (this.intervenciones[i].intervencion || this.isOccupiedTimeSlot(this.intervenciones[i].hora)) {
            canSelectToThisIndex = false;
            break;
          }
        }
      } 
      // If dragging upward
      else if (index < this.startRowIndex) {
        // Check all slots between current index and start
        for (let i = index; i < this.startRowIndex; i++) {
          if (this.intervenciones[i].intervencion || this.isOccupiedTimeSlot(this.intervenciones[i].hora)) {
            canSelectToThisIndex = false;
            break;
          }
        }
      }
      
      if (!canSelectToThisIndex) {
        return;
      }
      
      // Update current row index and selected time range
      this.currentRowIndex = index;
      
      // Update the end time based on the current row
      if (this.startRowIndex <= this.currentRowIndex) {
        // Dragging downward
        this.selectedTimeEnd = this.intervenciones[index].hora;
      } else {
        // Dragging upward
        this.selectedTimeStart = this.intervenciones[index].hora;
        this.selectedTimeEnd = this.intervenciones[this.startRowIndex].hora;
      }
      
      // Force Vue to re-render the selection
      this.$forceUpdate();
    },
    
    // Improved mouse move handler for smooth scrolling
    handleMouseMove(event) {
      if (!this.isMouseDown) return;
      
      // Get reference to table wrapper
      const tableWrapper = this.$refs.tableWrapper;
      if (!tableWrapper) return;
      
      const rect = tableWrapper.getBoundingClientRect();
      
      // Check if we need to auto-scroll
      if (event.clientY < rect.top + this.autoScrollThreshold) {
        // Near top - scroll up
        this.startAutoScroll('up', Math.max(1, (rect.top + this.autoScrollThreshold - event.clientY) / 10));
      } else if (event.clientY > rect.bottom - this.autoScrollThreshold) {
        // Near bottom - scroll down
        this.startAutoScroll('down', Math.max(1, (event.clientY - (rect.bottom - this.autoScrollThreshold)) / 10));
      } else {
        // Not near edges - stop scrolling
        this.clearAutoScrollInterval();
      }
    },
    
    // Start auto-scrolling in a direction with dynamic speed
    startAutoScroll(direction, speed) {
      // Clear any existing interval first
      this.clearAutoScrollInterval();
      
      const tableWrapper = this.$refs.tableWrapper;
      if (!tableWrapper) return;
      
      // Calculate scroll step based on speed (faster near edges)
      const scrollStep = Math.min(15, Math.max(3, speed * this.autoScrollSpeed));
      
      this.scrollInterval = setInterval(() => {
        if (direction === 'up') {
          tableWrapper.scrollTop -= scrollStep;
          
          // Find the row at the top of the visible area
          const rows = tableWrapper.querySelectorAll('tr.time-slot-row');
          for (let i = 0; i < rows.length; i++) {
            const rowRect = rows[i].getBoundingClientRect();
            const rect = tableWrapper.getBoundingClientRect(); // Define rect here
            if (rowRect.top >= rect.top) {
              // This row is at or below the top of the viewport
              this.handleMouseOver(null, i);
              break;
            }
          }
        } else {
          tableWrapper.scrollTop += scrollStep;
          
          // Find the row at the bottom of the visible area
          const rows = tableWrapper.querySelectorAll('tr.time-slot-row');
          for (let i = rows.length - 1; i >= 0; i--) {
            const rowRect = rows[i].getBoundingClientRect();
            const rect = tableWrapper.getBoundingClientRect(); // Define rect here
            if (rowRect.bottom <= rect.bottom) {
              // This row is at or above the bottom of the viewport
              this.handleMouseOver(null, i);
              break;
            }
          }
        }
      }, 16); // ~60fps for smooth scrolling
    },
    
    // Improved end drag selection
    endDragSelection() {
      if (this.isMouseDown) {
        this.isMouseDown = false;
        this.clearAutoScrollInterval();
        document.body.classList.remove('dragging-in-progress');
        
        // Keep isDragging true to maintain the selection visible
        // Only if we have an actual selection (not just clicking on a single cell)
        if (this.startRowIndex !== this.currentRowIndex) {
          this.isDragging = true;
          
          // Ensure the time range is correctly ordered
          const start = Math.min(this.startRowIndex, this.currentRowIndex);
          const end = Math.max(this.startRowIndex, this.currentRowIndex);
          
          this.selectedTimeStart = this.intervenciones[start].hora;
          this.selectedTimeEnd = this.intervenciones[end].hora;
        }
      }
    },
    
    // Improved row selection check
    isRowSelected(index) {
      if (!this.isDragging && !this.isMouseDown) return false;
      
      // Calculate row time
      const rowTime = this.intervenciones[index].hora;
      
      // Check if the row time is within or equal to the selected range
      if (this.selectedTimeStart && this.selectedTimeEnd) {
        if (this.selectedTimeStart <= this.selectedTimeEnd) {
          return rowTime >= this.selectedTimeStart && rowTime <= this.selectedTimeEnd;
        } else {
          return rowTime >= this.selectedTimeEnd && rowTime <= this.selectedTimeStart;
        }
      }
      
      // Fallback to index-based selection
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
      
      // Reset the selected intervention to null to ensure a new one is created
      this.selectedIntervencion = null;
      
      // Reset the intervention modal completely before opening
      if (this.$refs.intervencionModal) {
        this.$refs.intervencionModal.resetForm();
        
        // Explicitly set these values to ensure they're cleared from any previous interactions
        this.$nextTick(() => {
          // Set the values for a new intervention
          this.$refs.intervencionModal.form.id_medico = this.selectedMedico;
          this.$refs.intervencionModal.form.fecha = this.formatDate(this.selectedFecha);
          this.$refs.intervencionModal.form.hora_inicio = this.selectedTimeStart;
          this.$refs.intervencionModal.form.hora_fin = this.selectedTimeEnd;
          
          // Then open the modal
          this.$refs.intervencionModal.openModal();
        });
      }
      
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

/* Improve the visibility of selected rows - using light yellow color #fff3cd */
.time-slot-row.selected-row {
  background-color: #fff3cd !important; /* Light yellow color */
  border: 1px solid #664d03 !important; /* Darker border for better contrast */
  color: #664d03 !important; /* Dark text for contrast */
  position: relative;
  z-index: 2;
}

.time-slot-row.selected-row td {
  background-color: #fff3cd !important;
  color: #664d03 !important;
}

/* Make the table wrapper have a fixed height for proper scrolling */
.table-wrapper {
  position: relative;
  margin-bottom: 1rem;
  max-height: 500px;
  height: 500px; /* Fixed height for predictable scrolling */
  overflow-y: auto;
  overflow-x: hidden;
  user-select: none; /* Prevent text selection */
  scrollbar-width: thin;
  scrollbar-color: #ddd #f5f5f5;
}

.table-wrapper::-webkit-scrollbar {
  width: 8px;
}

.table-wrapper::-webkit-scrollbar-track {
  background: #f5f5f5;
}

.table-wrapper::-webkit-scrollbar-thumb {
  background-color: #ddd;
  border-radius: 10px;
  border: 2px solid #f5f5f5;
}

/* Change cursor to indicate dragging is in progress */
body.dragging-in-progress {
  cursor: grabbing !important;
}

body.dragging-in-progress * {
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

/* Styles for intervention slots - using light yellow color #fff3cd */
.time-slot-row.has-intervention {
  background-color: #fff3cd !important; /* Light yellow color */
  color: #664d03 !important;
  border: 1px solid #664d03 !important; /* Darker border for better contrast */
}

/* Use light yellow with darker borders for intervention styling */
.time-slot-row.intervention-start {
  border: 1px solid #664d03 !important;
  border-bottom: none !important;
  background-color: #fff3cd !important;
  color: #664d03 !important;
}

.time-slot-row.intervention-middle {
  border-left: 1px solid #664d03 !important;
  border-right: 1px solid #664d03 !important;
  border-top: none !important;
  border-bottom: none !important;
  background-color: #fff3cd !important;
  color: #664d03 !important;
}

.time-slot-row.intervention-end {
  border: 1px solid #664d03 !important;
  border-top: none !important;
  background-color: #fff3cd !important;
  color: #664d03 !important;
}

/* For all cells inside intervention rows */
.time-slot-row.has-intervention td,
.time-slot-row.intervention-start td,
.time-slot-row.intervention-middle td,
.time-slot-row.intervention-end td {
  background-color: #fff3cd !important;
  color: #664d03 !important;
  border-color: #664d03 !important;
}

/* Prevent selecting text during dragging */
.time-slot-row {
  cursor: pointer;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}
</style>