<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_historia',
        'nombres',
        'ap_paterno',
        'ap_materno',
        'sexo',
        'fecha_filiacion',
        'f_nacimiento',
        'estado_civil',
        'ocupacion',
        'nom_centro_laboral',
        'doc_identidad',
        'direccion_personal',
        'telefono_personal',
        'correo_personal',
        'direccion_trabajo',
        'telefono_trabajo',
        'correo_trabajo',
        'estado_historia',
        'observaciones',
    ];

    // ...existing code...
}
