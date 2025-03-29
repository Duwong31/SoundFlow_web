<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarAdditionalFeesSeeder extends Seeder
{
    public function run()
    {
        $additional_fees = [
                [
                    'id' => 1,
                    'name' => 'Tài sản thế chấp',
                    'amount' => 10000000,
                    'description' => 'Tiền mặt (chuyển khoản) trị giá 10 triệu đồng. Khoản tiền này sẽ được chủ xe hoàn trả cho bên thuê xe hoặc cấn trừ các chi phí như xăng, VETC,... theo chính sách từng Trạm xe/chủ xe.'
                ],
                [
                    'id' => 2, 
                    'name' => 'Phí quá giờ',
                    'amount' => 100000,
                    'unit' => 'đ/giờ',
                    'description' => 'Trường hợp bên thuê trả trễ. Trường hợp trễ quá 5 tiếng, phụ thu thêm 1 ngày thuê'
                ],
                [
                    'id' => 3,
                    'name' => 'Phụ phí vệ sinh', 
                    'amount' => 120000,
                    'description' => 'Phụ phí khi xe hoàn trả không đảm bảo vệ sinh'
                ],
                [
                    'id' => 4,
                    'name' => 'Phí vượt giới hạn',
                    'amount' => 5000,
                    'unit' => 'đ/km',
                    'description' => 'Tiền mặt (chuyển khoản) trị giá 10 triệu đồng. Khoản tiền này sẽ được chủ xe hoàn trả cho bên thuê xe.'
                ]
        ];

        DB::table('bravo_cars')->update([
            'additional_fees' => json_encode($additional_fees)
        ]);
    }
}