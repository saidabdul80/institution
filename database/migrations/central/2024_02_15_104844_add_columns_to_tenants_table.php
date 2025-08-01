<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddColumnsToTenantsTable extends Migration
{
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('school_name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('logo')->nullable();
            $table->string('domain')->nullable();
            $table->string('tenancy_db_name')->nullable();
        });
    }

    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['school_name', 'host', 'tenancy_db_name']);
        });
    }
}