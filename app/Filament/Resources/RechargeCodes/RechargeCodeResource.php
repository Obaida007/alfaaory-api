<?php

namespace App\Filament\Resources\RechargeCodes;

use App\Filament\Resources\RechargeCodes\Pages\ListRechargeCodes;
use App\Models\RechargeCode;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RechargeCodeResource extends Resource
{
    protected static ?string $model = RechargeCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'المالية';

    protected static ?string $modelLabel = 'كود شحن';

    protected static ?string $pluralModelLabel = 'أكواد الشحن';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('code_last4')->label('الكود')->formatStateUsing(fn (string $state) => '••••-••••-••••-'.$state),
                TextColumn::make('amount')->label('المبلغ')->money('USD', locale: 'en')->sortable(),
                TextColumn::make('batch')->label('الدفعة')->searchable(),
                TextColumn::make('user.username')->label('استخدمه')->placeholder('—'),
                TextColumn::make('used_at')->label('تاريخ الاستخدام')->dateTime()->placeholder('غير مستخدم'),
                TextColumn::make('expires_at')->label('ينتهي')->dateTime()->placeholder('—'),
                TextColumn::make('created_at')->label('أُنشئ')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('used')->label('الحالة')
                    ->trueLabel('مستخدم')->falseLabel('غير مستخدم')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('used_at'),
                        false: fn (Builder $q) => $q->whereNull('used_at'),
                    ),
                SelectFilter::make('batch')->label('الدفعة')
                    ->options(fn () => RechargeCode::query()->whereNotNull('batch')->distinct()->orderByDesc('batch')->pluck('batch', 'batch')->all())
                    ->searchable(),
            ])
            ->recordActions([DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRechargeCodes::route('/'),
        ];
    }
}
