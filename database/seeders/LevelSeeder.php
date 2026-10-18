<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /** Thresholds above the Silver level are provisional and editable from the admin panel. */
    public function run(): void
    {
        $levels = [
            ['name' => 'البرونزي', 'min_total_purchases' => 0, 'discount_percent' => 0, 'color' => '#CD7F32'],
            ['name' => 'الفضي', 'min_total_purchases' => 500, 'discount_percent' => 1, 'color' => '#C0C0C0'],
            ['name' => 'الذهبي', 'min_total_purchases' => 2000, 'discount_percent' => 2, 'color' => '#FFD700'],
            ['name' => 'البلاتيني', 'min_total_purchases' => 5000, 'discount_percent' => 3, 'color' => '#E5E4E2'],
            ['name' => 'الألماسي', 'min_total_purchases' => 15000, 'discount_percent' => 4, 'color' => '#B9F2FF'],
            ['name' => 'الفاعوري', 'min_total_purchases' => 50000, 'discount_percent' => 5, 'color' => '#8E44AD'],
        ];

        foreach ($levels as $sort => $level) {
            Level::updateOrCreate(['name' => $level['name']], $level + ['sort' => $sort]);
        }
    }
}
