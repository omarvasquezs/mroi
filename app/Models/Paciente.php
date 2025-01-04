<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_historia',
        'ap_paterno',
        'ap_materno',
        'nombres',
        'sexo',
    ];

    protected $table = 'pacientes';
}
