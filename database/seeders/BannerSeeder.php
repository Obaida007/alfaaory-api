<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::firstOrCreate(['title' => 'أهلًا بك في متجر الفاعوري'], [
            'body' => 'اشحن ألعابك واشتراكاتك فورًا من رصيد محفظتك.',
            'placement' => 'home_hero',
            'is_active' => true,
        ]);

        Banner::firstOrCreate(['title' => 'شحن ببجي تلقائي'], [
            'body' => 'شدات PUBG GLOBAL تصلك خلال ثوانٍ عبر معرّف اللاعب.',
            'link' => '/categories/pubg-global-auto',
            'placement' => 'home_popup',
            'is_active' => true,
        ]);
    }
}
