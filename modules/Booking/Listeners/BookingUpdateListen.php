<?php
    namespace Modules\Booking\Listeners;
    use App\Notifications\AdminChannelServices;
    use App\Notifications\PrivateChannelServices;
    use App\User;
    use Illuminate\Support\Facades\Auth;
    use Modules\Booking\Events\BookingUpdatedEvent;
    use Illuminate\Support\Facades\Cache;

    class BookingUpdateListen
    {
        public function handle(BookingUpdatedEvent $event)
        {
            $booking = $event->booking;
            
            $cacheKey = 'booking_updated_' . $booking->id . '_' . $booking->status;
            if (Cache::has($cacheKey)) {
                return;
            }
            
            Cache::put($cacheKey, true, 60);
            
            $vendor = User::where('id', $booking->vendor_id)->first();
            
            if(!empty($vendor)){
                $name = $vendor->getDisplayName();
                if($vendor->avatar_url){
                    $avatar = $vendor->avatar_url;
                }else{
                    $avatar = asset('images/avatar.png');
                }
            }else{
                $name = __('System');
                $avatar = asset('images/avatar.png');
            }
            
            $data = [
                'event'=>'BookingUpdatedEvent',
                'to'=>'admin',
                'id' => $booking->id,
                'name' => $name,
                'avatar' => $avatar,
                'link' => route('report.admin.booking'),
                'type' => 'car',
                'message' => __(':name has changed to :status', ['name' => $booking->service->title, 'status' => $booking->status])
            ];
            
            // Thông báo cho admin
            $adminUser = User::where('email', 'admin@gmail.com')->first();
            if ($adminUser) {
                $adminUser->notify(new AdminChannelServices($data));
            }
            
            // Thông báo cho vendor
            if($vendor && $vendor->id != Auth::id()){
                $data['to']='vendor';
                $data['link'] = route('vendor.bookingReport');
                $vendor->notify(new PrivateChannelServices($data));
            }
            
            // Thông báo cho người dùng 
            if ($booking->customer_id) {
                $user = User::find($booking->customer_id);
                if ($user) {
                    // BookingCancelledEvent xử lý thông báo khi hủy
                    if ($booking->status === 'cancelled') {
                        return;
                    }
                    
                    $carName = $booking->service->title ?? '';
                    $userData = [
                        'id' => $booking->id,
                        'event' => 'BookingUpdatedEvent',
                        'to' => 'customer',
                        'name' => Auth::user() ? Auth::user()->getDisplayName() : 'System',
                        'avatar' => Auth::user() && Auth::user()->avatar_url ? Auth::user()->avatar_url : asset('images/avatar.png'),
                        'link' => route('user.booking_history'),
                        'type' => 'car',
                    ];
                    
                    switch ($booking->status) {
                        case 'processing':
                            $userData['type'] = 'car';
                            $userData['message'] = "Đơn đặt xe {$carName} của bạn đang được xử lý. Chúng tôi sẽ thông báo khi hoàn tất!";
                            break;
                        case 'confirmed':
                            $userData['type'] = 'car';
                            $userData['message'] = "{$carName} đã sẵn sàng! Kiểm tra email để biết thêm chi tiết. Chúc bạn đi đường an toàn!";
                            break;
                        case 'completed':
                            $userData['type'] = 'car';
                            $userData['message'] = "Chuyến đi của bạn với {$carName} đã hoàn thành. Cảm ơn bạn đã sử dụng dịch vụ!";
                            break;
                        default:
                            return;
                    }
                    
                    $user->notify(new PrivateChannelServices($userData));
                }
            }
        }
    }
