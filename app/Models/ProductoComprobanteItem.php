<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoComprobanteItem extends Model
{
    protected $table = 'producto_comprobante_items';

    protected $fillable = [
        'producto_comprobante_id',
        'stock_id',
        'cantidad',
        'precio'
    ];

    public function productoComprobante()
    {
        return $this->belongsTo(ProductoComprobante::class, 'producto_comprobante_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}
