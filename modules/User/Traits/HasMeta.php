<?php

namespace Modules\User\Traits;

trait HasMeta
{
    public function deleteMeta($key)
    {
        return $this->updateMeta($key, null);
    }
} 