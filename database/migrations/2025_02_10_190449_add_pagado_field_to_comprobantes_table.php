<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->boolean('pagado')->default(false)->after('id_metodo_pago');
        });
    }

    public function down()
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn('pagado');
        });
    }
};
