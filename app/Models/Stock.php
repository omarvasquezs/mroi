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
        'num_stock'
    ];

    protected $table = 'stock';
}
