<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'razon_social',
        'ruc',
        'direccion',
        'telefono',
        'correo_electronico',
        'pagina_web',
        'nombre_representante'
    ];
}
