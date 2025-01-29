<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTiposCitasTable extends Migration
{
    public function up()
    {
        Schema::create('tipos_citas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_cita');
            $table->decimal('precio', 8, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tipos_citas');
    }
}
