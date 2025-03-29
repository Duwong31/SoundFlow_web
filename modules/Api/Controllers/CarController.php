<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Car\Models\Car;
use Modules\Car\Resources\CarResource;
use Modules\Location\Models\Location;
use Modules\Review\Models\Review;
use Illuminate\Support\Facades\Validator;

class CarController extends Controller
{
    protected $carClass;
    protected $reviewClass;
    
    public function __construct(Car $carClass, Review $reviewClass)
    {
        $this->carClass = $carClass;
        $this->reviewClass = $reviewClass;
    }

    protected function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
            return null;
        }

        $earthRadius = 6371; // Radius of the earth in km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta/2) * sin($latDelta/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta/2) * sin($lonDelta/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c; // Distance in km

        return round($distance, 1);
    }

    public function listCars(Request $request)
    {
        $query = Car::query()->where('status', 'publish');

        // Filter xe 5 sao
        if ($request->has('is_five_star')) {
            $value = $request->input('is_five_star');
            if ($value === "1" || $value === "true") {
                $query->where('review_score', '=', 5.0);
            }
        }

        // Filter xe đang giảm giá
        if ($request->has('is_discount')) {
            $value = $request->input('is_discount');
            if ($value === "1" || $value === "true") {
                $query->where('sale_price', '>', 0);
            }
        }

        // Filter xe đề xuất 
        if ($request->has('is_recommended')) {
            $value = $request->input('is_recommended');
            if ($value === "1" || $value === "true") {
                $query->where(function($q) {
                    $q->where('is_featured', 1)
                      ->orWhere(function($q) {
                          $q->where('review_score', '>=', 4.0) 
                            ->where('is_instant', 1);           
                      });
                });
            }
        }

        // Filter giao xe tận nơi
        if ($request->has('is_instant')) {
            $value = $request->input('is_instant');
            if ($value === "1" || $value === "true") {
                $query->where('is_instant', 1);
            }
        }

        // Filter theo loại xe
        if ($request->has('car_type')) {
            $carType = $request->input('car_type');
            switch($carType) {
                case 'otod_recommended':
                    $query->where('is_featured', 1);
                    break;
                case 'luxury':
                    $query->where('car_type', 'luxury');
                    break;
                case 'mid_range':
                    $query->where('car_type', 'mid_range');
                    break;
                case 'pickup':
                    $query->where('car_type', 'pickup');
                    break;
                case 'suv_mpv':
                    $query->whereIn('car_type', ['suv', 'mpv']);
                    break;
                case 'electric':
                    $query->where('fuel_type', 'điện');
                    break;
            }
        }

        // Filter theo hãng xe
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter theo giá
        if ($request->has('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        // Filter theo mẫu xe
        if ($request->has('model')) {
            $query->whereIn('model', $request->input('model')); 
        }

        // Filter theo số chỗ
        if ($request->has('passenger')) {
            $query->whereIn('passenger', $request->input('passenger')); 
        }

        // Filter theo loại xe
        if ($request->has('transmission_type')) {
            $query->whereIn('transmission_type', $request->input('transmission_type'));
        }

        // Filter theo nhiên liệu
        if ($request->has('fuel_type')) {
            $query->whereIn('fuel_type', $request->input('fuel_type'));
        }

        // Filter theo khu vực
        if ($request->has('address')) {
            $addresses = $request->input('address');
            $query->where(function($q) use ($addresses) {
                foreach ($addresses as $address) {
                    $q->orWhere('address', 'LIKE', '%' . $address . '%');
                }
            });
        }

        // Filter theo location
        if ($request->has('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'LIKE', '%' . $request->input('search') . '%');
        }

        // Filter by availability (start_date, end_date, pickup_location, dropoff_location)
        $availableCars = [];
        $needCheckAvailability = false;
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $pickupLat = $request->input('pickup_lat');
        $pickupLng = $request->input('pickup_lng');
        $dropoffLat = $request->input('dropoff_lat');
        $dropoffLng = $request->input('dropoff_lng');
        $returnSameLocation = $request->input('return_same_location', 'false');
        
        // Check if we need to filter by availability
        if ($startDate && $endDate) {
            $needCheckAvailability = true;
            
            if ($startDate && !strpos($startDate, ':')) {
                $startDateTime = date('Y-m-d 00:00:00', strtotime($startDate));
            } else {
                $startDateTime = date('Y-m-d H:i:s', strtotime($startDate));
            }

            if ($endDate && !strpos($endDate, ':')) {
                $endDateTime = date('Y-m-d 23:59:59', strtotime($endDate));
            } else {
                $endDateTime = date('Y-m-d H:i:s', strtotime($endDate));
            }
            
            // tìm xe có bất kỳ khung giờ nào khả dụng trong ngày
            $carIds = \Modules\Car\Models\CarDate::query()
                ->where('active', 1)
                ->where(function($query) use ($startDateTime, $endDateTime) {
                    $query->where(function($q) use ($startDateTime, $endDateTime) {
                        // Có thời gian khả dụng trong khoảng ngày được chọn
                        $q->where('start_date', '>=', $startDateTime)
                          ->where('start_date', '<=', $endDateTime);
                    })->orWhere(function($q) use ($startDateTime, $endDateTime) {
                        // Hoặc bao trùm toàn bộ khoảng thời gian được chọn
                        $q->where('start_date', '<=', $startDateTime)
                          ->where('end_date', '>=', $endDateTime);
                    });
                })
                ->where('number', '>', 0)
                ->pluck('target_id')
                ->toArray();
            
            if (!empty($carIds)) {
                $query->whereIn('id', $carIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        
        // Filter by location
        if (($pickupLat && $pickupLng) || ($dropoffLat && $dropoffLng)) {
            if ($returnSameLocation === 'true' || $returnSameLocation === '1') {
                if ($pickupLat && $pickupLng) {
                    // Find nearby cars based on pickup location
                    $nearbyCarIds = $this->findNearbyCarsByCoordinates($pickupLat, $pickupLng);
                    if (!empty($nearbyCarIds)) {
                        $query->whereIn('id', $nearbyCarIds);
                    }
                }
            } else {
                // Handle different pickup and dropoff locations
                $pickupCarIds = [];
                $dropoffCarIds = [];
                
                if ($pickupLat && $pickupLng) {
                    $pickupCarIds = $this->findNearbyCarsByCoordinates($pickupLat, $pickupLng);
                }
                
                if ($dropoffLat && $dropoffLng) {
                    $dropoffCarIds = $this->findNearbyCarsByCoordinates($dropoffLat, $dropoffLng);
                }
                
                // Only show cars available at both pickup and dropoff locations
                if (!empty($pickupCarIds) && !empty($dropoffCarIds)) {
                    $carIds = array_intersect($pickupCarIds, $dropoffCarIds);
                    if (!empty($carIds)) {
                        $query->whereIn('id', $carIds);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                } else if (!empty($pickupCarIds)) {
                    $query->whereIn('id', $pickupCarIds);
                } else if (!empty($dropoffCarIds)) {
                    $query->whereIn('id', $dropoffCarIds);
                }
            }
        }

        // Sort options
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $allowedSortFields = ['id', 'title', 'price', 'created_at', 'review_score'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id', 'asc');
        }

        $limit = $request->input('is_recommended') ? 4 : $request->input('limit', 10);
        
        $cars = $query->with(['location', 'translation', 'hasWishList'])
                     ->paginate($limit);

        // Calculate distance for each car
        $userLat = $request->input('latitude');
        $userLng = $request->input('longitude');

        $carsArray = $cars->items();
        foreach ($carsArray as &$car) {
            $distance = $this->calculateDistance(
                $userLat,
                $userLng,
                $car->map_lat,
                $car->map_lng
            );
            
            $car->distance = $distance ? $distance . 'km' : null;
            
            // Add wishlist_status as a boolean
            $car->wishlist_status = !empty($car->hasWishList);
            
            if ($needCheckAvailability && $startDate && $endDate) {
                // Kiểm tra nếu đang tìm kiếm cho cả ngày
                $isFullDaySearch = 
                    (!strpos($startDate, ':') && !strpos($endDate, ':')) ||
                    (date('H:i:s', strtotime($startDateTime)) == '00:00:00' && 
                    date('H:i:s', strtotime($endDateTime)) == '23:59:59');
                
                if ($isFullDaySearch) {
                    // Lấy tất cả khung giờ có sẵn trong ngày
                    $dayStart = date('Y-m-d 00:00:00', strtotime($startDateTime));
                    $dayEnd = date('Y-m-d 23:59:59', strtotime($startDateTime));
                    
                    $availableSlots = $this->getAvailableTimeSlotsForDay($car->id, $dayStart);
                    
                    if (empty($availableSlots)) {
                        $car->is_available = false;
                        $car->available_slots = [];
                    } else {
                        $car->is_available = true;
                        $car->available_slots = $availableSlots;
                    }
                } else {
                    // Nếu tìm kiếm cho khung giờ cụ thể
                    $isAvailable = $this->isCarAvailableForPeriod($car->id, $startDateTime, $endDateTime);
                    $car->is_available = $isAvailable;
                    
                    // Nếu không khả dụng trong khung giờ yêu cầu, gợi ý các khung giờ có sẵn
                    if (!$isAvailable) {
                        $availableSlots = $this->getAvailableTimeSlotsForDay($car->id, $startDateTime);
                        $car->available_slots = $availableSlots;
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'cars' => CarResource::collection($carsArray),
                'total' => $cars->total(),
                'total_pages' => $cars->lastPage(),
                'current_page' => $cars->currentPage(),
                'per_page' => $cars->perPage(),
            ]
        ]);
    }

    /**
     * Find nearby cars based on coordinates
     * 
     * @param float $latitude
     * @param float $longitude
     * @param float $radiusKm
     * @return array
     */
    protected function findNearbyCarsByCoordinates($latitude, $longitude, $radiusKm = 10)
    {
        // First, find cars with coordinates within the radius
        $nearbyCarsByCoordinates = Car::query()
            ->where('status', 'publish')
            ->whereNotNull('map_lat')
            ->whereNotNull('map_lng')
            ->get();
        
        $nearbyCarIds = [];
        
        foreach ($nearbyCarsByCoordinates as $car) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $car->map_lat,
                $car->map_lng
            );
            
            // If distance is within radius, add to result
            if ($distance !== null && $distance <= $radiusKm) {
                $nearbyCarIds[] = $car->id;
            }
        }
        
        return $nearbyCarIds;
    }

    /**
     * Check if a car is available for a specific period
     * 
     * @param int $carId
     * @param string $startDateTime
     * @param string $endDateTime
     * @return bool
     */
    protected function isCarAvailableForPeriod($carId, $startDateTime, $endDateTime)
    {
        $availability = \Modules\Car\Models\CarDate::where('target_id', $carId)
            ->where('active', 1)
            ->where('start_date', '<=', $startDateTime)
            ->where('end_date', '>=', $endDateTime)
            ->first();
        
        if (!$availability) {
            return false;
        }
        
        // Check if there are any remaining cars available
        $remainingCars = \Modules\Car\Models\CarDate::getRemainingAvailability(
            $carId,
            $startDateTime,
            $endDateTime
        );
        
        return $remainingCars > 0;
    }

    /**
     * Lấy tất cả các khung giờ khả dụng trong một ngày cụ thể
     * 
     * @param int $carId
     * @param string $date
     * @return array
     */
    protected function getAvailableTimeSlotsForDay($carId, $date)
    {
        $dayStart = date('Y-m-d 00:00:00', strtotime($date));
        $dayEnd = date('Y-m-d 23:59:59', strtotime($date));
        
        $availabilities = \Modules\Car\Models\CarDate::where('target_id', $carId)
            ->where('active', 1)
            ->where('number', '>', 0)
            ->where(function($query) use ($dayStart, $dayEnd) {
                $query->where(function($q) use ($dayStart, $dayEnd) {
                    $q->where('start_date', '>=', $dayStart)
                       ->where('start_date', '<=', $dayEnd);
                })->orWhere(function($q) use ($dayStart, $dayEnd) {
                    $q->where('end_date', '>=', $dayStart)
                       ->where('end_date', '<=', $dayEnd);
                })->orWhere(function($q) use ($dayStart, $dayEnd) {
                    $q->where('start_date', '<=', $dayStart)
                       ->where('end_date', '>=', $dayEnd);
                });
            })
            ->get();
        
        $timeSlots = [];
        
        foreach ($availabilities as $availability) {
            // Đảm bảo các mốc thời gian nằm trong ngày được chọn
            $slotStart = max(strtotime($availability->start_date), strtotime($dayStart));
            $slotEnd = min(strtotime($availability->end_date), strtotime($dayEnd));
            
            if ($slotStart >= $slotEnd) {
                continue;
            }
            
            // Kiểm tra xem có còn xe không
            $remainingCars = \Modules\Car\Models\CarDate::getRemainingAvailability(
                $carId,
                date('Y-m-d H:i:s', $slotStart),
                date('Y-m-d H:i:s', $slotEnd)
            );
            
            // Nếu còn xe, thêm vào danh sách khung giờ khả dụng
            if ($remainingCars > 0) {
                $timeSlots[] = [
                    'start_time' => date('H:i', $slotStart),
                    'end_time' => date('H:i', $slotEnd),
                    'formatted' => date('H:i', $slotStart) . ' - ' . date('H:i', $slotEnd),
                    'price' => (float)$availability->price,
                    'remaining_cars' => $remainingCars
                ];
            }
        }
        
        usort($timeSlots, function($a, $b) {
            return strtotime($a['start_time']) - strtotime($b['start_time']);
        });
        
        return $timeSlots;
    }

    /**
     * Kiểm tra xe có sẵn hay không dựa trên các tham số
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|integer|exists:bravo_cars,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'pickup_time' => 'required|date_format:H:i',
            'dropoff_time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        $serviceId = $request->input('service_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $pickupTime = $request->input('pickup_time');
        $dropoffTime = $request->input('dropoff_time');

        $startDateTime = date('Y-m-d H:i:s', strtotime("$startDate $pickupTime"));
        $endDateTime = date('Y-m-d H:i:s', strtotime("$endDate $dropoffTime"));

        $availability = \Modules\Car\Models\CarDate::where('target_id', $serviceId)
            ->where('active', 1)
            ->where('start_date', '<=', $startDateTime)
            ->where('end_date', '>=', $endDateTime)
            ->first();

        if (!$availability) {
            $availableSlots = $this->getAvailableTimeSlotsForDay($serviceId, $startDate);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Xe không có sẵn trong khung giờ yêu cầu',
                'available' => false,
                'available_slots' => $availableSlots
            ]);
        }

        $remainingCars = \Modules\Car\Models\CarDate::getRemainingAvailability(
            $serviceId,
            $startDateTime,
            $endDateTime
        );

        if ($remainingCars <= 0) {
            // Nếu hết xe, kiểm tra các khung giờ khác có sẵn
            $availableSlots = $this->getAvailableTimeSlotsForDay($serviceId, $startDate);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Xe đã được đặt hết trong khung giờ này',
                'available' => false,
                'available_slots' => $availableSlots
            ]);
        }

        $price = $availability->price;

        return response()->json([
            'status' => 'success',
            'message' => 'Xe có sẵn trong khung giờ yêu cầu',
            'available' => true,
            'price' => $price,
            'remaining_cars' => $remainingCars,
            'available_slots' => [
                [
                    'start_time' => $pickupTime,
                    'end_time' => $dropoffTime,
                    'formatted' => "$pickupTime - $dropoffTime",
                    'price' => (float)$price,
                    'remaining_cars' => $remainingCars
                ]
            ]
        ]);
    }
}
