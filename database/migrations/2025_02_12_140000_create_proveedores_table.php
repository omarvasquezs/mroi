<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('ruc')->unique();
            $table->text('direccion');
            $table->string('telefono', 255);
            $table->string('correo_electronico')->unique();
            $table->string('pagina_web', 255)->nullable();
            $table->string('nombre_representante');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('proveedores');
    }
};
