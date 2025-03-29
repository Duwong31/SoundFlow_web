<?php
namespace Modules\Feedback;

use Illuminate\Support\ServiceProvider;
use Modules\ModuleServiceProvider;
use Modules\Core\Helpers\SitemapHelper;

class ModuleProvider extends ModuleServiceProvider
{
    public function boot(SitemapHelper $sitemapHelper)
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Views', 'Feedback');
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public static function getAdminMenu()
    {
        return [
            'feedback' => [
                "position" => 56,
                'url' => 'admin/feedback',
                'title' => __('Feedback'),
                'icon' => 'icon ion-md-chatboxes',
                'permission' => 'feedback_view',
                // 'children' => [
                //     'all_feedback' => [
                //         'url' => 'admin/feedback',
                //         'title' => __('All Feedbacks'),
                //         'permission' => 'feedback_view',
                //     ],
                //     'feedback_settings' => [
                //         'url' => 'admin/feedback/settings',
                //         'title' => __('Settings'),
                //         'permission' => 'feedback_manage',
                //     ]
                // ]
            ]
        ];
    }
} 