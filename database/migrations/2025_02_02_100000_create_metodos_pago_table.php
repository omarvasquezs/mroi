<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insert default payment methods
        DB::table('metodos_pago')->insert([
            ['nombre' => 'Efectivo'],
            ['nombre' => 'Tarjeta'],
            ['nombre' => 'Yape'],
            ['nombre' => 'Plin'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('metodos_pago');
    }
};
