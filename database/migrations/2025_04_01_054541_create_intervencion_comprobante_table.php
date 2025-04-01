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
        Schema::create('intervencion_comprobante', function (Blueprint $table) {
            $table->unsignedBigInteger('intervencion_id');
            $table->unsignedBigInteger('comprobante_id');
            $table->decimal('monto', 10, 2);
            $table->timestamps();

            // Define the composite primary key
            $table->primary(['intervencion_id', 'comprobante_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intervencion_comprobante');
    }
};
