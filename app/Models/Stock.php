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
        'num_stock'
    ];

    protected $table = 'stock';

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }
}
