<?php

namespace App\Listeners;

use Modules\Booking\Events\BookingUpdatedEvent;
use Modules\Booking\Events\BookingCreatedEvent;
use Illuminate\Support\Facades\DB;

class UpdateCarBookCompleteCount
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $booking = $event->booking;
        
        if ($booking->object_model !== 'car') {
            return;
        }

        if ($booking->status === 'completed') {
            DB::table('bravo_cars')
                ->where('id', $booking->object_id)
                ->increment('book_complete');
        }
        
        if ($booking->getOriginal('status') === 'completed' && $booking->status !== 'completed') {
            DB::table('bravo_cars')
                ->where('id', $booking->object_id)
                ->decrement('book_complete');
        }

        // You may need to add a check here to determine the event type
        // if (get_class($event) === 'Modules\Booking\Events\BookingCreatedEvent') {
        //     // Handle BookingCreatedEvent
        // } elseif (get_class($event) === 'Modules\Booking\Events\BookingUpdatedEvent') {
        //     // Handle BookingUpdatedEvent
        // }
    }
} 