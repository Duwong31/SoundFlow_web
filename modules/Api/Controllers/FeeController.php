<?php

namespace Modules\Api\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class FeeController extends Controller
{
    public function calculateFee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_id'               => 'required|string',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date',
            'pickup_time'     => 'required|string',
            'dropoff_time'    => 'required|string',
            'is_delivery'          => 'required|boolean',
            // 'insurance_options'      => 'required|in:basic,premium', 
            // 'passenger_insurance' => 'required|boolean',
            'price_per_24h_or_10h' => 'required|boolean',
            'coupon_code'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error'    => 'Validation error',
                'messages' => $validator->errors()
            ], 422);
        }

        $car_id               = $request->input('car_id');
        $couponCode           = $request->input('coupon_code');
        $is_delivery          = $request->input('is_delivery');
        $price_per_24h_or_10h = $request->input('price_per_24h_or_10h', false);
        // $insurance = $request->input('insurance');
        // $passenger_insurance = $request->input('passenger_insurance');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $pickup_time = $request->input('pickup_time');
        $dropoff_time = $request->input('dropoff_time');

        $formatted_start_date = Carbon::parse($start_date . ' ' . $pickup_time)->format('Y-m-d H:i:s');
        $formatted_end_date   = Carbon::parse($end_date . ' ' . $dropoff_time)->format('Y-m-d H:i:s');

        $car = DB::table('bravo_cars')->where('id', $car_id)->first();
        if (!$car) {
            return response()->json(['error' => 'Car not found'], 404);
        }

        $base_price = $price_per_24h_or_10h
            ? floatval($car->price ?? 0)
            : floatval($car->price_per_10_hour ?? 0);

        $otodFee = DB::table('core_settings')->where('name', 'car_booking_buyer_fees')->first();
        $otod_fee = 0;
        $otod_fee_details = [];

        if ($otodFee) {
            $otodFees = json_decode($otodFee->val, true);
            if (is_array($otodFees)) {
                foreach ($otodFees as $fee) {
                    if (isset($fee['price']) && is_numeric($fee['price'])) {
                        $price = floatval($fee['price']);
                        $unit  = $fee['unit'] ?? 'fixed';

                        if ($unit === 'fixed') {
                            $otod_fee += $price;
                        } elseif ($unit === 'percent') {
                            if ($base_price > 0) {
                                $price_to_add = ($base_price * $price) / 100;
                                $otod_fee += $price_to_add;
                                $price = $price_to_add;
                            } else {
                                continue;
                            }
                        }
                        $otod_fee_details[] = [
                            'name'  => $fee['name'] ?? 'Unnamed Fee',
                            'price' => $price
                        ];
                    }
                }
            }
        }

        $deliveryFee = DB::table('core_settings')->where('name', 'car_booking_delivery_fees')->first();
        $delivery_fee = 0;
        if ($deliveryFee) {
            $deliveryFees = json_decode($deliveryFee->val, true);
            if (is_array($deliveryFees)) {
                foreach ($deliveryFees as $fee) {
                    if (isset($fee['name']) && mb_strtolower($fee['name']) === mb_strtolower("Phí giao xe")) {
                        $delivery_fee = $is_delivery ? floatval($fee['price']) : 0;
                        break;
                    }
                }
            }
        }

        // $insurance_fee = 0;
        // $insuranceInfo = json_decode($car->insurance_info, true);
        // if ($insurance == 'premium' && isset($insuranceInfo['options']['premium'])) {
        //     $premium_option = $insuranceInfo['options']['premium'];
        //     $price_per_day = floatval($premium_option['price'] ?? 0);

        //     // Calculate the number of days
        //     $start = Carbon::parse($start_date . ' ' . $pickup_time);
        //     $end = Carbon::parse($end_date . ' ' . $dropoff_time);
        //     $duration_in_days = ceil($end->diffInHours($start) / 24);

        //     $insurance_fee = $price_per_day * $duration_in_days;
        // }

        // $passenger_insurance_fee = 0;
        // if ($passenger_insurance && isset($insuranceInfo['passenger_insurance'])) {
        //     $passenger_option = $insuranceInfo['passenger_insurance'];
        //     $price_per_day = floatval($passenger_option['price'] ?? 0);

        //     // Calculate the number of days
        //     $start = Carbon::parse($start_date . ' ' . $pickup_time);
        //     $end = Carbon::parse($end_date . ' ' . $dropoff_time);
        //     $duration_in_days = ceil($end->diffInHours($start) / 24);

        //     $passenger_insurance_fee = $price_per_day * $duration_in_days;
        // }

        // $total_before_coupon = $base_price + $otod_fee + $delivery_fee + $insurance_fee + $passenger_insurance_fee;
        $total_before_coupon = $base_price + $otod_fee + $delivery_fee;

        $coupon_amount = 0;
        if ($couponCode) {
            $coupon = DB::table('bravo_coupons')->where('code', $couponCode)->first();
            if ($coupon) {
                if ($coupon->discount_type === 'percent') {
                    $coupon_amount = $total_before_coupon * floatval($coupon->amount) / 100;
                } else {
                    $coupon_amount = floatval($coupon->amount);
                }
            }
        }

        $total = $total_before_coupon - $coupon_amount;
        if ($total < 0) {
            $total = 0;
        }

        return response()->json([
            'start_date'           => $formatted_start_date,
            'end_date'             => $formatted_end_date,
            'otod_fee' => [
                'base_price'        => $base_price,
                'otod_fee'          => $otod_fee,
                'otod_fee_details'  => $otod_fee_details,
            ],
            'delivery_fee'         => $delivery_fee,
            // 'insurance' => $insurance_fee,
            // 'passenger_insurance' => $passenger_insurance_fee,
            'total_before_coupon'  => $total_before_coupon,
            'coupon_amount'        => $coupon_amount,
            'total'                => $total
        ], 200);
    }
}