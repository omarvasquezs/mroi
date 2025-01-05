@extends('template')

@section('content')
<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="w-100">
        <div class="row d-flex align-items-stretch">
            <!-- Entidades Section -->
            <div class="col-md-4 mb-4 custom-list-group d-flex">
                <div class="card flex-fill">
                    <div class="card-header">Entidades</div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item"><a href="{{ url('/pacientes/agregar') }}"><i class="fas fa-user"></i> Pacientes</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-user-md"></i> Médicos</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Atención Hospitalaria Section -->
            <div class="col-md-4 mb-4 custom-list-group d-flex">
                <div class="card flex-fill">
                    <div class="card-header">Atención Hospitalaria</div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item"><a href="#"><i class="fas fa-stethoscope"></i> Consulta Ambulatoria</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-glasses"></i> Optica</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Atención Médica Section -->
            <div class="col-md-4 mb-4 custom-list-group d-flex">
                <div class="card flex-fill">
                    <div class="card-header">Atención Médica</div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item"><a href="#"><i class="fas fa-syringe"></i> Intervenciones Quirúrgicas</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-notes-medical"></i> Historia Clínica</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-user-nurse"></i> Enfermería</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row d-flex align-items-stretch">
            <!-- Ayuda al Diagnóstico Section -->
            <div class="col-md-4 mb-4 custom-list-group d-flex">
                <div class="card flex-fill">
                    <div class="card-header">Examenes</div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item"><a href="#"><i class="fas fa-vials"></i> Laboratorio</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-x-ray"></i> Exámenes Auxiliares</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-procedures"></i> Procedimientos</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-eye"></i> Refraccion</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Servicios Prestados Section -->
            <div class="col-md-4 mb-4 custom-list-group d-flex">
                <div class="card flex-fill">
                    <div class="card-header">Servicios Contables</div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item"><a href="#"><i class="fas fa-cash-register"></i> Caja</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-file-invoice-dollar"></i> Facturación</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-calculator"></i> Presupuesto</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-book"></i> Contabilidad</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Servicios Recibidos Section -->
            <div class="col-md-4 mb-4 custom-list-group d-flex">
                <div class="card flex-fill">
                    <div class="card-header">Servicios Recibidos</div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item"><a href="#"><i class="fas fa-truck"></i> Proveedores</a></li>
                            <li class="list-group-item"><a href="#"><i class="fas fa-people-carry"></i> Logística</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection