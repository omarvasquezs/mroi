<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoIntervencion extends Model
{
    use HasFactory;

    protected $fillable = ['tipo_intervencion', 'precio'];

    protected $table = 'tipos_intervenciones';

    public function intervenciones()
    {
        return $this->hasMany(Intervencion::class, 'id_tipo_intervencion');
    }
}
