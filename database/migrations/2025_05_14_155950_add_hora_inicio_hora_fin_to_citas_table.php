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
        // 1. Add hora_inicio and hora_fin as nullable for now
        Schema::table('citas', function (Blueprint $table) {
            $table->time('hora_inicio')->nullable()->after('fecha');
            $table->time('hora_fin')->nullable()->after('hora_inicio');
        });

        // 2. Extract time from fecha into hora_inicio, and set hora_fin = hora_inicio + 30 min
        DB::statement('
            UPDATE citas 
            SET hora_inicio = TIME(fecha), 
                hora_fin = ADDTIME(TIME(fecha), "00:30:00")
        ');

        // 3. Change fecha from datetime to date
        Schema::table('citas', function (Blueprint $table) {
            $table->date('fecha')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Change fecha back to datetime
        Schema::table('citas', function (Blueprint $table) {
            $table->dateTime('fecha')->change();
        });

        // 2. Optionally, merge hora_inicio back into fecha (not implemented)
        // 3. Drop hora_inicio and hora_fin
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['hora_inicio', 'hora_fin']);
        });
    }
};
