<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateBravoCarsContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('bravo_cars')
        ->whereBetween('id', [1, 13])
        ->update([
            'content' => 'NISMO has become the embodiment of Nissan\'s outstanding performance, inspired by the most unforgiving proving ground, the "race track".'
        ]);
    }
}
