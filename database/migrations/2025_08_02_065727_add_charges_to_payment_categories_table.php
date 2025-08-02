<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_categories', function (Blueprint $table) {
            $table->decimal('charges', 10, 2)->default(0)->after('short_name');
            $table->string('charge_type', 20)->default('fixed')->after('charges'); // 'fixed' or 'percentage'
            $table->text('charge_description')->nullable()->after('charge_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_categories', function (Blueprint $table) {
            $table->dropColumn(['charges', 'charge_type', 'charge_description']);
        });
    }
};
