<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id('id');
            $table->string('num_historia');
            $table->string('ap_paterno')->nullable();
            $table->string('ap_materno')->nullable();
            $table->string('nombres')->nullable();
            $table->enum('sexo', ['F', 'M']);
            $table->date('fecha_filiacion');
            $table->date('f_nacimiento')->nullable();
            $table->enum('estado_civil', ['S', 'C', 'V', 'D'])->nullable(); // Updated line
            $table->string('ocupacion')->nullable();
            $table->string('doc_identidad')->nullable();
            $table->string('domicilio')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('trabajo')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('aseguradora')->nullable();
            $table->string('contratante')->nullable();
            $table->boolean('paciente_privado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pacientes');
    }
};
