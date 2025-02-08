<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNumStockDefaultInStockTable extends Migration
{
    public function up()
    {
        Schema::table('stock', function (Blueprint $table) {
            // Ensure you have doctrine/dbal installed to modify columns: composer require doctrine/dbal
            $table->integer('num_stock')->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->integer('num_stock')->default(null)->change();
        });
    }
}
