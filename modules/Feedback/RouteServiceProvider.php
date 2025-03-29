<?php
namespace Modules\Feedback;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleNamespace = 'Modules\Feedback';

    public function boot()
    {
        parent::boot();
        
        if(file_exists(__DIR__.'/Helpers/helper.php')) {
            require_once __DIR__.'/Helpers/helper.php';
        }
    }

    public function map()
    {
        $this->mapWebRoutes();
        $this->mapAdminRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/Routes/web.php');
    }

    protected function mapAdminRoutes()
    {
        Route::middleware(['web', 'dashboard'])
            ->namespace($this->moduleNamespace . '\Admin')
            ->prefix(config('admin.admin_route_prefix') ?? 'admin')
            ->group(__DIR__ . '/Routes/admin.php');
    }
    
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/Routes/api.php');
    }
} 