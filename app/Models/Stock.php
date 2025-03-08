<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'descripcion',
        'imagen',
        'precio',
        'tipo_producto',
        'id_marca',
        'num_stock',
        'codigo',
        'genero',
        'id_material',
        'fecha_compra'
    ];

    protected $table = 'stock';

    // Replace proveedor relationship with marca
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'id_marca');
    }
    
    public function material()
    {
        return $this->belongsTo(Material::class, 'id_material');
    }
}
