<?php
namespace Modules\Coupon;
use Modules\Core\Helpers\SitemapHelper;
use Modules\ModuleServiceProvider;
use Modules\User\Helpers\PermissionHelper;

class ModuleProvider extends ModuleServiceProvider
{

    public function boot(SitemapHelper $sitemapHelper){

        $this->loadMigrationsFrom(__DIR__ . '/Migrations');


        PermissionHelper::add([
            //Coupon
            'coupon_view',
            'coupon_create',
            'coupon_update',
            'coupon_delete',
        ]);

    }
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouterServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        return [
            'coupon'=>[
                "position"=>51,
                'url'        => route('coupon.admin.index'),
                'title'      => __('Coupon'),
                'icon'       => 'fa fa-ticket',
                'permission' => 'coupon_view',
            ],
        ];
    }
    public static function getUserMenu()
    {
        $res['coupon'] = [
            'url'   => route('coupon.vendor.index'),
            'title'      => __("Coupon"),
            'icon'       => "fa fa-ticket",
            'position'   => 80,
            'permission' => 'coupon_view',
        ];
        return $res;
    }
}
