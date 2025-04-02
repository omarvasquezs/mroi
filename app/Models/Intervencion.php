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
        'hora_inicio',
        'hora_fin',
        'duracion_minutos',
        'estado',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'time',
        'hora_fin' => 'time',
        'duracion_minutos' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Backward compatibility accessor for fecha_hora_inicio
    public function getFechaHoraInicioAttribute()
    {
        if ($this->fecha && $this->hora_inicio) {
            return $this->fecha->format('Y-m-d') . ' ' . $this->hora_inicio->format('H:i:s');
        }
        return null;
    }

    // Backward compatibility accessor for fecha_hora_fin
    public function getFechaHoraFinAttribute()
    {
        if ($this->fecha && $this->hora_fin) {
            return $this->fecha->format('Y-m-d') . ' ' . $this->hora_fin->format('H:i:s');
        } elseif ($this->fecha && $this->hora_inicio) {
            // Use hora_inicio as fallback if hora_fin is not set
            return $this->fecha->format('Y-m-d') . ' ' . $this->hora_inicio->format('H:i:s');
        }
        return null;
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
