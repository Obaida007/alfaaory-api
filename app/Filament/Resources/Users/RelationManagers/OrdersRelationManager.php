<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'الطلبات';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product'))
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('product.name')->label('المنتج'),
                TextColumn::make('player_id')->label('معرّف اللاعب'),
                TextColumn::make('total')->label('الإجمالي')->money('USD', locale: 'en'),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('created_at')->label('التاريخ')->dateTime(),
            ])
            ->filters([SelectFilter::make('status')->label('الحالة')->options(OrderStatus::class)])
            ->recordActions([
                Action::make('open')->label('فتح')->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
