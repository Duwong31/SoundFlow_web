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
            if (!Schema::hasColumn('bravo_cars', 'price_per_10_hour')) {
                $table->decimal('price_per_10_hour', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('bravo_cars', 'sale_price_per_10_hour')) {
                $table->decimal('sale_price_per_10_hour', 12, 2)->nullable();
            }
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
            if (Schema::hasColumn('bravo_cars', 'price_per_10_hour')) {
                $table->dropColumn('price_per_10_hour');
            }
            if (Schema::hasColumn('bravo_cars', 'sale_price_per_10_hour')) {
                $table->dropColumn('sale_price_per_10_hour');
            }
        });
    }
};