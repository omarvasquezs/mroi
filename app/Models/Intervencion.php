<?php

namespace App\Models;

use App\Casts\TimeCast;
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
        'observaciones',
        'clinica_inicial_id',
        'medico_que_indica_id',
        'sede_operacion_id'
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => TimeCast::class,
        'hora_fin' => TimeCast::class,
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

    public function clinicaInicial(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'clinica_inicial_id');
    }

    public function medicoQueIndica(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_que_indica_id');
    }

    public function sedeOperacion(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'sede_operacion_id');
    }
}
