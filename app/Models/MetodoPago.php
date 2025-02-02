<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';
    
    protected $fillable = [
        'nombre',
        'activo'
    ];

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'id_metodo_pago');
    }
}
