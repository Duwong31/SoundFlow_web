<?php

namespace Modules\Car\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AdminController;
use Modules\Car\Models\CarBrand;

class CarBrandController extends AdminController
{
    protected $carBrandClass;

    public function __construct(CarBrand $carBrandClass)
    {
        $this->setActiveMenu(route('car.admin.index'));
        $this->carBrandClass = $carBrandClass;
    }

    public function index(Request $request)
    {
        $this->checkPermission('car_manage_attributes');
        $query = $this->carBrandClass::query();
        
        if (!empty($search = $request->query('s'))) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }
        
        $query->orderBy('created_at', 'desc');
        
        $data = [
            'rows'        => $query->paginate(20),
            'row'         => new $this->carBrandClass(),
            'breadcrumbs' => [
                [
                    'name' => __('Car'),
                    'url'  => route('car.admin.index')
                ],
                [
                    'name'  => __('Brands'),
                    'class' => 'active'
                ],
            ]
        ];
        
        return view('Car::admin.brand.index', $data);
    }

    public function create(Request $request)
    {
        $this->checkPermission('car_manage_attributes');
        
        $row = new $this->carBrandClass();
        $row->status = 1;
        
        $data = [
            'row'          => $row,
            'breadcrumbs'  => [
                [
                    'name' => __('Car'),
                    'url'  => route('car.admin.index')
                ],
                [
                    'name' => __('Brands'),
                    'url'  => route('car.admin.brand.index')
                ],
                [
                    'name'  => __('Add Brand'),
                    'class' => 'active'
                ],
            ]
        ];
        
        return view('Car::admin.brand.detail', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('car_manage_attributes');
        
        $row = $this->carBrandClass::find($id);
        
        if (empty($row)) {
            return redirect(route('car.admin.brand.index'))->with('error', __('Brand not found!'));
        }
        
        $data = [
            'row'          => $row,
            'breadcrumbs'  => [
                [
                    'name' => __('Car'),
                    'url'  => route('car.admin.index')
                ],
                [
                    'name' => __('Brands'),
                    'url'  => route('car.admin.brand.index')
                ],
                [
                    'name'  => __('Edit Brand'),
                    'class' => 'active'
                ],
            ]
        ];
        
        return view('Car::admin.brand.detail', $data);
    }

    public function store(Request $request, $id)
    {
        $this->checkPermission('car_manage_attributes');
        
        $this->validate($request, [
            'name' => 'required'
        ]);
        
        $name = $request->input('name');
        $slug = Str::slug($name);
        $logo_id = $request->input('logo_id');
        $content = $request->input('content');
        $status = $request->input('status', 1);
        $now = now()->format('Y-m-d H:i:s');
        
        if ($id > 0) {
            // Update existing brand using direct DB query
            DB::table('car_brands')
                ->where('id', $id)
                ->update([
                    'name' => $name,
                    'slug' => $slug,
                    'logo_id' => $logo_id,
                    'content' => $content,
                    'status' => $status,
                    'updated_at' => $now
                ]);
                
            return redirect()->back()->with('success', __('Brand updated'));
        } else {
            // Create new brand using direct DB query
            DB::table('car_brands')->insert([
                'name' => $name,
                'slug' => $slug,
                'logo_id' => $logo_id,
                'content' => $content,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now
            ]);
            
            return redirect(route('car.admin.brand.index'))->with('success', __('Brand created'));
        }
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('car_manage_attributes');
        
        $ids = $request->input('ids');
        $action = $request->input('action');
        
        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', __('Select at least 1 item!'));
        }
        
        if (empty($action)) {
            return redirect()->back()->with('error', __('Select an Action!'));
        }
        
        if ($action == 'delete') {
            DB::table('car_brands')->whereIn('id', $ids)->delete();
        } else {
            DB::table('car_brands')->whereIn('id', $ids)->update(['status' => $action]);
        }
        
        return redirect()->back()->with('success', __('Updated success!'));
    }
} 