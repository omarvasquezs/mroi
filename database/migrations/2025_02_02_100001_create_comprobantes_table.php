<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['b', 'f']);
            $table->string('serie', 4); // B001, F001, etc
            $table->integer('correlativo'); // Sequential number for each tipo+serie combination (e.g., 000001, 000002, etc.)
            $table->decimal('monto_total', 10, 2);
            $table->timestamps();

            // Create a unique constraint for tipo, serie and correlativo
            $table->unique(['tipo', 'serie', 'correlativo']);
        });

        Schema::create('cita_comprobante', function (Blueprint $table) {
            // Regular unsigned big integers instead of foreign IDs
            $table->unsignedBigInteger('cita_id');
            $table->unsignedBigInteger('comprobante_id');
            
            $table->decimal('monto', 10, 2);
            $table->timestamps();

            // Composite primary key
            $table->primary(['cita_id', 'comprobante_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cita_comprobante');
        Schema::dropIfExists('comprobantes');
    }
};
