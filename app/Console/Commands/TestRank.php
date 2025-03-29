<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\User\Models\User;

class TestRank extends Command
{
    protected $signature = 'test:rank {user_id}';
    protected $description = 'Test rank calculation for a user';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error('User not found');
            return;
        }

        $this->info('User ID: ' . $user->id);
        $this->info('Points: ' . $user->points);
        $this->info('Rank: ' . $user->rank);
        
        return 0;
    }
} 