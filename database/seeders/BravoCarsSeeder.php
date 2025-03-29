<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BravoCarsSeeder extends Seeder
{
    public function run()
    {
        DB::table('bravo_cars')->where('id', 1)->update([
            'location_id' => 1,
            'address' => 'Hải Châu',
            'map_lat' => '16.054407',
            'map_lng' => '108.202164',
        ]);
        
        DB::table('bravo_cars')->where('id', 2)->update([
            'location_id' => 1,
            'address' => 'Sơn Trà',
            'map_lat' => '16.106889',
            'map_lng' => '108.233333',
        ]);
        
        DB::table('bravo_cars')->where('id', 3)->update([
            'location_id' => 1,
            'address' => 'Ngũ Hành Sơn',
            'map_lat' => '16.005970',
            'map_lng' => '108.259595',
        ]);
        
        DB::table('bravo_cars')->where('id', 4)->update([
            'location_id' => 2,
            'address' => 'Quận 4',
            'map_lat' => '10.764545',
            'map_lng' => '106.704147',
        ]);
        
        DB::table('bravo_cars')->where('id', 5)->update([
            'location_id' => 2,
            'address' => 'Quận 5',
            'map_lat' => '10.756851',
            'map_lng' => '106.667654',
        ]);
        
        DB::table('bravo_cars')->where('id', 6)->update([
            'location_id' => 2,
            'address' => 'Bình Thạnh',
            'map_lat' => '10.805512',
            'map_lng' => '106.712132',
        ]);
        
        DB::table('bravo_cars')->where('id', 7)->update([
            'location_id' => 2,
            'address' => 'Quận 7',
            'map_lat' => '10.732537',
            'map_lng' => '106.721471',
        ]);
        
        DB::table('bravo_cars')->where('id', 8)->update([
            'location_id' => 3,
            'address' => 'Tây Hồ',
            'map_lat' => '21.074284',
            'map_lng' => '105.819137',
        ]);
        
        DB::table('bravo_cars')->where('id', 9)->update([
            'location_id' => 3,
            'address' => 'Bắc Từ Liêm',
            'map_lat' => '21.076010',
            'map_lng' => '105.764913',
        ]);
        
        DB::table('bravo_cars')->where('id', 10)->update([
            'location_id' => 3,
            'address' => 'Cầu Giấy',
            'map_lat' => '21.028660',
            'map_lng' => '105.790860',
        ]);
        
        DB::table('bravo_cars')->where('id', 11)->update([
            'location_id' => 1,
            'address' => 'Liên Chiểu',
            'map_lat' => '16.086354',
            'map_lng' => '108.145719',
        ]);
        
        DB::table('bravo_cars')->where('id', 12)->update([
            'location_id' => 2,
            'address' => 'Quận 1',
            'map_lat' => '10.775659',
            'map_lng' => '106.700424',
        ]);
        
        DB::table('bravo_cars')->where('id', 13)->update([
            'location_id' => 3,
            'address' => 'Ba Đình',
            'map_lat' => '21.035059',
            'map_lng' => '105.814281',
        ]);
        
        
    }
}
