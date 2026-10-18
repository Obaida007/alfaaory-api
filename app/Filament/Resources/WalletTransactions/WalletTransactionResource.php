<?php

namespace App\Filament\Resources\WalletTransactions;

use App\Enums\TransactionType;
use App\Filament\Resources\WalletTransactions\Pages\ListWalletTransactions;
use App\Filament\Support\DateRangeFilter;
use App\Models\WalletTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Read-only wallet ledger. */
class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'المالية';

    protected static ?string $modelLabel = 'حركة محفظة';

    protected static ?string $pluralModelLabel = 'سجل حركات المحفظة';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return static::configureLedgerTable($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'creator']))
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('user.username')->label('المستخدم')->searchable(),
                ...static::ledgerColumns(),
            ]);
    }

    /** Shared with the user's wallet relation manager. */
    public static function configureLedgerTable(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('type')->label('النوع')->options(TransactionType::class),
                SelectFilter::make('source_type')->label('المصدر')->options([
                    'order' => 'طلب', 'deposit' => 'إيداع', 'recharge_code' => 'كود شحن', 'admin_adjustment' => 'تعديل إداري',
                ]),
                DateRangeFilter::make(),
            ]);
    }

    /** @return array<int, TextColumn> */
    public static function ledgerColumns(): array
    {
        return [
            TextColumn::make('type')->label('النوع')->badge(),
            TextColumn::make('amount')->label('المبلغ')->money('USD', locale: 'en')
                ->color(fn (WalletTransaction $record) => $record->type === TransactionType::Credit ? 'success' : 'danger'),
            TextColumn::make('balance_before')->label('قبل')->money('USD', locale: 'en'),
            TextColumn::make('balance_after')->label('بعد')->money('USD', locale: 'en'),
            TextColumn::make('description')->label('الوصف')->wrap()->searchable(),
            TextColumn::make('creator.username')->label('بواسطة')->placeholder('النظام'),
            TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletTransactions::route('/'),
        ];
    }
}
