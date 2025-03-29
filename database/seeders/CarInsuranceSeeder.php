<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarInsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $insurance_info = [
            'features' => [
                [
                    'id' => '1',
                    'name' => 'Thiệt hại do va chạm của xe cho thuê'
                ],
                [
                    'id' => '2', 
                    'name' => 'Xe cho thuê bị đánh cắp'
                ],
                [
                    'id' => '3',
                    'name' => 'Cửa sổ, gương, khung gầm và lốp xe bị hư hỏng'
                ],
                [
                    'id' => '4',
                    'name' => 'Chi phí hỗ trợ kéo xe và bên đường'
                ],
                [
                    'id' => '5',
                    'name' => 'Hoả hoạn cháy, nổ'
                ]
            ],
            'options' => [
                'basic' => [
                    'name' => 'Không, tôi sẵn sàng chấp nhận rủi ro',
                    'price' => 0,
                    'price_unit' => 'ngày',
                    'includes_all' => false
                ],
                'premium' => [
                    'name' => 'Vâng, tôi muốn bảo vệ bản thân mình',
                    'price' => 148000,
                    'price_unit' => 'ngày', 
                    'includes_all' => true,
                    'coverage_amount' => 300000000,
                    'coverage_note' => 'Nếu xe bị hư hỏng hoặc bị đánh cắp, bạn sẽ phải tự chi trả khoản phí phát sinh, nhưng miễn là bạn có Bảo hiểm toàn diện VNI, VNI sẽ hoàn lại cho bạn số tiền lên đến 300.000.000 đồng'
                ]
            ],
            'passenger_insurance' => [
                'name' => 'Bảo vệ người ngồi trên xe',
                'price' => 25000,
                'price_unit' => 'đồng/ngày',
                'description' => 'Bảo vệ người lái xe và hành khách: Bình An Vạn Dặm',
                'coverage_note' => 'Bảo vệ tất cả hành khách trên xe khỏi tai nạn hoặc tệ hơn với mức bảo hiểm tối đa 5 tỷ đồng/chuyến',
            ]
        ];

        // Update all cars with insurance info
        DB::table('bravo_cars')->update([
            'insurance_info' => json_encode($insurance_info)
        ]);
    }
}