<?php
namespace Modules\User\Models;

use Modules\Agency\Models\Agency;
use Modules\Agency\Models\AgencyAgent;
use Modules\User\Traits\HasRank;

class User extends \App\User
{
    use HasRank;

    protected $appends = ['rank'];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            // Generate referral code if not set
            if (!$user->referral_code) {
                $user->referral_code = self::generateReferralCode();
            }
        });
    }

    protected static function generateReferralCode()
    {
        do {
            // Generate 3 uppercase letters and 4 random numbers
            $letters = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
            $numbers = rand(1000, 9999);
            $code = $letters . $numbers;
        } while (static::where('referral_code', $code)->exists());
        
        return $code;
    }

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'birthday',
        'address',
        'address2',
        'bio',
        'status',
        'avatar_id',
        'referral_code',
        'wallet_balance',
        'points'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'points' => 'integer',
        'wallet_balance' => 'float'
    ];

    public function getPointsAttribute($value)
    {
        \Log::info('Points value: ' . $value);
        return (int)$value;
    }

    public function getRankAttribute()
    {
        \Log::info('Getting rank for user with points: ' . $this->points);
        return $this->calculateRank();
    }
}
