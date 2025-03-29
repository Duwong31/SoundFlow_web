<?php
namespace Modules\Car\Admin;

use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Car\Models\Car;
use Modules\Car\Models\CarDate;

class AvailabilityController extends \Modules\Car\Controllers\AvailabilityController
{
    protected $carClass;
    protected $carDateClass;
    protected $bookingClass;
    protected $indexView = 'Car::admin.availability';

    public function __construct(Car $carClass, CarDate $carDateClass, Booking $bookingClass)
    {
        $this->setActiveMenu(route('car.admin.index'));
        $this->middleware('dashboard');
        $this->carClass = $carClass;
        $this->carDateClass = $carDateClass;
        $this->bookingClass = $bookingClass;
    }
    
    /**
     * Store availability settings
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'target_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        
        $car_id = $request->input('target_id');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $start_time = $request->input('start_time', '00:00');
        $end_time = $request->input('end_time', '23:59');
        
        // Format full datetime for database storage
        $start_datetime = date('Y-m-d H:i:s', strtotime("$start_date $start_time"));
        $end_datetime = date('Y-m-d H:i:s', strtotime("$end_date $end_time"));
        
        // Check for overlapping time slots
        $overlapping = $this->checkOverlappingTimeSlots($car_id, $start_datetime, $end_datetime, $request->input('id'));
        
        if ($overlapping) {
            return response()->json([
                'status' => false,
                'message' => __('The selected time slot overlaps with an existing booking. Please choose a different time.')
            ]);
        }
        
        if ($request->input('id')) {
            $availability = $this->carDateClass::find($request->input('id'));
            if (!$availability) {
                return response()->json([
                    'status' => false,
                    'message' => __('Availability not found')
                ]);
            }
        } else {
            $availability = new $this->carDateClass();
            $availability->target_id = $car_id;
        }
        
        $availability->start_date = $start_datetime;
        $availability->end_date = $end_datetime;
        $availability->price = $request->input('price');
        $availability->number = $request->input('number', 1);
        $availability->active = $request->input('active', 0);
        $availability->is_instant = $request->input('is_instant', 0);
        
        $availability->save();
        
        return response()->json([
            'status' => true,
            'message' => __('Availability updated')
        ]);
    }
    
    /**
     * Check for overlapping time slots
     * 
     * @param int $car_id
     * @param string $start_datetime
     * @param string $end_datetime
     * @param int|null $except_id
     * @return bool
     */
    private function checkOverlappingTimeSlots($car_id, $start_datetime, $end_datetime, $except_id = null)
    {
        $query = $this->carDateClass::where('target_id', $car_id)
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
        
        return $query->exists();
    }
    
