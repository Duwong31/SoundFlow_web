<?php

namespace Modules\Car\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Location\Resources\LocationResource;

class CarResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'number' => $this->number,
            'is_instant' => $this->is_instant,
            'image_id' => $this->image_id,
            'banner_image_id' => $this->banner_image_id,
            'location_id' => $this->location_id,
            'address' => $this->address,
            'gallery' => $this->gallery,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'price_per_10_hour' => $this->price_per_10_hour,
            'sale_price_per_10_hour' => $this->sale_price_per_10_hour,
            'discount_percent' => $this->discount_percent,
            'image' => get_file_url($this->image_id),
            'review_score' => $this->review_score,
            'fuel_capacity' => $this->fuel_capacity,
            'rental_duration' => $this->rental_duration,
            'driver_type' => $this->driver_type,
            'passenger' => $this->passenger,
            'status' => $this->status,
            'distance' => $this->distance,
            'location' => $this->location ? new LocationResource($this->location) : null,
            'translation' => $this->translation,
            'has_wish_list' => $this->hasWishList,
            'brand' => $this->brand,
            'model' => $this->model,
            'transmission_type' => $this->transmission_type,
            'fuel_type' => $this->fuel_type,
            'is_featured' => $this->is_featured,
            'car_type' => $this->car_type,
            'enable_extra_price' => $this->enable_extra_price,
            'review_count' => $this->getNumberReviewsInService(),
            'is_available' => $this->when(isset($this->is_available), $this->is_available),
            'available_slots' => $this->when(isset($this->available_slots), $this->available_slots),
            'wishlist_status' => $this->wishlist_status ?? false,
        ];
    }
}