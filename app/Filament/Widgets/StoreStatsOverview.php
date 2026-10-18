<?php

namespace App\Filament\Widgets;

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Deposits\DepositResource;
use App\Filament\Resources\KycRequests\KycRequestResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Deposit;
use App\Models\KycRequest;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    /** Orders whose money was taken (refunded/rejected excluded). */
    public const SALE_STATUSES = [OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Completed];

    protected function getStats(): array
    {
        $isManager = (bool) auth()->user()?->hasAnyRole(['super_admin', 'admin']);
        $sales = fn ($from) => Money::round(Order::query()->whereIn('status', self::SALE_STATUSES)->where('created_at', '>=', $from)->sum('total'));

        $pendingOrders = Order::query()->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing])->count();
        $pendingDeposits = Deposit::query()->where('status', DepositStatus::Pending)->count();

        $stats = [
            Stat::make('الطلبات المعلّقة', $pendingOrders)
                ->icon(Heroicon::OutlinedClock)
                ->color($pendingOrders ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index', ['filters' => ['status' => ['values' => ['pending', 'processing']]]])),
            Stat::make('الإيداعات المعلّقة', $pendingDeposits)
                ->icon(Heroicon::OutlinedBanknotes)
                ->color($pendingDeposits ? 'warning' : 'success')
                ->url(DepositResource::getUrl('index', ['filters' => ['status' => ['value' => 'pending']]])),
        ];

        if (! $isManager) {
            return $stats;
        }

        $pendingKyc = KycRequest::query()->where('status', DepositStatus::Pending)->count();

        return [
            Stat::make('مبيعات اليوم', '$'.$sales(today()))
                ->description(Order::query()->whereIn('status', self::SALE_STATUSES)->where('created_at', '>=', today())->count().' طلب')
                ->icon(Heroicon::OutlinedShoppingCart)
                ->color('success'),
            Stat::make('مبيعات الشهر', '$'.$sales(now()->startOfMonth()))
                ->icon(Heroicon::OutlinedChartBar)
                ->color('primary'),
            ...$stats,
            Stat::make('طلبات KYC المعلّقة', $pendingKyc)
                ->icon(Heroicon::OutlinedIdentification)
                ->color($pendingKyc ? 'warning' : 'success')
                ->url(KycRequestResource::getUrl('index')),
            Stat::make('إجمالي أرصدة المستخدمين', '$'.Money::round(User::query()->sum('balance')))
                ->icon(Heroicon::OutlinedWallet)
                ->color('gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
