<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /** @var array<string, string> slug => name */
    protected array $sections = [
        'games' => 'قسم الألعاب',
        'subscriptions' => 'عالم الاشتراكات',
        'balances' => 'قسم الأرصدة',
        'gift-cards' => 'البطاقات الإلكترونية',
        'chat' => 'قسم الدردشة',
        'numbers-accounts' => 'الأرقام والحسابات',
        'crypto' => 'العملات الرقمية',
        'money-transfers' => 'حوالات مالية',
        'social-media' => 'السوشيال ميديا',
        'software-tools' => 'أدوات السوفت وير',
        'salary-check' => 'تشييك رواتب',
    ];

    /** @var array<int, string> */
    protected array $games = [
        'PUBG MOBILE', 'FREE FIRE', 'JAWAKER', 'YALLA LUDO', '8Ball Pool', 'BLOOD STRIKE',
        'DELTA FORCE', 'MOBILE LEGENDS', 'Genshin Impact', 'VALORANT', 'Call of Duty Mobile', 'Clash of Clans',
    ];

    /** @var array<int, string> */
    protected array $subscriptions = ['SHAHID', 'NETFLIX', 'OSN +', 'Watch It', 'IPTV', 'TANGO PRO'];

    public function run(): void
    {
        $sort = 0;
        foreach ($this->sections as $slug => $name) {
            $this->category($name, $slug, null, $sort++);
        }

        $games = Category::where('slug', 'games')->first();
        foreach ($this->games as $i => $name) {
            $this->category($name, null, $games, $i);
        }

        $subscriptions = Category::where('slug', 'subscriptions')->first();
        foreach ($this->subscriptions as $i => $name) {
            $this->category($name, null, $subscriptions, $i);
        }

        $this->seedPubg();
    }

    protected function seedPubg(): void
    {
        $pubg = Category::where('slug', 'pubg-mobile')->first();
        $auto = $this->category('PUBG GLOBAL AUTO', null, $pubg, 0, 'شحن شدات ببجي العالمية تلقائيًا عبر معرّف اللاعب.');

        $packages = ['60' => '1.01', '325' => '5.09', '660' => '10.12', '1800' => '24.21', '3850' => '46.46', '8100' => '91.77'];

        $sort = 0;
        foreach ($packages as $uc => $price) {
            Product::updateOrCreate(
                ['category_id' => $auto->id, 'name' => "{$uc} UC"],
                [
                    'price' => $price,
                    'cost' => $price,
                    'delivery_type' => 'auto',
                    'requires_player_id' => true,
                    'supports_name_check' => true,
                    'provider' => 'fake',
                    'provider_product_id' => "pubg-global-{$uc}",
                    'is_active' => true,
                    'sort' => $sort++,
                ],
            );
        }
    }

    protected function category(string $name, ?string $slug, ?Category $parent, int $sort, ?string $description = null): Category
    {
        $slug ??= Str::slug(str_replace('+', ' plus', $name));

        return Category::updateOrCreate(['slug' => $slug], [
            'name' => $name,
            'parent_id' => $parent?->id,
            'description' => $description,
            'is_active' => true,
            'sort' => $sort,
        ]);
    }
}
