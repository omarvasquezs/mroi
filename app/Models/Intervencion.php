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
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'duracion_minutos',
        'estado',
        'observaciones'
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'duracion_minutos' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Accessor to get fecha (date only) for backward compatibility
    public function getFechaAttribute()
    {
        return $this->fecha_hora_inicio ? $this->fecha_hora_inicio->toDateString() : null;
    }

    // Accessor to get hora_inicio (time only) for backward compatibility
    public function getHoraInicioAttribute()
    {
        return $this->fecha_hora_inicio ? $this->fecha_hora_inicio->format('H:i') : null;
    }

    // Accessor to get hora_fin (time only)
    public function getHoraFinAttribute()
    {
        return $this->fecha_hora_fin ? $this->fecha_hora_fin->format('H:i') : null;
    }

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
