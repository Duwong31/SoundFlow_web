<?php

namespace App\Console\Commands;

use App\Models\ZaloSetting;
use Illuminate\Console\Command;

class UpdateAccessTokenZalo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-access-token-zalo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates access token.';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Starting Zalo token update...');

        $zaloSettings = ZaloSetting::all();
        $url = "https://oauth.zaloapp.com/v4/oa/access_token";
        
        foreach ($zaloSettings as $zaloSetting) {
            $this->info('Processing Zalo setting ID: ' . $zaloSetting->id);
            $this->info('Current refresh token: ' . $zaloSetting->zalo_refresh_token);
            
            if(!empty($zaloSetting->zalo_refresh_token) && !empty($zaloSetting->zalo_app_secret) && !empty($zaloSetting->zalo_app_id)){
                $payload = [
                    'refresh_token' => $zaloSetting->zalo_refresh_token,
                    'app_id' => $zaloSetting->zalo_app_id,
                    'grant_type'   => 'refresh_token'
                ];
                $header = array(
                    "content-type: application/x-www-form-urlencoded",
                    "secret_key: " .  $zaloSetting->zalo_app_secret . "",
                );
                
                $this->info('Sending request to Zalo API...');
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                
                $response = curl_exec($ch);
                $this->info('Response from Zalo: ' . $response);
                
                $result = json_decode($response);
                if(property_exists($result,'error')){
                    $this->error('Error: ' . $result->error_name);
                    $zaloSetting->note = $result->error_name;
                    $zaloSetting->save();
                }else{
                    $this->info('Got new access token: ' . $result->access_token);
                    $this->info('Got new refresh token: ' . $result->refresh_token);
                    
                    $zaloSetting->zalo_access_token = $result->access_token;
                    $zaloSetting->zalo_refresh_token = $result->refresh_token;
                    $zaloSetting->note = "";
                    $zaloSetting->save();
                }
                
                curl_close($ch);
            } else {
                $this->error('Missing Zalo configuration parameters');
            }
        }
        
        $this->info('Zalo token update completed');
    }

}