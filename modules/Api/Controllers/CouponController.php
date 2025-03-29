<?php
namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Models\CouponBookings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;

class CouponController extends Controller
{
    public function getUserCoupons(Request $request)
    {
        $user = Auth::user();
        $query = Coupon::query();
        
        // Lọc coupon còn hạn sử dụng và đang active
        $query->where('status', 'publish')
              ->where(function($q) {
                  $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', date('Y-m-d'));
              });
        
        // Lọc coupon dành cho user
        $query->where(function($q) use ($user) {
            $q->whereNull('only_for_user')
              ->orWhereRaw('FIND_IN_SET(?, only_for_user)', [$user->id]);
        });

        $coupons = $query->get();
        
        // Lấy danh sách booking_id đã sử dụng coupon của user
        $usedCouponBookings = CouponBookings::where('create_user', $user->id)
            ->select('coupon_code', DB::raw('COUNT(*) as use_count'))
            ->groupBy('coupon_code')
            ->pluck('use_count', 'coupon_code')
            ->toArray();

        $availableCoupons = [];
        $usedCoupons = [];

        foreach ($coupons as $coupon) {
            $useCount = $usedCouponBookings[$coupon->code] ?? 0;
            $couponData = [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'amount' => $coupon->amount,
                'discount_type' => $coupon->discount_type,
                'end_date' => $coupon->end_date,
                'min_total' => $coupon->min_total,
                'max_total' => $coupon->max_total,
                'times_used' => $useCount,
                'limit_per_user' => $coupon->limit_per_user,
                'only_for_user' => $coupon->only_for_user,
                'quantity_limit' => $coupon->quantity_limit,
                'create_user' => $coupon->create_user,
            ];

            // Kiểm tra giới hạn sử dụng
            if ($coupon->limit_per_user && $useCount >= $coupon->limit_per_user) {
                $usedCoupons[] = $couponData;
            } else {
                $availableCoupons[] = $couponData;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'available_coupons' => $availableCoupons,
                'used_coupons' => $usedCoupons
            ]
        ]);
    }

    public function verifyCode(Request $request) 
    {
        $request->validate([
            'code' => 'required'
        ]);

        $coupon = Coupon::where('code', $request->code)
                        ->where('status', 'publish')
                        ->first();

        if (!$coupon) {
            return response()->json([
                'status' => 'error',
                'message' => __('Coupon not found')
            ], 404);
        }

        // Kiểm tra hạn sử dụng
        if ($coupon->end_date && strtotime($coupon->end_date) < time()) {
            return response()->json([
                'status' => 'error', 
                'message' => __('Coupon has expired')
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'amount' => $coupon->amount,
                    'discount_type' => $coupon->discount_type,
                    'end_date' => $coupon->end_date,
                    'min_total' => $coupon->min_total,
                    'max_total' => $coupon->max_total
                ]
            ]
        ]);
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'booking_id' => 'required|integer',
            'total' => 'required|numeric|min:0',
            'object_id' => 'required|integer',
            'object_model' => 'required|string'
        ]);

        // Kiểm tra coupon tồn tại
        $coupon = Coupon::where('code', $request->code)
                        ->where('status', 'publish')
                        ->first();
                        
        if (!$coupon) {
            return $this->sendError(__('Coupon not found'));
        }

        // Kiểm tra booking tồn tại
        $booking = Booking::where([
            'id' => $request->booking_id,
            'object_id' => $request->object_id,
            'object_model' => $request->object_model,
            'status' => 'draft'
        ])->first();

        if (!$booking) {
            return $this->sendError(__('Booking not found'));
        }

        try {
            \Log::info('Applying coupon:', $request->all());
            
            $result = $coupon->applyCoupon($booking, 'add', [
                'total' => (float)$request->total,
                'object_id' => $request->object_id,
                'object_model' => $request->object_model
            ]);

            if ($result['status']) {
                return $this->sendSuccess([
                    'booking_id' => $booking->id,
                    'coupon_code' => $coupon->code,
                    'coupon_amount' => $result['discount_amount'],
                    'total_after_discount' => (float)$request->total - $result['discount_amount']
                ]);
            }

            return $this->sendError($result['message']);
        } catch (\Exception $e) {
            \Log::error('Coupon apply error: ' . $e->getMessage());
            return $this->sendError($e->getMessage());
        }
    }

    public function removeCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'booking_id' => 'required|integer'
        ]);

        $coupon = Coupon::where('code', $request->code)
                        ->where('status', 'publish')
                        ->first();

        if (!$coupon) {
            return $this->sendError(__('error.coupon_not_found'));
        }

        // Kiểm tra booking tồn tại
        $booking = Booking::find($request->booking_id);
        if (!$booking) {
            return $this->sendError(__('error.booking_not_found'));
        }

        try {
            $result = $coupon->applyCoupon($booking, 'remove');
            if ($result['status']) {
                return $this->sendSuccess([
                    'message' => __('error.coupon_removed')
                ]);
            }
            return $this->sendError($result['message']);
        } catch (\Exception $e) {
            \Log::error('Remove coupon error: ' . $e->getMessage());
            return $this->sendError($e->getMessage());
        }
    }
} 