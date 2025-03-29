<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Theme\ThemeManager;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $active_theme = ThemeManager::current();
        $theme_seeder = '\\Themes\\'.ucfirst($active_theme)."\\Database\\Seeders\\DatabaseSeeder";
        if(class_exists($theme_seeder)){
            $this->call($theme_seeder);
            return;
        }

        Artisan::call('cache:clear');
        $this->call([
            RolesAndPermissionsSeeder::class,
            Language::class,
            UsersTableSeeder::class,
            MediaFileSeeder::class,
            General::class,
            LocationSeeder::class,
            News::class,
            Tour::class,
            SpaceSeeder::class,
            HotelSeeder::class,
            CarSeeder::class,
            CarBrandSeeder::class,
            EventSeeder::class,
            SocialSeeder::class,
            DemoSeeder::class,
            FlightSeeder::class,
            BoatSeeder::class,
            UpdateUserPointsAndWalletSeeder::class,
            CouponsTableSeeder::class,
            BookingsTableSeeder::class,
            NotificationSeeder::class,
            UpdateInsuranceInfo::class
        ]);

        $this->call(CarInsuranceSeeder::class);
    }
}
