<?php

namespace Modules\User\Traits;

trait HasRank
{
    public function getRankAttribute()
    {
        $points = (int)$this->points;
        
        if ($points < 100) {
            return 'Bronze';
        } elseif ($points < 300) {
            return 'Silver';
        } elseif ($points < 500) {
            return 'Gold';
        } else {
            return 'Diamond';
        }
    }
} 