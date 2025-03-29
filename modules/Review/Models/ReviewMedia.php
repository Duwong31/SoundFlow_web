<?php
namespace Modules\Review\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewMedia extends Model
{
    protected $table = 'bravo_review_media';
    
    protected $fillable = [
        'review_id',
        'media_id',
        'media_type',
    ];
    
    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id');
    }
    
    public function media()
    {
        return $this->belongsTo(\Modules\Media\Models\MediaFile::class, 'media_id');
    }
} 