<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeletePendingRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all users with pending registration status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // Count all pending registrations
            $count = DB::table('users')
                ->where('registration_status', '=', 'pending')
                ->count();
            
            if ($count > 0) {
                // Delete all users with pending registration status
                $deleted = DB::table('users')
                    ->where('registration_status', '=', 'pending')
                    ->delete();
                
                // Log the deletion
                Log::info("Deleted {$deleted} users with pending registration status");
                $this->info("Successfully deleted {$deleted} users with pending registration status");
            } else {
                $this->info("No users with pending registration status found");
            }
            
            return 0;
        } catch (\Exception $e) {
            Log::error("Error deleting pending registrations: " . $e->getMessage());
            $this->error("Failed to delete pending registrations: " . $e->getMessage());
            
            return 1;
        }
    }
} 