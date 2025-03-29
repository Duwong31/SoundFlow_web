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
        Schema::create('zalo_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id');
            $table->string('zalo_app_id', 191)->nullable();
            $table->string('zalo_app_secret', 191)->nullable();
            $table->string('zalo_refresh_token', 500)->nullable();
            $table->string('zalo_access_token', 500)->nullable();
            $table->timestamps();
            $table->string('note', 191)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zalo_settings');
    }
};