<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('usuarios')->insert([
            'username' => 'admin',
            'name' => 'admin',
            'password' => Hash::make('12345678'),
            'role' => 's',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
