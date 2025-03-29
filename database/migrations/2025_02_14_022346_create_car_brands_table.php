<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarBrandsTable extends Migration
{
    public function up()
    {
        Schema::create('car_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('logo_id')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });

        Schema::table('bravo_cars', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('brand');
            $table->foreign('brand_id')->references('id')->on('car_brands');
        });
    }

    public function down()
    {
        Schema::table('bravo_cars', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });
        
        Schema::dropIfExists('car_brands');
    }
} 