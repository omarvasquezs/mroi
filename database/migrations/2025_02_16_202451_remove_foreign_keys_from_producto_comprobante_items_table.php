<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('producto_comprobante_items', function (Blueprint $table) {
            $table->dropForeign(['producto_comprobante_id']);
            $table->dropForeign(['stock_id']);
        });
    }

    public function down()
    {
        Schema::table('producto_comprobante_items', function (Blueprint $table) {
            $table->foreign('producto_comprobante_id')->references('id')->on('productos_comprobante')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stock')->onDelete('cascade');
        });
    }
};
