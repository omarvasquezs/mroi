<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('productos_comprobante', function (Blueprint $table) {
            $table->dropColumn(['stock_id', 'cantidad', 'precio']);
            $table->string('nombres')->after('comprobante_id');
            $table->string('telefono')->after('nombres');
            $table->string('correo')->after('telefono');
            $table->decimal('monto_total', 10, 2)->after('correo');
            $table->unsignedBigInteger('comprobante_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('productos_comprobante', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_id')->after('comprobante_id');
            $table->integer('cantidad')->after('stock_id');
            $table->decimal('precio', 10, 2)->after('cantidad');
            $table->dropColumn(['nombres', 'telefono', 'correo', 'monto_total']);
            $table->unsignedBigInteger('comprobante_id')->nullable(false)->change();
        });
    }
};
