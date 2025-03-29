<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ZaloSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, try to update if exists
        $zaloSetting = DB::table('zalo_settings')->where('id', 1)->first();
        
        $data = [
            'company_id' => 1,
            'zalo_app_id' => '3672042734964834761',
            'zalo_app_secret' => 'HSb0tCW1d1FYf0SiNQN5',
            'zalo_refresh_token' => 'PqPgQ15WU7vdG6ng765vUc9AQsWKTW4U32rw23fO64qmVWa96HfhR10bSYyl3o4ZDaTrR3Gp41fD6KLKLcub4M46LsG2UJ8LEZDQ0K5M3Kjb1KumNdyiPNmKQ0rZKGLq5GHdC09XTqGc10LLFrzN4KWt6dqsRcjf6mq-9ZP8KtG962OZCqz2QKKfA2POGNfoRmeXF4jED7bJ679jAd0T5Xu9QKvZVXbMVZbt50GR97G2ENGF55nc6Ju36657PtiOSpyKKLPf14X80c8QDbqdGWuhKG9rM2L1OZfXAsrmSJjdKMDD174F8YyP6tCqOLKrCqCZOnSzQmi97Z12A5zyBnKNRJWZVo5n1XT4EIbzCNqK0tWD0degTYKaSY0SGJjA3Huf6mf0P5ar8dOrBbiI9KjE4Y4X7NfvV0',
            'zalo_access_token' => 'Q5P1MZF2FZzeK4TOFiC_EsrQG5esr4L31YT2PZtTN0SMArSqFUXj12mVSHqVXmWLAbKL37627HLhKW04GvCPD5b_5ZvRdYWa2MSnEY62DoCNOK8S5P9BVWHGI04Ukp5dDa8wUJkH36TWHtW6IuXxDnfUU54Fg7it5LuFC4-P5H9PV0m7IBGn3HvCIHT8tq8h6J9B8odKLKKEJKXyEffKNJXyAdyrjnP2B4PZO1UL708zNnKW3SeQ5YO_P2qWy6Gu00KUCWttFYu0BZmnKSir5q4_67Pfp0HsEo1vT6gm11HpKIuU9CGA7YKgDY0opayU0YrtEZJC2IGP1XnR8j8qIpSYErWToWq-Dpan7J6e4X1XKXCtGy8Q2qCk9pyHmmm18JaB0ZhT2XaBFo4Y6TC4EKyG0pnZe4nIFzW_FG',
            'note' => '',
            'updated_at' => now()
        ];

        if ($zaloSetting) {
            // Update existing record
            DB::table('zalo_settings')
                ->where('id', 1)
                ->update($data);
        } else {
            // Insert new record
            $data['id'] = 1;
            $data['created_at'] = now();
            DB::table('zalo_settings')->insert($data);
        }
    }
}