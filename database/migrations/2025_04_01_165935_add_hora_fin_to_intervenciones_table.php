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
        Schema::table('intervenciones', function (Blueprint $table) {
            // Rename 'fecha' column to 'fecha_hora_inicio' for clarity
            $table->renameColumn('fecha', 'fecha_hora_inicio');
            
            // Add 'fecha_hora_fin' column for the end time of intervention
            $table->dateTime('fecha_hora_fin')->nullable()->after('fecha_hora_inicio');
            
            // Add 'duracion_minutos' to store the duration in minutes
            $table->integer('duracion_minutos')->default(60)->after('fecha_hora_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervenciones', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['fecha_hora_fin', 'duracion_minutos']);
            
            // Rename back to original column name
            $table->renameColumn('fecha_hora_inicio', 'fecha');
        });
    }
};
