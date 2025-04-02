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
            $table->dropColumn('duracion_minutos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervenciones', function (Blueprint $table) {
            $table->integer('duracion_minutos')->nullable()->after('hora_fin');
        });
    }
};
