<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LevelSeeder::class,
            CategorySeeder::class,
            PaymentMethodSeeder::class,
            AgentSeeder::class,
            SettingSeeder::class,
            BannerSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
