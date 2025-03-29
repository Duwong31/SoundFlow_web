<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Media\Models\MediaFile;

class BravoLocationsSeeder extends Seeder
{
    public function run()
    {
        DB::table('bravo_locations')->truncate();
        DB::statement('ALTER TABLE bravo_locations AUTO_INCREMENT = 1;');
        $tripIdea1 = MediaFile::findMediaByName("trip-idea-1")->id;
        $tripIdea2 = MediaFile::findMediaByName("trip-idea-2")->id;
        $locations = [
            [
                'name' => 'Đà Nẵng',
                'slug' => 'da-nang',
                'image_id' => 108,
                'map_lat' => '16.047079',
                'map_lng' => '108.206230',
                'map_zoom' => 12,
                'status' => 'publish',
                'create_user' => 1,
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'name' => 'TP Hồ Chí Minh',
                'slug' => 'tp-ho-chi-minh',
                'map_lat' => '10.81541822540918',
                'map_lng' => '106.63089602960646',
                'map_zoom' => 12,
                'status' => 'publish',
                'create_user' => 7,
                'created_at' => date("Y-m-d H:i:s"),
            ],
            [
                'name' => 'Hà Nội',
                'slug' => 'ha-noi',
                'map_lat' => '21.105057053863963',
                'map_lng' => '105.85270138800976',
                'map_zoom' => 7,
                'status' => 'publish',
                'create_user' => 7,
                'created_at' => date("Y-m-d H:i:s"),
            ],
        ];

        foreach ($locations as $location) {
            DB::table('bravo_locations')->insert($location);
        }
    }
}
