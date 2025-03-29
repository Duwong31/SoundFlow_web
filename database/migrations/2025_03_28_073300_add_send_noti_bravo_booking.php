<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bravo_bookings', function (Blueprint $table) {
            $table->boolean('sent_notification')->default(false)->after('coupon_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bravo_bookings', function (Blueprint $table) {
            $table->dropColumn('sent_notification');
        });
    }
};

