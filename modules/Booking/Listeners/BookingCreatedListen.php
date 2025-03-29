<?php

    namespace Modules\Booking\Listeners;

    use App\Notifications\AdminChannelServices;
    use App\Notifications\PrivateChannelServices;
    use App\User;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Cache;
    use Modules\Booking\Events\BookingCreatedEvent;

    class BookingCreatedListen
    {
        public function handle(BookingCreatedEvent $event)
        {
            $booking = $event->booking;
            
            // dis duplicate notification
            $cacheKey = 'booking_created_' . $booking->id;
            if (Cache::has($cacheKey)) {
                return;
            }
            
            Cache::put($cacheKey, true, 60);
            
            $customer = User::find($booking->customer_id);
            $vendor = User::find($booking->vendor_id);
            
            if (!$customer) return;
            
            $customerName = $customer->getDisplayName();
            $customerAvatar = $customer->avatar_url ? $customer->avatar_url : asset('images/avatar.png');
            
            // thông báo đến Admin
            $adminData = [
                'event' => 'BookingCreatedEvent',
                'to' => 'admin',
                'id' => $booking->id,
                'name' => $customerName,
                'avatar' => $customerAvatar,
                'link' => route('report.admin.booking'),
                'type' => $booking->object_model,
                'message' => __(':name has created new Booking', ['name' => $customerName])
            ];
            
            $adminUser = User::where('email', 'admin@gmail.com')->first();
            if ($adminUser) {
                $adminUser->notify(new AdminChannelServices($adminData));
            }
            
            // thông báo đến Vendor
            if ($vendor) {
                $vendorData = $adminData;
                $vendorData['to'] = 'vendor';
                $vendorData['link'] = route('vendor.bookingReport');
                
                $vendor->notify(new PrivateChannelServices($vendorData));
            }
            
            // thông báo đến User
            $carName = $booking->service->title ?? '';
            $userData = [
                'id' => $booking->id,
                'event' => 'BookingCreatedEvent',
                'to' => 'customer',
                'name' => Auth::user() ? Auth::user()->getDisplayName() : 'System',
                'avatar' => Auth::user() && Auth::user()->avatar_url ? Auth::user()->avatar_url : asset('images/avatar.png'),
                'link' => route('user.booking_history'),
                'type' => 'booking',
                'notification_type' => 'booking_created',
                'message' => "Đặt xe {$carName} thành công! Chúng tôi sẽ xem xét đơn của bạn và thông báo sớm nhất."
            ];
            
            $customer->notify(new PrivateChannelServices($userData));
            
            // Thông báo xác nhận thời gian đón/trả xe
            $pickupTime = date('H:i', strtotime($booking->start_date));
            $pickupData = [
                'id' => $booking->id,
                'event' => 'BookingPickupTimeEvent',
                'to' => 'customer',
                'notification_type' => 'pickup_time',
                'name' => Auth::user() ? Auth::user()->getDisplayName() : 'System',
                'avatar' => Auth::user() && Auth::user()->avatar_url ? Auth::user()->avatar_url : asset('images/avatar.png'),
                'link' => route('user.booking_history'),
                'type' => 'booking',
                'message' => "Đã xác nhận thời gian đón! Gặp bạn lúc {$pickupTime} để thuê xe. Đã xác nhận thời gian trả xe!"
            ];
            
            $customer->notify(new PrivateChannelServices($pickupData));
        }
    }
