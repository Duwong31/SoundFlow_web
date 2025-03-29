<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bravo_cars', function (Blueprint $table) {
            $table->boolean('car_auth')->default(false);
            $table->integer('book_complete')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bravo_cars', function (Blueprint $table) {
            $table->dropColumn('car_auth');
            $table->dropColumn('book_complete');
        });
    }
};
