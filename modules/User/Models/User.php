<?php
namespace Modules\User\Models;

use Modules\Agency\Models\Agency;
use Modules\Agency\Models\AgencyAgent;
use Modules\User\Traits\HasRank;
use Modules\Media\Models\MediaFile; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function avatar(): BelongsTo
    {
        // Sử dụng lớp MediaFile và khóa ngoại avatar_id
        // withDefault trả về một đối tượng MediaFile rỗng nếu không tìm thấy
        // để tránh lỗi khi truy cập thuộc tính của $user->avatar
        return $this->belongsTo(MediaFile::class, 'avatar_id', 'id')->withDefault();
    }

     /**
     * Lấy URL của avatar, sử dụng FileHelper
     *
     * @param string $size Kích thước mong muốn (ví dụ: 'thumb', 'medium')
     * @return string|false URL của avatar hoặc false nếu không có
     */
    public function getAvatarUrlAttribute(string $size = 'thumb')
    {
        if ($this->avatar_id) {
            return \Modules\Media\Helpers\FileHelper::url($this->avatar_id, $size);
        }
        // Trả về URL avatar mặc định nếu cần
        // return asset('images/default-avatar.png');
        return false; // Hoặc null
    }
}
