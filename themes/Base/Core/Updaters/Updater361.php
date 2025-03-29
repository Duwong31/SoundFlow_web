<?php

namespace Themes\Base\Core\Updaters;

use Illuminate\Support\Facades\Artisan;

class Updater361
{


    public static function run()
    {
        $version = '1.1';
        if (version_compare(setting_item('update_to_361'), $version, '>=')) return;

        Artisan::call('migrate', [
            '--force' => true,
        ]);
        
        // Run Update
        Artisan::call('cache:clear');

        setting_update_item('update_to_361', $version);
    }
}
