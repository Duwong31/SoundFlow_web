<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Model;

class UserFavorite extends Model
{
    protected $table = 'user_favorite';
    protected $fillable = ['user_id', 'song_ids']; // Chỉnh sửa fillable
    public $timestamps = true;

    // Cast song_ids thành mảng
    protected $casts = [
        'song_ids' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}