<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            if (!Schema::hasColumn('bravo_cars', 'car_type')) {
                Schema::table('bravo_cars', function (Blueprint $table) {
                    $table->enum('car_type', [
                        'otod_recommended',
                        'luxury',          // Xe cao cấp
                        'mid_range',       // Xe trung cấp
                        'compact',         // Xe cỡ nhỏ
                        'pickup',          // Xe bán tải
                        'suv',             // SUV
                        'mpv',             // MPV
                        'electric'         // Xe điện
                    ])->nullable()->after('status');
                });

                // Xe cao cấp
                DB::table('bravo_cars')
                    ->whereNull('car_type')
                    ->where('price', '>=', 400)
                    ->where('passenger', '>=', 4)
                    ->update(['car_type' => 'luxury']);

                // Xe cỡ nhỏ
                DB::table('bravo_cars')
                    ->whereNull('car_type')
                    ->where(function($query) {
                        $query->where('passenger', '<=', 4)
                              ->orWhere('price', '<', 300);
                    })
                    ->update(['car_type' => 'compact']);

                // Xe trung cấp
                DB::table('bravo_cars')
                    ->whereNull('car_type')
                    ->whereBetween('price', [199, 250])
                    ->update(['car_type' => 'mid_range']);

                // Xe điện
                DB::table('bravo_cars')
                    ->whereNull('car_type')
                    ->where('fuel_type', 'electric')
                    ->update(['car_type' => 'electric']);

                // SUV/MPV based on model or title
                DB::table('bravo_cars')
                    ->whereNull('car_type')
                    ->where(function($query) {
                        $query->where('title', 'like', '%suv%')
                              ->orWhere('model', 'like', '%suv%');
                    })
                    ->update(['car_type' => 'suv']);

                DB::table('bravo_cars')
                    ->whereNull('car_type')
                    ->where(function($query) {
                        $query->where('title', 'like', '%mpv%')
                              ->orWhere('model', 'like', '%mpv%');
                    })
                    ->update(['car_type' => 'mpv']);

                // Pickup trucks based on model or title
                DB::table('bravo_cars')
                    ->whereNull('car_type')
                    ->where(function($query) {
                        $query->where('title', 'like', '%pickup%')
                              ->orWhere('title', 'like', '%bán tải%')
                              ->orWhere('model', 'like', '%pickup%');
                    })
                    ->update(['car_type' => 'pickup']);
            }
        } catch (\Exception $e) {
            // Log error if needed
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bravo_cars', function (Blueprint $table) {
            if (Schema::hasColumn('bravo_cars', 'car_type')) {
                $table->dropColumn('car_type');
            }
        });
    }
}; 