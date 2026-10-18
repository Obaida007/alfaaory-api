<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'المبيعات — آخر 30 يومًا';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['super_admin', 'admin']);
    }

    protected function getData(): array
    {
        $start = today()->subDays(29);

        $totals = Order::query()
            ->whereIn('status', StoreStatsOverview::SALE_STATUSES)
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, SUM(total) as total, COUNT(*) as orders')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        $days = collect(range(0, 29))->map(fn (int $i) => $start->copy()->addDays($i)->toDateString());

        return [
            'datasets' => [
                [
                    'label' => 'المبيعات ($)',
                    'data' => $days->map(fn (string $d) => round((float) ($totals[$d]->total ?? 0), 2))->all(),
                    'borderColor' => '#7c3aed',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'عدد الطلبات',
                    'data' => $days->map(fn (string $d) => (int) ($totals[$d]->orders ?? 0))->all(),
                    'borderColor' => '#f59e0b',
                    'yAxisID' => 'y1',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $days->map(fn (string $d) => Carbon::parse($d)->format('m/d'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'left'],
                'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
