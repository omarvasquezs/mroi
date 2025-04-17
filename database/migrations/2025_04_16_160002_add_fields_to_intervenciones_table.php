<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('intervenciones', function (Blueprint $table) {
            $table->unsignedBigInteger('clinica_inicial_id')->nullable()->after('observaciones');
            $table->unsignedBigInteger('medico_que_indica_id')->nullable()->after('clinica_inicial_id');
            $table->unsignedBigInteger('sede_operacion_id')->nullable()->after('medico_que_indica_id');
        });
    }

    public function down()
    {
        Schema::table('intervenciones', function (Blueprint $table) {
            $table->dropColumn(['clinica_inicial_id', 'medico_que_indica_id', 'sede_operacion_id']);
        });
    }
};
