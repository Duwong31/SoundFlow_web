<?php
namespace Modules\Api\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Modules\Booking\Models\Booking;
use Illuminate\Http\Request;
use Modules\Location\Models\Location;
use Modules\User\Models\UserWishList;
use Modules\Booking\Events\BookingUpdatedEvent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Translation\Translator;
use Modules\Booking\Events\BookingCancelledEvent;

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
}
