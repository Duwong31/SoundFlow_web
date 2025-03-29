<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CarBrandSeeder extends Seeder
{
    public function run()
    {
        
        $brands = [
            [
                'name' => 'BMW',
                'logo_id' => null
            ],
            [
                'name' => 'Honda',
                'logo_id' => null
            ],
            [
                'name' => 'Audi',
                'logo_id' => null
            ],
            [
                'name' => 'Vinfast',
                'logo_id' => null
            ],
            [
                'name' => 'Mercedes',
                'logo_id' => null
            ],
            [
                'name' => 'Toyota',
                'logo_id' => null
            ],
            [
                'name' => 'Porsche',
                'logo_id' => null
            ]
        ];

        // Thêm brands và lưu mapping của id
        $brandIdMap = [];
        foreach ($brands as $brand) {
            $id = DB::table('car_brands')->insertGetId([
                'name' => $brand['name'],
                'slug' => Str::slug($brand['name']),
                'logo_id' => $brand['logo_id'],
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $brandIdMap[$brand['name']] = $id;
        }

        // Cập nhật brand_id trong bảng bravo_cars
        foreach ($brandIdMap as $brandName => $brandId) {
            DB::table('bravo_cars')
                ->where('brand', $brandName)
                ->update(['brand_id' => $brandId]);
        }
    }
} 