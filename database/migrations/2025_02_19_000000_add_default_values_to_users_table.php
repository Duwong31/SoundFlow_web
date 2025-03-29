<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddDefaultValuesToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Đặt default values
            $table->decimal('wallet_balance', 12, 2)->default(0)->change();
            $table->integer('points')->default(0)->change();
        });
        
        // Generate referral codes for existing users
        DB::table('users')->whereNull('referral_code')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                do {
                    $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
                    $numbers = rand(1000, 9999);
                    $code = $letters . $numbers;
                } while (DB::table('users')->where('referral_code', $code)->exists());
                
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['referral_code' => $code]);
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('wallet_balance', 12, 2)->change();
            $table->integer('points')->change();
        });
    }
} 