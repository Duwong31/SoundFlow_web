<?php

namespace Modules\Location\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'map_lat' => $this->map_lat,
            'map_lng' => $this->map_lng,
        ];
    }
}