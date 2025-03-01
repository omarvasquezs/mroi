<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'material'
    ];

    protected $table = 'materiales';

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'id_material');
    }
}
