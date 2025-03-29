<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up()
        {
            Schema::table('users', function (Blueprint $table) {
                DB::statement('ALTER TABLE users MODIFY COLUMN registration_status ENUM("pending", "completed") NOT NULL DEFAULT "pending"');
            });
        }
    
        public function down()
        {
            Schema::table('users', function (Blueprint $table) {
                DB::statement('ALTER TABLE users MODIFY COLUMN registration_status ENUM("pending", "completed") NOT NULL DEFAULT "pending"');
            });
        }
};
