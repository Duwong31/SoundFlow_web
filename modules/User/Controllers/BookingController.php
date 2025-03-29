<?php
namespace Modules\User\Controllers;

use Modules\FrontendController;
use Illuminate\Support\Facades\Auth;
use Validator;
use Modules\Booking\Models\Booking;
class BookingController extends FrontendController
{
    private Booking $booking;

    public function __construct(Booking $booking)
    {
        parent::__construct();
        $this->booking = $booking;
    }

    public function bookingInvoice($code)
    {
        $booking = $this->booking->where('code', $code)->first();
        $user_id = Auth::id();
        if (empty($booking)) {
            return redirect('user/booking-history');
        }
        if ($booking->customer_id != $user_id and $booking->vendor_id != $user_id) {
            return redirect('user/booking-history');
        }
        $data = [
            'booking'    => $booking,
            'service'    => $booking->service,
            'page_title' => __("Invoice")
        ];
        return view('User::frontend.bookingInvoice', $data);
    }

}
