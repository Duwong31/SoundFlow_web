<?php
namespace Modules\Booking\Listeners;

use App\Notifications\AdminChannelServices;
use App\Notifications\PrivateChannelServices;
use Modules\Booking\Events\BookingCreatedEvent;
use Modules\Booking\Events\BookingCancelledEvent;
use Modules\Booking\Events\BookingPickupTimeEvent;
use Modules\Booking\Events\BookingReturnLateEvent;
use Modules\Booking\Models\Booking;
use App\User;
use Modules\Booking\Events\BookingUpdatedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BookingNotificationListener
{
    /**
     * Xử lý thông báo khi đặt xe thành công
     *
     * @param BookingCreatedEvent $event
     */
    public function handleBookingCreated(BookingCreatedEvent $event)
    {
        $booking = $event->booking;
        $user = User::find($booking->customer_id);
        
        if(!$user) return;
        
        // Lấy thông tin xe từ booking
        $car = $booking->service;
        $carName = $car ? $car->title : '';
        
        $data = [
            'id' => $booking->id,
            'event' => 'BookingCreatedEvent',
            'to' => 'customer',
            'type' => 'car',
            'name' => $user->getDisplayName(),
            'avatar' => $user->avatar_url,
            'link' => route('user.booking_history'),
            'message' => "Xe {$carName} đã sẵn sàng. Chúc bạn đi đường an toàn!"
        ];
        
        $user->notify(new PrivateChannelServices($data));
    }
    
    /**
     * Xử lý thông báo về thời gian đón/trả xe
     *
     * @param BookingPickupTimeEvent $event
     */
    public function handlePickupTime(BookingPickupTimeEvent $event)
    {
        $booking = $event->booking;
        $user = User::find($booking->customer_id);
        
        if(!$user) return;
        
        $pickupTime = date("h:i A", strtotime($booking->start_date));
        
        $data = [
            'id' => $booking->id,
            'event' => 'BookingPickupTimeEvent',
            'to' => 'customer',
            'type' => 'car',
            'name' => $user->getDisplayName(),
            'avatar' => $user->avatar_url,
            'link' => route('user.booking_history'),
            'message' => "Đã xác nhận thời gian đón! Gặp bạn lúc {$pickupTime} để thuê xe. Đã xác nhận thời gian trả xe!"
        ];
        
        $user->notify(new PrivateChannelServices($data));
    }
    
    /**
     * Xử lý thông báo khi cập nhật đặt xe
     * 
     * @param BookingUpdatedEvent $event
     */
    public function handleBookingUpdated(BookingUpdatedEvent $event)
    {
        $booking = $event->booking;
        $user = User::find($booking->customer_id);
        
        if (!$user) return;
        
        switch ($booking->status) {
            case 'cancelled':
                return;
                
            case 'completed':
                $car_name = $booking->service->title ?? '';
                $data = [
                    'id' => $booking->id,
                    'event' => 'BookingUpdatedEvent',
                    'to' => 'customer',
                    'type' => 'car',
                    'name' => $user->getDisplayName(),
                    'avatar' => $user->avatar_url,
                    'link' => route('user.booking_history'),
                    'message' => "Chuyến đi của bạn với {$car_name} đã hoàn thành. Cảm ơn bạn đã sử dụng dịch vụ!"
                ];
                break;
                
            case 'processing':
                $car_name = $booking->service->title ?? '';
                $data = [
                    'id' => $booking->id,
                    'event' => 'BookingUpdatedEvent',
                    'to' => 'customer',
                    'type' => 'car',
                    'name' => $user->getDisplayName(),
                    'avatar' => $user->avatar_url,
                    'link' => route('user.booking_history'),
                    'message' => "Đơn đặt xe {$car_name} của bạn đang được xử lý. Chúng tôi sẽ thông báo khi hoàn tất!"
                ];
                break;
                
            default:
                return;
        }
        
        $user->notify(new PrivateChannelServices($data));
    }
    
    /**
     * Xử lý thông báo cảnh báo trả xe trễ
     *
     * @param BookingReturnLateEvent $event
     */
    public function handleReturnLate(BookingReturnLateEvent $event)
    {
        $booking = $event->booking;
        $user = User::find($booking->customer_id);
        
        if(!$user) return;
        
        $car = $booking->service;
        $carName = $car ? $car->title : '';
        
        $data = [
            'id' => $booking->id,
            'event' => 'BookingReturnLateEvent',
            'to' => 'customer',
            'type' => 'car',
            'name' => $user->getDisplayName(),
            'avatar' => $user->avatar_url,
            'link' => route('user.booking_history'),
            'message' => "Hợp Đồng đã kết thúc, vui lòng trả xe {$carName} về vị trí đã đặt hẹn."
        ];
        
        $user->notify(new PrivateChannelServices($data));
    }

    public function handle($event)
    {
        if ($event instanceof BookingCancelledEvent) {
            $this->handleBookingCancelled($event);
        }
    }

    public function handleBookingCancelled(BookingCancelledEvent $event)
    {
        $booking = $event->booking;
        
        $cacheKey = 'booking_cancelled_' . $booking->id;
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, 60);
        
        // thông báo users
        $customer = User::find($booking->customer_id);
        if ($customer) {
            $car_name = $booking->service->title ?? '';
            
            $userData = [
                'id' => $booking->id,
                'event' => 'BookingCancelledEvent',
                'to' => 'customer',
                'name' => 'System',
                'avatar' => asset('images/avatar.png'),
                'link' => route('user.booking_history'),
                'type' => 'car',
                'message' => "Đơn {$car_name} của bạn đã được hủy thành công."
            ];
            
            $customer->notify(new PrivateChannelServices($userData));
        }
        
        // thông báo đến vendor
        $vendor = User::find($booking->vendor_id);
        if ($vendor) {
            $customer_name = $customer ? $customer->getDisplayName() : 'Khách hàng';
            $car_name = $booking->service->title ?? '';
            
            $vendorData = [
                'id' => $booking->id,
                'event' => 'BookingCancelledEvent',
                'to' => 'vendor',
                'name' => $customer_name,
                'avatar' => $customer && $customer->avatar_url ? $customer->avatar_url : asset('images/avatar.png'),
                'link' => route('vendor.bookingReport'),
                'type' => 'car',
                'message' => "{$customer_name} đã hủy đặt xe {$car_name}."
            ];
            
            $vendor->notify(new PrivateChannelServices($vendorData));
        }
        
        // thông báo đến admin
        $adminUser = User::where('email', 'admin@gmail.com')->first();
        if ($adminUser) {
            $customer_name = $customer ? $customer->getDisplayName() : 'Khách hàng';
            $car_name = $booking->service->title ?? '';
            
            $adminData = [
                'event' => 'BookingCancelledEvent',
                'to' => 'admin',
                'id' => $booking->id,
                'name' => $customer_name,
                'avatar' => $customer && $customer->avatar_url ? $customer->avatar_url : asset('images/avatar.png'),
                'link' => route('report.admin.booking'),
                'type' => 'car',
                'message' => "{$customer_name} đã hủy đặt xe {$car_name}."
            ];
            
            $adminUser->notify(new AdminChannelServices($adminData));
        }
    }
} 