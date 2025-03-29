<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingsTableSeeder extends Seeder
{
    public function run()
    {
        // Xóa dữ liệu cũ
        DB::table('bravo_bookings')->truncate();
        
        // Reset auto increment
        DB::statement('ALTER TABLE bravo_bookings AUTO_INCREMENT = 1');

        $bookings = [
            [
                'code' => 'BOOK'.time(),
                'vendor_id' => null,
                'customer_id' => 30,
                'object_id' => 1,
                'object_model' => 'car',
                'start_date' => now(),
                'end_date' => now()->addDays(3),
                'total' => 300000,
                'total_guests' => 1,
                'currency' => 'VND',
                'status' => 'draft', // Trạng thái draft để có thể apply coupon
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone' => '0123456789',
                'address' => 'Test Address',
                'create_user' => 30,
                'total_before_fees' => 300000,
                'total_before_discount' => 300000,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'BOOK'.(time()+1),
                'vendor_id' => null,
                'customer_id' => 30,
                'object_id' => 2,
                'object_model' => 'car', 
                'start_date' => now()->addDay(),
                'end_date' => now()->addDays(4),
                'total' => 1000000,
                'total_guests' => 1,
                'currency' => 'VND',
                'status' => 'draft',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone' => '0123456789',
                'address' => 'Test Address',
                'create_user' => 30,
                'total_before_fees' => 1000000,
                'total_before_discount' => 1000000,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('bravo_bookings')->insert($bookings);
    }
} 