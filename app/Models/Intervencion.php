<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intervencion extends Model
{
    protected $table = 'intervenciones';

    protected $fillable = [
        'num_historia',
        'id_medico',
        'id_tipo_intervencion',
        'fecha',
        'estado',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'num_historia', 'num_historia');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico');
    }

    public function tipoIntervencion(): BelongsTo
    {
        return $this->belongsTo(TipoIntervencion::class, 'id_tipo_intervencion');
    }

    public function comprobantes()
    {
        return $this->belongsToMany(Comprobante::class, 'intervencion_comprobante')
            ->withPivot('monto')
            ->withTimestamps();
    }
}
