<template>
  <div class="modal fade" id="intervencionModal" tabindex="-1" aria-labelledby="intervencionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="intervencionModalLabel">
            <i class="fas fa-file-medical me-2"></i>
            {{ intervencion ? (isEditingIntervention ? 'Editar Intervención' : 'Información de la Intervención') : 'Registrar Nueva Intervención' }}
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div v-if="loading" class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2">Cargando...</p>
          </div>
          <div v-else-if="intervencion && !isRescheduling && !isEditingIntervention">
            <!-- Display intervention details view -->
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-light">
                <h6 class="mb-0">Detalles del Paciente</h6>
              </div>
              <div class="card-body">
                <div class="row mb-2">
                  <div class="col-md-2"><strong>Historia:</strong></div>
                  <div class="col-md-4">{{ intervencion.num_historia }}</div>
                  <div class="col-md-2"><strong>Paciente:</strong></div>
                  <div class="col-md-4">
                    {{ intervencion.paciente ? `${intervencion.paciente.nombres} ${intervencion.paciente.ap_paterno} ${intervencion.paciente.ap_materno}` : '' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="card shadow-sm mb-4">
              <div class="card-header bg-light">
                <h6 class="mb-0">Detalles de la Intervención</h6>
              </div>
              <div class="card-body">
                <div class="row mb-2">
                  <div class="col-md-2"><strong>Médico:</strong></div>
                  <div class="col-md-4">
                    {{ selectedMedicoName }}
                  </div>
                  <div class="col-md-2"><strong>Médico que Indica:</strong></div>
                  <div class="col-md-4">
                    {{ getMedicoQueIndicaDisplay(intervencion.medico_que_indica_id) || 'No especificado' }}
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-2"><strong>Clínica Inicial:</strong></div>
                  <div class="col-md-4">
                    {{ getClinicaDisplay(intervencion.clinica_inicial_id) || 'No especificada' }}
                  </div>
                  <div class="col-md-2"><strong>Sede de Operación:</strong></div>
                  <div class="col-md-4">
                    {{ getClinicaDisplay(intervencion.sede_operacion_id) || 'No especificada' }}
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-2"><strong>Horario:</strong></div>
                  <div class="col-md-4">{{ formattedDate }} | {{ formattedTimeRange }}</div>
                  <div class="col-md-2"><strong>Tipo de Intervención:</strong></div>
                  <div class="col-md-4">
                    {{ getTipoIntervencionDisplay(intervencion) }}
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-2"><strong>Estado de Pago:</strong></div>
                  <div class="col-md-10">
                    <span :class="['badge', intervencion.estado === 'p' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis']"
                      style="font-size: 0.9em; padding: 0.5em 1em;">
                      <i :class="['fas', intervencion.estado === 'p' ? 'fa-check-circle' : 'fa-hourglass-half', 'me-1']"></i>
                      {{ intervencion.estado === 'p' ? 'PAGADO' : 'PENDIENTE' }}
                    </span>
                  </div>
                </div>
                <div class="row mb-2">
                  <div class="col-md-2"><strong>Observaciones:</strong></div>
                  <div class="col-md-10"><pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ intervencion.observaciones || 'Sin observaciones' }}</pre></div>
                </div>
              </div>
            </div>
            
            <div class="d-flex justify-content-end pt-3 border-top">
              <button v-if="intervencion" type="button" class="btn btn-primary me-2" @click="switchToEditMode">
                <i class="fas fa-pencil-alt me-2"></i> Editar Intervención
              </button>
              <button v-if="intervencion" type="button" class="btn btn-danger" @click="confirmDelete">
                <i class="fas fa-trash-alt me-2"></i> Eliminar Intervención
              </button>
            </div>
          </div>
          <div v-else>
            <!-- Form for creating or updating intervention -->
            <form @submit.prevent="handleSubmit">
              <div class="mb-4">
                <label class="form-label"><strong>Paciente:</strong></label>
                <div class="input-group">
                  <select v-model="form.num_historia" class="form-select" required :class="{'is-invalid': formValidationErrors.includes('paciente')}">
                    <option value="" disabled>Seleccione un paciente</option>
                    <option v-for="paciente in pacientes" :key="paciente.num_historia" :value="paciente.num_historia">
                      {{ paciente.nombre }}
                    </option>
                  </select>
                  <button 
                    @click="editPaciente(pacientes.find(p => p.num_historia === form.num_historia))" 
                    class="btn btn-success ms-2" 
                    v-if="form.num_historia && pacientes.find(p => p.num_historia === form.num_historia)" 
                    type="button">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button @click="showNewPacienteForm" class="btn btn-primary ms-2" 
                    title="Agregar nuevo paciente" type="button">
                    <i class="fas fa-user-plus"></i>
                  </button>
                </div>
                <div class="invalid-feedback" v-if="formValidationErrors.includes('paciente')">
                  Por favor seleccione un paciente.
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label"><strong>Tipo de Intervención:</strong></label>
                <div class="input-group">
                  <select class="form-select" v-model="form.id_tipo_intervencion" required :class="{'is-invalid': formValidationErrors.includes('tipo_intervencion')}">
                    <option value="" disabled>Seleccione un tipo</option>
                    <option v-for="tipo in tiposIntervenciones" :key="tipo.id" :value="tipo.id">
                      {{ tipo.tipo_intervencion }} - S/. {{ tipo.precio }}
                    </option>
                  </select>
                  <button @click="editTipoIntervencion(tiposIntervenciones.find(t => t.id === form.id_tipo_intervencion))" 
                    class="btn btn-success ms-2" v-if="form.id_tipo_intervencion" type="button">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button @click="showCreateTipoIntervencionForm" class="btn btn-primary ms-2" 
                    title="Agregar nuevo tipo de intervención" type="button">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
                <div class="invalid-feedback" v-if="formValidationErrors.includes('tipo_intervencion')">
                  Por favor seleccione un tipo de intervención.
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label"><strong>Clínica Inicial:</strong></label>
                <div class="input-group">
                  <select v-model="form.clinica_inicial_id" class="form-select">
                    <option value="" disabled>Seleccione una clínica</option>
                    <option v-for="local in locales" :key="local.id" :value="local.id">
                      {{ local.nombre }}
                    </option>
                  </select>
                  <button @click="editLocal(locales.find(l => l.id === form.clinica_inicial_id))" class="btn btn-success ms-2" v-if="form.clinica_inicial_id" type="button">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button @click="showNewLocalForm" class="btn btn-primary ms-2" title="Agregar nueva clínica" type="button">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label"><strong>Médico que Indica:</strong></label>
                <div class="input-group">
                  <select v-model="form.medico_que_indica_id" class="form-select">
                    <option value="" disabled>Seleccione un médico</option>
                    <option v-for="medico in medicos" :key="medico.id" :value="medico.id">
                      {{ medico.nombres }} {{ medico.ap_paterno }} {{ medico.ap_materno }}
                    </option>
                  </select>
                  <button @click="editMedico(medicos.find(m => m.id === form.medico_que_indica_id))" class="btn btn-success ms-2" v-if="form.medico_que_indica_id" type="button">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button @click="showNewMedicoForm" class="btn btn-primary ms-2" title="Agregar nuevo médico" type="button">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label"><strong>Sede de Operación:</strong></label>
                <div class="input-group">
                  <select v-model="form.sede_operacion_id" class="form-select">
                    <option value="" disabled>Seleccione una sede</option>
                    <option v-for="local in locales" :key="local.id" :value="local.id">
                      {{ local.nombre }}
                    </option>
                  </select>
                  <button @click="editLocal(locales.find(l => l.id === form.sede_operacion_id))" class="btn btn-success ms-2" v-if="form.sede_operacion_id" type="button">
                    <i class="fas fa-pencil-alt"></i>
                  </button>
                  <button @click="showNewLocalForm" class="btn btn-primary ms-2" title="Agregar nueva sede" type="button">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>

              <!-- Hidden fecha field - No longer visible but still stores the value -->
              <input type="hidden" v-model="form.fecha">

              <div class="mb-4">
                <label class="form-label"><strong>Horario:</strong></label>
                <div class="time-range-display p-3 border rounded bg-light">
                  <div class="date-display mb-2">{{ selectedDateFormatted }}</div>
                  {{ timeRangeDisplay || 'No se ha seleccionado un rango de horas' }}
                  <small class="d-block text-muted mt-1">
                    Para seleccionar o cambiar el horario, arrastra sobre las franjas horarias en el calendario
                  </small>
                </div>
                <!-- Hidden inputs to store the values -->
                <input type="hidden" v-model="form.hora_inicio">
                <input type="hidden" v-model="form.hora_fin">
              </div>

              <div class="mb-4">
                <label class="form-label"><strong>Observaciones:</strong></label>
                <textarea class="form-control" rows="3" v-model="form.observaciones"></textarea>
              </div>

              <div class="alert alert-danger" v-if="errorMessage">
                {{ errorMessage }}
              </div>

              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" @click="cancelEditOrClose">{{ intervencion && isEditingIntervention ? 'Cancelar Edición' : 'Cancelar' }}</button>
                <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                  {{ intervencion && isEditingIntervention ? 'Actualizar Intervención' : (intervencion ? 'Actualizar' : 'Generar Orden de Cirugía') }}
                </button>
              </div>
            </form>
          </div>
        </div>
        <div class="modal-footer" v-if="intervencion && !isRescheduling && !isEditingIntervention">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>CERRAR</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal for Creating/Editing Tipo de Intervención -->
  <div class="modal fade" id="tipoIntervencionModal" tabindex="-1" aria-labelledby="tipoIntervencionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="tipoIntervencionModalLabel">
            {{ isEditingTipoIntervencion ? 'Editar Tipo de Intervención' : 'Crear Tipo de Intervención' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitTipoIntervencionForm">
            <div class="mb-3">
              <label for="tipo_intervencion" class="form-label">Tipo de Intervención*:</label>
              <input type="text" v-model="tipoIntervencionForm.tipo_intervencion" id="tipo_intervencion" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="precio" class="form-label">Precio*:</label>
              <div class="input-group">
                <span class="input-group-text">S/.</span>
                <input type="number" v-model="tipoIntervencionForm.precio" id="precio" class="form-control" step="0.01" required>
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary">{{ isEditingTipoIntervencion ? 'Actualizar' : 'Guardar' }}</button>
              <button type="button" @click="closeTipoIntervencionModal" class="btn btn-secondary">Cerrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal for Creating/Editing Locales -->
  <div class="modal fade" id="localModal" tabindex="-1" aria-labelledby="localModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="localModalLabel">{{ isEditingLocal ? 'Editar Clínica/Sede' : 'Crear Clínica/Sede' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitLocalForm">
            <div v-if="localFormErrors.length" class="alert alert-danger alert-dismissible fade show" role="alert">
              <ul class="mb-0">
                <li v-for="(error, idx) in localFormErrors" :key="idx">{{ error }}</li>
              </ul>
              <button type="button" class="btn-close" @click="localFormErrors = []" aria-label="Close"></button>
            </div>
            <div class="mb-3">
              <label for="nombreLocal" class="form-label">Nombre*:</label>
              <input type="text" v-model="localForm.nombre" id="nombreLocal" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="direccionLocal" class="form-label">Dirección:</label>
              <input type="text" v-model="localForm.direccion" id="direccionLocal" class="form-control">
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary">{{ isEditingLocal ? 'Actualizar' : 'Guardar' }}</button>
              <button type="button" @click="closeLocalModal" class="btn btn-secondary">Cerrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal for Creating/Editing Médicos -->
  <div class="modal fade" id="medicoModal" tabindex="-1" aria-labelledby="medicoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="medicoModalLabel">{{ isEditingMedico ? 'Editar Médico' : 'Crear Médico' }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form @submit.prevent="submitMedicoForm" class="mb-5" autocomplete="off">
            <div v-if="medicoFormErrors.includes('DNI ya existe en la base de datos.')" class="alert alert-danger alert-dismissible fade show" role="alert">
              DNI ya existe en la base de datos.
              <button type="button" class="btn-close" @click="closeMedicoFormErrorAlert('DNI ya existe en la base de datos.')" aria-label="Close"></button>
            </div>
            <div v-if="medicoFormErrors.includes('CMP ya existe en la base de datos.')" class="alert alert-danger alert-dismissible fade show" role="alert">
              CMP ya existe en la base de datos.
              <button type="button" class="btn-close" @click="closeMedicoFormErrorAlert('CMP ya existe en la base de datos.')" aria-label="Close"></button>
            </div>
            <div class="mb-3">
              <label for="nombresMedico" class="form-label">Nombres*:</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" v-model="medicoForm.nombres" id="nombresMedico" class="form-control" placeholder="Ej: Juan" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="apPaternoMedico" class="form-label">Apellido Paterno*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                  <input type="text" v-model="medicoForm.ap_paterno" id="apPaternoMedico" class="form-control" placeholder="Ej: Pérez" required>
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="apMaternoMedico" class="form-label">Apellido Materno*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                  <input type="text" v-model="medicoForm.ap_materno" id="apMaternoMedico" class="form-control" placeholder="Ej: Gómez" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="dniMedico" class="form-label">DNI*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                  <input type="text" v-model="medicoForm.dni" @input="onlyNumbers" id="dniMedico" class="form-control" placeholder="Ej: 00123456" required minlength="8" maxlength="8">
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="cmpMedico" class="form-label">CMP*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                  <input type="text" v-model="medicoForm.cmp" @input="onlyNumbers" id="cmpMedico" class="form-control" placeholder="Ej: 001234" required maxlength="6">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="telefonoMedico" class="form-label">Teléfono*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-phone"></i></span>
                  <input type="tel" v-model="medicoForm.telefono" id="telefonoMedico" class="form-control" placeholder="Ej: 987654321" required maxlength="9">
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="emailMedico" class="form-label">Email*:</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                  <input type="email" v-model="medicoForm.email" id="emailMedico" class="form-control" placeholder="Ej: juan@example.com" required>
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="direccionMedico" class="form-label">Dirección*:</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                <input type="text" v-model="medicoForm.direccion" id="direccionMedico" class="form-control" placeholder="Ej: Calle Falsa 123" required>
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary">{{ isEditingMedico ? 'Actualizar' : 'Guardar' }}</button>
              <button type="button" @click="closeMedicoModal" class="btn btn-secondary">Cerrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Modal for Creating/Editing Paciente -->
  <div class="modal fade" id="pacienteModal" tabindex="-1" aria-labelledby="pacienteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="pacienteModalLabel">
            {{ isEditingPaciente ? `Editar Paciente con DNI ${pacienteForm.doc_identidad}` : 'Crear Paciente' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form @submit.prevent="submitPacienteForm" class="mb-3" autocomplete="off">
            <div v-if="pacienteFormErrors.includes('DNI ya existe en la base de datos.')"
              class="alert alert-danger alert-dismissible fade show" role="alert">
              DNI ya existe en la base de datos.
              <button type="button" class="btn-close" @click="closeFormErrorAlert" aria-label="Close"></button>
            </div>
            <fieldset class="mb-3">
              <legend>Identificación Personal</legend>
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label for="PacientesNombres" class="form-label">Nombres*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="pacienteForm.nombres" id="PacientesNombres" name="PacientesNombres"
                      class="form-control" placeholder="Ej: Juan" autocomplete="new-nombres" required>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesApPaterno" class="form-label">Apellido Paterno*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="pacienteForm.ap_paterno" id="PacientesApPaterno" name="PacientesApPaterno"
                      class="form-control" placeholder="Ej: Pérez" autocomplete="new-ap_paterno" required>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesApMaterno" class="form-label">Apellido Materno*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" v-model="pacienteForm.ap_materno" id="PacientesApMaterno" name="PacientesApMaterno"
                      class="form-control" placeholder="Ej: Gómez" autocomplete="new-ap_materno" required>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="PacientesDocIdentidad" class="form-label">DNI*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" v-model="pacienteForm.doc_identidad" @input="onlyNumbers"
                      id="PacientesDocIdentidad" name="PacientesDocIdentidad" class="form-control"
                      placeholder="Ej: 00123456" autocomplete="new-doc_identidad" required minlength="8"
                      maxlength="8">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesFNacimiento" class="form-label">Fecha de Nacimiento:</label>
                  <input type="date" v-model="pacienteForm.f_nacimiento" id="PacientesFNacimiento" name="PacientesFNacimiento"
                    class="form-control" autocomplete="new-f_nacimiento">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesEstadoCivil" class="form-label">Estado Civil:</label>
                  <select v-model="pacienteForm.estado_civil" id="PacientesEstadoCivil" name="PacientesEstadoCivil"
                    class="form-select" autocomplete="new-estado_civil">
                    <option value="" disabled>Seleccione Estado Civil</option>
                    <option value="S">Soltero</option>
                    <option value="C">Casado</option>
                    <option value="V">Viudo</option>
                    <option value="D">Divorciado</option>
                  </select>
                </div>
              </div>
            </fieldset>

            <fieldset class="mb-3">
              <legend>Información de Contacto</legend>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="PacientesTelefonoPersonal" class="form-label">Teléfono Personal*:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" v-model="pacienteForm.telefono_personal" id="PacientesTelefonoPersonal"
                      name="PacientesTelefonoPersonal" class="form-control" placeholder="Ej: 987654321"
                      autocomplete="new-telefono_personal" required maxlength="9">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesCorreoPersonal" class="form-label">Correo Personal:</label>
                  <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="email" v-model="pacienteForm.correo_personal" id="PacientesCorreoPersonal"
                      name="PacientesCorreoPersonal" class="form-control" placeholder="Ej: ejemplo@correo.com"
                      autocomplete="new-correo_personal">
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesDireccionPersonal" class="form-label">Dirección Personal:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                    <input type="text" v-model="pacienteForm.direccion_personal" id="PacientesDireccionPersonal"
                      name="PacientesDireccionPersonal" class="form-control" placeholder="Ej: Av. Siempre Viva 123"
                      autocomplete="new-direccion_personal">
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="mb-3">
              <legend>Información Profesional (Opcional)</legend>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="PacientesOcupacion" class="form-label">Ocupación:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                    <input type="text" v-model="pacienteForm.ocupacion" id="PacientesOcupacion" name="PacientesOcupacion"
                      class="form-control" placeholder="Ej: Ingeniero" autocomplete="new-ocupacion">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesNomCentroLaboral" class="form-label">Nombre del Centro Laboral:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                    <input type="text" v-model="pacienteForm.nom_centro_laboral" id="PacientesNomCentroLaboral"
                      name="PacientesNomCentroLaboral" class="form-control" placeholder="Ej: Empresa XYZ"
                      autocomplete="new-nom_centro_laboral">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesTelefonoTrabajo" class="form-label">Teléfono del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="tel" v-model="pacienteForm.telefono_trabajo" id="PacientesTelefonoTrabajo"
                      name="PacientesTelefonoTrabajo" class="form-control" placeholder="Ej: 123456789"
                      autocomplete="new-telefono_trabajo" maxlength="9">
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="PacientesCorreoTrabajo" class="form-label">Correo del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text">@</span>
                    <input type="email" v-model="pacienteForm.correo_trabajo" id="PacientesCorreoTrabajo"
                      name="PacientesCorreoTrabajo" class="form-control" placeholder="Ej: trabajo@empresa.com"
                      autocomplete="new-correo_trabajo">
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesDireccionTrabajo" class="form-label">Dirección del Trabajo:</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                    <input type="text" v-model="pacienteForm.direccion_trabajo" id="PacientesDireccionTrabajo"
                      name="PacientesDireccionTrabajo" class="form-control" placeholder="Ej: Calle Falsa 456"
                      autocomplete="new-direccion_trabajo">
                  </div>
                </div>
              </div>
            </fieldset>

            <fieldset class="mb-3">
              <legend>Administración Médica</legend>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Estado de Historia*:</label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="pacienteForm.estado_historia" id="estadoActivo"
                      value="1" required>
                    <label class="form-check-label" for="estadoActivo">ACTIVO</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" v-model="pacienteForm.estado_historia" id="estadoInactivo"
                      value="0" required>
                    <label class="form-check-label" for="estadoInactivo">INACTIVO</label>
                  </div>
                </div>
                <div class="col-md-12 mb-3">
                  <label for="PacientesObservaciones" class="form-label">Observaciones:</label>
                  <textarea v-model="pacienteForm.observaciones" id="PacientesObservaciones" name="PacientesObservaciones"
                    class="form-control" placeholder="Escriba sus observaciones aquí" autocomplete="new-observaciones"
                    rows="5"></textarea>
                </div>
              </div>
            </fieldset>

            <div class="d-flex justify-content-end gap-2">
              <button type="submit" class="btn btn-primary">{{ isEditingPaciente ? 'Actualizar' : 'Guardar' }}</button>
              <button type="button" @click="closePacienteModal" class="btn btn-secondary">Cerrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap';

export default {
  props: {
    selectedTime: {
      type: String,
      default: ''
    },
    selectedTimeEnd: {
      type: String,
      default: ''
    },
    selectedDate: {
      type: Date,
      default: null
    },
    selectedMedico: {
      type: [String, Number],
      default: ''
    },
    selectedMedicoName: {
      type: String,
      default: ''
    },
    intervencion: {
      type: Object,
      default: null
    },
  },
  data() {
    return {
      modal: null,
      loading: false,
      form: {
        id: '',
        id_medico: this.selectedMedico || '',
        num_historia: '',
        fecha: this.selectedDate ? this.formatDateForInput(this.selectedDate) : '',
        hora_inicio: this.selectedTime || '',
        hora_fin: this.selectedTimeEnd || this.getDefaultEndTime(this.selectedTime) || '',
        observaciones: '',
        id_tipo_intervencion: '', // Corrected field name
        clinica_inicial_id: '',
        medico_que_indica_id: '',
        sede_operacion_id: '',
        estado: 'n' // Default to 'n' for new interventions
      },
      pacientes: [],
      medicos: [],
      tiposIntervenciones: [],
      errorMessage: '',
      isSubmitting: false,
      formValidationErrors: [],
      isEditingIntervention: false, // Added for edit mode
      // Add properties for local display of date and time
      displayDate: null,
      displayTimeStart: '',
      displayTimeEnd: '',
      // For tipo intervencion modal
      isEditingTipoIntervencion: false,
      tipoIntervencionForm: {
        tipo_intervencion: '',
        precio: ''
      },
      tipoIntervencionModal: null,
      // For paciente modal
      isEditingPaciente: false,
      pacienteForm: {
        num_historia: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        doc_identidad: '',
        estado_historia: '1',
        fecha_filiacion: '',
        f_nacimiento: '',
        estado_civil: '',
        telefono_personal: '',
        correo_personal: '',
        direccion_personal: '',
        ocupacion: '',
        nom_centro_laboral: '',
        telefono_trabajo: '',
        correo_trabajo: '',
        direccion_trabajo: '',
        observaciones: ''
      },
      pacienteFormErrors: [],
      editingPacienteId: null,
      pacienteModal: null,
      // For local modal
      isEditingLocal: false,
      localForm: {
        nombre: '',
        direccion: '',
        telefono: ''
      },
      localModal: null,
      localFormErrors: [],
      // For médico modal
      isEditingMedico: false,
      medicoForm: {
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        dni: '',
        cmp: '',
        telefono: '',
        email: '',
        direccion: '',
        estado: 1
      },
      medicoModal: null,
      medicoFormErrors: [],
      rescheduling: false,
      locales: [],
    };
  },
  computed: {
    formattedDate() {
      if (!this.intervencion || !this.intervencion.fecha) return '';
      return new Date(this.intervencion.fecha).toLocaleDateString();
    },
    formattedTimeRange() {
      if (!this.intervencion) return '';
      
      let horaInicio = this.intervencion.hora_inicio || 
                       (this.intervencion.fecha ? new Date(this.intervencion.fecha).toLocaleTimeString('es-ES', {
                          hour: '2-digit',
                          minute: '2-digit'
                        }) : '');
                        
      let horaFin = this.intervencion.hora_fin || horaInicio;
      
      if (horaInicio === horaFin) {
        return horaInicio;
      } else {
        return `${horaInicio} - ${horaFin}`;
      }
    },
    isRescheduling() {
      return this.rescheduling;
    },
    timeRangeDisplay() {
      if (this.selectedTime && this.selectedTimeEnd && this.selectedTime !== this.selectedTimeEnd) {
        return `${this.selectedTime} - ${this.selectedTimeEnd}`;
      } else if (this.selectedTime) {
        return this.selectedTime;
      }
      return '';
    },
    selectedDateFormatted() {
      if (!this.selectedDate && !this.form.fecha) return '';
      
      let dateToFormat;
      if (this.form.fecha) {
        // If we have a form date value, use that
        const [year, month, day] = this.form.fecha.split('-').map(Number);
        dateToFormat = new Date(year, month - 1, day);
      } else {
        // Otherwise use the selected date prop
        dateToFormat = new Date(this.selectedDate);
      }
      
      // Format the date as a localized string
      return dateToFormat.toLocaleDateString('es-ES', {
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
      });
    },
    minDate() {
      const today = new Date();
      return today.toISOString().split('T')[0];
    }
  },
  created() {
    this.displayDate = this.selectedDate;
    this.displayTimeStart = this.selectedTime;
    this.displayTimeEnd = this.selectedTimeEnd || this.getDefaultEndTime(this.selectedTime);
  },
  mounted() {
    this.fetchPacientes();
    this.fetchMedicos();
    this.fetchTiposIntervenciones();
    this.fetchLocales();
  },
  methods: {
    switchToEditMode() {
      this.isEditingIntervention = true;
      this.populateForm(); // Ensure form is populated with current intervention data
    },
    async fetchPacientes() {
      try {
        const response = await fetch('/api/pacientes-list');
        if (!response.ok) {
          throw new Error('Error al cargar los pacientes');
        }
        const data = await response.json();
        this.pacientes = data.map(paciente => ({
          num_historia: paciente.num_historia,
          id: paciente.id,
          nombre: `${paciente.nombres} ${paciente.ap_paterno} ${paciente.ap_materno} (${paciente.doc_identidad})`,
          ...paciente
        }));
      } catch (error) {
        console.error('Error fetching pacientes:', error);
      }
    },
    
    async fetchMedicos() {
      try {
        console.log('Fetching médicos for dropdown...');
        const response = await fetch('/api/medicos-list');
        if (!response.ok) {
          throw new Error('Error al cargar los médicos');
        }
        const data = await response.json();
        console.log('Médicos fetched:', data);
        // Store the data directly, as it now contains the required fields
        this.medicos = data; 
      } catch (error) {
        console.error('Error fetching medicos:', error);
      }
    },
    
    async fetchTiposIntervenciones() {
      try {
        const response = await fetch('/api/tipos-intervenciones');
        if (!response.ok) {
          throw new Error('Error al cargar los tipos de intervenciones');
        }
        const data = await response.json();
        if (Array.isArray(data)) {
          this.tiposIntervenciones = data.filter(item => item && typeof item === 'object' && item.id != null);
        } else if (data && typeof data === 'object') {
          // If data has a data property that's an array
          if (Array.isArray(data.data)) {
            this.tiposIntervenciones = data.data.filter(item => item && typeof item === 'object' && item.id != null);
          } else {
            // Convert object to array if needed
            this.tiposIntervenciones = Object.values(data).filter(item => item && typeof item === 'object' && item.id != null);
          }
        } else {
          this.tiposIntervenciones = [];
          console.error('Unexpected API response format:', data);
        }
      } catch (error) {
        console.error('Error fetching tipos de intervenciones:', error);
      }
    },
    
    async fetchLocales() {
      try {
        // Use the new endpoint that returns all locales without pagination
        const response = await fetch('/api/locales-list');
        if (!response.ok) throw new Error('Error al cargar los locales');
        const data = await response.json();
        this.locales = Array.isArray(data) ? data : [];
      } catch (error) {
        console.error('Error fetching locales:', error);
      }
    },
    // Locales modal logic
    showNewLocalForm() {
      // Hide the intervencion modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Reset the local form
      this.localForm = {
        nombre: '',
        direccion: '',
        telefono: ''
      };
      
      // Set editing flag to false
      this.isEditingLocal = false;
      
      // Show the local modal
      setTimeout(() => {
        this.localModal = new Modal(document.getElementById('localModal'));
        this.localModal.show();
      }, 500);
    },
    
    editLocal(local) {
      if (!local) return;
      
      // Hide the intervencion modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Copy local data to form
      this.localForm = { ...local };
      
      // Set editing flag
      this.isEditingLocal = true;
      
      // Show the local modal
      setTimeout(() => {
        this.localModal = new Modal(document.getElementById('localModal'));
        this.localModal.show();
      }, 500);
    },
    
    async submitLocalForm() {
      this.localFormErrors = [];
      
      try {
        let response;
        
        if (this.isEditingLocal) {
          // Update existing local
          response = await fetch(`/api/locales/${this.localForm.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify(this.localForm)
          });
        } else {
          // Create new local
          response = await fetch('/api/locales', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify(this.localForm)
          });
        }
        
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.message || 'Error al guardar la clínica/sede');
        }
        
        // Close modal and refresh locales
        this.closeLocalModal();
        this.fetchLocales();
        
        // Show success message
        alert(this.isEditingLocal ? 'Clínica/Sede actualizada exitosamente' : 'Clínica/Sede creada exitosamente');
        
      } catch (error) {
        console.error('Error saving local:', error);
        
        if (error.response && error.response.status === 422) {
          this.localFormErrors = Object.values(error.response.data.errors).flat();
        } else {
          this.localFormErrors = ['Error al guardar la clínica/sede'];
        }
      }
    },
    
    closeLocalModal() {
      if (this.localModal) {
        this.localModal.hide();
      }
      
      // Reset form and errors
      this.localFormErrors = [];
      
      // Reopen the intervención modal
      setTimeout(() => {
        this.modal = new Modal(document.getElementById('intervencionModal'));
        this.modal.show();
      }, 500);
    },
    
    // Médicos modal logic
    showNewMedicoForm() {
      // Hide the intervencion modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Reset the médico form
      this.medicoForm = {
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        dni: '',
        cmp: '',
        telefono: '',
        email: '',
        direccion: '',
        estado: 1
      };
      
      // Set editing flag to false
      this.isEditingMedico = false;
      
      // Show the médico modal
      setTimeout(() => {
        this.medicoModal = new Modal(document.getElementById('medicoModal'));
        this.medicoModal.show();
      }, 500);
    },
    
    async editMedico(medico) {
      if (!medico) return;
      
      // Hide the intervencion modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();
      
      try {
        console.log('Fetching complete médico details for:', medico.id);
        
        // Fetch complete médico details from the dedicated endpoint
        const response = await fetch(`/api/medicos-detail/${medico.id}`);
        
        if (!response.ok) {
          throw new Error(`Error fetching médico details: ${response.status}`);
        }
        
        // Get the complete médico data with all fields
        const medicoDetails = await response.json();
        console.log('Complete médico details:', medicoDetails);
        
        // Copy médico data to form using the returned details
        this.medicoForm = { 
          id: medicoDetails.id,
          nombres: medicoDetails.nombres || '',
          ap_paterno: medicoDetails.ap_paterno || '',
          ap_materno: medicoDetails.ap_materno || '',
          dni: medicoDetails.dni || '',
          cmp: medicoDetails.cmp || '',
          telefono: medicoDetails.telefono || '',
          email: medicoDetails.email || '',
          direccion: medicoDetails.direccion || '',
          estado: medicoDetails.estado || 1
        };
        
        console.log('Form data after mapping:', this.medicoForm);
        
        // Set editing flag
        this.isEditingMedico = true;
        
        // Show the médico modal
        setTimeout(() => {
          this.medicoModal = new Modal(document.getElementById('medicoModal'));
          this.medicoModal.show();
        }, 500);
        
      } catch (error) {
        console.error('Error in editMedico:', error);
        alert('Error al cargar los datos del médico. Por favor intente nuevamente.');
        
        // Reopen the intervención modal if there was an error
        setTimeout(() => {
          this.modal = new Modal(document.getElementById('intervencionModal'));
          this.modal.show();
        }, 500);
      }
    },
    
    async submitMedicoForm() {
      this.medicoFormErrors = [];
      
      try {
        // Check if DNI already exists (for new doctors or changed DNIs)
        if (!this.isEditingMedico || 
            (this.isEditingMedico && this.medicoForm.dni !== this.medicos.find(m => m.id === this.medicoForm.id)?.dni)) {
          const dniExists = await this.checkDniExists(this.medicoForm.dni);
          if (dniExists) {
            this.medicoFormErrors.push('DNI ya existe en la base de datos.');
            return;
          }
        }
        
        // Check if CMP already exists
        if (!this.isEditingMedico || 
            (this.isEditingMedico && this.medicoForm.cmp !== this.medicos.find(m => m.id === this.medicoForm.id)?.cmp)) {
          const cmpExists = await this.checkCmpExists(this.medicoForm.cmp);
          if (cmpExists) {
            this.medicoFormErrors.push('CMP ya existe en la base de datos.');
            return;
          }
        }
        
        let response;
        
        if (this.isEditingMedico) {
          // Update existing médico
          response = await fetch(`/api/medicos/${this.medicoForm.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify(this.medicoForm)
          });
        } else {
          // Create new médico
          response = await fetch('/api/medicos', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify(this.medicoForm)
          });
        }
        
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.message || 'Error al guardar el médico');
        }
        
        // Close modal and refresh médicos
        this.closeMedicoModal();
        this.fetchMedicos();
        
        // Show success message
        alert(this.isEditingMedico ? 'Médico actualizado exitosamente' : 'Médico creado exitosamente');
        
      } catch (error) {
        console.error('Error saving médico:', error);
        
        if (error.response && error.response.status === 422) {
          this.medicoFormErrors = Object.values(error.response.data.errors).flat();
        } else {
          this.medicoFormErrors = ['Error al guardar el médico'];
        }
      }
    },
    
    closeMedicoModal() {
      if (this.medicoModal) {
        this.medicoModal.hide();
      }
      
      // Reset form and errors
      this.medicoFormErrors = [];
      
      // Reopen the intervención modal
      setTimeout(() => {
        this.modal = new Modal(document.getElementById('intervencionModal'));
        this.modal.show();
      }, 500);
    },
    
    closeMedicoFormErrorAlert(error) {
      if (error) {
        this.medicoFormErrors = this.medicoFormErrors.filter(e => e !== error);
      } else {
        this.medicoFormErrors = [];
      }
    },
    
    async checkDniExists(dni) {
      try {
        const response = await fetch(`/api/medicos/check-dni?dni=${dni}`);
        if (!response.ok) throw new Error('Error al verificar DNI');
        const data = await response.json();
        return data.exists;
      } catch (error) {
        console.error('Error checking DNI:', error);
        return false;
      }
    },
    
    async checkCmpExists(cmp) {
      try {
        const response = await fetch(`/api/medicos/check-cmp?cmp=${cmp}`);
        if (!response.ok) throw new Error('Error al verificar CMP');
        const data = await response.json();
        return data.exists;
      } catch (error) {
        console.error('Error checking CMP:', error);
        return false;
      }
    },
    openModal() {
      this.loading = true;
      this.errorMessage = '';
      this.isSubmitting = false;
      this.isEditingIntervention = false; // Ensure edit mode is off when opening for new/view
      
      // Reset form first - always start with a clean form
      this.resetForm();
      
      // If we have an intervention, we're viewing/editing, otherwise creating new
      if (this.intervencion) {
        this.populateForm();
      } else {
        // Set default values from props for new intervention
        this.form.id_medico = this.selectedMedico;
        if (this.selectedDate) {
          this.form.fecha = this.formatDate(this.selectedDate);
        }
        this.form.hora_inicio = this.selectedTime || '';
        this.form.hora_fin = this.selectedTimeEnd || this.getDefaultEndTime(this.selectedTime) || '';
      }
      
      this.loading = false;
      
      // Open modal using Bootstrap JS
      const modal = new bootstrap.Modal(document.getElementById('intervencionModal'));
      this.modal = modal; // Store reference for later use
      modal.show();
    },
    
    // Helper method to calculate the end time based on start time
    getDefaultEndTime(startTime) {
      if (!startTime) return '';
      
      // Calculate the next time slot (adding 30 minutes)
      const [hours, minutes] = startTime.split(':').map(Number);
      let newHours = hours;
      let newMinutes = minutes + 30;
      
      if (newMinutes >= 60) {
        newHours += 1;
        newMinutes -= 60;
      }
      
      return `${String(newHours).padStart(2, '0')}:${String(newMinutes).padStart(2, '0')}`;
    },
    
    resetForm() {
      this.form = {
        id: '',
        id_medico: this.selectedMedico || '',
        num_historia: '',
        fecha: this.selectedDate ? this.formatDateForInput(this.selectedDate) : '',
        hora_inicio: this.selectedTime || '',
        hora_fin: this.selectedTimeEnd || this.getDefaultEndTime(this.selectedTime) || '',
        observaciones: '',
        id_tipo_intervencion: '', // Corrected field name
        clinica_inicial_id: '',
        medico_que_indica_id: '',
        sede_operacion_id: '',
        estado: 'n' // Default to 'n' for new interventions
      };
      this.formValidationErrors = [];
      this.errorMessage = '';
      this.isEditingIntervention = false; // Reset edit mode on form reset
      this.rescheduling = false;
    },
    populateForm() {
      if (this.intervencion) {
        this.form = {
          ...this.intervencion,
          id_tipo_intervencion: this.intervencion.id_tipo_intervencion || this.intervencion.tipo_intervencion_id,
          fecha: this.intervencion.fecha ? this.formatDateForInput(new Date(this.intervencion.fecha)) : '',
          // Ensure other fields are correctly mapped if names differ
        };
        // If editing, set the selected medico and patient if not already set by intervention data
        if (!this.form.id_medico && this.selectedMedico) {
          this.form.id_medico = this.selectedMedico;
        }
        // Populate display date/time as well if needed for the form's display elements
        this.displayDate = new Date(this.intervencion.fecha);
        this.displayTimeStart = this.intervencion.hora_inicio;
        this.displayTimeEnd = this.intervencion.hora_fin;
      } else {
        // This case is for new interventions, handled by openModal/resetForm
        this.resetForm();
      }
      console.log('Form populated:', this.form);
    },
    formatDateForInput(date) {
      if (!date) return '';
      const d = new Date(date);
      let month = '' + (d.getMonth() + 1);
      let day = '' + d.getDate();
      const year = d.getFullYear();
      if (month.length < 2) month = '0' + month;
      if (day.length < 2) day = '0' + day;
      return [year, month, day].join('-');
    },
    async handleSubmit() {
      this.isSubmitting = true;
      this.errorMessage = '';
      this.formValidationErrors = [];

      // Basic Validations
      if (!this.form.num_historia) this.formValidationErrors.push('paciente');
      if (!this.form.id_tipo_intervencion) this.formValidationErrors.push('tipo_intervencion');
      // Add other necessary validations here, e.g., for horario if it's mandatory

      if (this.formValidationErrors.length > 0) {
        this.isSubmitting = false;
        this.errorMessage = "Por favor, corrija los errores del formulario.";
        return;
      }

      const payload = { ...this.form };

      // Ensure fecha is in YYYY-MM-DD format if it's coming from a Date object
      if (payload.fecha && payload.fecha instanceof Date) {
        payload.fecha = this.formatDateForInput(payload.fecha);
      }
      
      // If hora_inicio or hora_fin are not set from selection, try to use form's existing values
      // This might be relevant if the user edits other fields without re-selecting time
      if (!this.selectedTime && payload.hora_inicio) {
        // Keep existing form.hora_inicio
      } else {
        payload.hora_inicio = this.selectedTime || payload.hora_inicio || '00:00'; 
      }

      if (!this.selectedTimeEnd && payload.hora_fin) {
        // Keep existing form.hora_fin
      } else {
        payload.hora_fin = this.selectedTimeEnd || payload.hora_fin || this.getDefaultEndTime(payload.hora_inicio) || '00:30';
      }

      try {
        let response;
        let url;
        let method;

        if (this.intervencion && this.isEditingIntervention) {
          // Updating existing intervention
          url = `/api/intervenciones/${this.intervencion.id}`;
          method = 'PUT';
        } else if (this.intervencion && this.rescheduling) {
          // Rescheduling an existing intervention (handled by specific logic if different from update)
          // For now, let's assume rescheduling is a form of update
          url = `/api/intervenciones/${this.intervencion.id}/reschedule`; // Or just update endpoint
          method = 'PUT'; // Or POST if your reschedule endpoint expects that
        } else {
          // Creating new intervention
          url = '/api/intervenciones';
          method = 'POST';
        }

        response = await fetch(url, {
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify(payload)
        });

        const responseData = await response.json();

        if (!response.ok) {
          if (response.status === 422 && responseData.errors) {
            this.errorMessage = "Por favor, corrija los errores del formulario.";
            // Populate formValidationErrors based on responseData.errors for more specific feedback
            Object.keys(responseData.errors).forEach(key => {
              // You might need to map backend error keys to frontend field names
              this.formValidationErrors.push(key);
            });
          } else {
            this.errorMessage = responseData.message || 'Ocurrió un error al guardar la intervención.';
          }
          throw new Error(this.errorMessage);
        }

        this.$emit('intervencionSaved', responseData); // Emit event with saved data
        this.modal.hide();
        this.resetForm(); // Reset form after successful submission
        
        // Show success alert
        alert(`Intervención ${this.isEditingIntervention ? 'actualizada' : 'registrada'} exitosamente.`);

      } catch (error) {
        console.error('Error submitting intervention:', error);
        // errorMessage is already set or will be set by the response check
        if (!this.errorMessage) {
            this.errorMessage = 'Ocurrió un error inesperado.';
        }
      } finally {
        this.isSubmitting = false;
      }
    },
    cancelEditOrClose() {
      if (this.isEditingIntervention) {
        this.isEditingIntervention = false;
        // Optionally, repopulate form with original intervention data if changes were made but not saved
        // or simply revert to display view, which will use original `intervencion` prop data.
        // For now, just switch back to display view.
        if (this.intervencion) {
            this.populateForm(); // Revert to original data if needed, or just let the view re-render
        }
      } else {
        this.modal.hide();
      }
    },
    async confirmDelete() {
      if (confirm('¿Está seguro que desea eliminar esta intervención?')) {
        try {
          const response = await fetch(`/api/intervenciones/${this.intervencion.id}`, {
            method: 'DELETE'
          });

          if (response.ok) {
            this.modal.hide();
            this.$emit('intervencionCreated');
          } else {
            const errorData = await response.json();
            this.errorMessage = errorData.message || 'Error al eliminar la intervención.';
          }
        } catch (error) {
          console.error('Error:', error);
          this.errorMessage = 'Error al procesar la solicitud de eliminación.';
        }
      }
    },
    
    // Methods for Tipo de Intervención
    showCreateTipoIntervencionForm() {
      // Hide the intervención modal before showing tipo intervención modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Reset the form
      this.tipoIntervencionForm = {
        tipo_intervencion: '',
        precio: ''
      };
      
      // Set editing flag
      this.isEditingTipoIntervencion = false;
      
      // Show the tipo intervencion modal
      setTimeout(() => {
        this.tipoIntervencionModal = new Modal(document.getElementById('tipoIntervencionModal'));
        this.tipoIntervencionModal.show();
      }, 500);
    },
    
    editTipoIntervencion(tipoIntervencion) {
      if (!tipoIntervencion) return;
      
      // Hide the intervención modal before showing tipo intervención modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Copy tipo intervencion data to form
      this.tipoIntervencionForm = { 
        id: tipoIntervencion.id,
        tipo_intervencion: tipoIntervencion.tipo_intervencion,
        precio: tipoIntervencion.precio
      };
      
      // Set editing flag
      this.isEditingTipoIntervencion = true;
      
      // Show the tipo intervencion modal
      setTimeout(() => {
        this.tipoIntervencionModal = new Modal(document.getElementById('tipoIntervencionModal'));
        this.tipoIntervencionModal.show();
      }, 500);
    },
    
    async submitTipoIntervencionForm() {
      try {
        this.isSubmitting = true;
        let response;
        
        if (this.isEditingTipoIntervencion) {
          // Update existing tipo intervencion
          response = await axios.put(`/api/tipos-intervenciones/${this.tipoIntervencionForm.id}`, this.tipoIntervencionForm);
        } else {
          // Create new tipo intervencion
          response = await axios.post('/api/tipos-intervenciones', this.tipoIntervencionForm);
        }
        
        // Close modal and refresh tipos intervenciones
        this.closeTipoIntervencionModal();
        await this.fetchTiposIntervenciones();
        
        // If creating new, select the newly created tipo intervención
        if (!this.isEditingTipoIntervencion && response.data && response.data.id) {
          this.form.id_tipo_intervencion = response.data.id;
        }
        
        // Show success message
        const message = this.isEditingTipoIntervencion 
          ? 'Tipo de Intervención actualizado con éxito' 
          : 'Tipo de Intervención creado con éxito';
        alert(message);
        
      } catch (error) {
        console.error('Error saving tipo intervencion:', error);
        alert('Error al guardar el tipo de intervención');
      } finally {
        this.isSubmitting = false;
      }
    },
    
    closeTipoIntervencionModal() {
      if (this.tipoIntervencionModal) {
        this.tipoIntervencionModal.hide();
      }
      
      // Reopen the intervención modal
      setTimeout(() => {
        this.modal = new Modal(document.getElementById('intervencionModal'));
        this.modal.show();
      }, 500);
    },
    
    // Methods for handling patients
    editPaciente(paciente) {
      if (!paciente) {
        console.error('No patient found with the specified num_historia');
        return;
      }
      
      console.log('Editing patient:', paciente);
      
      // Store the ID of the patient being edited
      this.editingPacienteId = paciente.id;
      
      // Copy all patient data to the form
      this.pacienteForm = { 
        ...paciente,
        // Ensure all required fields are present
        estado_historia: paciente.estado_historia || '1',
        f_nacimiento: paciente.f_nacimiento || '',
        estado_civil: paciente.estado_civil || '',
        telefono_personal: paciente.telefono_personal || '',
        correo_personal: paciente.correo_personal || '',
        direccion_personal: paciente.direccion_personal || '',
        ocupacion: paciente.ocupacion || '',
        nom_centro_laboral: paciente.nom_centro_laboral || '',
        telefono_trabajo: paciente.telefono_trabajo || '',
        correo_trabajo: paciente.correo_trabajo || '',
        direccion_trabajo: paciente.direccion_trabajo || '',
        observaciones: paciente.observaciones || ''
      };
      
      // Set editing flag
      this.isEditingPaciente = true;
      
      // Close intervencion modal before showing paciente modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Show the paciente modal
      setTimeout(() => {
        this.pacienteModal = new Modal(document.getElementById('pacienteModal'));
        this.pacienteModal.show();
      }, 500);
    },
    
    showNewPacienteForm() {
      // Hide the intervencion modal before showing paciente modal
      const intervencionModalElement = document.getElementById('intervencionModal');
      const intervencionModal = Modal.getInstance(intervencionModalElement);
      intervencionModal.hide();

      // Reset the form
      this.pacienteForm = {
        num_historia: '',
        nombres: '',
        ap_paterno: '',
        ap_materno: '',
        doc_identidad: '',
        estado_historia: '1',
        fecha_filiacion: new Date().toISOString().split('T')[0],
        f_nacimiento: '',
        estado_civil: '',
        telefono_personal: '',
        correo_personal: '',
        direccion_personal: '',
        ocupacion: '',
        nom_centro_laboral: '',
        telefono_trabajo: '',
        correo_trabajo: '',
        direccion_trabajo: '',
        observaciones: ''
      };
      
      // Reset editing flag and ID
      this.isEditingPaciente = false;
      this.editingPacienteId = null;
      
      // Show the paciente modal
      setTimeout(() => {
        this.pacienteModal = new Modal(document.getElementById('pacienteModal'));
        this.pacienteModal.show();
      }, 500);
    },
    
    async submitPacienteForm() {
      this.pacienteFormErrors = [];
      
      try {
        // Check if DNI already exists (for new patients or changed DNIs)
        if (!this.isEditingPaciente || 
            (this.isEditingPaciente && this.pacienteForm.doc_identidad !== this.pacientes.find(p => p.id === this.editingPacienteId)?.doc_identidad)) {
          const dniExists = await this.checkDocIdentidadExists(this.pacienteForm.doc_identidad);
          if (dniExists) {
            this.pacienteFormErrors.push('DNI ya existe en la base de datos.');
            this.scrollToTop();
            setTimeout(() => {
              this.pacienteFormErrors = this.pacienteFormErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
            }, 3000);
            return;
          }
        }

        let response;
        if (this.isEditingPaciente) {
          // Update existing patient
          response = await axios.put(`/api/pacientes/${this.pacienteForm.id}`, this.pacienteForm);
        } else {
          // Create new patient
          response = await axios.post('/api/pacientes', this.pacienteForm);
        }
        
        const updatedPaciente = response.data;
        
        // Refresh the patients list
        await this.fetchPacientes();
        
        // Close the modal
        this.closePacienteModal();
        
        // Show success message
        alert(this.isEditingPaciente ? 'Paciente actualizado exitosamente.' : 'Paciente creado exitosamente.');
        
        // Reopen the intervencion modal and select the patient
        setTimeout(() => {
          this.form.num_historia = updatedPaciente.num_historia;
          this.modal = new Modal(document.getElementById('intervencionModal'));
          this.modal.show();
        }, 500);
        
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.pacienteFormErrors = Object.values(error.response.data.errors).flat();
          this.scrollToTop();
        } else {
          console.error('Error saving patient:', error);
          alert('Error al guardar el paciente');
        }
      }
    },
    
    async checkDocIdentidadExists(doc_identidad) {
      try {
        const response = await axios.get(`/api/pacientes/check-doc-identidad`, { params: { doc_identidad } });
        return response.data.exists;
      } catch (error) {
        console.error('Error checking DNI:', error);
        return false;
      }
    },
    
    closePacienteModal() {
      if (this.pacienteModal) {
        this.pacienteModal.hide();
      }
      
      // Reset form and errors
      this.pacienteFormErrors = [];
      
      // Reopen the intervención modal
      setTimeout(() => {
        this.modal = new Modal(document.getElementById('intervencionModal'));
        this.modal.show();
      }, 500);
    },
    
    closeFormErrorAlert() {
      this.pacienteFormErrors = this.pacienteFormErrors.filter(error => error !== 'DNI ya existe en la base de datos.');
    },
    
    scrollToTop() {
      const modal = document.getElementById('pacienteModal');
      if (modal) {
        modal.scrollTop = 0;
      }
    },
    
    onlyNumbers(event) {
      event.target.value = event.target.value.replace(/[^\d]/g, '');
    },
    getClinicaDisplay(clinicaId) {
      if (!clinicaId) return null;
      
      // Debug logging to see what data we have
      console.log('getClinicaDisplay for clinicaId:', clinicaId);
      console.log('Available locales:', this.locales);
      console.log('Intervencion relation data:', this.intervencion);
      
      // First check if the clinic data is available via relationship
      if (this.intervencion && this.intervencion.clinicaInicial && clinicaId === this.intervencion.clinica_inicial_id) {
        return this.intervencion.clinicaInicial.nombre;
      } else if (this.intervencion && this.intervencion.sedeOperacion && clinicaId === this.intervencion.sede_operacion_id) {
        return this.intervencion.sedeOperacion.nombre;
      }
      
      // Fallback to the locales array if relationship data isn't available
      // Convert clinicaId to number for proper comparison
      const clinicaIdNum = parseInt(clinicaId, 10);
      const clinica = this.locales.find(c => parseInt(c.id, 10) === clinicaIdNum);
      return clinica ? clinica.nombre : `Clínica ID: ${clinicaId}`;
    },
    getMedicoQueIndicaDisplay(medicoId) {
      if (!medicoId) return null;
      
      // First check if the doctor data is available via relationship
      if (this.intervencion && this.intervencion.medicoQueIndica && medicoId === this.intervencion.medico_que_indica_id) {
        const medico = this.intervencion.medicoQueIndica;
        return `${medico.nombres} ${medico.ap_paterno} ${medico.ap_materno}`;
      }
      
      // Fallback to the medicos array if relationship data isn't available
      const medico = this.medicos.find(m => m.id === medicoId);
      return medico ? `${medico.nombres} ${medico.ap_paterno} ${medico.ap_materno}` : `Médico ID: ${medicoId}`;
    },
    getTipoIntervencionDisplay(intervencion) {
      // Debug logging
      console.log('Getting tipo_intervencion display for:', intervencion);
      
      // Case 1: Standard relationship format
      if (intervencion && intervencion.tipoIntervencion && intervencion.tipoIntervencion.tipo_intervencion) {
        return `${intervencion.tipoIntervencion.tipo_intervencion} - S/. ${intervencion.tipoIntervencion.precio}`;
      } 
      // Case 2: Using id_tipo_intervencion to look up from available types
      else if (intervencion && intervencion.id_tipo_intervencion) {
        const tipo = this.tiposIntervenciones.find(t => t.id === intervencion.id_tipo_intervencion);
        if (tipo) {
          return `${tipo.tipo_intervencion} - S/. ${tipo.precio}`;
        }
      }
      // Case 3: Direct tipo_intervencion object
      else if (intervencion && intervencion.tipo_intervencion && typeof intervencion.tipo_intervencion === 'object') {
        return `${intervencion.tipo_intervencion.tipo_intervencion} - S/. ${intervencion.tipo_intervencion.precio}`;
      }
      // Case 4: Direct tipo_intervencion string
      else if (intervencion && intervencion.tipo_intervencion && typeof intervencion.tipo_intervencion === 'string') {
        return intervencion.tipo_intervencion;
      }
      
      // Fallback
      return 'No especificado';
    },
    getMedicoDisplay() {
      try {
        // Debug logging to see what data we have
        console.log('Intervencion in getMedicoDisplay:', this.intervencion);
        console.log('Available médicos:', this.medicos);
        console.log('Selected médico:', this.selectedMedico);
        console.log('Selected médico name:', this.selectedMedicoName);

        // Case 1: Standard relationship format with full object
        if (
          this.intervencion &&
          this.intervencion.medico &&
          typeof this.intervencion.medico === 'object' &&
          this.intervencion.medico.nombres &&
          this.intervencion.medico.ap_paterno
        ) {
          return `${this.intervencion.medico.nombres} ${this.intervencion.medico.ap_paterno} ${this.intervencion.medico.ap_materno || ''}`.trim();
        }

        // Case 2: Using id_medico to look up from available medicos
        if (this.intervencion && this.intervencion.id_medico) {
          const medicoId = parseInt(this.intervencion.id_medico, 10);
          const medico = this.medicos.find((m) => m.id === medicoId);
          if (medico && medico.nombre) {
            return medico.nombre;
          }

          // If we don't have the medico in our list but we have selectedMedicoName
          if (
            medicoId === parseInt(this.selectedMedico, 10) &&
            this.selectedMedicoName
          ) {
            return this.selectedMedicoName;
          }
        }

        // Case 3: Look at selectedMedicoName from props
        if (this.selectedMedicoName) {
          return this.selectedMedicoName;
        }
      } catch (error) {
        console.error('Error in getMedicoDisplay:', error);
      }

      // Fallback
      return 'No especificado';
    }
  }
};
</script>

<style scoped>
.search-dropdown {
  position: absolute;
  width: 100%;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  border: 1px solid #ced4da;
  border-radius: 0 0 4px 4px;
  z-index: 1000;
}

.search-item {
  padding: 8px 12px;
  cursor: pointer;
}

.search-item:hover {
  background-color: #f8f9fa;
}

/* Add styles for time slot selection */
.time-slots-container {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.time-slot {
  width: 70px;
}

/* Styling for calendar view with interventions */
.calendar-cell {
  position: relative;
  height: 40px;
  border: 1px solid #dee2e6;
  cursor: pointer;
}

.calendar-cell.selected {
  background-color: rgba(0, 123, 255, 0.1);
}

.intervention-block {
  position: absolute;
  left: 0;
  right: 0;
  background-color: #007bff;
  color: white;
  font-size: 0.8rem;
  padding: 2px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  z-index: 1;
}

.hover-range {
  background-color: rgba(0, 123, 255, 0.2);
}

.time-range-display {
  font-size: 1.1rem;
  font-weight: bold;
  text-align: center;
}
</style>