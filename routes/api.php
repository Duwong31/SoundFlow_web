<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\CarController;
use App\Models\ZaloSetting;
use App\Helpers\SmsHelper;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('cars', [CarController::class, 'listCars']);
});
// // Test Zalo OTP API
// Route::post('/test-zalo-sms', function (Request $request) {
//     try {
//         $validator = Validator::make($request->all(), [
//             'phone' => 'required|string|regex:/^\+84[0-9]{9}$/',
//             'message' => 'required|string'
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => 0,
//                 'message' => 'Dữ liệu không hợp lệ',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $zaloSetting = \App\Models\ZaloSetting::first();
//         if (!$zaloSetting || empty($zaloSetting->zalo_access_token)) {
//             return response()->json([
//                 'status' => 0,
//                 'message' => 'Thiếu cấu hình Zalo'
//             ], 500);
//         }

//         $response = \App\Helpers\SmsHelper::send(
//             $request->phone, 
//             $request->message, 
//             $zaloSetting->zalo_access_token
//         );

//         return response()->json([
//             'status' => 1,
//             'message' => 'Gửi tin nhắn thành công',
//             'response' => $response
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 0,
//             'message' => 'Lỗi: ' . $e->getMessage()
//         ], 500);
//     }
// });

// // Test Zalo OTP API
// Route::post('/test-zalo-otp', function (Request $request) {
//     try {
//         $validator = Validator::make($request->all(), [
//             'phone' => 'required|string|regex:/^\+84[0-9]{9}$/',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'status' => 0,
//                 'message' => 'Dữ liệu không hợp lệ',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $zaloSetting = ZaloSetting::first();
//         if (!$zaloSetting || empty($zaloSetting->zalo_access_token)) {
//             return response()->json([
//                 'status' => 0,
//                 'message' => 'Không tìm thấy cấu hình Zalo'
//             ], 500);
//         }

//         $otp = rand(100000, 999999);
        
//         $response = SmsHelper::send(
//             $request->phone, 
//             $otp, 
//             $zaloSetting->zalo_access_token
//         );

//         return response()->json([
//             'status' => 1,
//             'message' => 'Gửi OTP thành công',
//             'debug' => [
//                 'otp' => $otp,
//                 'response' => $response
//             ]
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 0,
//             'message' => 'Lỗi: ' . $e->getMessage()
//         ], 500);
//     }
// });
