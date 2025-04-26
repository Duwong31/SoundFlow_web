<?php
namespace Modules\Api\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Modules\Booking\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Location\Models\Location;
use Modules\User\Models\UserWishList;
use Modules\Booking\Events\BookingUpdatedEvent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Translation\Translator;
use Modules\Booking\Events\BookingCancelledEvent;
use Illuminate\Support\Facades\Cache;


class UserController extends Controller
{
    protected $translator;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(Translator $translator)
    {
        $this->middleware('auth:sanctum');
        $this->translator = $translator;
    }

    public function getBookingHistory(Request $request){
        $user_id = Auth::id();
        $query = Booking::getBookingHistory(false, $user_id); 
        $sortedQuery = $query->sortBy('id');
        
        $completeRows = [];
        $upcomingRows = [];
        
        foreach ($sortedQuery as $item){
            $service = $item->service;
            if (!$service) {
                continue;
            }
            $serviceTranslation = $service->translate();
            $meta_tmp = $item->getAllMeta();
            $item = $item->toArray();
            $meta = [];
            if(!empty($meta_tmp)){
                foreach ($meta_tmp as $val){
                    $meta[$val->name] = !empty($json = json_decode($val->val,true)) ? $json : $val->val;
                }
            }
            $item['commission_type'] = json_decode( $item['commission_type'] , true);
            // $item['buyer_fees'] = json_decode( $item['buyer_fees'] , true);
            unset($item['buyer_fees']);
            $item['booking_meta'] = $meta;
            $item['service_icon'] = $service->getServiceIconFeatured() ?? null;
            
            $serviceLocation = $service->location ?? null;
            $location = null;
            
            if($serviceLocation) {
                $location = [
                    'id' => $serviceLocation->id,
                    'name' => $serviceLocation->name
                ];
            }
            
            $item['service'] = [
                'id' => $service->id,
                'title' => $serviceTranslation->title,
                'price' => $service->price ?? 0,
                'sale_price' => $service->sale_price ?? 0,
                'discount_percent' => $service->discount_percent ?? null,
                'image' => $service->image_url ?? null,
                'distance' => $service->distance ?? null,
                'fuel_capacity' => $service->fuel_capacity ?? null,
                'driver_type' => $service->driver_type ?? null,
                'passenger' => $service->passenger ?? null,
                'rental_duration' => $service->rental_duration ?? null,
                'formatted_date' => $service->formatted_date ?? null,
                'location' => $location,
                'wishlist_status' => $this->isServiceInWishlist($service->id),
            ];
            
            if($item['status'] === 'completed' || $item['status'] === 'cancelled') {
                $completeRows[] = $item;
            } elseif($item['status'] === 'confirmed' || $item['status'] === 'processing') {
                $upcomingRows[] = $item;
            }
        }
        
        usort($completeRows, function($a, $b) {
            return $a['id'] - $b['id'];
        });
        
        return $this->sendSuccess([
            'data' => [
                'complete' => $completeRows,
                'upcoming' => $upcomingRows,
            ],
            'total' => count($sortedQuery),
            'max_pages' => 1,
            'status' => 1
        ]);
    }

    public function handleWishList(Request $request){
        $class = new \Modules\User\Controllers\UserWishListController();
        return $class->handleWishList($request);
    }

    public function indexWishlist(Request $request){
        $query = UserWishList::query()
            ->where("user_wishlist.user_id",Auth::id())
            ->orderBy('user_wishlist.id', 'desc')
            ->paginate(5);
        $rows = [];
        foreach ($query as $item){
            $service = $item->service;
            if(empty($service)) continue;

            $item = $item->toArray();
            $serviceTranslation = $service->translate();
            $serviceData = [
                'id'=>$service->id,
                'title'=>$serviceTranslation->title ?? '',
                'price'=>$service->price ?? 0,
                'sale_price'=>$service->sale_price ?? 0,
                'discount_percent'=>$service->discount_percent ?? null,
                'image'=>get_file_url($service->image_id),
                'content'=>$serviceTranslation->content ?? '',
                'model'=>$service->model ?? '',
                'address'=>$service->address ?? '',
                'distance'=>'',
                'fuel_capacity'=>$service->fuel_capacity ?? '', 
                'rental_duration'=>$service->rental_duration ?? '', 
                'driver_type'=>$service->driver_type ?? '', 
                'passenger'=>$service->passenger ?? 0, 
                // 'service_type'=>$service->getModelName() ?? '',
            ];
            
            // check information location
            if(!empty($service->location_id)) {
                $serviceData['location'] = [
                    'id' => $service->location_id,
                    'name' => $service->location->name ?? ''
                ];
            }
            
            $item['service'] = $serviceData;
            $rows[] = $item;
        }
        return $this->sendSuccess(
            [
                'data'=>$rows,
                'total'=>$query->total(),
                'total_pages'=>$query->lastPage(),
            ]
        );
    }

    public function isServiceInWishlist($serviceId, $userId = null)
    {
        if (!$userId) {
            $user = auth()->user();
            if (!$user) {
                return false;
            }
            $userId = $user->id;
        }
        
        return UserWishList::where('user_id', $userId)
            ->where('object_id', $serviceId)
            ->exists();
    }

