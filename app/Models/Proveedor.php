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

    /**
     * The marcas that belong to the proveedor.
     */
    public function marcas()
    {
        return $this->belongsToMany(Marca::class, 'marca_proveedor', 'proveedor_id', 'marca_id')
                    ->withTimestamps();
    }
}
