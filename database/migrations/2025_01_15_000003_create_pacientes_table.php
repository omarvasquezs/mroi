<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id('id');
            $table->string('num_historia');
            $table->string('nombres')->nullable();
            $table->string('ap_paterno')->nullable();
            $table->string('ap_materno')->nullable();
            $table->enum('sexo', ['F', 'M']);
            $table->date('fecha_filiacion')->nullable();
            $table->date('f_nacimiento')->nullable();
            $table->enum('estado_civil', ['S', 'C', 'V', 'D'])->nullable();
            $table->string('ocupacion')->nullable();
            $table->string('nom_centro_laboral')->nullable();
            $table->string('doc_identidad')->nullable();
            $table->string('direccion_personal')->nullable();
            $table->string('telefono_personal')->nullable();
            $table->string('correo_personal')->nullable();
            $table->string('direccion_trabajo')->nullable();
            $table->string('telefono_trabajo')->nullable();
            $table->string('correo_trabajo')->nullable();
            $table->boolean('estado_historia')->default(false);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
