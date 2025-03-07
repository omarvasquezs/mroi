<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyStockTableRenameProductoToDescripcion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock', function (Blueprint $table) {
            // Rename producto to descripcion
            $table->renameColumn('producto', 'descripcion');
            
            // Make descripcion nullable
            $table->string('descripcion')->nullable()->change();
            
            // Make imagen nullable
            $table->string('imagen')->nullable()->change();
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
            // Revert changes
            $table->renameColumn('descripcion', 'producto');
            $table->string('producto')->nullable(false)->change();
            $table->string('imagen')->nullable(false)->change();
        });
    }
}
