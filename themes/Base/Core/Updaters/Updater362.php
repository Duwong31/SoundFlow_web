<?php

namespace Themes\Base\Core\Updaters;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Updater362
{


    public static function run()
    {
        $version = '1.0';
        if (version_compare(setting_item('update_to_362'), $version, '>=')) return;

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        if(Schema::hasTable('bravo_tours'))
        {
            Schema::table('bravo_tours',function(Blueprint $blueprint){
                if(!Schema::hasColumn('bravo_tours','date_select_type')){
                    $blueprint->string('date_select_type')->nullable();
                }
            });
        }
        
        // Run Update
        Artisan::call('cache:clear');

        setting_update_item('update_to_362', $version);
    }
}
