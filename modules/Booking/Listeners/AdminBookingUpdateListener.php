<?php
namespace Modules\Booking\Listeners;

use App\Notifications\AdminChannelServices;
use App\Notifications\PrivateChannelServices;
use Modules\Booking\Events\BookingUpdatedEvent;
use App\User;
use Illuminate\Support\Facades\DB;

class AdminBookingUpdateListener
{
    public function handle(BookingUpdatedEvent $event)
    {
        $booking = $event->booking;
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
        
        // Thông báo cho admin
        $data = [
            'event' => 'BookingUpdatedEvent',
            'to' => 'admin',
            'id' => $booking->id,
            'name' => $name,
            'avatar' => $avatar,
            'link' => route('report.admin.booking'),
            'type' => $booking->object_model,
            'message' => __(':name has changed to :status', ['name' => $booking->service->title, 'status' => $booking->status])
        ];
        
        // thông báo admin for_admin = 1
        $systemUser = User::find(1);
        if ($systemUser) {
            $systemUser->notify(new AdminChannelServices($data));
        }
        
        // Gửi thông báo cho vendor (nếu cần)
        if ($vendor) {
            $vendor_data = $data;
            $vendor_data['to'] = 'vendor';
            $vendor_data['link'] = route('vendor.bookingReport');
            $vendor->notify(new PrivateChannelServices($vendor_data));
        }
    }
} 