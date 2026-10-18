<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Support\DateRangeFilter;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->whereIn('status', [OrderStatus::Pending, OrderStatus::Processing])->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الطلب')->columns(3)->schema([
                TextEntry::make('id')->label('رقم الطلب')->prefix('#'),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('created_at')->label('التاريخ')->dateTime(),
                TextEntry::make('user.full_name')->label('المستخدم')
                    ->url(fn (Order $record) => route('filament.admin.resources.users.edit', $record->user_id)),
                TextEntry::make('user.username')->label('اسم المستخدم'),
                TextEntry::make('product.name')->label('المنتج')
                    ->belowContent(fn (Order $record) => $record->product?->category?->name),
                TextEntry::make('player_id')->label('معرّف اللاعب')->copyable()->placeholder('—'),
                TextEntry::make('player_name')->label('اسم اللاعب')->placeholder('—'),
                TextEntry::make('quantity')->label('الكمية'),
                TextEntry::make('unit_price')->label('سعر الوحدة')->money('USD', locale: 'en'),
                TextEntry::make('discount')->label('الخصم')->money('USD', locale: 'en'),
                TextEntry::make('total')->label('الإجمالي')->money('USD', locale: 'en')->weight('bold'),
                TextEntry::make('completed_at')->label('تاريخ التنفيذ')->dateTime()->placeholder('—'),
                TextEntry::make('admin_note')->label('ملاحظة الإدارة')->placeholder('—')->columnSpan(2),
                TextEntry::make('delivered_code')->label('الكود المسلَّم')->copyable()->placeholder('—')->columnSpanFull(),
                KeyValueEntry::make('extra_data')->label('بيانات إضافية')->columnSpanFull()
                    ->visible(fn (Order $record) => filled($record->extra_data)),
            ]),
            Section::make('رد المزوّد')->collapsed()
                ->visible(fn (Order $record) => filled($record->provider_response))
                ->schema([
                    TextEntry::make('provider_response')->label('')
                        ->state(fn (Order $record) => json_encode($record->provider_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                        ->fontFamily('mono'),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'product.category']))
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->searchable(),
                TextColumn::make('user.username')->label('المستخدم')->searchable()
                    ->description(fn (Order $record) => $record->user?->full_name),
                TextColumn::make('product.name')->label('المنتج')->searchable()
                    ->description(fn (Order $record) => $record->product?->category?->name),
                TextColumn::make('player_id')->label('معرّف اللاعب')->searchable()->copyable()
                    ->description(fn (Order $record) => $record->player_name),
                TextColumn::make('total')->label('الإجمالي')->money('USD', locale: 'en')->sortable(),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(OrderStatus::class)->multiple(),
                DateRangeFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(OrderActions::all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
