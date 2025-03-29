<?php
namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Theme\ThemeManager;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $active_theme = ThemeManager::current();
        $active_theme = strtolower($active_theme);
        $active_theme = ($active_theme == "bc" || $active_theme == "modmix") ? "bookingcore" : $active_theme;

        $customer1 = User::forceCreate([
            'first_name'        => 'Customer',
            'last_name'         => '01',
            'email'             => 'customer1@'.$active_theme.'.test',
            'password'          => bcrypt(md5(rand())),
            'phone'             => '112 666 888',
            'status'            => 'publish',
            'city'            => 'New York',
            'country'            => 'US',
            'created_at'        => date("Y-m-d H:i:s"),
            'email_verified_at' => date("Y-m-d H:i:s"),
            'bio' => 'We\'re designers who have fallen in love with creating spaces for others to reflect, reset, and create. We split our time between two deserts (the Mojave, and the Sonoran). We love the way the heat sinks into our bones, the vibrant sunsets, and the wildlife we get to call our neighbors.',
            "need_update_pw" => !is_demo_mode() ? 1 : 0
        ]);
        $customer1->assignRole('customer');
        $vendors = [
            [
                'Vendor',
                '01'
            ],
            [
                'Elise',
                'Aarohi'
            ],
            [
                'Kaytlyn',
                'Alvapriya'
            ],
            [
                'Lynne',
                'Victoria'
            ]
        ];
        foreach ($vendors as $k => $v) {
            $vendor = User::forceCreate([
                'first_name'        => $v[0],
                'last_name'         => $v[1],
                'email'             => strtolower($v[1]) . '@'.$active_theme.'.test',
                'password'          => bcrypt(md5(rand())),
                'phone'             => '112 666 888',
                'status'            => 'publish',
                'city'            => 'New York',
                'country'            => 'US',
                'created_at'        => date("Y-m-d H:i:s"),
                'email_verified_at' => date("Y-m-d H:i:s"),
                'bio' => 'We\'re designers who have fallen in love with creating spaces for others to reflect, reset, and create. We split our time between two deserts (the Mojave, and the Sonoran). We love the way the heat sinks into our bones, the vibrant sunsets, and the wildlife we get to call our neighbors.',
                "need_update_pw" => !is_demo_mode() ? 1 : 0
            ]);
            $vendor->assignRole('vendor');
        }
    }
}
