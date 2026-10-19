<?php

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('returns the home payload in the unified envelope', function () {
    $this->getJson('/api/v1/home')
        ->assertOk()
        ->assertJsonStructure(['data' => ['hero_banners', 'popup_banners', 'sections', 'top_products'], 'meta', 'message'])
        ->assertJsonCount(11, 'data.sections');
});

it('returns the category tree', function () {
    $response = $this->getJson('/api/v1/categories')->assertOk();

    $games = collect($response->json('data'))->firstWhere('slug', 'games');
    $pubg = collect($games['children'])->firstWhere('slug', 'pubg-mobile');

    expect($pubg['children'][0]['slug'])->toBe('pubg-global-auto');
});

it('shows a category with its products', function () {
    $this->getJson('/api/v1/categories/pubg-global-auto')
        ->assertOk()
        ->assertJsonPath('data.parent.slug', 'pubg-mobile')
        ->assertJsonCount(6, 'data.products')
        ->assertJsonPath('data.products.0.name', '60 UC')
        ->assertJsonPath('data.products.0.price', '1.01');
});

it('returns 404 in Arabic for unknown categories', function () {
    $this->getJson('/api/v1/categories/nope')
        ->assertNotFound()
        ->assertJsonPath('message', __('store.not_found'))
        ->assertJsonPath('data', null);
});

it('hides inactive products', function () {
    $product = Product::factory()->for(Category::factory())->create(['is_active' => false]);

    $this->getJson("/api/v1/products/{$product->id}")->assertNotFound();
});

it('searches products and categories', function () {
    $this->getJson('/api/v1/search?q=pubg')
        ->assertOk()
        ->assertJsonCount(6, 'data.products');
});

it('groups payment methods', function () {
    $groups = collect($this->getJson('/api/v1/payment-methods')->assertOk()->json('data'));

    expect($groups->pluck('group')->all())->toBe(['wallet', 'crypto', 'bank', 'agency_salary', 'code'])
        ->and($groups->firstWhere('group', 'crypto')['methods'])->toHaveCount(3);
});

it('lists the levels from the lowest threshold', function () {
    $this->getJson('/api/v1/levels')
        ->assertOk()
        ->assertJsonCount(6, 'data')
        ->assertJsonPath('data.0.name', 'البرونزي')
        ->assertJsonPath('data.1.min_total_purchases', '500.00');
});

it('returns payment method subtitles', function () {
    $crypto = collect($this->getJson('/api/v1/payment-methods')->json('data'))->firstWhere('group', 'crypto');

    expect(collect($crypto['methods'])->firstWhere('name', 'USDT TRC20')['subtitle'])->toBe('أوتوماتيك');
});

it('serves agents, pages and public settings', function () {
    $this->getJson('/api/v1/agents')->assertOk()->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/pages/about')->assertOk()->assertJsonPath('data.page', 'about');
    $this->getJson('/api/v1/pages/privacy')->assertOk();
    $this->getJson('/api/v1/settings/public')->assertOk()->assertJsonPath('data.site_name', 'متجر الفاعوري');
});
