<?php

namespace App\Filament\Resources\Deposits;

use App\Enums\DepositStatus;
use App\Filament\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Resources\Deposits\Pages\ViewDeposit;
use App\Filament\Support\DateRangeFilter;
use App\Models\Deposit;
use App\Support\Media;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?string $modelLabel = 'إيداع';

    protected static ?string $pluralModelLabel = 'الإيداعات';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Deposit::query()->where('status', DepositStatus::Pending)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الإيداع')->columns(3)->schema([
                TextEntry::make('id')->label('رقم الإيداع')->prefix('#'),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('created_at')->label('التاريخ')->dateTime(),
                TextEntry::make('user.full_name')->label('المستخدم')
                    ->url(fn (Deposit $record) => route('filament.admin.resources.users.edit', $record->user_id)),
                TextEntry::make('user.username')->label('اسم المستخدم'),
                TextEntry::make('paymentMethod.name')->label('طريقة الدفع'),
                TextEntry::make('amount')->label('المبلغ المرسل')
                    ->formatStateUsing(fn (Deposit $record) => "{$record->amount} {$record->currency}"),
                TextEntry::make('amount_usd')->label('بالدولار')->money('USD', locale: 'en')->weight('bold'),
                TextEntry::make('sender_name')->label('اسم المرسل')->placeholder('—'),
                TextEntry::make('transaction_ref')->label('رقم العملية')->copyable()->placeholder('—'),
                TextEntry::make('reviewer.full_name')->label('راجعه')->placeholder('—'),
                TextEntry::make('reviewed_at')->label('تاريخ المراجعة')->dateTime()->placeholder('—'),
                TextEntry::make('admin_note')->label('ملاحظة')->placeholder('—')->columnSpanFull(),
            ]),
            Section::make('صورة الإيصال')->schema([
                ImageEntry::make('receipt_image')->label('')->disk('public')->imageHeight(480)
                    ->url(fn (Deposit $record) => Media::url($record->receipt_image), shouldOpenInNewTab: true)
                    ->placeholder('لا توجد صورة'),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'paymentMethod']))
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->searchable(),
                ImageColumn::make('receipt_image')->label('الإيصال')->disk('public')->square(),
                TextColumn::make('user.username')->label('المستخدم')->searchable()
                    ->description(fn (Deposit $record) => $record->user?->full_name),
                TextColumn::make('paymentMethod.name')->label('الطريقة'),
                TextColumn::make('amount')->label('المبلغ')
                    ->formatStateUsing(fn (Deposit $record) => "{$record->amount} {$record->currency}"),
                TextColumn::make('amount_usd')->label('بالدولار')->money('USD', locale: 'en')->sortable(),
                TextColumn::make('transaction_ref')->label('رقم العملية')->searchable()->toggleable(),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(DepositStatus::class),
                SelectFilter::make('payment_method_id')->label('طريقة الدفع')->relationship('paymentMethod', 'name')->searchable()->preload(),
                DateRangeFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(DepositActions::all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeposits::route('/'),
            'view' => ViewDeposit::route('/{record}'),
        ];
    }
}