    /**
     * Load dates for availability calendar
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loadDates(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'start' => 'required',
            'end' => 'required',
        ]);
        
        $car_id = $request->input('id');
        $start = $request->input('start');
        $end = $request->input('end');
        
        // Create a formatted start and end date to ensure we get all events
        $formattedStart = date('Y-m-d 00:00:00', strtotime($start));
        $formattedEnd = date('Y-m-d 23:59:59', strtotime($end));
        
        $dates = $this->carDateClass::where('target_id', $car_id)
            ->where(function($query) use ($formattedStart, $formattedEnd) {
                // Get all events that overlap with the requested period
                $query->where(function($q) use ($formattedStart, $formattedEnd) {
                    // Start date is within the period
                    $q->where('start_date', '>=', $formattedStart)
                      ->where('start_date', '<=', $formattedEnd);
                })->orWhere(function($q) use ($formattedStart, $formattedEnd) {
                    // End date is within the period
                    $q->where('end_date', '>=', $formattedStart)
                      ->where('end_date', '<=', $formattedEnd);
                })->orWhere(function($q) use ($formattedStart, $formattedEnd) {
                    // Event spans the entire period
                    $q->where('start_date', '<=', $formattedStart)
                      ->where('end_date', '>=', $formattedEnd);
                });
            })
            ->orderBy('start_date')
            ->get();
        
        $events = [];
        
        foreach ($dates as $date) {
            $start_datetime = $date->start_date;
            $end_datetime = $date->end_date;
            
            $bookings = $this->bookingClass::where('object_id', $car_id)
                ->where('object_model', 'car')
                ->whereIn('status', ['processing', 'confirmed', 'completed', 'paid'])
                ->where(function($query) use ($start_datetime, $end_datetime) {
                    $query->where(function($q) use ($start_datetime, $end_datetime) {
                        $q->where('start_date', '>=', $start_datetime)
                          ->where('start_date', '<', $end_datetime);
                    })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                        $q->where('end_date', '>', $start_datetime)
                          ->where('end_date', '<=', $end_datetime);
                    })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                        $q->where('start_date', '<=', $start_datetime)
                          ->where('end_date', '>=', $end_datetime);
                    });
                })
                ->get();
            
            $totalBooked = $bookings->sum('number');
            $remainingSlots = max(0, $date->number - $totalBooked);
            $isFullyBooked = $remainingSlots <= 0;
            
            $slotInfo = $date->price ? format_money($date->price) : __('Free');
            if ($totalBooked > 0) {
                $slotInfo .= " (" . $totalBooked . "/" . $date->number . " " . __('booked') . ")";
            }
            
            $events[] = [
                'id' => $date->id,
                'title' => $slotInfo,
                'start' => date('Y-m-d H:i:s', strtotime($date->start_date)),
                'end' => date('Y-m-d H:i:s', strtotime($date->end_date)),
                'backgroundColor' => $isFullyBooked ? '#f56565' : ($date->active ? '#3490dc' : '#f56565'),
                'borderColor' => $isFullyBooked ? '#e53e3e' : ($date->active ? '#3490dc' : '#f56565'),
                'textColor' => '#fff',
                'extendedProps' => [
                    'id' => $date->id,
                    'price' => $date->price,
                    'active' => $isFullyBooked ? 0 : $date->active,
                    'number' => $date->number,
                    'is_instant' => $date->is_instant,
                    'total_booked' => $totalBooked,
                    'remaining' => $remainingSlots,
                    'is_fully_booked' => $isFullyBooked
                ]
            ];
        }
        
        $allBookings = $this->bookingClass::where('object_id', $car_id)
            ->where('object_model', 'car')
            ->whereIn('status', ['processing', 'confirmed', 'completed', 'paid'])
            ->where(function($query) use ($formattedStart, $formattedEnd) {
                $query->where(function($q) use ($formattedStart, $formattedEnd) {
                    $q->where('start_date', '>=', $formattedStart)
                      ->where('start_date', '<=', $formattedEnd);
                })->orWhere(function($q) use ($formattedStart, $formattedEnd) {
                    $q->where('end_date', '>=', $formattedStart)
                      ->where('end_date', '<=', $formattedEnd);
                })->orWhere(function($q) use ($formattedStart, $formattedEnd) {
                    $q->where('start_date', '<=', $formattedStart)
                      ->where('end_date', '>=', $formattedEnd);
                });
            })
            ->get();
        
        $existingTimeSlots = [];
        foreach ($events as $event) {
            $existingTimeSlots[$event['start'] . '_' . $event['end']] = true;
        }
        
        foreach ($allBookings as $booking) {
            $bookingStartTime = $booking->start_date;
            $bookingEndTime = $booking->end_date;
            
            $timeSlotKey = $bookingStartTime . '_' . $bookingEndTime;
            if (!isset($existingTimeSlots[$timeSlotKey])) {
                $metaData = \DB::table('bravo_booking_meta')
                    ->where('booking_id', $booking->id)
                    ->whereIn('name', ['pickup_time', 'dropoff_time'])
                    ->get();
                
                $bookingMeta = [];
                foreach ($metaData as $meta) {
                    $bookingMeta[$meta->name] = $meta->val;
                }
                
                $events[] = [
                    'id' => 'booking_' . $booking->id,
                    'title' => __('Booked') . ' (' . $booking->status . ')',
                    'start' => $bookingStartTime,
                    'end' => $bookingEndTime,
                    'backgroundColor' => '#f56565',
                    'borderColor' => '#e53e3e',
                    'textColor' => '#fff',
                    'extendedProps' => [
                        'booking_id' => $booking->id,
                        'is_booking' => true,
                        'status' => $booking->status,
                        'number' => $booking->number,
                        'pickup_time' => $bookingMeta['pickup_time'] ?? null,
                        'dropoff_time' => $bookingMeta['dropoff_time'] ?? null
                    ]
                ];
                
                $existingTimeSlots[$timeSlotKey] = true;
            }
        }
        
        return response()->json($events);
    }
    
    /**
     * Get hourly availability for a week
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWeeklyAvailability(Request $request)
    {
        $request->validate([
            'car_id' => 'required',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);
        
        $car_id = $request->input('car_id');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        
        // Add time to make sure we get all events
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        
        $dates = $this->carDateClass::where('target_id', $car_id)
            ->where(function($query) use ($start_datetime, $end_datetime) {
                $query->where(function($q) use ($start_datetime, $end_datetime) {
                    // Start date is within range
                    $q->where('start_date', '>=', $start_datetime)
                      ->where('start_date', '<=', $end_datetime);
                })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                    // End date is within range
                    $q->where('end_date', '>=', $start_datetime)
                      ->where('end_date', '<=', $end_datetime);
                })->orWhere(function($q) use ($start_datetime, $end_datetime) {
                    // Event spans the entire range
                    $q->where('start_date', '<=', $start_datetime)
                      ->where('end_date', '>=', $end_datetime);
                });
            })
            ->get();
        
        // Format the data for the weekly grid view
        $result = [];
        
        foreach ($dates as $date) {
            $start = strtotime($date->start_date);
            $end = strtotime($date->end_date);
            
            // Get the day of the date
            $day = date('Y-m-d', $start);
            
            // Get the hour of the date
            $startHour = intval(date('H', $start));
            $endHour = intval(date('H', $end));
            
            // If end hour is 0, it means it's midnight of the next day
            if ($endHour === 0 && date('i:s', $end) === '00:00') {
                $endHour = 24;
            }
            
            // Create an entry for each hour in the range
            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $hourKey = sprintf('%02d', $hour);
                
                if (!isset($result[$day])) {
                    $result[$day] = [];
                }
                
                $result[$day][$hourKey] = [
                    'id' => $date->id,
                    'price' => $date->price,
                    'active' => $date->active,
                    'number' => $date->number,
                    'is_instant' => $date->is_instant,
                ];
            }
        }
        
        return response()->json([
            'status' => true,
            'data' => $result
        ]);
    }
}
