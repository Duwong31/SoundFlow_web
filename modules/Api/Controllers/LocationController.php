<?php
namespace Modules\Api\Controllers;
use App\Http\Controllers\Controller;
use Modules\Location\Models\Location;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function search(){
        $class = new Location();
        $rows = $class->search(request()->input());
        
        // Sắp xếp kết quả theo id
        $sorted = collect($rows->items())->sortBy('id')->values();
        
        return $this->sendSuccess([
            'total' => $rows->total(),
            'total_pages' => $rows->lastPage(),
            'data' => $sorted->map(function($row){
                return $row->dataForApi();
            }),
        ]);
    }
    
    public function detail($id = '')    {
        if(empty($id)){
            return $this->sendError(__("error.location_not_found"));
        }
        $row = Location::find($id);
        if(empty($row))
        {
            return $this->sendError(__("error.location_not_found"));
        }

        return $this->sendSuccess([
            'data'=>$row->dataForApi(true)
        ]);
    }
}
