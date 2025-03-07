<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;

    protected $fillable = ['marca'];

    protected $table = 'marcas';
    
    /**
     * The proveedores that belong to the marca.
     */
    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'marca_proveedor', 'marca_id', 'proveedor_id')
                    ->withTimestamps();
    }
}
