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
        Schema::create('bravo_review_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('review_id');
            $table->unsignedInteger('media_id');
            $table->enum('media_type', ['image', 'video'])->default('image');
            $table->timestamps();
            
            // Tạo index
            $table->index('review_id');
            $table->index('media_id');
            
            // Thêm foreign key nếu cần
            // $table->foreign('review_id')->references('id')->on('bravo_review')->onDelete('cascade');
            // $table->foreign('media_id')->references('id')->on('media_files')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bravo_review_media');
    }
};
