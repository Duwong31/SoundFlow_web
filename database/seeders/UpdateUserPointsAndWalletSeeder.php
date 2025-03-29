<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateUserPointsAndWalletSeeder extends Seeder
{
    public function run()
    {
        // Lấy tất cả users hiện tại
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            $wallet_balance = rand(0, 1000) * 100000;
            
            $points = rand(0, 1000);
            
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'wallet_balance' => $wallet_balance,
                    'points' => $points,
                ]);
        }
    }

} 