    public function permanentlyDelete(Request  $request)
    {
        return (new \Modules\User\Controllers\UserController())->permanentlyDelete($request);
    }

    public function cancelBooking(Request $request)
    {
        $booking_id = $request->input('booking_id');
        $reason = $request->input('reason', '');
        
        if (empty($booking_id)) {
            return $this->sendError(__('Booking ID is required'));
        }
        
        $user_id = Auth::id();
        $booking = Booking::where('id', $booking_id)
            ->where('customer_id', $user_id)
            ->first();
        
        if (!$booking) {
            return $this->sendError(__('Booking not found or you don\'t have permission to cancel'));
        }
        
        // Check if booking can be cancelled
        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return $this->sendError(__('Cannot cancel booking with status: ') . booking_status_to_text($booking->status));
        }
        
        $canCancel = true;
        
        
        if (!$canCancel) {
            return $this->sendError(__('This booking cannot be cancelled due to the service\'s cancellation policy'));
        }
        
        $booking->status = 'cancelled';
        if (!empty($reason)) {
            $booking->addMeta('cancel_reason', $reason);
        }
        $booking->save();
        
        // Process refund if needed
        $booking->tryRefundToWallet();
        
        event(new BookingCancelledEvent($booking));
        
        return $this->sendSuccess([
            'message' => __('Booking has been cancelled successfully'),
            'status' => $booking->status
        ]);
    }

    public function updateProfile(Request $request)
{
    /** @var \App\User $user */
    $user = Auth::user();

    if (!$user) {
        return $this->sendError('Unauthenticated.', [], 401);
    }

    // --- Validation ---
    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'avatar_id' => [ // Validate avatar_id
            'nullable', // Cho phép gán avatar_id = null (xóa avatar)
            'integer',
            // Đảm bảo ID tồn tại trong bảng media_files
            Rule::exists('media_files', 'id')->where(function ($query) {
                // Tùy chọn: Thêm điều kiện khác nếu cần (vd: type='image')
            }),
        ],
         // Thêm validation cho các trường khác nếu muốn cập nhật chúng
    ]);
     if ($validator->fails()) {
        return $this->sendError('Validation Error.', ['errors' => $validator->errors()], 422);
    }

    // --- Update Logic ---
    $input = $validator->validated();
    $profileUpdated = false;

    // Gán giá trị MỚI và đánh dấu là cần cập nhật
    if (isset($input['name'])) {
        $newName = trim($input['name']);
        $nameParts = explode(' ', $newName, 2);
        $newFirstName = $nameParts[0];
        $newLastName = $nameParts[1] ?? '';

        // Gán giá trị mới BẤT KỂ có khác giá trị cũ hay không
        $user->name = $newName;
        $user->first_name = $newFirstName;
        $user->last_name = $newLastName;
        $profileUpdated = true; // Đánh dấu là có thay đổi để thực hiện save
    }
    if (array_key_exists('avatar_id', $input)) {
        if ($user->avatar_id !== $input['avatar_id']) { // Kiểm tra nếu ID thay đổi
            $user->avatar_id = $input['avatar_id']; // Gán ID mới (có thể là null)
            $profileUpdated = true;
        }
    }
    // Xử lý các trường khác tương tự nếu có (gán trực tiếp)
    // Ví dụ:
    // if (isset($input['bio'])) {
    //    $user->bio = clean($input['bio']);
    //    $profileUpdated = true; // Chỉ set true nếu thực sự có input['bio']
    // }


    if ($profileUpdated) {
         // Log TRƯỚC KHI SAVE để kiểm tra giá trị gán
        \Log::info("User object state JUST BEFORE save():");
        \Log::info("User Name: " . $user->name);
        \Log::info("User First Name: " . $user->first_name);
        \Log::info("User Last Name: " . $user->last_name);

        try {
            $user->save(); // Lưu tất cả thay đổi

            // ---- KHÔNG CẦN REFRESH NỮA ----
            // $user->refresh(); // Bỏ dòng này đi vì đối tượng $user đã đúng

            // ---- XÓA CACHE (VẪN NÊN GIỮ) ----
            Cache::forget('user_data_' . $user->id);
            \Log::info("Cache file potentially cleared for user ID: " . $user->id);
            // ---- KẾT THÚC XÓA CACHE ----


            // Log SAU KHI SAVE để kiểm tra lần cuối
            \Log::info("User object state AFTER save():");
            \Log::info("User Name: " . $user->name);
            \Log::info("User First Name: " . $user->first_name);
            \Log::info("User Last Name: " . $user->last_name);

            return $this->sendSuccess([
                'data' => new \Modules\User\Resources\UserResource($user), // Dùng đối tượng $user hiện tại
                'message' => __('Profile updated successfully')
            ]);

        } catch (\Exception $e) {
            \Log::error('API Profile Update Error: ' . $e->getMessage());
            return $this->sendError(__('Failed to update profile.'), [], 500);
        }
    } else {
        // ... (response không thay đổi) ...
    }
}
}
