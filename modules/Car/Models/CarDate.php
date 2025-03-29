<?php
namespace Modules\Car\Models;

use App\BaseModel;
use Carbon\Carbon;

class CarDate extends BaseModel
{
    protected $table = 'bravo_car_dates';

    protected $casts = [
        'person_types'=>'array',
        'price'=>'float',
        'sale_price'=>'float',
    ];

    protected $fillable = [
        'start_date',
        'end_date',
        'price',
        'number',
        'active',
        'note_to_customer',
        'note_to_admin',
        'is_instant',
        'target_id'
    ];

    /**
     * Get dates in range for daily view
     * 
     * @param string $start_date
     * @param string $end_date
     * @param int $id
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDatesInRanges($start_date, $end_date, $id)
    {
        return static::query()->where([
            ['start_date', '>=', $start_date],
            ['end_date', '<=', $end_date],
            ['target_id', '=', $id],
        ])->take(100)->get();
    }

    /**
     * Get hourly availability for a specific day
     * 
     * @param string $date Date in Y-m-d format
     * @param int $id Car ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getHourlyAvailabilityForDay($date, $id)
    {
        $start_of_day = date('Y-m-d 00:00:00', strtotime($date));
        $end_of_day = date('Y-m-d 23:59:59', strtotime($date));
        
        return static::query()->where('target_id', $id)
            ->where(function($query) use ($start_of_day, $end_of_day) {
                // Cases where availability overlaps with the day
                $query->where(function($q) use ($start_of_day, $end_of_day) {
                    // Case 1: Starts within the day
                    $q->where('start_date', '>=', $start_of_day)
                      ->where('start_date', '<=', $end_of_day);
                })->orWhere(function($q) use ($start_of_day, $end_of_day) {
                    // Case 2: Ends within the day
                    $q->where('end_date', '>=', $start_of_day)
                      ->where('end_date', '<=', $end_of_day);
                })->orWhere(function($q) use ($start_of_day, $end_of_day) {
                    // Case 3: Spans across the entire day
                    $q->where('start_date', '<=', $start_of_day)
                      ->where('end_date', '>=', $end_of_day);
                });
            })
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Check if a specific time slot is available for a car
     * 
     * @param int $car_id
     * @param string $start_datetime
     * @param string $end_datetime
     * @param int|null $except_id
     * @return bool
     */
    public static function isTimeSlotAvailable($car_id, $start_datetime, $end_datetime, $except_id = null)
    {
        $query = static::where('target_id', $car_id)
            ->where('active', 1)
            ->where(function($query) use ($start_datetime, $end_datetime) {
                $query->where(function($q) use ($start_datetime, $end_datetime) {
                    // Case 1: Start time is between existing start and end
                    $q->where('start_date', '<=', $start_datetime)
                      ->where('end_date', '>', $start_datetime);
                })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                    // Case 2: End time is between existing start and end
                    $q->where('start_date', '<', $end_datetime)
                      ->where('end_date', '>=', $end_datetime);
                })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                    // Case 3: Completely enclosing an existing slot
                    $q->where('start_date', '>=', $start_datetime)
                      ->where('end_date', '<=', $end_datetime);
                });
            });
            
        if ($except_id) {
            $query->where('id', '!=', $except_id);
        }
        
        // If there are any records, the time slot is not available
        return !$query->exists();
    }

    /**
     * Calculate price for a specific time slot
     * 
     * @param int $car_id
     * @param string $start_datetime
     * @param string $end_datetime
     * @return float|null
     */
    public static function calculatePriceForTimeSlot($car_id, $start_datetime, $end_datetime)
    {
        $availability = static::where('target_id', $car_id)
            ->where('active', 1)
            ->where('start_date', '<=', $start_datetime)
            ->where('end_date', '>=', $end_datetime)
            ->first();
        
        if ($availability) {
            // Calculate total hours (rounded up to nearest hour)
            $start = Carbon::parse($start_datetime);
            $end = Carbon::parse($end_datetime);
            $hours = ceil($end->diffInMinutes($start) / 60);
            
            // Price per hour
            return $availability->price * $hours;
        }
        
        return null;
    }

    /**
     * Get the remaining available cars for a specific time period
     * 
     * @param int $car_id
     * @param string $start_datetime
     * @param string $end_datetime
     * @param int|null $exclude_booking_id
     * @return int
     */
    public static function getRemainingAvailability($car_id, $start_datetime, $end_datetime, $exclude_booking_id = null)
    {
        
        // First find the relevant availability records
        $availabilityRecords = static::where('target_id', $car_id)
            ->where('active', 1)
            ->where(function($query) use ($start_datetime, $end_datetime) {
                $query->where(function($q) use ($start_datetime, $end_datetime) {
                    $q->where('start_date', '<=', $start_datetime)
                      ->where('end_date', '>=', $start_datetime);
                })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                    $q->where('start_date', '<=', $end_datetime)
                      ->where('end_date', '>=', $end_datetime);
                })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                    $q->where('start_date', '>=', $start_datetime)
                      ->where('end_date', '<=', $end_datetime);
                });
            })
            ->get();
            
        if ($availabilityRecords->isEmpty()) {
            return 0;
        }
        
        $totalAvailable = $availabilityRecords->min('number') ?? 0;
        
        $bookingClass = \Modules\Booking\Models\Booking::class;
        $existingBookings = $bookingClass::where('object_id', $car_id)
            ->where('object_model', 'car')
            ->whereIn('status', ['processing', 'confirmed', 'completed', 'paid']) 
            ->where(function($query) use ($start_datetime, $end_datetime) {
                $query->where(function($q) use ($start_datetime, $end_datetime) {
                    // Case 1: Booking start time is between our start and end
                    $q->where('start_date', '>=', $start_datetime)
                      ->where('start_date', '<', $end_datetime);
                })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                    // Case 2: Booking end time is between our start and end
                    $q->where('end_date', '>', $start_datetime)
                      ->where('end_date', '<=', $end_datetime);
                })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                    // Case 3: Booking completely spans our time period
                    $q->where('start_date', '<=', $start_datetime)
                      ->where('end_date', '>=', $end_datetime);
                });
            });
            
        if ($exclude_booking_id) {
            $existingBookings->where('id', '!=', $exclude_booking_id);
        }
        
        $bookings = $existingBookings->get();
        
        // Calculate total booked cars
        $totalBooked = 0;
        
        foreach ($bookings as $booking) {
            $totalBooked += $booking->number;
        }
        
        $remaining = max(0, $totalAvailable - $totalBooked);
        
        return $remaining;
    }
}
