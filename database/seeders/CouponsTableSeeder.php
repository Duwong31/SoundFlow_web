<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CouponsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('bravo_coupons')->truncate();
        DB::table('bravo_booking_coupons')->truncate();
        
        DB::statement('ALTER TABLE bravo_coupons AUTO_INCREMENT = 1;');
        DB::statement('ALTER TABLE bravo_booking_coupons AUTO_INCREMENT = 1;');

        $coupons = [
            [
                'code'            => 'WELCOME2024',
                'name'            => 'Chào mừng năm 2024',
                'amount'          => 100000.00,
                'discount_type'   => 'fixed',
                'quantity_limit'  => 1000,
                'end_date'        => '2025-12-31',
                'min_total'       => 300000.00,
                'max_total'       => null,
                'limit_per_user'  => 1,
                'status'          => 'publish',
                'created_at'      => now(),
                'updated_at'      => now()
            ],
            [
                'code'            => 'NEWYEAR24',
                'name'            => 'Khuyến mãi đầu năm',
                'amount'          => 15.00,
                'discount_type'   => 'percent',
                'quantity_limit'  => 500,
                'end_date'        => '2025-03-31',
                'min_total'       => 300000.00,
                'max_total'       => 5000000.00,
                'limit_per_user'  => 2,
                'status'          => 'publish',
                'created_at'      => now(),
                'updated_at'      => now()
            ],
            [
                'code'            => 'SUMMER2024',
                'name'            => 'Khuyến mãi hè 2024',
                'amount'          => 200000.00,
                'discount_type'   => 'fixed',
                'quantity_limit'  => 300,
                'end_date'        => '2025-08-31',
                'min_total'       => 300000.00,
                'max_total'       => null,
                'limit_per_user'  => 1,
                'status'          => 'publish',
                'created_at'      => now(),
                'updated_at'      => now()
            ],
            [
                'code'            => 'FLASH24H',
                'name'            => 'Flash Sale 24h',
                'amount'          => 25.00,
                'discount_type'   => 'percent',
                'quantity_limit'  => 100,
                'end_date'        => '2025-08-31',
                'min_total'       => 300000.00,
                'max_total'       => 2000000.00,
                'limit_per_user'  => 1,
                'status'          => 'publish',
                'created_at'      => now(),
                'updated_at'      => now()
            ],
        ];

        DB::table('bravo_coupons')->insert($coupons);

        $userIds = DB::table('users')->pluck('id')->take(5)->toArray();
        $bookingCoupons = [
            [
                'booking_id'      => 1,
                'booking_status'  => 'completed',
                'object_id'       => 1,
                'object_model'    => 'car',
                'coupon_code'     => 'WELCOME2024',
                'coupon_amount'   => 100000,
                'coupon_data'     => json_encode([
                    'code' => 'WELCOME2024',
                    'amount' => 100000,
                    'discount_type' => 'fixed'
                ]),
                'create_user'     => 30,
                'created_at'      => now()->subDays(5),
                'updated_at'      => now()->subDays(5)
            ],
            [
                'booking_id'      => 2,
                'booking_status'  => 'completed',
                'object_id'       => 2,
                'object_model'    => 'car',
                'coupon_code'     => 'NEWYEAR24',
                'coupon_amount'   => 150000, // 15% của 1000000
                'coupon_data'     => json_encode([
                    'code' => 'NEWYEAR24',
                    'amount' => 15,
                    'discount_type' => 'percent'
                ]),
                'create_user'     => 30,
                'created_at'      => now()->subDays(3),
                'updated_at'      => now()->subDays(3)
            ],
            [
                'booking_id'      => 3,
                'booking_status'  => 'completed',
                'object_id'       => 3,
                'object_model'    => 'car',
                'coupon_code'     => 'NEWYEAR24',
                'coupon_amount'   => 300000, // 15% của 2000000
                'coupon_data'     => json_encode([
                    'code' => 'NEWYEAR24',
                    'amount' => 15,
                    'discount_type' => 'percent'
                ]),
                'create_user'     => 30,
                'created_at'      => now()->subDays(1),
                'updated_at'      => now()->subDays(1)
            ]
        ];

        DB::table('bravo_booking_coupons')->insert($bookingCoupons);
    }
}
