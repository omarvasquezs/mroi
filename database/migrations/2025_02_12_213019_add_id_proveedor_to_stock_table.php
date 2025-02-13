<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->unsignedBigInteger('id_proveedor')->nullable()->after('tipo_producto');
        });
    }

    public function down()
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->dropColumn('id_proveedor');
        });
    }
};
