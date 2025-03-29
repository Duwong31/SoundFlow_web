<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CarBrandController extends Controller 
{
    public function listBrands()
    {
        $brands = DB::table('car_brands')
            ->where('status', 1)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function($brand) {
                // Get cars count
                $carsCount = DB::table('bravo_cars')
                    ->where('brand_id', $brand->id)
                    ->where('status', 'publish')
                    ->count();
                    
                // Get cars
                $cars = DB::table('bravo_cars')
                    ->where('brand_id', $brand->id)
                    ->where('status', 'publish')
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
                            'passenger' => $car->passenger,
                            'status' => $car->status
                        ];
                    });

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'logo' => get_file_url($brand->logo_id, 'full'),
                    'content' => $brand->content,
                    'count' => $carsCount,
                    'cars' => $cars
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'brands' => $brands
            ]
        ]);
    }

    public function getCarsByBrand($brand_id)
    {
        $brand = DB::table('car_brands')
            ->where('id', $brand_id)
            ->where('status', 1)
            ->first();

        if (!$brand) {
            return response()->json([
                'status' => 'error',
                'message' => __('error.brand_not_found')
            ], 404);
        }

        $cars = DB::table('bravo_cars')
            ->where('brand_id', $brand_id)
            ->where('status', 'publish')
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
                    'passenger' => $car->passenger,
                    'status' => $car->status
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'logo' => get_file_url($brand->logo_id, 'full'),
                    'content' => $brand->content
                ],
                'cars' => $cars
            ]
        ]);
    }
} 