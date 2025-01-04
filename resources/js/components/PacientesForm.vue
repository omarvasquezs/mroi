<template>
    <form @submit.prevent="submitForm" class="mb-5" autocomplete="off">
        <fieldset class="mb-4">
            <legend>Información Personal</legend>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="PacientesNombres" class="form-label">Nombres*:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" v-model="form.nombres" id="PacientesNombres" name="PacientesNombres" class="form-control" placeholder="Ej: Juan" autocomplete="new-nombres" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="PacientesApPaterno" class="form-label">Apellido Paterno*:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" v-model="form.ap_paterno" id="PacientesApPaterno" name="PacientesApPaterno" class="form-control" placeholder="Ej: Pérez" autocomplete="new-ap_paterno" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="PacientesApMaterno" class="form-label">Apellido Materno*:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" v-model="form.ap_materno" id="PacientesApMaterno" name="PacientesApMaterno" class="form-control" placeholder="Ej: Gómez" autocomplete="new-ap_materno" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2 col-md-6 mb-3">
                    <label for="PacientesDocIdentidad" class="form-label">DNI*:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                        <input type="number" v-model="form.doc_identidad" id="PacientesDocIdentidad" name="PacientesDocIdentidad" class="form-control" placeholder="Ej: 12345678" autocomplete="new-doc_identidad" required>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <label for="PacientesEstadoCivil" class="form-label">Estado Civil*:</label>
                    <select v-model="form.estado_civil" id="PacientesEstadoCivil" name="PacientesEstadoCivil" class="form-select" autocomplete="new-estado_civil" required>
                        <option value="" disabled selected>Seleccione Estado Civil</option>
                        <option value="S">Soltero</option>
                        <option value="C">Casado</option>
                        <option value="V">Viudo</option>
                        <option value="D">Divorciado</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="PacientesFNacimiento" class="form-label">Fecha de Nacimiento*:</label>
                    <input type="date" v-model="form.f_nacimiento" id="PacientesFNacimiento" name="PacientesFNacimiento" class="form-control" autocomplete="new-f_nacimiento" required>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <label class="form-label">Sexo*:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" v-model="form.sexo" id="PacientesSexoM" name="PacientesSexo" value="M" autocomplete="new-sexo_m" required>
                        <label class="form-check-label" for="PacientesSexoM">Masculino</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" v-model="form.sexo" id="PacientesSexoF" name="PacientesSexo" value="F" autocomplete="new-sexo_f" required>
                        <label class="form-check-label" for="PacientesSexoF">Femenino</label>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="PacientesDireccionPersonal" class="form-label">Dirección Personal*:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                        <input type="text" v-model="form.direccion_personal" id="PacientesDireccionPersonal" name="PacientesDireccionPersonal" class="form-control" placeholder="Ej: Av. Siempre Viva 123" autocomplete="new-direccion_personal" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="PacientesCorreoPersonal" class="form-label">Correo Personal:</label>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="email" v-model="form.correo_personal" id="PacientesCorreoPersonal" name="PacientesCorreoPersonal" class="form-control" placeholder="Ej: ejemplo@correo.com" autocomplete="new-correo_personal">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="PacientesTelefonoPersonal" class="form-label">Teléfono Personal:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" v-model="form.telefono_personal" id="PacientesTelefonoPersonal" name="PacientesTelefonoPersonal" class="form-control" placeholder="Ej: 987654321" autocomplete="new-telefono_personal">
                    </div>
                </div>                
            </div>
        </fieldset>

        <fieldset class="mb-4">
            <legend>Información Adicional (Opcional)</legend>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="PacientesOcupacion" class="form-label">Ocupación:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                        <input type="text" v-model="form.ocupacion" id="PacientesOcupacion" name="PacientesOcupacion" class="form-control" placeholder="Ej: Ingeniero" autocomplete="new-ocupacion">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="PacientesNomCentroLaboral" class="form-label">Nombre del Centro Laboral:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                        <input type="text" v-model="form.nom_centro_laboral" id="PacientesNomCentroLaboral" name="PacientesNomCentroLaboral" class="form-control" placeholder="Ej: Empresa XYZ" autocomplete="new-nom_centro_laboral">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="PacientesTelefonoTrabajo" class="form-label">Teléfono del Trabajo:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" v-model="form.telefono_trabajo" id="PacientesTelefonoTrabajo" name="PacientesTelefonoTrabajo" class="form-control" placeholder="Ej: 123456789" autocomplete="new-telefono_trabajo">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <label for="PacientesCorreoTrabajo" class="form-label">Correo del Trabajo:</label>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="email" v-model="form.correo_trabajo" id="PacientesCorreoTrabajo" name="PacientesCorreoTrabajo" class="form-control" placeholder="Ej: trabajo@empresa.com" autocomplete="new-correo_trabajo">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="PacientesDireccionTrabajo" class="form-label">Dirección del Trabajo:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-address-book"></i></span>
                        <input type="text" v-model="form.direccion_trabajo" id="PacientesDireccionTrabajo" name="PacientesDireccionTrabajo" class="form-control" placeholder="Ej: Calle Falsa 456" autocomplete="new-direccion_trabajo">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="PacientesObservaciones" class="form-label">Observaciones:</label>
                    <textarea v-model="form.observaciones" id="PacientesObservaciones" name="PacientesObservaciones" class="form-control" placeholder="Escriba sus observaciones aquí" autocomplete="new-observaciones" rows="5"></textarea>
                </div>
            </div>
        </fieldset>

        <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-lg">Guardar</button>
            <button type="button" @click="resetForm" class="btn btn-secondary btn-lg">Limpiar</button>
        </div>
    </form>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            form: {
                nombres: '',
                ap_paterno: '',
                ap_materno: '',
                sexo: '',
                f_nacimiento: '',
                estado_civil: '',
                ocupacion: '',
                nom_centro_laboral: '',
                doc_identidad: '',
                direccion_personal: '',
                telefono_personal: '',
                correo_personal: '',
                direccion_trabajo: '',
                telefono_trabajo: '',
                correo_trabajo: '',
                estado_historia: false,
                observaciones: ''
            }
        }
    },
    methods: {
        submitForm() {
            axios.post('/api/pacientes', this.form)
                .then(response => {
                    console.log(response.data);
                })
                .catch(error => {
                    console.error(error);
                });
        },
        resetForm() {
            this.form = {
                nombres: '',
                ap_paterno: '',
                ap_materno: '',
                sexo: '',
                f_nacimiento: '',
                estado_civil: '',
                ocupacion: '',
                nom_centro_laboral: '',
                doc_identidad: '',
                direccion_personal: '',
                telefono_personal: '',
                correo_personal: '',
                direccion_trabajo: '',
                telefono_trabajo: '',
                correo_trabajo: '',
                estado_historia: false,
                observaciones: ''
            };
        }
    }
}
</script>
