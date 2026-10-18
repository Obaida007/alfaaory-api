<?php

namespace App\Filament\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;

/**
 * "From / to" date filter on a timestamp column, used by the financial tables.
 */
class DateRangeFilter
{
    public static function make(string $column = 'created_at', string $name = 'date_range'): Filter
    {
        return Filter::make($name)
            ->label('التاريخ')
            ->schema([
                DatePicker::make('from')->label('من تاريخ'),
                DatePicker::make('until')->label('إلى تاريخ'),
            ])
            ->query(fn (Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate($column, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate($column, '<=', $date)))
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['from'] ?? null) {
                    $indicators[] = Indicator::make('من '.$data['from'])->removeField('from');
                }
                if ($data['until'] ?? null) {
                    $indicators[] = Indicator::make('إلى '.$data['until'])->removeField('until');
                }

                return $indicators;
            });
    }
}
