<?php

namespace App\Http\Controllers;

use App\Helpers\Assets;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function sendError($message, $data = [], $code = 400)
    {
        // Try to get language from header first, if not exist then get from app locale
        $language = request()->header('Language') ?? app()->getLocale();
        
        // Get translated message from error language file
        $message = __('error.'.$message, [], $language);

        $response = [
            'status' => 0,
            'message' => $message
        ];

        return response()->json($response, $code);
    }

    public function sendSuccess($data = [],$message = '')
    {
        if(is_string($data))
        {
            return response()->json([
                'message'=>$data,
                'status'=>true
            ]);
        }

        if(!isset($data['status'])) $data['status'] = 1;

        if($message)
        $data['message'] = $message;

        return response()->json($data);
    }

    public function setActiveMenu($item)
    {
        set_active_menu($item);
    }

    public function currentUser()
    {
        return Auth::user();
    }

    protected function registerJs($file,$inFooter = true, $pos = 10,$version = false){
        Assets::registerJs($file,$inFooter,$pos,$version);
    }
    
    protected function registerCss($file,$inFooter = false, $pos = 10,$version = false){
        Assets::registerCss($file,$inFooter,$pos,$version);
    }
}
