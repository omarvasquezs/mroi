<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intervenciones', function (Blueprint $table) {
            $table->id();
            $table->string('num_historia');
            $table->unsignedBigInteger('id_medico');
            $table->unsignedBigInteger('id_tipo_intervencion');
            $table->enum('estado', ['p', 'd'])->default('d'); // p = realizada, d = pendiente
            $table->dateTime('fecha');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intervenciones');
    }
};
