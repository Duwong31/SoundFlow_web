<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Booking\Models\Booking;
use Modules\Booking\Events\BookingReturnLateEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotiReturnCarLate extends Command
{
    protected $signature = 'booking:check-late-returns';
    protected $description = 'Check for car bookings that are past their return date and send notifications';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $now = Carbon::now();
            
            // Find car bookings:
            // 1. Past their end date
            // 2. Not completed or cancelled
            // 3. Notification hasn't been sent yet
            $lateBookings = Booking::whereNotIn('status', ['completed', 'cancelled'])
                ->where('end_date', '<', $now)
                ->where('sent_notification', 0)
                ->get();
            $count = 0;
            foreach ($lateBookings as $booking) {
                event(new BookingReturnLateEvent($booking));
                $booking->sent_notification = 1;
                $booking->save();
                $count++;
            }
            
            $this->info("Processed {$count} late car return notifications");
            Log::info("Late car return check completed: {$count} notifications sent");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error("Error in late car return notification job: " . $e->getMessage());
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
