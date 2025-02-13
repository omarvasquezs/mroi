<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_colegiado',
        'nombres',
        'ap_paterno',
        'ap_materno',
        'especialidad',
        'dni',
        'cmp',
        'telefono',
        'email',
        'direccion',
    ];
}
