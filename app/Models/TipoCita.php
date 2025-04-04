<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCita extends Model
{
    use HasFactory;

    protected $fillable = ['tipo_cita', 'precio'];

    protected $table = 'tipos_citas';
}
