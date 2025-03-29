<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ZaloSetting extends Model
{
protected $table    = 'zalo_settings';

    protected $fillable = [
        'zalo_app_id',      
        'zalo_app_secret',  
        'zalo_refresh_token', 
        'zalo_access_token', 
        'note'              
    ];
} 