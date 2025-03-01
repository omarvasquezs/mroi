<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto',
        'imagen',
        'precio',
        'tipo_producto',
        'id_proveedor',
        'num_stock',
        'codigo',
        'genero',
        'id_material',
        'fecha_compra'
    ];

    protected $table = 'stock';

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }
    
    public function material()
    {
        return $this->belongsTo(Material::class, 'id_material');
    }
}
