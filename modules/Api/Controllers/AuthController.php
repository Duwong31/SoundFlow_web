<?php

namespace Modules\Api\Controllers;

use App\User;
use App\Models\ZaloSetting;
use App\Helpers\SmsHelper;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Matrix\Exception;
use Modules\User\Emails\ResetPasswordToken;
use Modules\User\Events\SendMailUserRegistered;
use Modules\User\Resources\UserResource;
use Validator;
use Illuminate\Support\Str;
use Modules\Sms\Core\Facade\Sms;
use Illuminate\Support\Facades\DB;
use Modules\User\Models\User as ModulesUser;
use Modules\User\Emails\ResetPasswordOtpEmail;
class AuthController extends Controller
{
    /**
     * Array of test phone numbers that will always receive OTP 123456
     * @var array
     */
    protected $testPhoneNumbers = [
        '+84973366537',
        '+84865336466',
        '+84123456222',
        '+84477809998',
    ];

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum', [
            'except' => [
                'login',
                'register',
                'forgotPassword',
                'resetPassword',
                'verifyPhoneOTP',
                'resendOTP',
                'forgotPasswordEmail', 
                'resetPasswordEmail',
            ]
        ]);

        // Add custom middleware to handle unauthenticated response
        $this->middleware(function ($request, $next) {
            if (!$request->bearerToken() || !auth()->check()) {
                return response()->json([
                    'status' => 0,
                    'message' => __('auth.unauthenticated'),
                    'require_login' => 1
                ], 401);
            }
            return $next($request);
        }, ['only' => ['changePassword', 'logout', 'me', 'updateUser']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     */
    public function login(Request $request)
    {
        // Check if request is empty
        if (empty($request->all())) {
            return $this->sendError('empty_data');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            
            if ($errors->has('password')) {
                return $this->sendError('required_password');
            }
            if ($errors->has('email')) {
                return $this->sendError('required_email');
            }
            if ($errors->has('device_name')) {
                return $this->sendError('required_device');
            }
        }

        // Validate phone number format
        // $phone = $request->phone;
        // if (!preg_match('/^\+84\d{9}$/', $phone)) {
        //     return $this->sendError('invalid_phone');
        // }

        // $user = User::where('phone', $request->phone)->first();

        // if (!$user || !Hash::check($request->password, $user->password)) {
        //     return $this->sendError('invalid_credentials');
        // }

        $user = User::where('email', $request->email)->first(); // Find user by email


        // --- Password Check and Response ---
        // This part is fine, assuming the user was found correctly above
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->sendError('invalid_credentials'); 
        }

        return $this->sendSuccess([
            'access_token' => $user->createToken($request->device_name)->plainTextToken,
            'user' => new UserResource($user)
        ]);
    }

    // create otp
    protected function makeOtp($phone)
    {
        if ($this->isTestPhone($phone)) {
            return $this->testOtp();
        }
        
        return (string) rand(100000, 999999);
    }

    // send sms
    protected function sendSms($phone, $message)
    {
        try {
            $smsDriver = app('sms')->driver('log');
            return $smsDriver->send($phone, $message);
        } catch (\Exception $e) {
            \Log::error("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }
    public function register(Request $request)
    {
        // Check if request is empty
        if (empty($request->all())) {
            return $this->sendError('empty_data');
        }

        if (!is_enable_registration()) {
            return $this->sendError(__("You are not allowed to register"));
        }

        // Check if registration is via phone (mobile app) or email (web)
        $isPhoneRegistration = $request->input('registration_type') === 'phone';

        if ($isPhoneRegistration) {
            return $this->registerViaPhone($request);
        } else {
            return $this->registerViaEmail($request);
        }
    }

    protected function registerViaPhone(Request $request)
    {
        // Check if request is empty
        if (empty($request->all())) {
            return $this->sendError('empty_data');
        }

        $rules = [
            'phone' => [
                'required',
                'string',
                'regex:/^\+84[0-9]{9}$/'
            ],
            'term' => [
                'required',
                'in:1,true'
            ],
            'device_name' => [
                'required',
                'string',
                'max:255'
            ],
            'registration_type' => [
                'required',
                'string',
                'in:phone'
            ],
            'referral_code' => [
                'nullable',
                'string',
                'exists:users,referral_code'
            ]
        ];

        $messages = [
            'phone.required' => 'required_phone',
            'phone.regex' => 'invalid_phone',
            'term.required' => 'required_term',
            'term.in' => 'invalid_term',
            'device_name.required' => 'required_device',
            'registration_type.required' => 'required_registration_type',
            'registration_type.in' => 'invalid_registration_type',
            'referral_code.exists' => 'invalid_referral_code'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            $errors = $validator->errors();
            
            if ($errors->has('phone')) {
                // Check if phone is empty 
                if (!$request->phone) {
                    return $this->sendError('required_phone');
                }
                return $this->sendError('invalid_phone');
            }

            // Check registration_type
            if ($errors->has('registration_type')) {
                $messages = $errors->get('registration_type');
                foreach ($messages as $message) {
                    if (strpos($message, 'The selected registration type is invalid') !== false) {
                        return $this->sendError('invalid_registration_type');
                    }
                }
                return $this->sendError('required_registration_type');
            }
            
            if ($errors->has('device_name')) {
                return $this->sendError('required_device');
            }
            
            if ($errors->has('term')) {
                return $this->sendError('required_term');
            }
            
            return $this->sendError('validation_error');
        }

        if ($this->RegisterLimitDay($request->phone)) {
            return $this->sendError('otp_limit_exceeded_24h');
        }

        $existingUser = User::where('phone', $request->phone)
                            ->where('registration_status', 'completed')
                            ->first();

        if ($existingUser) {
            return $this->sendError('phone_exists');
        }

        $pendingUser = User::where('phone', $request->phone)
                          ->where('registration_status', 'pending')
                          ->first();

        if ($pendingUser) {
            $pendingUser->delete();
        }

        $tempEmail = 'temp_' . Str::random(10) . '@temp.com';

        $tempPassword = '123456';
        
        $user = new \Modules\User\Models\User();    
        $user->phone = $request->input('phone');
        $user->email = $tempEmail;
        $user->password = Hash::make($tempPassword);
        $user->status = 'publish';
        $user->phone_verified = 0;
        $user->registration_status = 'pending';

        // save referral code to referral_code_used
        if($request->filled('referral_code')){
            $user->referral_code_used = $request->input('referral_code');
        }
        $user->save();

        // plus 5 points for referrer
        if ($request->filled('referral_code')) {
            $referrer = \Modules\User\Models\User::where('referral_code', $request->input('referral_code'))->first();
            if ($referrer) {
                $referrer->points = $referrer->points + 5;
                $referrer->save();
            }
        }

        DB::statement("UPDATE users SET registration_status = 'pending' WHERE id = ?", [$user->id]);

        try {
            $otp = in_array($request->phone, $this->testPhoneNumbers) ? '123456' : rand(100000, 999999);
            $message = __(':code is your verification code', ['code' => $otp]);

            // Store OTP in user meta
            $user->addMeta('phone_verify_code', $otp);
            $user->addMeta('phone_verify_code_timestamp', Carbon::now()->format('Y-m-d H:i:s'));

            // Send SMS 
            if (!in_array($request->phone, $this->testPhoneNumbers)) {
                try {
                    $zaloSetting = ZaloSetting::first();
                    if (!$zaloSetting || empty($zaloSetting->zalo_access_token)) {
                        throw new \Exception('Zalo configuration not found');
                    }
                    
                    $response = SmsHelper::send($request->phone, $otp, $zaloSetting->zalo_access_token);
                    if (isset($response->error) && $response->error !== 0) {
                        Log::channel('sms')->error("SMS sending failed: " . json_encode($response));
                    }
                } catch (\Exception $e) {
                    Log::channel('sms')->error("SMS sending failed: " . $e->getMessage());
                }
            }

            return response()->json([
                'status' => 1,
                'message' => __('auth.otp_sent'),
                'data' => [
                    'user_id' => $user->id,
                    'requires_password' => true,
                    'otp' => $otp
                ]
            ]);

        } catch (\Exception $e) {
            $user->delete();
            return $this->sendError($e->getMessage());
        }
    }

    protected function registerViaEmail(Request $request)
    {
        // Check if request is empty
        if (empty($request->all())) {
            return $this->sendError('empty_data');
        }

        $rules = [
            // 'first_name' => [
            //     'required',
            //     'string',
            //     'max:255'
            // ],
            // 'last_name' => [
            //     'required',
            //     'string',
            //     'max:255'
            // ],
            'name' =>[
                'required',
                'string',
                'max:255'
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users'
            ],
            'password' => [
                'required',
                'string'
            ],
            // 'term' => ['required'],
        ];

        $messages = [
            'name.required' => __('Vui lòng nhập tên của bạn'),
            'email.required' => __('Email is required'),
            'email.email' => __('Email is invalid'),
            'password.required' => __('Password is required'),
            // 'first_name.required' => __('The first name is required'),
            // 'last_name.required' => __('The last name is required'),
            // 'term.required' => __('The terms and conditions field is required'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return $this->sendError('validation_error', ['errors' => $validator->errors()->toArray()]);
        }

        $user = \App\User::create([
            // 'first_name' => $request->input('first_name'),
            // 'last_name' => $request->input('last_name'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'publish' => $request->input('publish'),
            // 'phone' => $request->input('phone'),
        ]);

        event(new Registered($user));
        try {
            event(new SendMailUserRegistered($user));
        } catch (Exception $exception) {
            Log::warning("SendMailUserRegistered: " . $exception->getMessage());
        }

        $user->assignRole(setting_item('user_role'));
        return $this->sendSuccess(__('Register successfully'));
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth()->user();
        return $this->sendSuccess([
            'data' => new UserResource($user)
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = Auth::user();
        
        $rules = [];
        $messages = [];
        
        if ($request->has('first_name')) {
            $rules['first_name'] = 'required|max:255';
            $messages['first_name.required'] = __('The first name is required field');
        }
        
        if ($request->has('last_name')) {
            $rules['last_name'] = 'required|max:255';
            $messages['last_name.required'] = __('The last name is required field');
        }
        
        if ($request->has('email')) {
            $rules['email'] = [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ];
            $messages['email.required'] = __('The email field is required');
            $messages['email.email'] = __('The email must be a valid email address');
            $messages['email.unique'] = __('The email has already been taken');
        }
        
        if ($request->has('birthday')) {
            $rules['birthday'] = 'nullable|date';
        }
        
        if ($request->has('user_name')) {
            $rules['user_name'] = 'nullable|string|max:255';
        }
        
        if ($request->has('gender')) {
            $rules['gender'] = 'nullable|string|max:50';
        }
        
        if ($request->has('so_bhxh')) {
            $rules['so_bhxh'] = 'nullable|string|max:50';
        }
        
        if ($request->has('address')) {
            $rules['address'] = 'nullable|string|max:255';
        }
        
        if ($request->has('business_name')) {
            $rules['business_name'] = 'nullable|string|max:255';
        }

        if ($request->has('avatar_id')) {
            $rules['avatar_id'] = 'nullable|integer|exists:media_files,id';
            $messages['avatar_id.exists'] = __('ID ảnh không tồn tại trong hệ thống');
        }

        if (!empty($rules)) {
            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return $this->sendError('validation_error', ['errors' => $validator->errors()->toArray()]);
            }
        }

        $fieldsToUpdate = [
            'first_name',
            'last_name',
            'email',
            'birthday',
            'user_name',
            'gender',
            'so_bhxh',
            'address',
            'business_name',
            'avatar_id'
        ];

        $dataToUpdate = [];
        foreach ($fieldsToUpdate as $field) {
            if ($request->has($field)) {
                $dataToUpdate[$field] = $request->input($field);
            }
        }
        
        // Properly handle birthday field if present
        if (isset($dataToUpdate['birthday']) && $dataToUpdate['birthday']) {
            $dataToUpdate['birthday'] = date("Y-m-d", strtotime($dataToUpdate['birthday']));
        }
        
        // Update name field if first_name or last_name was changed
        if ($request->has('first_name') || $request->has('last_name')) {
            $firstName = $request->has('first_name') ? $request->first_name : $user->first_name;
            $lastName = $request->has('last_name') ? $request->last_name : $user->last_name;
            $dataToUpdate['name'] = $firstName . ' ' . $lastName;
        }

        $user->fill($dataToUpdate);
        $user->save();

        return $this->sendSuccess([
            'message' => __('Update successfully'),
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
            return $this->sendSuccess(__('auth.logout_success'));
        }
        return $this->sendError('unauthorized');
    }

    public function changePassword(Request $request)
    {
        // Check token
        if (!$request->bearerToken()) {
            return response()->json([
                'status' => 0,
                'message' => __('auth.unauthenticated'),
                'require_login' => 1
            ], 401);
        }

        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'status' => 0,
                'message' => __('auth.unauthenticated'),
                'require_login' => 1
            ], 401);
        }

        $rules = [
            'current_password' => 'required',
            'new_password' => [
                'required',
                'string',
                'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/'
            ]
        ];

        $messages = [
            'current_password.required' => 'required_old_password',
            'new_password.required' => 'required_new_password',
            'new_password.regex' => 'password_format'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            $errors = $validator->errors();
            
            if ($errors->has('current_password')) {
                return $this->sendError('required_old_password');
            }
            
            if ($errors->has('new_password')) {
                if ($request->new_password) {
                    return $this->sendError('password_format');
                }
                return $this->sendError('required_new_password');
            }
            
            return $this->sendError('validation_error');
        }

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendError('invalid_old_credentials');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendSuccess(['message' => __('auth.password_changed')]);
    }

    public function forgotPassword(Request $request)
    {
        $rules = [
            'phone' => [
                'required',
                'string',
                'regex:/^\+84[0-9]{9}$/'
            ]
        ];

        $messages = [
            'phone.required' => 'required_phone',
            'phone.regex' => 'invalid_phone'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            $errors = $validator->errors();
            
            if ($errors->has('phone')) {
                if (!$request->phone) {
                    return $this->sendError('required_phone');
                }
                return $this->sendError('invalid_phone');
            }
            
            return $this->sendError('validation_error');
        }

        list($exceeded, $count) = $this->OtpLimitDay($request->phone);
        if ($exceeded) {
            return response()->json([
                'status' => 0,
                'message' => __('Bạn đã gửi yêu cầu quá 3 lần trong 24 giờ. Vui lòng thử lại vào ngày mai.')
            ]);
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return $this->sendError('user_not_found');
        }

        // Generate and send OTP
        try {
            $otp = in_array($request->phone, $this->testPhoneNumbers) ? '123456' : rand(100000, 999999);
            
            // Store OTP in user meta
            $user->addMeta('phone_verify_code', $otp);
            $user->addMeta('phone_verify_code_timestamp', Carbon::now()->format('Y-m-d H:i:s'));
            
            // Update OTP request counter
            $this->updateOtpRequestCounter($request->phone);

            // Send SMS using ZaloSetting and SmsHelper
            if (!in_array($request->phone, $this->testPhoneNumbers)) {
                try {
                    $zaloSetting = ZaloSetting::first();
                    if (!$zaloSetting || empty($zaloSetting->zalo_access_token)) {
                        return $this->sendError('zalo_config_not_found');
                    }
                    
                    $response = SmsHelper::send(
                        $request->phone, 
                        $otp, 
                        $zaloSetting->zalo_access_token
                    );
                    
                    if (isset($response->error) && $response->error !== 0) {
                        \Log::channel('sms')->error("OTP sending failed: " . json_encode($response));
                    }
                } catch (\Exception $e) {
                    \Log::channel('sms')->error("OTP sending failed: " . $e->getMessage());
                }
            }

            return $this->sendSuccess([
                'message' => __('auth.otp_sent'),
                'user_id' => $user->id,
                'otp' => $otp
            ]);

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
    public function forgotPasswordEmail(Request $request)
    {
        $rules = [
            'email' => 'required|email|exists:users,email', // Giữ nguyên validation
        ];

        $messages = [
            'email.required' => 'required_email',
            'email.email' => 'invalid_email_format',
            'email.exists' => 'email_not_found', // Giữ nguyên lỗi tùy chỉnh
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('email')) {
                if ($errors->first('email') === 'email_not_found') {
                    return $this->sendError('email_not_found');
                }
                 if ($errors->first('email') === 'invalid_email_format') {
                    return $this->sendError('invalid_email_format');
                }
                return $this->sendError('required_email');
            }
            return $this->sendError('validation_error', ['errors' => $validator->errors()->toArray()]);
        }

        // --- Rate Limiting (Giữ hoặc comment tùy nhu cầu test) ---
        // list($exceeded, $count) = $this->EmailOtpLimitDay($request->email);
        // if ($exceeded) {
        //     return response()->json([
        //         'status' => 0,
        //         'message' => __('Bạn đã gửi yêu cầu OTP qua email quá 3 lần trong 24 giờ. Vui lòng thử lại vào ngày mai.')
        //     ], 429);
        // }
        // --- End Rate Limiting ---

        $user = User::where('email', $request->email)->first();
        // Validation đã đảm bảo user tồn tại
        if (!$user) {
            // Dòng này không nên được chạy tới nếu validation hoạt động
            return $this->sendError('email_not_found');
        }

        // --- CHẾ ĐỘ TẠM THỜI: BỎ QUA VIỆC GỬI MAIL THỰC TẾ ---
        try {
            // Tạo OTP giả hoặc cố định để lưu vào meta (tùy chọn, có thể không cần nếu màn OTP dùng mã cứng)
            $otp = '123456'; // Mã OTP cố định cho mục đích test

            // Lưu OTP *cố định* vào user meta (để hàm resetPasswordEmail có thể kiểm tra nếu cần)
            $user->addMeta('email_reset_otp', $otp);
            $user->addMeta('email_reset_otp_timestamp', Carbon::now()->format('Y-m-d H:i:s'));

            // --- Cập nhật bộ đếm OTP Email (Giữ hoặc comment tùy nhu cầu test) ---
            // $this->updateEmailOtpRequestCounter($request->email);
            // --- End Update Counter ---

            // --- VÔ HIỆU HÓA GỬI EMAIL ---
            /*
            // Khối try...catch gốc để gửi mail
            try {
                 // Ensure MAIL_* variables are set in your .env file
                Mail::to($user->email)->send(new ResetPasswordOtpEmail($otp, $user)); // Pass OTP and user if needed in email template
                Log::info("Password reset OTP email sent to: " . $user->email);
            } catch (\Exception $e) {
                 Log::error("Failed to send password reset OTP email to {$user->email}: " . $e->getMessage());
                 // Trong chế độ tạm thời, chúng ta bỏ qua lỗi gửi mail này và vẫn tiếp tục
                 // return $this->sendError('mail_send_failed'); // KHÔNG trả về lỗi ở đây trong chế độ tạm thời
                 Log::warning("TEMP MODE: Ignored mail sending failure for {$user->email}: " . $e->getMessage());
            }
            */
            // Ghi log thay thế cho việc gửi mail
            Log::info("TEMP MODE: Skipped sending password reset OTP email to: " . $user->email . " (OTP would be: " . $otp . ")");
            // --- KẾT THÚC VÔ HIỆU HÓA GỬI EMAIL ---

            // Trả về response thành công, báo rằng email tồn tại và gửi user_id
            // Flutter sẽ dùng user_id này và chuyển sang màn hình OTP
            return $this->sendSuccess([
                // Có thể dùng message khác để biết là đang ở chế độ test
                'message' => __('auth.email_exists_proceed_to_otp'), // Thêm translation key này nếu muốn
                'user_id' => $user->id,
                 // Bạn có thể bỏ dòng otp này nếu Flutter tự biết dùng '123456'
                // 'test_otp' => $otp
            ]);

        } catch (\Exception $e) {
            // Bắt các lỗi không mong muốn khác (ví dụ: lỗi khi lưu meta)
            Log::error("Error during forgotPasswordEmail (TEMP MODE) for {$request->email}: " . $e->getMessage());
            return $this->sendError('server_error'); // Lỗi server chung
        }
        // --- KẾT THÚC CHẾ ĐỘ TẠM THỜI ---
    }

    // public function forgotPasswordEmail(Request $request)
    // {
    //     $rules = [
    //         'email' => 'required|email|exists:users,email', // Ensure email exists
    //     ];

    //     $messages = [
    //         'email.required' => 'required_email',
    //         'email.email' => 'invalid_email_format',
    //         'email.exists' => 'email_not_found', // Custom error for non-existent email
    //     ];

    //     $validator = Validator::make($request->all(), $rules, $messages);

    //     if ($validator->fails()) {
    //         $errors = $validator->errors();
    //         if ($errors->has('email')) {
    //              // Prioritize specific errors
    //             if ($errors->first('email') === 'email_not_found') {
    //                 return $this->sendError('email_not_found');
    //             }
    //              if ($errors->first('email') === 'invalid_email_format') {
    //                 return $this->sendError('invalid_email_format');
    //             }
    //             return $this->sendError('required_email'); 
    //         }
    //         return $this->sendError('validation_error', ['errors' => $validator->errors()->toArray()]);
    //     }
    // // --- Rate Limiting (Adapt from phone OTP logic) ---
    //     // You should implement similar rate limiting for email to prevent abuse
    //     // Example (you'll need to create these helper methods):
    //     // list($exceeded, $count) = $this->EmailOtpLimitDay($request->email);
    //     // if ($exceeded) {
    //     //     return response()->json([
    //     //         'status' => 0,
    //     //         'message' => __('Bạn đã gửi yêu cầu OTP qua email quá 3 lần trong 24 giờ. Vui lòng thử lại vào ngày mai.')
    //     //     ], 429); // 429 Too Many Requests
    //     // }
    //     // --- End Rate Limiting ---

    //     $user = User::where('email', $request->email)->first();
    //     // Check again just in case (though 'exists' rule should cover it)
    //     if (!$user) {
    //         return $this->sendError('email_not_found');
    //     }

    //     // Generate OTP
    //     try {
    //         $otp = (string) rand(100000, 999999); // Simple 6-digit OTP

    //         // Store OTP in user meta (using different keys than phone OTP)
    //         $user->addMeta('email_reset_otp', $otp);
    //         $user->addMeta('email_reset_otp_timestamp', Carbon::now()->format('Y-m-d H:i:s'));

    //         // --- Update Email OTP Request Counter (Adapt from phone) ---
    //         // $this->updateEmailOtpRequestCounter($request->email);
    //         // --- End Update Counter ---

    //         // --- Send Email ---
    //         try {
    //              // Ensure MAIL_* variables are set in your .env file
    //             Mail::to($user->email)->send(new ResetPasswordOtpEmail($otp, $user)); // Pass OTP and user if needed in email template
    //             Log::info("Password reset OTP email sent to: " . $user->email); 
    //         } catch (\Exception $e) {
    //              Log::error("Failed to send password reset OTP email to {$user->email}: " . $e->getMessage());
    //              // Don't necessarily reveal failure details to the user, but log it
    //              // Maybe return a generic error, or proceed but log the mail failure
    //              // For critical OTPs, failing here might warrant an error response:
    //              return $this->sendError('mail_send_failed'); // Add this error code to your sendError handler
    //         }
    //         // --- End Send Email ---

    //         // Return success response *without* the OTP for security
    //         return $this->sendSuccess([
    //             'message' => __('auth.otp_sent_to_email'), // Add this translation key
    //             'user_id' => $user->id, // Send user_id if needed by the frontend flow
    //             // 'otp' => $otp // DO NOT SEND OTP IN RESPONSE FOR EMAIL
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error("Error during forgotPasswordEmail for {$request->email}: " . $e->getMessage());
    //         return $this->sendError('server_error'); // Generic server error
    //     }
    // }
    public function resetPassword(Request $request)
    {
        // Check if request is empty
        if (empty($request->all())) {
            return $this->sendError('empty_data');
        }

        $rules = [
            'phone' => [
                'required',
                'string',
                'regex:/^\+84[0-9]{9}$/'
            ],
            // 'otp' => 'required',
            'password' => [
                'required',
                'string',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/'
            ]
        ];

        $messages = [
            'phone.required' => 'required_phone',
            'phone.regex' => 'invalid_phone',
            // 'otp.required' => 'required_otp',
            'password.required' => 'required_password',
            'password.confirmed' => 'password_not_match',
            'password.regex' => 'password_format'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            $errors = $validator->errors();
            
            if ($errors->has('phone')) {
                if (!$request->phone) {
                    return $this->sendError('required_phone');
                }
                return $this->sendError('invalid_phone');
            }

            // if ($errors->has('otp')) {
            //     return $this->sendError('required_otp');
            // }

            if ($errors->has('password')) {
                if (!$request->password) {
                    return $this->sendError('required_password');
                }
                if ($errors->has('password.confirmed')) {
                    return $this->sendError('password_not_match');
                }
                return $this->sendError('password_format');
            }
            
            return $this->sendError('validation_error');
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return $this->sendError('user_not_found');
        }

        // $storedOTP = $user->getMeta('phone_verify_code');
        // $otpTimestamp = $user->getMeta('phone_verify_code_timestamp');

        // if (!$storedOTP) {
        //     return $this->sendError('invalid_otp');
        // }

        // // Check if OTP is expired (after 5 minutes)
        // try {
        //     $otpTime = Carbon::parse($otpTimestamp);
        //     if (Carbon::now()->diffInMinutes($otpTime) > 5) {
        //         return $this->sendError('otp_expired');
        //     }
        // } catch (\Exception $e) {
        //     return $this->sendError('invalid_otp');
        // }

        // if ($storedOTP != $request->otp) {
        //     return $this->sendError('invalid_otp');
        // }

        $user->password = Hash::make($request->password);
        $user->save();

        // Remove OTP after reset password success
        $user->deleteMeta('phone_verify_code');
        $user->deleteMeta('phone_verify_code_timestamp');

        return $this->sendSuccess(['message' => __('auth.password_reset_success')]);
    }

    public function resetPasswordEmail(Request $request)
    {
        // Check if request is empty
        if (empty($request->all())) {
            return $this->sendError('empty_data');
        }

        $rules = [
            'email' => 'required|email|exists:users,email',
            // 'otp' => 'required|string|digits:6', 
            'password' => [
                'required',
                'string',
                'confirmed', // Requires 'password_confirmation' field in request
                // Consider keeping the same regex as your other password fields
                'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/'
            ]
        ];

        $messages = [
            'email.required' => 'required_email',
            'email.email' => 'invalid_email_format',
            'email.exists' => 'email_not_found', // Should not happen if OTP was sent, but good check
            // 'otp.required' => 'required_otp',
            // 'otp.digits' => 'invalid_otp_format',
            'password.required' => 'required_password',
            'password.confirmed' => 'password_not_match',
            'password.regex' => 'password_format'
            // Add other messages as needed
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            // Provide specific feedback based on validation errors
            if ($errors->has('email')) return $this->sendError($errors->first('email'));
            // if ($errors->has('otp')) return $this->sendError($errors->first('otp'));
            if ($errors->has('password')) return $this->sendError($errors->first('password'));
            // Fallback
            return $this->sendError('validation_error', ['errors' => $validator->errors()->toArray()]);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
             // Should be caught by validation, but double-check
            return $this->sendError('email_not_found');
        }

        // --- Validate OTP ---
        // $storedOTP = $user->getMeta('email_reset_otp');
        // $otpTimestamp = $user->getMeta('email_reset_otp_timestamp');

        // if (!$storedOTP || !$otpTimestamp) {
        //     Log::warning("No OTP found for email reset attempt: " . $request->email);
        //     return $this->sendError('invalid_otp'); // Or 'otp_expired' if you prefer generic
        // }

        // // Check OTP expiry (e.g., 10 minutes)
        // try {
        //     $otpTime = Carbon::parse($otpTimestamp);
        //     if (Carbon::now()->diffInMinutes($otpTime) > 10) { // Adjust expiry time as needed
        //         Log::warning("Expired OTP used for email reset attempt: " . $request->email);
        //         // Optionally clear the expired OTP here
        //         // $user->deleteMeta('email_reset_otp');
        //         // $user->deleteMeta('email_reset_otp_timestamp');
        //         return $this->sendError('otp_expired');
        //     }
        // } catch (\Exception $e) {
        //     Log::error("Error parsing OTP timestamp for {$request->email}: " . $e->getMessage());
        //     return $this->sendError('invalid_otp'); // Treat parsing error as invalid
        // }

        // // Check OTP match
        // if ($storedOTP != $request->otp) {
        //     Log::warning("Invalid OTP used for email reset attempt: " . $request->email);
        //     // Consider adding attempt tracking here to lock accounts after too many failures
        //     return $this->sendError('invalid_otp');
        // }
        // --- End OTP Validation ---


        // --- Reset Password ---
        try {
            $user->password = Hash::make($request->password);
            // Optional: Mark email as verified if it wasn't already (though usually registered users have verified emails)
            // $user->email_verified_at = now();
            $user->save();

            // --- IMPORTANT: Invalidate the used OTP ---
            $user->deleteMeta('email_reset_otp');
            $user->deleteMeta('email_reset_otp_timestamp');
            // Optional: Also clear the rate limit counter upon success
            // $user->deleteMeta('email_otp_request_count_24h');

            Log::info("Password successfully reset via email OTP for: " . $request->email);

            // Don't usually log the user in or return tokens here.
            // Force them to log in again with the new password.
            return $this->sendSuccess(['message' => __('auth.password_reset_success')]);

        } catch (\Exception $e) {
            Log::error("Error saving new password for {$request->email}: " . $e->getMessage());
            return $this->sendError('server_error');
        }
        // --- End Reset Password ---
    }
    
    // OTP verification
    public function verifyPhoneOtp(Request $request)
    {
        // Check if request is empty
        if (empty($request->all())) {
            return $this->sendError('empty_data');
        }

        $rules = [
            'user_id' => 'required',
            'otp' => 'required',
            'device_name' => 'required|string|max:255'
        ];

        $messages = [
            'user_id.required' => 'required_user_id',
            'otp.required' => 'required_otp',
            'device_name.required' => 'required_device'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errors = $validator->errors();
            
            if ($errors->has('user_id')) {
                return $this->sendError('required_user_id');
            }
            
            if ($errors->has('otp')) {
                return $this->sendError('required_otp');
            }

            if ($errors->has('device_name')) {
                return $this->sendError('required_device');
            }
            
            return $this->sendError('validation_error');
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return $this->sendError('user_not_found');
        }

        $otp = $user->getMeta('phone_verify_code');
        $otpTimestamp = $user->getMeta('phone_verify_code_timestamp');

        if (!$otp || !$otpTimestamp) {
            return $this->sendError('invalid_otp');
        }

        // Check if OTP is expired (after 5 minutes)
        $otpTime = Carbon::createFromFormat('Y-m-d H:i:s', $otpTimestamp);
        if (Carbon::now()->diffInMinutes($otpTime) > 5) {
            return $this->sendError('otp_expired');
        }

        if ($request->otp != $otp) {
            return $this->sendError('invalid_otp');
        }

        $user->phone_verified = 1;
        $user->registration_status = 'completed';
        $user->save();

        \App\User::where('id', $user->id)->update([
            'phone_verified' => 1,
            'registration_status' => 'completed'
        ]);

        DB::statement("UPDATE users SET registration_status = 'completed', phone_verified = 1 WHERE id = ?", [$user->id]);

        return $this->sendSuccess([
            'message' => __('auth.phone_verified'),
            'user_id' => $user->id,
            'access_token' => $user->createToken($request->device_name)->plainTextToken
        ]);
    }

    // Resend OTP
    public function resendOTP(Request $request)
    {
        try {
            $rules = [
                'phone' => [
                    'required',
                    'string',
                    'regex:/^\+84[0-9]{9}$/'
                ]
            ];

            $messages = [
                'phone.required' => 'required_phone',
                'phone.regex' => 'invalid_phone'
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            
            if ($validator->fails()) {
                $errors = $validator->errors();
                
                if ($errors->has('phone')) {
                    if (!$request->phone) {
                        return $this->sendError('required_phone');
                    }
                    return $this->sendError('invalid_phone');
                }
                
                return $this->sendError('validation_error');
            }

            if ($this->OtpLimitMinute($request->phone)) {
                return response()->json([
                    'status' => 0,
                    'message' => __('Vui lòng đợi ít nhất 1 phút trước khi yêu cầu OTP mới')
                ]);
            }
            
            list($exceeded, $count) = $this->OtpLimitDay($request->phone);
            if ($exceeded) {
                return response()->json([
                    'status' => 0,
                    'message' => __('Bạn đã vượt quá giới hạn gửi OTP trong 24 giờ. Vui lòng thử lại sau.')
                ]);
            }

            $otp = in_array($request->phone, $this->testPhoneNumbers) ? '123456' : mt_rand(100000, 999999);
            
            $user = User::where('phone', $request->phone)->first();
            
            if ($user) {
                $user->addMeta('phone_verify_code', $otp);
                $user->addMeta('phone_verify_code_timestamp', Carbon::now()->format('Y-m-d H:i:s'));
                
                $this->updateOtpRequestCounter($request->phone);
            }

            if (!in_array($request->phone, $this->testPhoneNumbers)) {
                try {
                    $zaloSetting = ZaloSetting::first();
                    if (!$zaloSetting || empty($zaloSetting->zalo_access_token)) {
                        \Log::error("Zalo configuration not found or empty access token");
                        return $this->sendError('zalo_config_not_found');
                    }
                    
                    $response = SmsHelper::send(
                        $request->phone, 
                        $otp, 
                        $zaloSetting->zalo_access_token
                    );
                    
                    if (isset($response->error) && $response->error !== 0) {
                        $errorMsg = json_encode($response);
                        \Log::channel('sms')->error("OTP sending failed: " . $errorMsg);
                        return response()->json([
                            'status' => 0,
                            'message' => __('Không thể gửi OTP: ') . (isset($response->message) ? $response->message : 'Lỗi không xác định'),
                            'debug_info' => $errorMsg
                        ]);
                    }
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    \Log::channel('sms')->error("SMS sending failed: " . $errorMsg);
                    return response()->json([
                        'status' => 0,
                        'message' => __('Không thể gửi OTP qua SMS. Vui lòng thử lại sau.'),
                        'debug_info' => $errorMsg
                    ]);
                }
            }

            session(['phone_otp' => [
                'phone' => $request->phone,
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5)
            ]]);

            return $this->sendSuccess([
                'message' => __('auth.otp_sent'),
                'otp' => $otp  
            ]);

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            \Log::error("Resend OTP failed with error: " . $errorMsg);
            return response()->json([
                'status' => 0,
                'message' => __('Lỗi hệ thống khi gửi OTP. Vui lòng thử lại sau.'),
                'debug_info' => $errorMsg
            ]);
        }
    }

    public function setPassword(Request $request)
    {
        $rules = [
            'user_id' => 'required',
            'password' => [
                'required',
                'string',
                'regex:/^(?=.*[A-Z])(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/'
            ],
            'device_name' => 'required'
        ];

        $messages = [
            'user_id.required' => 'required_user_id',
            'password.required' => 'required_password',
            'password.regex' => 'password_format',
            'device_name.required' => 'required_device'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        
        if ($validator->fails()) {
            $errors = $validator->errors();
            
            if ($errors->has('user_id')) {
                return $this->sendError('required_user_id');
            }
            
            if ($errors->has('password')) {
                if ($request->password) {
                    return $this->sendError('password_format');
                }
                return $this->sendError('required_password');
            }

            if ($errors->has('device_name')) {
                return $this->sendError('required_device');
            }
            
            return $this->sendError('validation_error');
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return $this->sendError('user_not_found');
        }

        if (!$user->phone_verified) {
            return $this->sendError('phone_not_verified');
        }

        $user->password = Hash::make($request->password);
        $user->registration_status = 'completed';
        $user->save();

        return $this->sendSuccess([
            'message' => __('auth.password_set_success'),
            'user' => new UserResource($user),
            'access_token' => $user->createToken($request->device_name)->plainTextToken
        ]);
    }

    protected function OtpLimitMinute($phone)
    {
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return false;
        }

        $lastOtpTime = $user->getMeta('phone_verify_code_timestamp');
        if (!$lastOtpTime) {
            return false; 
        }

        return Carbon::now()->diffInSeconds(Carbon::parse($lastOtpTime)) < 60;
    }

    protected function OtpLimitDay($phone)
    {
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return [false, 0]; 
        }

        $countMeta = $user->getMeta('otp_request_count_24h');
        $lastOtpTime = $user->getMeta('phone_verify_code_timestamp');
        
        if (!$lastOtpTime || !$countMeta) {
            return [false, 0];
        }
        
        $hoursSinceLastOtp = Carbon::now()->diffInHours(Carbon::parse($lastOtpTime));
        
        if ($hoursSinceLastOtp >= 24) {
            return [false, 0];
        }
        
        $count = (int)$countMeta;
        return [$count >= 3, $count];
    }

    protected function updateOtpRequestCounter($phone)
    {
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return; 
        }
        
        $lastOtpTime = $user->getMeta('phone_verify_code_timestamp');
        $countMeta = $user->getMeta('otp_request_count_24h');
        
        if (!$lastOtpTime || Carbon::now()->diffInHours(Carbon::parse($lastOtpTime)) >= 24) {
            $newCount = 1;
        } else {
            $newCount = $countMeta ? (int)$countMeta + 1 : 1;
        }
        
        $user->addMeta('otp_request_count_24h', $newCount);
    }

    protected function RegisterLimitDay($phone)
    {
        $oneDayAgo = Carbon::now()->subDay();
        
        $pendingCount = DB::table('users')
            ->where('phone', $phone)
            ->where('registration_status', 'pending')
            ->where('created_at', '>=', $oneDayAgo)
            ->count();
        
        $deletedPendingCount = DB::table('users')
            ->where('phone', $phone)
            ->where('registration_status', 'pending')
            ->where('deleted_at', '>=', $oneDayAgo)
            ->where('deleted_at', '!=', null)
            ->count();
        
        $totalPendingCount = $pendingCount + $deletedPendingCount;
        
        return $totalPendingCount > 3;
    }
}