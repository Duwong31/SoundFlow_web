<?php
namespace Modules\Car\Models;

use App\BaseModel;
use Modules\Core\Models\Terms;
use Modules\Core\Models\Attributes;

class CarTerm extends BaseModel
{
    protected $table = 'bravo_car_term';
    protected $fillable = [
        'term_id',
        'target_id'
    ];

    public function term()
    {
        return $this->belongsTo(Terms::class, 'term_id');
    }

    public function attribute()
    {
        return $this->belongsTo(Attributes::class, 'attr_id');
    }
}