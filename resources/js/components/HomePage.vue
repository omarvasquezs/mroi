<template>
    <div class="d-flex justify-content-center" style="min-height: 80vh;">
        <div class="w-100">
            <!-- Submenu Options -->
            <div class="bg-light mb-4" style="min-height: 100px;">
                <div class="container-fluid">
                    <div class="d-flex flex-wrap">
                        <div v-for="item in submenuItems" :key="item.name" class="text-center submenu-item">
                            <router-link v-if="item.type === 'link'" :to="item.url"
                                class="btn custom-btn mx-1 my-1 text-wrap">
                                <i :class="item.icon" class="d-block"></i> {{ item.label }}
                            </router-link>
                            <a v-else-if="item.type === 'a'" :href="item.url"
                                class="btn custom-btn mx-1 my-1 text-wrap">
                                <i :class="item.icon" class="d-block"></i> {{ item.label }}
                            </a>
                            <button v-else class="btn custom-btn mx-1 my-1 text-wrap">
                                <i :class="item.icon" class="d-block"></i> {{ item.label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row d-flex align-items-stretch">
                <!-- Entidades Section -->
                <div class="col-md-4 mb-4 custom-list-group d-flex" v-for="menu in menus" :key="menu.title">
                    <div class="card flex-fill">
                        <div class="text-white card-header">{{ menu.title }}</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li v-for="item in menu.items" :key="item.name" class="list-group-item"
                                    @click="selectMenu(menu, item)">
                                    <a href="" @click.prevent><i :class="item.icon"></i> {{ item.name }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            selectedMenu: null,
            selectedSubMenu: null,
            menus: [
                {
                    title: 'Atención Hospitalaria',
                    items: [
                        { name: 'Registro de Citas', icon: 'fas fa-stethoscope', url: '' },
                        { name: 'Pacientes', icon: 'fas fa-user', url: '' } // Moved from Entidades
                    ]
                },
                {
                    title: 'Servicios Contables',
                    items: [
                        { name: 'Caja', icon: 'fas fa-cash-register', url: '' },
                        { name: 'Facturación', icon: 'fas fa-file-invoice-dollar', url: '' },
                        { name: 'Contabilidad', icon: 'fas fa-book', url: '' }
                    ]
                },
                {
                    title: 'Optica',
                    items: [
                        { name: 'Optica', icon: 'fas fa-glasses', url: '' }
                    ]
                },
                {
                    title: 'Atención Médica',
                    items: [
                        { name: 'Intervenciones Quirúrgicas', icon: 'fas fa-syringe', url: '' },
                        { name: 'Historia Clínica', icon: 'fas fa-notes-medical', url: '' },
                        { name: 'Enfermería', icon: 'fas fa-user-nurse', url: '' }
                    ]
                },
                {
                    title: 'Examenes',
                    items: [
                        { name: 'Laboratorio', icon: 'fas fa-vials', url: '' },
                        { name: 'Exámenes Auxiliares', icon: 'fas fa-x-ray', url: '' },
                        { name: 'Procedimientos', icon: 'fas fa-procedures', url: '' },
                        { name: 'Refraccion', icon: 'fas fa-eye', url: '' }
                    ]
                },
                {
                    title: 'Servicios Recibidos',
                    items: [
                        { name: 'Proveedores', icon: 'fas fa-truck', url: '' },
                        { name: 'Inventario', icon: 'fas fa-warehouse', url: '' }
                    ]
                },
                {
                    title: 'Entidades',
                    items: [
                        { name: 'Médicos', icon: 'fas fa-user-md', url: '' },
                        { name: 'Utilitarios', icon: 'fas fa-tools', url: '' }
                    ]
                }
            ],
            availableMenuItems: [  // Renamed from submenuItems
                { type: 'link', label: 'Catálogo de lentes', icon: 'fas fa-glasses', url: '/catalogo-lentes', condition: 'Optica|Optica' },
                { type: 'link', label: 'Control de stock', icon: 'fas fa-boxes', url: '/control-stock', condition: 'Optica|Optica' },
                { type: 'link', label: 'Materiales', icon: 'fas fa-cubes', url: '/materiales', condition: 'Optica|Optica' },
                { type: 'link', label: 'Gestionar Pacientes', icon: 'fas fa-users', url: '/pacientes', condition: 'Atención Hospitalaria|Pacientes' },
                { type: 'link', label: 'Gestionar Médicos', icon: 'fas fa-user-md', url: '/medicos', condition: 'Entidades|Médicos' },
                { type: 'link', label: 'Administrar Usuarios del Sistema', icon: 'fas fa-user-cog', url: '/usuarios', condition: 'Entidades|Utilitarios' },
                { type: 'link', label: 'Administrar tipos de cita', icon: 'fas fa-calendar-alt', url: '/tipos-citas', condition: 'Entidades|Utilitarios' },
                { type: 'link', label: 'Registro Proveedores', icon: 'fas fa-truck', url: '/proveedores', condition: 'Servicios Recibidos|Proveedores' },
                { type: 'link', label: 'Equipos de optometría', icon: 'fas fa-tools', url: '/equipos-de-optometria', condition: 'Servicios Recibidos|Inventario' },
                { type: 'link', label: 'HC Ambulatoria', icon: 'fas fa-notes-medical', url: '/hc-ambulatoria', condition: 'Atención Médica|Historia Clínica' },
                { type: 'link', label: 'Registro de Intervenciones', icon: 'fas fa-syringe', url: '/registro-intervenciones', condition: 'Atención Médica|Intervenciones Quirúrgicas' },
                { type: 'link', label: 'Tipo de Intervención', icon: 'fas fa-syringe', url: '/tipo-intervencion', condition: 'Atención Médica|Intervenciones Quirúrgicas' },
                { type: 'link', label: 'Quirófanos', icon: 'fas fa-procedures', url: '/quirofanos', condition: 'Atención Médica|Intervenciones Quirúrgicas' },
                { type: 'link', label: 'Separación Quirófanos', icon: 'fas fa-procedures', url: '/separacion-quirofanos', condition: 'Atención Médica|Intervenciones Quirúrgicas' },
                { type: 'link', label: 'Disponibilidad Quirófanos', icon: 'fas fa-procedures', url: '/disponibilidad-quirofanos', condition: 'Atención Médica|Intervenciones Quirúrgicas' },
                { type: 'link', label: 'Consulta de Programación', icon: 'fas fa-calendar-check', url: '/consulta-programacion', condition: 'Atención Médica|Intervenciones Quirúrgicas' },
                { type: 'link', label: 'Enfermeras', icon: 'fas fa-user-nurse', url: '/enfermeras', condition: 'Atención Médica|Enfermería' },
                { type: 'link', label: 'Turnos de Enfermería', icon: 'fas fa-calendar-alt', url: '/turnos-enfermeria', condition: 'Atención Médica|Enfermería' },
                { type: 'link', label: 'HC Enfermería', icon: 'fas fa-notes-medical', url: '/hc-enfermeria', condition: 'Atención Médica|Enfermería' },
                { type: 'link', label: 'Monitor Enfermería', icon: 'fas fa-desktop', url: '/monitor-enfermeria', condition: 'Atención Médica|Enfermería' },
                { type: 'link', label: 'Registro de Examenes', icon: 'fas fa-vials', url: '/registro-examenes', condition: 'Examenes|Laboratorio' },
                { type: 'link', label: 'Registro de Citas', icon: 'fas fa-stethoscope', url: '/registro-citas', condition: 'Atención Hospitalaria|Registro de Citas' },
                { type: 'link', label: 'Gestionar Caja', icon: 'fas fa-cash-register', url: '/caja', condition: 'Servicios Contables|Caja' },
                { type: 'link', label: 'Movimientos', icon: 'fas fa-receipt', url: '/facturacion', condition: 'Servicios Contables|Facturación' },
                { 
                    type: 'link', 
                    label: 'Administrar métodos de pago', 
                    icon: 'fas fa-money-bill', 
                    url: '/metodos-pago', 
                    condition: 'Entidades|Utilitarios' 
                }
            ]
        };
    },
    methods: {
        selectMenu(menu, item) {
            this.selectedMenu = menu;
            this.selectedSubMenu = item.name;
        }
    },
    computed: {
        submenuItems() {  // This stays as computed property
            return this.availableMenuItems.filter(item => {
                const [menuTitle, subMenuTitle] = item.condition.split('|');
                return this.selectedMenu && 
                       this.selectedMenu.title === menuTitle && 
                       this.selectedSubMenu === subMenuTitle;
            });
        }
    }
};
</script>

<style>
.custom-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.2rem;
    transition: background-color 0.3s, color 0.3s;
    white-space: normal;
    word-break: break-word;
    width: 150px;
    /* Standardized width */
    height: 100%;
    /* Added to match the height of nav-item */
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.custom-btn:hover {
    background-color: #343a40;
    color: #ffffff;
}

.submenu-item {
    height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
</style>