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
        Schema::table('bravo_cars', function (Blueprint $table) {
            $table->decimal('price_per_10_hour', 12, 2)->nullable()->after('sale_price');
            $table->decimal('sale_price_per_10_hour', 12, 2)->nullable()->after('price_per_10_hour');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bravo_cars', function (Blueprint $table) {
            $table->dropColumn('price_per_10_hour');
            $table->dropColumn('sale_price_per_10_hour');
        });
    }
};
