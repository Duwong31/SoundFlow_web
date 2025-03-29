<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        if (!Schema::hasTable('bravo_feedbacks')) {
            Schema::create('bravo_feedbacks', function (Blueprint $table) {
                $table->id();
                $table->text('content');
                $table->integer('user_id')->unsigned();
                $table->text('media_ids')->nullable();
                $table->enum('status', ['pending', 'progress', 'resolved', 'rejected'])->default('pending');
                $table->text('admin_response')->nullable();
                $table->integer('responded_by')->unsigned()->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();
            });
            
            if (Schema::hasColumn('users', 'id')) {
                try {
                    Schema::table('bravo_feedbacks', function (Blueprint $table) {
                        $table->foreign('user_id')
                            ->references('id')
                            ->on('users')
                            ->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    DB::statement('ALTER TABLE bravo_feedbacks ADD INDEX user_id_index (user_id)');
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bravo_feedbacks');
    }
};