<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'name',
        'password',
        'role',
        'estado'
    ];

    protected $hidden = [
        'password'
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Ensure the model uses the correct table if it's different from 'users'
    protected $table = 'usuarios'; // Change this if your table name is different
}
