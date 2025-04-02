<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('intervenciones', function (Blueprint $table) {
            // Add new date field
            $table->date('fecha')->nullable()->after('estado');
            
            // Add new time fields
            $table->time('hora_inicio')->nullable()->after('fecha');
            $table->time('hora_fin')->nullable()->after('hora_inicio');
        });

        // Copy data from existing datetime fields to new separate fields
        DB::statement('UPDATE intervenciones SET fecha = DATE(fecha_hora_inicio), 
                      hora_inicio = TIME(fecha_hora_inicio), 
                      hora_fin = IF(fecha_hora_fin IS NOT NULL, TIME(fecha_hora_fin), TIME(fecha_hora_inicio))');
        
        // Make new fields required after data migration
        Schema::table('intervenciones', function (Blueprint $table) {
            $table->date('fecha')->nullable(false)->change();
            $table->time('hora_inicio')->nullable(false)->change();
        });

        // Drop old datetime fields
        Schema::table('intervenciones', function (Blueprint $table) {
            $table->dropColumn(['fecha_hora_inicio', 'fecha_hora_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervenciones', function (Blueprint $table) {
            // Re-add datetime fields
            $table->dateTime('fecha_hora_inicio')->nullable()->after('estado');
            $table->dateTime('fecha_hora_fin')->nullable()->after('fecha_hora_inicio');
            
            // Copy data back
            DB::statement('UPDATE intervenciones SET fecha_hora_inicio = CONCAT(fecha, " ", hora_inicio), 
                          fecha_hora_fin = CONCAT(fecha, " ", hora_fin)');
            
            // Make required field not nullable after data migration
            Schema::table('intervenciones', function (Blueprint $table) {
                $table->dateTime('fecha_hora_inicio')->nullable(false)->change();
            });
            
            // Drop new separate fields
            $table->dropColumn(['fecha', 'hora_inicio', 'hora_fin']);
        });
    }
};
