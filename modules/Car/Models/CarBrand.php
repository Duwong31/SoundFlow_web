<?php

namespace Modules\Car\Models;

use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarBrand extends BaseModel
{
    protected $table = 'car_brands';
    
    protected $fillable = [
        'name',
        'slug', 
        'logo_id',
        'content',
        'status'
    ];

    /**
     * Get cars associated with this brand
     */
    public function cars()
    {
        return $this->hasMany(Car::class, 'brand_id');
    }
    
    /**
     * Get the logo image URL
     */
    public function getLogo($size = 'thumb')
    {
        $url = get_file_url($this->logo_id, $size);
        return $url ? $url : asset('images/placeholder.png');
    }
}
