<?php
namespace Modules\Feedback\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Feedback extends Model
{
    protected $table = 'bravo_feedbacks';

    protected $fillable = [
        'content',
        'user_id',
        'media_ids',
        'status',
        'admin_response',
        'responded_by',
        'responded_at'
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function getMediaAttribute()
    {
        if (empty($this->media_ids)) {
            return [];
        }

        $mediaIds = json_decode($this->media_ids, true);
        $result = [];

        foreach ($mediaIds as $id) {
            $mediaFile = \Modules\Media\Models\MediaFile::find($id);
            if ($mediaFile) {
                $result[] = [
                    'id' => $id,
                    'url' => get_file_url($id),
                    'name' => $mediaFile->file_name,
                    'type' => strtolower($mediaFile->file_extension) == 'mp4' ? 'video' : 'image'
                ];
            }
        }

        return $result;
    }
} 