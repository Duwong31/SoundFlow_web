<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferralCodeWalletBalancePointsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 50)->nullable()->after('phone');
            $table->decimal('wallet_balance', 12, 2)->default(0)->after('credit_balance');
            $table->integer('points')->default(0)->after('wallet_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'wallet_balance', 'points']);
        });
    }
};
