<?php

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\RechargeCodes\Pages\ListRechargeCodes;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Category;
use App\Models\Deposit;
use App\Models\KycRequest;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\RechargeCode;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function staff(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('keeps customers out of the panel', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('renders every admin page for a super admin', function (string $url) {
    $this->seed(DatabaseSeeder::class);
    $ids = [
        '{order}' => Order::factory()->create()->id,
        '{deposit}' => Deposit::factory()->create()->id,
        '{method}' => PaymentMethod::query()->value('id'),
        '{user}' => User::factory()->create()->id,
        '{product}' => Product::query()->value('id'),
        '{kyc}' => KycRequest::factory()->create()->id,
        '{category}' => Category::query()->value('id'),
    ];

    $this->actingAs(staff('super_admin'))->get(strtr($url, $ids))->assertOk();
})->with([
    '/admin', '/admin/users', '/admin/users/create', '/admin/categories', '/admin/categories/create', '/admin/products',
    '/admin/products/create', '/admin/products/{product}/edit', '/admin/users/{user}/edit',
    '/admin/orders', '/admin/orders/{order}', '/admin/deposits', '/admin/deposits/{deposit}',
    '/admin/payment-methods', '/admin/payment-methods/{method}/edit', '/admin/recharge-codes', '/admin/kyc-requests', '/admin/kyc-requests/{kyc}', '/admin/categories/{category}/edit',
    '/admin/levels', '/admin/agents', '/admin/banners', '/admin/wallet-transactions', '/admin/manage-settings',
]);

it('limits support staff to orders and deposits', function () {
    $support = staff('support');

    $this->actingAs($support)->get('/admin/orders')->assertOk();
    $this->actingAs($support)->get('/admin/deposits')->assertOk();
    $this->actingAs($support)->get('/admin/products')->assertForbidden();
    $this->actingAs($support)->get('/admin/users')->assertForbidden();
    $this->actingAs($support)->get('/admin/manage-settings')->assertForbidden();
});

it('stops admins from editing staff accounts', function () {
    $admin = staff('admin');
    $other = staff('admin');

    $this->actingAs($admin)->get("/admin/users/{$other->id}/edit")->assertForbidden();
    $this->actingAs($admin)->get('/admin/users/'.User::factory()->create()->id.'/edit')->assertOk();
});

it('rejects an order with a refund from the table', function () {
    $user = buyer('20.00');
    [$order] = app(OrderService::class)->place($user, product('7.00'), ['player_id' => '5123456'], (string) Str::uuid());
    $this->actingAs(staff('support'));

    Livewire::test(ListOrders::class)
        ->callTableAction('reject', $order, ['note' => 'معرّف غير صحيح'])
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($user->fresh()->balance)->toBe('20.00');
});

it('completes an order from its page with a delivered code', function () {
    $order = Order::factory()->create();
    $this->actingAs(staff('admin'));

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->callAction('complete', ['delivered_code' => 'CODE-123', 'note' => null]);

    expect($order->fresh())->status->toBe(OrderStatus::Completed)->delivered_code->toBe('CODE-123');
});

it('approves a deposit and credits the corrected amount', function () {
    $user = buyer();
    $deposit = Deposit::factory()->for($user)->create(['amount_usd' => '10.00']);
    $this->actingAs(staff('support'));

    Livewire::test(ListDeposits::class)
        ->callTableAction('approve', $deposit, ['amount_usd' => '9.50', 'note' => null]);

    expect($deposit->fresh()->status)->toBe(DepositStatus::Approved)
        ->and($user->fresh()->balance)->toBe('9.50');
});

it('adjusts a balance manually with a reason', function () {
    $user = buyer('5.00');
    $admin = staff('admin');
    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->callAction('adjustBalance', ['amount' => '-2', 'reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    Livewire::test(ListUsers::class)
        ->callTableAction('adjustBalance', $user, ['amount' => '-2', 'reason' => 'تصحيح خطأ']);

    expect($user->fresh()->balance)->toBe('3.00')
        ->and($user->walletTransactions()->first())
        ->created_by->toBe($admin->id)
        ->source_type->toBe('admin_adjustment');
});

it('bulk adjusts product prices by percent', function () {
    $a = product('10.00');
    $b = product('20.00');
    $this->actingAs(staff('admin'));

    Livewire::test(ListProducts::class)
        ->callTableBulkAction('adjustPrices', [$a, $b], ['mode' => 'percent', 'value' => '10', 'field' => 'price']);

    expect($a->fresh()->price)->toBe('11.00')->and($b->fresh()->price)->toBe('22.00');
});

it('generates a batch of recharge codes as a CSV download', function () {
    $this->actingAs(staff('admin'));

    Livewire::test(ListRechargeCodes::class)
        ->callAction('generate', ['amount' => '5', 'count' => 3, 'batch' => 'TEST', 'expires_at' => null])
        ->assertFileDownloaded('recharge-codes-TEST.csv');

    expect(RechargeCode::where('batch', 'TEST')->count())->toBe(3);
});

it('saves the store settings', function () {
    $this->actingAs(staff('admin'));

    Livewire::test(ManageSettings::class)
        ->fillForm(['site_name' => 'متجر الفاعوري', 'admin_whatsapp' => '+963900000000', 'about_text' => 'نص جديد'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('admin_whatsapp'))->toBe('+963900000000')->and(Setting::get('about_text'))->toBe('نص جديد');
});
