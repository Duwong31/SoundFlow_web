<?php
namespace Modules\Coupon\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Service;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Models\CouponServices;
use Modules\FrontendController;

class CouponController extends FrontendController
{

    public function index(Request $request)
    {
        $this->checkPermission('coupon_view');

        $user_id = Auth::id();

        $query = Coupon::query()->where("author_id", $user_id) ;

        $query->orderBy('id', 'desc');
        if (!empty($coupon_name = $request->input('s'))) {
            $query->where('name', 'LIKE', '%' . $coupon_name . '%');
            $query->orWhere('code',   $coupon_name );
        }

        $query->orWhere('code',   $coupon_name );

        $data = [
            'rows'               => $query->with(['author'])->paginate(20),
            'breadcrumbs'        => [
                [
                    'name'  => __('Coupon Management'),
                    'class' => 'active'
                ],
            ],
            'page_title'=>__("Coupon Management"),
        ];
        return view('Coupon::frontend.vendor.index', $data);
    }

    public function edit(Request $request,$id)
    {
        $this->checkPermission('coupon_update');
        $user_id = Auth::id();
        $row = Coupon::where("author_id", $user_id)->find($id);
        if (empty($row)) {
            return redirect(route('coupon.vendor.index'));
        }
        $data = [
            'row'            => $row,
            'breadcrumbs'    => [
                [
                    'name' => __('All Coupons'),
                    'url'  => route('coupon.vendor.index')
                ],
                [
                    'name' => __('Edit Coupon: :name',['name'=>$row->code]),
                ],
            ],
            'page_title'=>__("Edit: :name",['name'=>$row->code]),
        ];
        return view('Coupon::frontend.vendor.detail', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('coupon_create');

        $row = new Coupon();
        $data = [
            'row'            => $row,
            'breadcrumbs'    => [
                [
                    'name' => __('All Coupons'),
                    'url'  => route('coupon.vendor.index')
                ],
                [
                    'name' => __('Create Coupon'),
                ],
            ],
            'page_title'=>__('Create Coupon'),
        ];
        return view('Coupon::frontend.vendor.detail', $data);
    }

    public function store( Request $request,$id ){
        $request->validate([
            'code'=>[
                'required',
                'max:50',
                'string',
                'alpha_dash',
                Rule::unique('bravo_coupons')->ignore($id > 0 ? $id : false)
            ],
            'amount'=>['required']
        ]);

        if($id>0){
            $this->checkPermission('coupon_update');
            $row = Coupon::find($id);
            if (empty($row)) {
                return redirect(route('coupon.vendor.index'));
            }

        }else{
            $this->checkPermission('coupon_create');
            $row = new Coupon();
            $row->status = "publish";
        }

        $dataKeys = [
            'name',
            'code',
            'status',
            'amount',
            'discount_type',
            'end_date',
            'min_total',
            'max_total',
            'only_for_user',
            'quantity_limit',
            'limit_per_user',
            'image_id'
        ];

        $row->fillByAttr($dataKeys,$request->input());
        $row->author_id = Auth::id();
        $row->is_vendor = 1;

        //Save Coupon Product
        $services = $request->input('services');
        $coupon_product = new CouponServices();
        $coupon_product->clean($row->id);
        if(!empty($services) and is_array($services)){
            $services = Service::selectRaw('id,object_id,object_model')->whereIn('id',$services)->where("author_id", $row->author_id)->get();
            if($services->count() > 0){
                foreach ($services as $service){
                    $coupon_product = new CouponServices();
                    $coupon_product->fill([
                        'coupon_id' => $row->id,
                        'object_id' => $service->object_id,
                        'object_model' => $service->object_model,
                        'service_id' => $service->id,
                    ]);
                    $coupon_product->save();
                    $service_ids[] = $service->id;
                }
            }
        }
        $row->services = $service_ids ?? [];
        $res = $row->save();
        if ($res) {

            if($id > 0 ){
                return redirect()->back()->with('success',  __('Coupon updated') );
            }else{
                return redirect()->to(route('coupon.vendor.index'))->with('success',  __('Coupon created') );
            }
        }
    }

    public function delete($id)
    {
        $this->checkPermission('coupon_delete');
        $user_id = Auth::id();
        $query = Coupon::query()->where("author_id", $user_id)->where("id", $id)->first();
        if(!empty($query)){
            $query->delete();
        }
        return redirect(route('coupon.vendor.index'))->with('success', __('Delete success!'));
    }


    public function applyCoupon($code , Request $request){
        $validator = \Validator::make($request->all(), [
            'coupon_code' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        $coupon = Coupon::where('code',$request->input('coupon_code'))->where("status","publish")->first();
        if(empty($coupon)){
            return $this->sendError( __("Invalid coupon code!"));
        }
        $booking = Booking::where('code', $code)->first();
        if ( !empty($booking) and !in_array($booking->status , ['draft','unpaid'])) {
            return $this->sendError( __("Booking not found!"));
        }
        $res = $coupon->applyCoupon($booking,'add');
        if($res['status']==1){
            $res['reload'] = 1;
        }
        return $this->sendSuccess($res);
    }

    public function removeCoupon($code , Request $request){
        $coupon = Coupon::where('code',$request->input('coupon_code'))->where("status","publish")->first();
        if(empty($coupon)){
            return $this->sendError( __("Invalid coupon code!"));
        }
        $booking = Booking::where('code', $code)->first();
        if ( !empty($booking) and !in_array($booking->status , ['draft','unpaid'])) {
            return $this->sendError( __("Booking not found!"));
        }
        $res = $coupon->applyCoupon($booking,'remove');
        if($res['status']==1){
            $res['reload'] = 1;
        }
        return $this->sendSuccess($res);
    }

    function getServiceForSelect2(Request $request){
        $q = $request->query('q');
        $query = Service::select('*');
        $user_id = Auth::id();
        $query->where("author_id", $user_id);
        if ($q) {
            $query->where(function ($query) use ($q) {
                $query->where('title', 'like', '%' . $q . '%')
                    ->orWhere('id', $q);
            });
        }
        $res = $query->orderBy('id', 'desc')->orderBy('title', 'asc')->limit(20)->get();
        $data = [];
        if (!empty($res)) {
            foreach ($res as $item) {
                $data[] = [
                    'id'   => $item->id,
                    'text' => strtoupper($item->object_model)." (#{$item->object_id}): {$item->title}",
                ];
            }
        }
        return response()->json([
            'results' => $data
        ]);
    }
}
