<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateInsuranceInfo extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cars = DB::table('bravo_cars')->get();
        $count = 0;

        foreach ($cars as $car) {
            $insurance_info = null;
            if (!empty($car->insurance_info)) {
                $insurance_info = json_decode($car->insurance_info, true);
            }
            
            $features = [];
            if ($insurance_info && isset($insurance_info['features'])) {
                $features = $insurance_info['features'];
            } else {
                $features = [
                    ['id' => '1', 'name' => 'Thiệt hại do va chạm của xe cho thuê'],
                    ['id' => '2', 'name' => 'Xe cho thuê bị đánh cắp'],
                    ['id' => '3', 'name' => 'Cửa sổ, gương, khung gầm và lốp xe bị hư hỏng'],
                    ['id' => '4', 'name' => 'Chi phí hỗ trợ kéo xe và bên đường'],
                    ['id' => '5', 'name' => 'Hoả hoạn cháy, nổ']
                ];
            }

            $premium_price = '148000';
            $premium_price_unit = 'ngày';
            
            if ($insurance_info && isset($insurance_info['options']) && isset($insurance_info['options']['premium'])) {
                if (isset($insurance_info['options']['premium']['price'])) {
                    $premium_price = $insurance_info['options']['premium']['price'];
                }
                if (isset($insurance_info['options']['premium']['price_unit'])) {
                    $premium_price_unit = $insurance_info['options']['premium']['price_unit'];
                }
            }
            
            $options = [
                'basic' => [
                    'name' => 'Không, tôi sẵn sàng chấp nhận rủi ro'
                ],
                'premium' => [
                    'name' => 'Vâng, tôi muốn bảo vệ bản thân mình',
                    'price' => $premium_price,
                    'price_unit' => $premium_price_unit,
                    'description' => 'Nếu xe bị hư hỏng hoặc bị đánh cắp, bạn sẽ phải tự chi trả khoản phí phát sinh, nhưng miễn là bạn có Bảo hiểm toàn diện VNI, VNI sẽ hoàn lại cho bạn số tiền lên đến 300.000.000 đồng'
                ]
            ];

            $passenger_insurance = [
                'name' => 'Bảo vệ người ngồi trên xe',
                'price' => 25000,
                'price_unit' => 'đồng/ngày',
                'description' => 'Bảo vệ người lái xe và hành khách: Bình An Vạn Dặm' . "\n\n" . 
                                'Bảo vệ tất cả hành khách trên xe khỏi tai nạn hoặc tệ hơn với mức bảo hiểm tối đa 5 tỷ đồng/chuyến'
            ];
            
            if ($insurance_info && isset($insurance_info['passenger_insurance'])) {
                if (isset($insurance_info['passenger_insurance']['name'])) {
                    $passenger_insurance['name'] = $insurance_info['passenger_insurance']['name'];
                }
                if (isset($insurance_info['passenger_insurance']['price'])) {
                    $passenger_insurance['price'] = $insurance_info['passenger_insurance']['price'];
                }
                if (isset($insurance_info['passenger_insurance']['price_unit'])) {
                    $passenger_insurance['price_unit'] = $insurance_info['passenger_insurance']['price_unit'];
                }
                
                $description = isset($insurance_info['passenger_insurance']['description']) ? 
                    $insurance_info['passenger_insurance']['description'] : '';
                
                if (isset($insurance_info['passenger_insurance']['coverage_note']) && !empty($insurance_info['passenger_insurance']['coverage_note'])) {
                    if (!empty($description)) {
                        $description .= "\n\n";
                    }
                    $description .= $insurance_info['passenger_insurance']['coverage_note'];
                }
                
                if (!empty($description)) {
                    $passenger_insurance['description'] = $description;
                }
            }

            $new_insurance_info = [
                'features' => $features,
                'options' => $options,
                'passenger_insurance' => $passenger_insurance
            ];

            DB::table('bravo_cars')
                ->where('id', $car->id)
                ->update([
                    'insurance_info' => json_encode($new_insurance_info, JSON_UNESCAPED_UNICODE)
                ]);
                
            $count++;
        }

        $this->command->info("Đã cập nhật thông tin bảo hiểm cho {$count} xe!");
    }
}
