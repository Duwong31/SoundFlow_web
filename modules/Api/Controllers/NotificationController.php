<?php

namespace Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\NotificationPush;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    private function getNotificationTitle($event, &$type)
    {
        switch ($event) {
            case 'BookingCreatedEvent':
            case 'BookingUpdatedEvent':
            case 'BookingCancelledEvent':
            case 'BookingPickupTimeEvent':
            case 'BookingReturnLateEvent':
                $type = 'car';
                break;
        
            case 'BookingInvoiceEvent':
            case 'PaymentSuccessEvent':
                $type = 'payment';
                break;
        
            case 'CreateReviewEvent':
                $type = 'user';
                break;
            default:
                $type = $type ?: 'car';
                break;
        }
        
        switch ($event) {
            case 'BookingCreatedEvent':
                return __('Đặt xe thành công');
            case 'BookingUpdatedEvent':
                return __('Cập nhật đặt xe');
            case 'BookingCancelledEvent':
                return __('Thông báo hủy bỏ');
            case 'BookingPickupTimeEvent':
                return __('Thời gian đón/trả xe');
            case 'BookingReturnLateEvent':
                return __('Thông báo trả xe trễ');
            case 'BookingInvoiceEvent':
                return __('Hóa đơn chuyến đi');
            case 'CreateReviewEvent':
                return __('Đánh giá mới');
            case 'PaymentSuccessEvent':
                return __('Thông báo thanh toán');
            default:
                return __('Thông báo');
        }
    }

    private function formatNotification(NotificationPush $notification)
    {
        $notifyData = json_decode($notification->data);
        
        if (isset($notifyData->data) && is_string($notifyData->data)) {
            $notifyData = json_decode($notifyData->data);
        }
        
        $ids = $this->extractBookingAndCarId($notifyData);
        $bookingId = $ids['booking_id'];
        $carId = $ids['car_id'];
        
        if (!$carId && $notification->car_id) {
            $carId = $notification->car_id;
        }
        
        if (isset($notifyData->notification)) {
            $event = $notifyData->notification->event ?? '';
            $type = $notifyData->notification->type ?? '';
            $to = $notifyData->notification->to ?? '';
            $message = $notifyData->notification->message ?? '';
            $link = $notifyData->notification->link ?? '';
            $avatar = $notifyData->notification->avatar ?? null;
            $name = $notifyData->notification->name ?? '';
            
            $title = $notifyData->notification->title ?? $this->getNotificationTitle($event, $type);
            
            return [
                'id' => strval($notification->id),
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'notification_type' => $type,
                'event' => $event,
                'is_read' => !empty($notification->read_at),
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                'avatar' => $avatar,
                'name' => $name,
                'booking_id' => $bookingId ?? ($notifyData->notification->id ?? null),
                'car_id' => $carId,
                'to' => $to
            ];
        } 
        // Handle simple notification structure
        else {
            $event = $notifyData->event ?? '';
            $type = $notifyData->type ?? '';
            
            $title = $notifyData->title ?? $this->getNotificationTitle($event, $type);
            
            return [
                'id' => strval($notification->id),
                'title' => $title,
                'message' => $notifyData->message ?? '',
                'link' => $notifyData->link ?? '',
                'notification_type' => $type,
                'event' => $event,
                'is_read' => !empty($notification->read_at),
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                'avatar' => $notifyData->avatar ?? null,
                'name' => $notifyData->name ?? '',
                'booking_id' => $bookingId ?? ($notifyData->id ?? null),
                'car_id' => $carId,
            ];
        }
    }

    /**
     * Extract booking ID and car ID from notification data
     * 
     * @param object $notifyData
     * @return array
     */
    private function extractBookingAndCarId($notifyData)
    {
        $bookingId = null;
        $carId = null;
        
        // Check different possible locations of booking ID in notification data
        if (isset($notifyData->notification)) {
            $bookingId = $notifyData->notification->id ?? null;
        } elseif (isset($notifyData->id) && is_numeric($notifyData->id)) {
            $bookingId = $notifyData->id;
        }
        
        // Check different possible locations of car ID
        if (isset($notifyData->car_id)) {
            $carId = $notifyData->car_id;
        } elseif (isset($notifyData->notification->car_id)) {
            $carId = $notifyData->notification->car_id;
        } elseif (isset($notifyData->object_id) && $notifyData->object_model === 'car') {
            $carId = $notifyData->object_id;
        }
        
        // If we have a booking ID but no car ID, fetch the car ID from the booking
        if ($bookingId && !$carId) {
            $booking = DB::table('bravo_bookings')
                ->where('id', $bookingId)
                ->where('object_model', 'car')
                ->first();
            
            if ($booking) {
                $carId = $booking->object_id;
            }
        }
        
        return [
            'booking_id' => $bookingId,
            'car_id' => $carId
        ];
    }

    public function getNotifications(Request $request)
    {
        $query = NotificationPush::query()
            ->where('notifiable_id', Auth::id())
            ->where('for_admin', 0);
        
        if($request->type == 'unread') {
            $query->whereNull('read_at');
        }
        if($request->type == 'read') {
            $query->whereNotNull('read_at');
        }

        $query->orderBy('created_at', 'desc');
        
        $notifications = $query->paginate($request->limit ?? 20);
        
        $data = [];
        foreach($notifications as $notification) {
            $data[] = $this->formatNotification($notification);
        }

        return response()->json([
            'data' => $data,
            'total' => $notifications->total(),
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage()
        ]);
    }

    public function getUnreadCount()
    {
        $count = NotificationPush::where('notifiable_id', Auth::id())
            ->where('for_admin', 0)
            ->whereNull('read_at')
            ->count();
            
        return response()->json([
            'count' => $count
        ]);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $notification = DB::table('notifications')
            ->where('id', $request->id)
            ->where('notifiable_id', Auth::id())
            ->where('for_admin', 0)
            ->whereNull('read_at')
            ->first();
        
        if($notification) {
            DB::table('notifications')
                ->where('id', $notification->id)
                ->update([
                    'read_at' => now(),
                    'updated_at' => now()
                ]);
            
            return response()->json([
                'success' => true,
                'message' => __('Notification marked as read successfully')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Notification not found or already read')
        ], 404);
    }

    public function markAllAsRead()
    {
        $affected = DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->where('for_admin', 0)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => __('All notifications marked as read'),
            'affected_count' => $affected
        ]);
    }

    public function deleteNotification(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $deleted = DB::table('notifications')
            ->where('id', $request->id)
            ->where('notifiable_id', Auth::id())
            ->where('for_admin', 0)
            ->delete();
        
        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => __('Notification deleted successfully')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __('Notification not found')
        ], 404);
    }

    public function deleteAllNotifications(Request $request)
    {
        $query = DB::table('notifications')
            ->where('notifiable_id', Auth::id())
            ->where('for_admin', 0);
            
        // Optional: filter by read/unread status
        if ($request->has('status')) {
            if ($request->status === 'read') {
                $query->whereNotNull('read_at');
            } else if ($request->status === 'unread') {
                $query->whereNull('read_at');
            }
        }
        
        $count = $query->count();
        $query->delete();
        
        return response()->json([
            'success' => true,
            'message' => __('Notifications deleted successfully'),
            'deleted_count' => $count
        ]);
    }
} 