<?php
/**
 * Created by PhpStorm.
 * User: h2 gaming
 * Date: 8/9/2019
 * Time: 11:56 PM
 */
namespace Modules\Core\Models;

use App\BaseModel;

class NotificationPush extends BaseModel
{
    protected $table = 'notifications';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'for_admin',
        'created_at',
        'updated_at'
    ];

    public $timestamps = true;
    
    // Disable auto-adding create_user
    public static function boot()
    {
        parent::boot();
        static::creating(function($model) {
            // Override to prevent adding create_user
        });
    }
}
