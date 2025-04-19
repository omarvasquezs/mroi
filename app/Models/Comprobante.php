<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    protected $fillable = [
        'tipo',
        'serie',
        'correlativo',
        'monto_total',
        'id_metodo_pago'
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function citas()
    {
        return $this->belongsToMany(Cita::class)
            ->withPivot('monto')
            ->withTimestamps();
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago');
    }

    public function productoComprobante()
    {
        return $this->hasOne(ProductoComprobante::class, 'comprobante_id');
    }

    public function intervenciones()
    {
        return $this->belongsToMany(Intervencion::class, 'intervencion_comprobante')
            ->withPivot('monto')
            ->withTimestamps();
    }
}
