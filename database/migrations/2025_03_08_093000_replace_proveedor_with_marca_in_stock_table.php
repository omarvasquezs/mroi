<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReplaceProveedorWithMarcaInStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock', function (Blueprint $table) {
            // Drop the id_proveedor column
            $table->dropColumn('id_proveedor');

            // Add the new id_marca column with foreign key constraint
            $table->unsignedBigInteger('id_marca')->nullable()->after('tipo_producto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock', function (Blueprint $table) {
            // Drop the id_marca column
            $table->dropColumn('id_marca');

            // Re-add the id_proveedor column
            $table->unsignedBigInteger('id_proveedor')->nullable()->after('tipo_producto');
        });
    }
}
