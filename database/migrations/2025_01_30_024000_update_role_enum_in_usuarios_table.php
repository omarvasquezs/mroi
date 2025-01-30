<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateRoleEnumInUsuariosTable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN role ENUM('a','o','m','s','c') NOT NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN role ENUM('a','o','m','s') NOT NULL");
    }
}
