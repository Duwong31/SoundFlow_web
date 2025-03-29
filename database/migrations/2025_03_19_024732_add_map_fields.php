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
        Schema::table('core_news', function (Blueprint $table) {
            if (!Schema::hasColumn('core_news', 'map_lat')) {
                $table->string('map_lat', 20)->nullable();
            }
            if (!Schema::hasColumn('core_news', 'map_lng')) {
                $table->string('map_lng', 20)->nullable();
            }
            if (!Schema::hasColumn('core_news', 'map_zoom')) {
                $table->integer('map_zoom')->nullable();
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
        Schema::table('core_news', function (Blueprint $table) {
            $table->dropColumn(['map_lat', 'map_lng', 'map_zoom']);
        });
    }
}; 
