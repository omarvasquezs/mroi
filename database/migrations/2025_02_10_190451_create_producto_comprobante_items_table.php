<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('producto_comprobante_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_comprobante_id');
            $table->unsignedBigInteger('stock_id');
            $table->integer('cantidad');
            $table->decimal('precio', 10, 2);
            $table->timestamps();

            $table->foreign('producto_comprobante_id')->references('id')->on('productos_comprobante')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stock')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('producto_comprobante_items');
    }
};
