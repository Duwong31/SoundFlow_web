<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Location\Models\Location;

class CarLocationController extends Controller
{
    /**
     * Lấy danh sách các địa điểm phổ biến có xe
     * 
     * @return \Illuminate\Http\JsonResponse Trả về danh sách địa điểm kèm số lượng xe
     */
    public function getPopularLocations()
    {
        // Lấy thông tin địa điểm từ bảng bravo_locations
        // Join với bảng bravo_cars để lấy các địa điểm có xe
        $locations = DB::table('bravo_locations as l')
            ->select('l.id', 'l.name', 'l.image_id', 'l.map_lat', 'l.map_lng')
            ->join('bravo_cars as c', 'l.id', '=', 'c.location_id')
            ->where('c.status', 'publish')
            ->groupBy('l.id', 'l.name', 'l.image_id', 'l.map_lat', 'l.map_lng')
            ->orderBy('l.id', 'asc')
            ->get()
            ->map(function($location) {
                // Đếm số lượng xe tại mỗi địa điểm
                $carsCount = DB::table('bravo_cars')
                    ->where('location_id', $location->id)
                    ->where('status', 'publish')
                    ->count();
                    
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'image' => get_file_url($location->image_id, 'full'),
                    'map_lat' => $location->map_lat,
                    'map_lng' => $location->map_lng,
                    'cars_count' => $carsCount . '+xe'
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'locations' => $locations
            ]
        ]);
    }

    /**
     * Lấy danh sách xe theo địa điểm
     * 
     * @param int $location_id ID của địa điểm
     * @return \Illuminate\Http\JsonResponse Trả về thông tin địa điểm và danh sách xe
     */
    public function getCarsByLocation($location_id)
    {
        // Kiểm tra địa điểm có tồn tại
        $location = DB::table('bravo_locations')
            ->where('id', $location_id)
            ->first();

        if (!$location) {
            return response()->json([
                'status' => 'error',
                'message' => __('error.location_not_found')
            ], 404);
        }

        // Lấy danh sách xe tại địa điểm
        // Join với bảng car_brands để lấy thông tin hãng xe
        $cars = DB::table('bravo_cars as c')
            ->join('car_brands as b', 'c.brand_id', '=', 'b.id')
            ->where('c.location_id', $location_id)
            ->where('c.status', 'publish') 
            ->select('c.*', 'b.name as brand_name', 'b.slug as brand_slug')
            ->orderBy('c.id', 'asc')
            ->get()
            ->map(function($car) {
                return [
                    'id' => $car->id,
                    'title' => $car->title,
                    'model' => $car->model,
                    'image' => get_file_url($car->image_id, 'full'),
                    'price' => $car->price,
                    'sale_price' => $car->sale_price,
                    'transmission_type' => $car->transmission_type,
                    'fuel_type' => $car->fuel_type,
                    'fuel_capacity' => $car->fuel_capacity,
                    'rental_duration' => $car->rental_duration,
                    'driver_type' => $car->driver_type,
                    'passenger' => $car->passenger,

                    'brand' => [
                        'name' => $car->brand_name,
                        'slug' => $car->brand_slug
                    ],
                    'address' => $car->address,
                    'is_featured' => $car->is_featured,
                    'is_recommended' => $car->is_recommended
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'location' => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'image' => get_file_url($location->image_id, 'full'),
                    'map_lat' => $location->map_lat,
                    'map_lng' => $location->map_lng
                ],
                'cars' => $cars
            ]
        ]);
    }
} 