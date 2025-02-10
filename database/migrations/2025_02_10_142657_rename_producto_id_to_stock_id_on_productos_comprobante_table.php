<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('productos_comprobante', function (Blueprint $table) {
            $table->renameColumn('producto_id', 'stock_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productos_comprobante', function (Blueprint $table) {
            $table->renameColumn('stock_id', 'producto_id');
        });
    }
};
