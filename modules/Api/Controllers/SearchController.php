<?php
namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Service;
use Modules\Flight\Controllers\FlightController;
use Modules\Review\Models\Review;

class SearchController extends Controller
{

    public function search($type = ''){
        $type = $type ? $type : request()->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }

        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }

        if(!empty(request()->query('limit'))){
            $limit = request()->query('limit');
        }else{
            $limit = !empty(setting_item($type."_page_limit_item"))? setting_item($type."_page_limit_item") : 9;
        }

        $query = new $class();
        $rows = $query->search(request()->input())->paginate($limit);

        $total = $rows->total();
        return $this->sendSuccess(
            [
                'total'=>$total,
                'total_pages'=>$rows->lastPage(),
                'data'=>$rows->map(function($row){
                    return $row->dataForApi();
                }),
            ]
        );
    }


    public function searchServices(){
        if(!empty(request()->query('limit'))){
            $limit = request()->query('limit');
        }else{
            $limit = 9;
        }
        $query = new Service();
        $rows = $query->search(request()->input())->paginate($limit);
        $total = $rows->total();
        return $this->sendSuccess(
            [
                'total'=>$total,
                'total_pages'=>$rows->lastPage(),
                'data'=>$rows->map(function($row){
                    return $row->dataForApi();
                }),
            ]
        );
    }

    public function getFilters($type = ''){
        $type = $type ? $type : request()->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $data = call_user_func([$class,'getFiltersSearch'],request());
        return $this->sendSuccess(
            [
                'data'=>$data
            ]
        );
    }

    public function getFormSearch($type = ''){
        $type = $type ? $type : request()->get('type');
        if(empty($type))
        {
            return $this->sendError(__("Type is required"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $data = call_user_func([$class,'getFormSearch'],request());
        return $this->sendSuccess(
            [
                'data'=>$data
            ]
        );
    }

    public function detail($type = '',$id = '')
    {
        if(empty($type)){
            return $this->sendError(__("Resource is not available"));
        }
        if(empty($id)){
            return $this->sendError(__("Resource ID is not available"));
        }

        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }

        // Load car with terms
        $row = $class::with(['location', 'terms.term', 'brand'])->find($id);
        if(empty($row)) {
            return $this->sendError(__("Resource not found"));
        }

        if($type=='flight'){
            return app()->make(FlightController::class)->getData(\request(),$id);
        }

        if($type == 'car') {
            // Get reviews
            $reviews = Review::where('object_id', $id)
                           ->where('object_model', 'car')
                           ->where('status', 'approved')
                           ->with(['author' => function($query) {
                               $query->select('id', 'name', 'first_name', 'last_name', 'avatar_id');
                           }])
                           ->orderBy('id', 'desc')
                           ->get();

            // Get available coupons
            $coupons = \Modules\Coupon\Models\Coupon::where(function($query) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', date('Y-m-d'));
            })
            ->where(function($query) {
                $query->whereNull('services')
                      ->orWhere('services', 'like', '%car%');
            })
            ->where('status', 'publish')
            ->get();

            // Get gallery images from media_files
            $gallery = [];
            $galleryPath = public_path('uploads/demo/car');
            if (is_dir($galleryPath)) {
                $files = glob($galleryPath . '/gallery-*.jpg');
                foreach ($files as $file) {
                    $gallery[] = url('uploads/demo/car/' . basename($file));
                }
            }

            // Process terms with proper relationships
            $terms = [];
            if ($row->terms) {
                $terms = $row->terms->map(function($carTerm) {
                    if (!$carTerm->term) return null;
                    
                    // Get attribute info directly from database
                    $attr = \Modules\Core\Models\Attributes::find($carTerm->term->attr_id);
                    
                    return [
                        'id' => $carTerm->term->id,
                        'title' => $carTerm->term->name,
                        'content' => $carTerm->term->content,
                        'image_id' => get_file_url($carTerm->term->image_id),
                        'icon' => $carTerm->term->icon,
                        'attr_id' => $carTerm->term->attr_id,
                        'slug' => $carTerm->term->slug
                    ];
                })->filter()->groupBy('attr_id')->map(function($terms, $attr_id) {
                    // Get attribute info
                    $attr = \Modules\Core\Models\Attributes::find($attr_id);
                    
                    return [
                        'parent' => $attr ? [
                            'id' => $attr->id,
                            'title' => $attr->name,
                            'slug' => $attr->slug,
                            'service' => $attr->service,
                            'display_type' => $attr->display_type,
                            'hide_in_single' => $attr->hide_in_single
                        ] : null,
                        'child' => $terms->toArray()
                    ];
                });
            }

            $additional_fees = [];
            if (!empty($row->additional_fees)) {
                if (!is_array($row->additional_fees)) {
                    $additional_fees = json_decode($row->additional_fees, true);
                } else {
                    $additional_fees = $row->additional_fees;
                }

                // Convert associative array to sequential array if needed
                if (isset($additional_fees['__number__'])) {
                    unset($additional_fees['__number__']);
                }
                
                // If it's an associative array with numeric keys, convert to sequential
                if (array_keys($additional_fees) !== range(0, count($additional_fees) - 1)) {
                    $temp = [];
                    foreach ($additional_fees as $fee) {
                        if (is_array($fee) && (!empty($fee['name']) || !empty($fee['amount']))) {
                            $temp[] = $fee;
                        }
                    }
                    $additional_fees = $temp;
                }

                // Add ID to each fee
                foreach ($additional_fees as $key => &$fee) {
                    $numericKey = (int)$key;
                    
                    $icon_url = null;
                    if (!empty($fee['icon_id'])) {
                        $icon_url = get_file_url($fee['icon_id'], 'full');
                    }
                    
                    $orderedFee = [
                        'id' => $numericKey + 1, 
                        'icon_url' => $icon_url,
                        'icon_id' => $fee['icon_id'] ?? null,
                        'name' => $fee['name'] ?? '',
                        'amount' => $fee['amount'] ?? '',
                        'unit' => $fee['unit'] ?? null,
                        'description' => $fee['description'] ?? null
                    ];
                    
                    // Replace the original fee with the ordered one
                    $fee = $orderedFee;
                }
            }

            // Get current rental information if user is logged in and has an active booking for this car
            $rentalInfo = null;
            if (auth('sanctum')->check()) {
                $user = auth('sanctum')->user();
                $booking = \Modules\Booking\Models\Booking::where('customer_id', $user->id)
                    ->where('object_id', $id)
                    ->where('object_model', 'car')
                    ->whereIn('status', ['confirmed', 'processing', 'paid', 'completed'])
                    ->latest()
                    ->first();

                if ($booking) {
                    $pickupLocation = $booking->getMeta('pickup_location');
                    $deliveryLocation = $booking->getMeta('delivery_location');
                    $pickupTime = $booking->getMeta('pickup_time');
                    $dropoffTime = $booking->getMeta('dropoff_time');
                    $returnSameLocation = $booking->getMeta('return_same_location');
                    
                    $startDatetime = $booking->start_date;
                    $endDatetime = $booking->end_date;
                    
                    if ($pickupTime) {
                        $startDate = date('Y-m-d', strtotime($startDatetime));
                        $startDatetime = $startDate . ' ' . $pickupTime . ':00';
                    }
                    
                    if ($dropoffTime) {
                        $endDate = date('Y-m-d', strtotime($endDatetime));
                        $endDatetime = $endDate . ' ' . $dropoffTime . ':00';
                    }
                    
                    $rentalInfo = [
                        'rental_time' => [
                            'start_date' => $startDatetime,
                            'end_date' => $endDatetime,
                        ],
                        'pickup_location' => $pickupLocation ?? null,
                        'delivery_location' => $deliveryLocation ?? null,
                        'return_same_location' => $returnSameLocation ?? '0',
                        'booking_id' => $booking->id,
                        'booking_status' => $booking->status
                    ];
                    
                } 
            }
            // Get wishlist status as boolean
            $wishlist_status = false;
            if(auth('sanctum')->check()) {
                $user_id = auth('sanctum')->id();
                $wishlist = \Modules\User\Models\UserWishList::where('object_id', $row->id)
                    ->where('object_model', 'car')
                    ->where('user_id', $user_id)
                    ->first();
                
                $wishlist_status = !empty($wishlist);
            }

            // Book_complete count
            $bookCompleteCount = \Modules\Booking\Models\Booking::where([
                'object_id' => $id,
                'object_model' => 'car',
                'status' => 'completed'
            ])->count();

            $data = [
                'id' => $row->id,
                'object_model' => 'car',
                'title' => $row->title,
                'brand' => [
                    'id' => $row->brand_id,
                    'name' => $row->brand
                ],
                'model' => $row->model,
                'fuel_capacity' => $row->fuel_capacity,
                'fuel_type' => $row->fuel_type,
                'transmission_type' => $row->transmission_type,
                'passenger' => $row->passenger,
                'banner_image' => url('uploads/demo/car/banner-single.jpg'),
                'gallery' => $gallery,
                'video' => $row->video,
                'location' => [
                    'id' => $row->location_id,
                    'name' => $row->location->name ?? ''
                ],
                'address' => $row->address,
                'map_lat' => $row->map_lat,
                'map_lng' => $row->map_lng,
                'map_zoom' => $row->map_zoom,
                'price' => [
                    '24h' => [
                        'price' => $row->price,
                        'sale_price' => $row->sale_price
                    ],
                    '10h' => [
                        'price' => $row->price_per_10_hour,
                        'sale_price' => $row->sale_price_per_10_hour
                    ]
                ],
                'discount_percent' => $row->discount_percent,
                'review_lists' => [
                    'total' => $reviews->count(),
                    'data' => $reviews
                ],
                'available_coupons' => $coupons,
                'terms' => $terms,
                'content' => $row->content,
                'insurance_info' => is_string($row->insurance_info) ? json_decode($row->insurance_info) : $row->insurance_info,
                'additional_fees' => $additional_fees,
                'rental_info' => $rentalInfo,
                'wishlist_status' => $wishlist_status,
                'car_auth' => (bool)$row->car_auth,
                'book_complete' => $bookCompleteCount
            ];

            return $this->sendSuccess([
                'data' => $data
            ]);
        }

        return $this->sendSuccess([
            'data'=>$row->dataForApi(true)
        ]);
    }

    public function checkAvailability(Request $request , $type = '',$id = ''){
        if(empty($type)){
            return $this->sendError(__("Resource is not available"));
        }
        if(empty($id)){
            return $this->sendError(__("Resource ID is not available"));
        }
        $class = get_bookable_service_by_id($type);
        if(empty($class) or !class_exists($class)){
            return $this->sendError(__("Type does not exists"));
        }
        $classAvailability = $class::getClassAvailability();
        $classAvailability = app()->make($classAvailability);
        $request->merge(['id' => $id]);
        if($type == "hotel"){
            $request->merge(['hotel_id' => $id]);
            return $classAvailability->checkAvailability($request);
        }
        return $classAvailability->loadDates($request);
    }

    public function checkBoatAvailability(Request $request ,$id = ''){
        if(empty($id)){
            return $this->sendError(__("Boat ID is not available"));
        }
        $class = get_bookable_service_by_id('boat');
        $classAvailability = $class::getClassAvailability();
        $classAvailability = app()->make($classAvailability);
        $request->merge(['id' => $id]);
        return $classAvailability->availabilityBooking($request);
    }
}
