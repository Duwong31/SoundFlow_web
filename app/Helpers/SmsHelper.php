<?php

namespace App\Helpers;

class SmsHelper
{
    public static function send($receiver, $otp , $zalo_access_token)
    {
        $response = self::zalo($receiver, $otp , $zalo_access_token);
        
        /*$smsSetting = SmsSetting::first();
        $config = self::get_settings('twilio_sms');
        if (isset($config) && $config['status'] == 1) {
            $response = self::zalo($receiver, $otp);
            return $response;
        }*/

        /*$config = self::get_settings('nexmo_sms');
        if (isset($config) && $config['status'] == 1) {
            $response = self::nexmo($receiver, $otp);
            return $response;
        }

        $config = self::get_settings('2factor_sms');
        if (isset($config) && $config['status'] == 1) {
            $response = self::two_factor($receiver, $otp);
            return $response;
        }

        $config = self::get_settings('msg91_sms');
        if (isset($config) && $config['status'] == 1) {
            $response = self::msg_91($receiver, $otp);
            return $response;
        }*/

        return json_decode($response);
    }

    public static function zalo($receiver, $otp, $zalo_access_token )
    {
        $url = "https://business.openapi.zalo.me/message/template";
        $header = array(
            "content-type: application/json",
            "access_token: " .  $zalo_access_token . "",
        );
        $postdata = '{
            "phone" : "' . $receiver . '",
            "template_id": "414344",
            "template_data" : {
                "otp":"' . $otp . '",
            },
        }';

        $ch = curl_init();
        $timeout = 120;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // Get URL content
        $result = curl_exec($ch);
        // close handle to release resources
        curl_close($ch);

        return $result;
        
        
    }

    public static function twilio($receiver, $otp)
    {
        $config = self::get_settings('twilio_sms');
        $response = 'error';

        if (isset($config) && $config['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            $sid = $config['sid'];
            $token = $config['token'];
            try {
                $twilio = new Client($sid, $token);
                $twilio->messages
                    ->create($receiver, // to
                        array(
                            "messagingServiceSid" => $config['messaging_service_id'],
                            "body" => $message
                        )
                    );
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        } elseif (empty($config)) {
            DB::table('business_settings')->updateOrInsert(['key' => 'twilio_sms'], [
                'key' => 'twilio_sms',
                'value' => json_encode([
                    'status' => 0,
                    'sid' => '',
                    'token' => '',
                    'from' => '',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $response;
    }

    public static function nexmo($receiver, $otp)
    {
        $sms_nexmo = self::get_settings('nexmo_sms');
        $response = 'error';
        if (isset($sms_nexmo) && $sms_nexmo['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $sms_nexmo['otp_template']);
            try {
                $config = [
                    'api_key' => $sms_nexmo['api_key'],
                    'api_secret' => $sms_nexmo['api_secret'],
                    'signature_secret' => '',
                    'private_key' => '',
                    'application_id' => '',
                    'app' => ['name' => '', 'version' => ''],
                    'http_client' => ''
                ];
                Config::set('nexmo', $config);
                Nexmo::message()->send([
                    'to' => $receiver,
                    'from' => $sms_nexmo['from'],
                    'text' => $message
                ]);
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        } elseif (empty($config)) {
            DB::table('business_settings')->updateOrInsert(['key' => 'nexmo_sms'], [
                'key' => 'nexmo_sms',
                'value' => json_encode([
                    'status' => 0,
                    'api_key' => '',
                    'api_secret' => '',
                    'signature_secret' => '',
                    'private_key' => '',
                    'application_id' => '',
                    'from' => '',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $response;
    }

    // public static function two_factor($receiver, $otp)
    // {
    //     $config = self::get_settings('2factor_sms');
    //     $response = 'error';
    //     if (isset($config) && $config['status'] == 1) {
    //         $api_key = $config['api_key'];
    //         $curl = curl_init();
    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => "https://2factor.in/API/V1/" . $api_key . "/SMS/" . $receiver . "/" . $otp . "",
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => "",
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 30,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => "GET",
    //         ));
    //         $response = curl_exec($curl);
    //         $err = curl_error($curl);
    //         curl_close($curl);

    //         if (!$err) {
    //             $response = 'success';
    //         } else {
    //             $response = 'error';
    //         }
    //     } elseif (empty($config)) {
    //         DB::table('business_settings')->updateOrInsert(['key' => '2factor_sms'], [
    //             'key' => '2factor_sms',
    //             'value' => json_encode([
    //                 'status' => 0,
    //                 'api_key' => 'aabf4e9c-f55f-11eb-85d5-0200cd936042',
    //             ]),
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);
    //     }
    //     return $response;
    // }

    // public static function msg_91($receiver, $otp)
    // {
    //     $config = self::get_settings('msg91_sms');
    //     $response = 'error';
    //     if (isset($config) && $config['status'] == 1) {
    //         $receiver = str_replace("+", "", $receiver);
    //         $curl = curl_init();
    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => "https://api.msg91.com/api/v5/otp?template_id=" . $config['template_id'] . "&mobile=" . $receiver . "&authkey=" . $config['authkey'] . "",
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => "",
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 30,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => "GET",
    //             CURLOPT_POSTFIELDS => "{\"OTP\":\"$otp\"}",
    //             CURLOPT_HTTPHEADER => array(
    //                 "content-type: application/json"
    //             ),
    //         ));
    //         $response = curl_exec($curl);
    //         $err = curl_error($curl);
    //         curl_close($curl);
    //         if (!$err) {
    //             $response = 'success';
    //         } else {
    //             $response = 'error';
    //         }
    //     } elseif (empty($config)) {
    //         DB::table('business_settings')->updateOrInsert(['key' => 'msg91_sms'], [
    //             'key' => 'msg91_sms',
    //             'value' => json_encode([
    //                 'status' => 0,
    //                 'template_id' => '',
    //                 'authkey' => '',
    //             ]),
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);
    //     }
    //     return $response;
    // }

    public static function get_settings($name)
    {
        $config = null;
        $data = BusinessSetting::where(['key' => $name])->first();
        if (isset($data)) {
            $config = json_decode($data['value'], true);
            if (is_null($config)) {
                $config = $data['value'];
            }
        }
        return $config;
    }
}
