<?php

namespace Modules\Booking\Commands;

use Illuminate\Console\Command;
use Modules\Booking\Events\BookingReturnLateEvent;
use Modules\Booking\Models\Booking;

class CheckReturnTimeCommand extends Command
{
    protected $signature = 'booking:check-return-time';
    protected $description = 'Check bookings that are approaching return time and send notifications';

    public function handle()
    {
        $now = now();
        $thresholdTime = now()->addMinutes(30); // 30 phút trước khi phải trả xe
        
        $bookings = Booking::where('status', 'active')
                         ->where('end_date', '<=', $thresholdTime)
                         ->where('end_date', '>', $now)
                         ->get();
        
        $count = 0;
        foreach ($bookings as $booking) {
            event(new BookingReturnLateEvent($booking));
            $count++;
        }
        
        $this->info("Sent {$count} return time notifications.");
    }
} 