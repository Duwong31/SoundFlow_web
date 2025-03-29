<?php

namespace Modules\User\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        $birth_year = null;
        if ($this->birthday) {
            try {
                $birth_year = Carbon::parse($this->birthday)->year;
            } catch (\Exception $e) {
            }
        }

        $full_name = trim($this->first_name . ' ' . $this->last_name);

        return [
            'id' => $this->id,
            'nickname' => $this->user_name ?? '',
            'full_name' => $full_name,  
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_verified' => (bool)$this->phone_verified,
            'birth_year' => $birth_year,
            'gender' => $this->gender,
            'referral_code' => $this->referral_code,
            'wallet_balance' => (float)$this->wallet_balance,
            'points' => (int)$this->points,
            'rank' => $this->rank,
            'so_bhxh' => $this->so_bhxh,
            'facebook_linked' => !empty($this->facebook_id),
            'tiktok_linked' => !empty($this->tiktok_id),
            'current_address' => $this->address,
            'company' => $this->business_name,
            'active_status' => (bool)$this->active_status,
            'dark_mode' => (bool)$this->dark_mode,
            'messenger_color' => $this->messenger_color,
            'avatar_id' => $this->avatar_id,
            'avatar_url' => get_file_url($this->avatar_id, 'full'),
            'avatar_thumb_url' => get_file_url($this->avatar_id),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}