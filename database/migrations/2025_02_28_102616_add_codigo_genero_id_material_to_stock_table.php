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
        Schema::table('stock', function (Blueprint $table) {
            $table->string('codigo')->nullable()->after('num_stock');
            $table->enum('genero', ['H', 'M', 'N', 'U'])->nullable()->after('codigo');
            $table->unsignedBigInteger('id_material')->nullable()->after('genero');
            $table->date('fecha_compra')->nullable()->after('id_material');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->dropColumn(['codigo', 'genero', 'id_material', 'fecha_compra']);
        });
    }
};