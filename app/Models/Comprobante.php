<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    protected $fillable = [
        'tipo',
        'serie',          // Changed from numero_serie
        'correlativo',    // Changed from numero_correlativo
        'monto_total',
        'id_metodo_pago' // Added this field
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
}
