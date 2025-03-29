<?php
namespace Modules\Coupon\Models;
use App\BaseModel;
use App\User;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Service;

class Coupon extends BaseModel
{
    protected $table = 'bravo_coupons';
    protected $casts = [
        'services'      => 'array',
        'only_for_user'      => 'array',
    ];

    protected $bookingClass;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bookingClass = Booking::class;
    }

    public function applyCoupon($booking, $action = 'add', $params = [])
    {
        // Validate Coupon
        $res = $this->applyCouponValidate($booking, $action, $params);
        if ($res['status'] === false) {
            return $res;
        }

        switch ($action) {
            case "add":
                $this->add($booking);
                break;
            case "remove":
                $this->remove($booking);
                break;
        }

        $booking->reloadCalculateTotalBooking();
        return [
            'status' => true,
            'message' => __("Coupon code is applied successfully!"),
            'discount_amount' => $res['discount_amount'] ?? 0
        ];
    }

    public function applyCouponValidate($booking, $action = 'add', $params = [])
    {
        if (empty($booking)) {
            return [
                'status' => false,
                'message' => __('error.booking_not_found')
            ];
        }

        $total = 0;

        if ($action === 'add') {
            // Kiểm tra số lần sử dụng
            $count = CouponBookings::where('coupon_code', $this->code)
                ->where('create_user', auth()->id())
                ->count();

            if ($this->limit_per_user && $count >= $this->limit_per_user) {
                return [
                    'status' => false,
                    'message' => __('error.limit_reached')
                ];
            }

            // Kiểm tra tổng số lượng
            $totalUsed = CouponBookings::where('coupon_code', $this->code)->count();
            if ($this->quantity_limit && $totalUsed >= $this->quantity_limit) {
                return [
                    'status' => false,
                    'message' => __('error.usage_limit_reached')
                ];
            }

            // Kiểm tra giá trị đơn hàng
            $total = isset($params['total']) ? (float)$params['total'] : 0;
            $minTotal = (float)($this->min_total ?? 0);
            $maxTotal = (float)($this->max_total ?? 0);

            if ($minTotal > 0 && $total < $minTotal) {
                return [
                    'status' => false,
                    'message' => __('error.minimum_amount', ['amount' => format_money($minTotal)])
                ];
            }

            if ($maxTotal > 0 && $total > $maxTotal) {
                return [
                    'status' => false,
                    'message' => __('error.maximum_amount', ['amount' => format_money($maxTotal)])
                ];
            }
        }

        // Kiểm tra xem coupon có được áp dụng cho booking này không
        $couponBooking = CouponBookings::where('coupon_code', $this->code)
            ->where('booking_id', $booking->id)
            ->first();

        if ($action === 'remove' && !$couponBooking) {
            return [
                'status' => false,
                'message' => __('error.coupon_not_applied')
            ];
        }

        return [
            'status' => true,
            'discount_amount' => $action === 'add' ? $this->getDiscountAmount($total) : 0
        ];
    }

    protected function getDiscountAmount($total)
    {
        $total = (float)$total;
        if ($this->discount_type === 'fixed') {
            return (float)$this->amount;
        }
        
        // Percent discount
        $amount = ($total * (float)$this->amount) / 100;
        return $amount;
    }

    public function remove($booking)
    {
        $couponBooking = CouponBookings::where('coupon_code', $this->code)
            ->where('booking_id', $booking->id)
            ->first();
        
        if (!empty($couponBooking)) {
            $couponBooking->delete();
        }
    }

    public function add($booking)
    {
        //for Type Fixed
        $coupon_amount = $this->amount;
        //for Type Percent
        if($this->discount_type == 'percent'){
            $coupon_amount =  $booking->total_before_discount / 100 * $this->amount;
        }
        $couponBooking = new CouponBookings();
        $couponBooking->fill([
            'booking_id' => $booking->id,
            'booking_status' => $booking->status,
            'object_id' => $booking->object_id,
            'object_model' => $booking->object_model,
            'coupon_code' => $this->code,
            'coupon_amount' => $coupon_amount,
            'coupon_data' => $this->toArray(),
        ]);
        $couponBooking->save();
    }

    public function couponServices(){
        return $this->hasMany( CouponServices::class, 'coupon_id');
    }
    /**
     * Using for select2
     * @return array
     */
    public function getServicesToArray(){
        $data = [];
        if(!empty($this->services)){
            $services = Service::selectRaw('id,object_id,object_model,title')->whereIn('id',$this->services)->where('object_model', 'car')->get();
            foreach ($services as $item){
                $data[] = [
                    'id'   => $item->id,
                    'text' => strtoupper($item->object_model) . " (#{$item->object_id}): {$item->title}"
                ];
            }
        }
        return $data;
    }
    /**
     * Using for select2
     * @return array
     */
    public function getUsersToArray(){
        $data = [];
        if(!empty($this->only_for_user)){
            $users = User::where('status','publish')->whereIn('id',$this->only_for_user)->get();
            foreach ($users as $item){
                $data[] = [
                    'id'   => $item->id,
                    'text' => "(#{$item->id}): {$item->getDisplayName()}"
                ];
            }
        }
        return $data;
    }
}
